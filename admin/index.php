<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

ini_set('session.use_strict_mode', '1');
session_name('fusti_admin_session');
$adminPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')), '/') . '/';
$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $adminPath,
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

$scriptNonce = base64_encode(random_bytes(18));

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'; script-src 'self' 'nonce-" . $scriptNonce . "'");

function adminEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function adminCsrfToken(): string
{
    if (!isset($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf'];
}

function validAdminReturnAnchor($anchor): string
{
    if (!is_string($anchor)) {
        return '';
    }

    return preg_match(
        '/^(?:admin-header|admin-login|product-create|offer-settings|user-create|user-list|password-settings|product-list|product-[1-9][0-9]*|user-[1-9][0-9]*)$/D',
        $anchor
    ) ? $anchor : '';
}

function adminReturnAnchor(): string
{
    return validAdminReturnAnchor($_POST['return_anchor'] ?? '');
}

function findCurrentAdmin(PDO $pdo): ?array
{
    $userId = filter_var($_SESSION['admin_user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$userId) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, username, can_login
         FROM admin_users
         WHERE id = :id'
    );
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['can_login'] !== 1) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }

    return $user;
}

function redirectAdmin(string $adminUrl, string $message, array $query = []): never
{
    $_SESSION['admin_flash'] = $message;
    $anchor = adminReturnAnchor();
    if ($anchor !== '') {
        $query['return_anchor'] = $anchor;
    }
    $queryString = $query ? '?' . http_build_query($query) : '';
    $fragment = $anchor !== '' ? '#' . rawurlencode($anchor) : '';
    header('Location: ' . $adminUrl . '/' . $queryString . $fragment);
    exit;
}

final class AdminImageUploadException extends RuntimeException
{
}

function storeAdminImage(array $upload): string
{
    $uploadError = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'A kép meghaladja a szerveren engedélyezett méretet.',
            UPLOAD_ERR_FORM_SIZE => 'A kép meghaladja az űrlapon engedélyezett méretet.',
            UPLOAD_ERR_PARTIAL => 'A kép csak részben töltődött fel.',
            UPLOAD_ERR_NO_FILE => 'Válassz ki egy képet.',
            UPLOAD_ERR_NO_TMP_DIR => 'A szerveren hiányzik az ideiglenes feltöltési mappa.',
            UPLOAD_ERR_CANT_WRITE => 'A szerver nem tudta lemezre írni a képet.',
            UPLOAD_ERR_EXTENSION => 'A szerver leállította a kép feltöltését.',
        ];
        throw new AdminImageUploadException($messages[$uploadError] ?? 'A kép feltöltése nem sikerült.');
    }

    $temporaryPath = (string)($upload['tmp_name'] ?? '');
    $fileSize = (int)($upload['size'] ?? 0);
    if ($fileSize < 1 || $fileSize > 5 * 1024 * 1024 || !is_uploaded_file($temporaryPath)) {
        throw new AdminImageUploadException('A kép legfeljebb 5 MB-os lehet.');
    }

    $extensionByMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$fileInfo->file($temporaryPath);
    if (!isset($extensionByMime[$mime])) {
        throw new AdminImageUploadException('Csak valódi JPG, PNG vagy WebP kép tölthető fel.');
    }

    $imageDimensions = getimagesize($temporaryPath);
    if ($imageDimensions === false) {
        throw new AdminImageUploadException('A feltöltött fájl nem érvényes kép.');
    }
    if (
        $imageDimensions[0] > 8000
        || $imageDimensions[1] > 8000
        || $imageDimensions[0] * $imageDimensions[1] > 25000000
    ) {
        throw new AdminImageUploadException('A kép mérete túl nagy feldolgozáshoz.');
    }

    $uploadDirectory = __DIR__ . '/../backend/Uploads';
    if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
        throw new AdminImageUploadException('A backend/Uploads mappa nem írható.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensionByMime[$mime];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new AdminImageUploadException('A képfájlt nem sikerült a feltöltések közé menteni.');
    }

    return 'Uploads/' . $filename;
}

function deleteAdminImage(string $imagePath): void
{
    if (!preg_match('#^Uploads/[a-f0-9]{32}\.(?:jpg|png|webp)$#D', $imagePath)) {
        return;
    }

    $absolutePath = __DIR__ . '/../backend/' . $imagePath;
    if (is_file($absolutePath) && !unlink($absolutePath)) {
        error_log('Unable to remove replaced admin image: ' . $imagePath);
    }
}

function adminProductImageUrl(string $projectRoot, string $imagePath): string
{
    if (strncmp($imagePath, 'Uploads/', 8) !== 0) {
        return '';
    }

    $filename = substr($imagePath, 8);
    if (
        $filename === ''
        || basename($filename) !== $filename
        || !preg_match('/\.(?:jpe?g|png|webp)$/iD', $filename)
    ) {
        return '';
    }

    return ($projectRoot !== '' ? $projectRoot : '')
        . '/backend/Uploads/'
        . rawurlencode($filename);
}

$adminUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')), '/');
$projectRoot = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php'))), '/');
$homeUrl = ($projectRoot !== '' ? $projectRoot : '') . '/';
$errors = [];
$flash = isset($_SESSION['admin_flash']) ? (string)$_SESSION['admin_flash'] : '';
unset($_SESSION['admin_flash']);
$schemaReady = true;
$productNameValue = '';
$productPriceValue = '';
$productSearch = trim((string)($_GET['product_search'] ?? ''));
$categories = [
    'Szeletes sütemények',
    'Alkalmi Torták',
    'Aprósütemények',
    'Fagylalt',
    'Sütifree',
    'Tejszínes torták',
    'Főzött krémes torták',
    'Különleges torták',
    'Kreatív díszítésű torták',
    'Pohárkrém',
];
$returnAnchor = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? adminReturnAnchor()
    : validAdminReturnAnchor($_GET['return_anchor'] ?? '');

try {
    $pdo->query('SELECT id FROM admin_users LIMIT 0');
    $pdo->query('SELECT setting_key, setting_value FROM site_settings LIMIT 0');
} catch (PDOException $exception) {
    error_log('Admin schema check failed: ' . $exception->getMessage());
    $schemaReady = false;
}

$currentAdmin = $schemaReady ? findCurrentAdmin($pdo) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(adminCsrfToken(), (string)($_POST['_csrf'] ?? ''))) {
        http_response_code(400);
        $errors[] = 'A kérés biztonsági ellenőrzése lejárt. Frissítsd az oldalt, majd próbáld újra.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'logout') {
            $_SESSION = [];
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $cookie['path'],
                'secure' => $cookie['secure'],
                'httponly' => $cookie['httponly'],
                'samesite' => 'Strict',
            ]);
            session_destroy();
            header('Location: ' . $adminUrl . '/');
            exit;
        }

        if (!$schemaReady) {
            $errors[] = 'Az admin adatbázistáblák még nincsenek létrehozva. Futtasd a backend/sql_migrate_admin.sql fájlt.';
        } elseif (!$currentAdmin && $action === 'login') {
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            try {
                $stmt = $pdo->prepare(
                    'SELECT id, username, password_hash, can_login, failed_login_attempts, locked_until
                     FROM admin_users
                     WHERE username = :username
                     LIMIT 1'
                );
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch();
                $isLocked = $user && $user['locked_until'] && strtotime((string)$user['locked_until']) > time();

                if ($user && !$isLocked && (int)$user['can_login'] === 1 && password_verify($password, (string)$user['password_hash'])) {
                    $passwordHash = (string)$user['password_hash'];
                    if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    }

                    $update = $pdo->prepare(
                        'UPDATE admin_users
                         SET password_hash = :password_hash, failed_login_attempts = 0, locked_until = NULL
                         WHERE id = :id'
                    );
                    $update->execute([
                        ':password_hash' => $passwordHash,
                        ':id' => $user['id'],
                    ]);

                    session_regenerate_id(true);
                    $_SESSION['admin_user_id'] = (int)$user['id'];
                    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
                    redirectAdmin($adminUrl, 'Sikeres bejelentkezés.');
                }

                if ($user && (int)$user['can_login'] === 1 && !$isLocked) {
                    $failedAttempts = (int)$user['failed_login_attempts'] + 1;
                    $lockedUntil = null;
                    if ($failedAttempts >= 5) {
                        $failedAttempts = 0;
                        $lockedUntil = date('Y-m-d H:i:s', time() + 15 * 60);
                    }

                    $update = $pdo->prepare(
                        'UPDATE admin_users
                         SET failed_login_attempts = :failed_attempts, locked_until = :locked_until
                         WHERE id = :id'
                    );
                    $update->execute([
                        ':failed_attempts' => $failedAttempts,
                        ':locked_until' => $lockedUntil,
                        ':id' => $user['id'],
                    ]);
                }
            } catch (PDOException $exception) {
                error_log('Admin login failed: ' . $exception->getMessage());
                $errors[] = 'Az adatbázis nem tudta feldolgozni a bejelentkezést.';
            }

            if (!$errors) {
                $errors[] = 'Hibás felhasználónév vagy jelszó, illetve nincs belépési jogosultság.';
            }
        } elseif (!$currentAdmin) {
            $errors[] = 'A művelethez jelentkezz be.';
        } else {
            try {
                if ($action === 'change_password') {
                    $currentPassword = (string)($_POST['current_password'] ?? '');
                    $newPassword = (string)($_POST['new_password'] ?? '');
                    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

                    $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = :id');
                    $stmt->execute([':id' => $currentAdmin['id']]);
                    $storedHash = (string)$stmt->fetchColumn();

                    if (!password_verify($currentPassword, $storedHash)) {
                        $errors[] = 'A jelenlegi jelszó nem megfelelő.';
                    } elseif (strlen($newPassword) < 6) {
                        $errors[] = 'Az új jelszó legalább 6 karakter legyen.';
                    } elseif ($newPassword !== $confirmPassword) {
                        $errors[] = 'A két új jelszó nem egyezik.';
                    } else {
                        $update = $pdo->prepare(
                            'UPDATE admin_users
                             SET password_hash = :password_hash, failed_login_attempts = 0,
                                 locked_until = NULL
                             WHERE id = :id'
                        );
                        $update->execute([
                            ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                            ':id' => $currentAdmin['id'],
                        ]);
                        redirectAdmin($adminUrl, 'A jelszót sikeresen módosítottad.');
                    }
                } elseif ($action === 'create_product') {
                    $productNameValue = trim((string)($_POST['product_name'] ?? ''));
                    $category = (string)($_POST['category'] ?? '');
                    $productPriceValue = trim((string)($_POST['price'] ?? ''));
                    $price = filter_var($productPriceValue, FILTER_VALIDATE_INT);

                    if ($productNameValue === '' || strlen($productNameValue) > 150) {
                        $errors[] = 'A termék neve kötelező, legfeljebb 150 karakter lehet.';
                    }
                    if (!in_array($category, $categories, true)) {
                        $errors[] = 'Válassz érvényes kategóriát.';
                    }
                    if ($price === false || $price < 0 || $price > 10000000) {
                        $errors[] = 'Az ár 0 és 10 000 000 Ft közötti egész szám legyen.';
                    }

                    $storedImage = null;
                    if (!$errors) {
                        $upload = $_FILES['product_image'] ?? null;
                        if (!is_array($upload)) {
                            $errors[] = 'Válassz ki egy képet.';
                        } else {
                            try {
                                $storedImage = storeAdminImage($upload);
                            } catch (AdminImageUploadException $exception) {
                                $errors[] = $exception->getMessage();
                            }
                        }
                    }

                    if (!$errors && $storedImage !== null) {
                        try {
                            $stmt = $pdo->prepare(
                                'INSERT INTO termekek (nev, kategoria, ar, image_url)
                                 VALUES (:nev, :kategoria, :ar, :image_url)'
                            );
                            $stmt->execute([
                                ':nev' => $productNameValue,
                                ':kategoria' => $category,
                                ':ar' => $price,
                                ':image_url' => $storedImage,
                            ]);
                            redirectAdmin($adminUrl, 'A termék és a képe sikeresen felkerült.');
                        } catch (PDOException $exception) {
                            deleteAdminImage($storedImage);
                            error_log('Product creation failed: ' . $exception->getMessage());
                            $errors[] = 'Az adatbázis nem tudta menteni a terméket.';
                        }
                    }
                } elseif ($action === 'update_product') {
                    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
                    $productSearch = trim((string)($_POST['product_search'] ?? ''));
                    $productName = trim((string)($_POST['product_name'] ?? ''));
                    $category = (string)($_POST['category'] ?? '');
                    $productPriceValue = trim((string)($_POST['price'] ?? ''));
                    $price = filter_var($productPriceValue, FILTER_VALIDATE_INT);

                    if (!$productId || $productId < 1) {
                        $errors[] = 'Érvénytelen termékazonosító.';
                    }
                    if ($productName === '' || strlen($productName) > 150) {
                        $errors[] = 'A termék neve kötelező, legfeljebb 150 karakter lehet.';
                    }
                    if (!in_array($category, $categories, true)) {
                        $errors[] = 'Válassz érvényes kategóriát.';
                    }
                    if ($price === false || $price < 0 || $price > 10000000) {
                        $errors[] = 'Az ár 0 és 10 000 000 Ft közötti egész szám legyen.';
                    }

                    $existingProduct = null;
                    if (!$errors) {
                        $stmt = $pdo->prepare('SELECT image_url FROM termekek WHERE id = :id');
                        $stmt->execute([':id' => $productId]);
                        $existingProduct = $stmt->fetch();
                        if (!$existingProduct) {
                            $errors[] = 'A termék már nem található.';
                        }
                    }

                    $productUpload = $_FILES['product_image'] ?? null;
                    $uploadError = is_array($productUpload)
                        ? (int)($productUpload['error'] ?? UPLOAD_ERR_NO_FILE)
                        : UPLOAD_ERR_NO_FILE;
                    $hasProductUpload = $uploadError !== UPLOAD_ERR_NO_FILE;
                    $newProductImage = null;

                    if (!$errors && $hasProductUpload) {
                        if (!is_array($productUpload)) {
                            $errors[] = 'A kép feltöltése nem sikerült.';
                        } else {
                            try {
                                $newProductImage = storeAdminImage($productUpload);
                            } catch (AdminImageUploadException $exception) {
                                $errors[] = $exception->getMessage();
                            }
                        }
                    }

                    if (!$errors && $existingProduct) {
                        $imageToSave = $newProductImage ?? (string)$existingProduct['image_url'];
                        try {
                            $stmt = $pdo->prepare(
                                'UPDATE termekek
                                 SET nev = :nev, kategoria = :kategoria, ar = :ar, image_url = :image_url
                                 WHERE id = :id'
                            );
                            $stmt->execute([
                                ':nev' => $productName,
                                ':kategoria' => $category,
                                ':ar' => $price,
                                ':image_url' => $imageToSave,
                                ':id' => $productId,
                            ]);
                        } catch (PDOException $exception) {
                            if ($newProductImage !== null) {
                                deleteAdminImage($newProductImage);
                            }
                            throw $exception;
                        }

                        if ($newProductImage !== null) {
                            deleteAdminImage((string)$existingProduct['image_url']);
                        }
                        redirectAdmin(
                            $adminUrl,
                            'A termék adatai sikeresen módosultak.',
                            $productSearch !== '' ? ['product_search' => $productSearch] : []
                        );
                    }
                } elseif ($action === 'delete_product') {
                    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
                    $productSearch = trim((string)($_POST['product_search'] ?? ''));

                    if (!$productId || $productId < 1) {
                        $errors[] = 'Érvénytelen termékazonosító.';
                    } else {
                        $stmt = $pdo->prepare('SELECT image_url FROM termekek WHERE id = :id');
                        $stmt->execute([':id' => $productId]);
                        $productToDelete = $stmt->fetch();

                        if (!$productToDelete) {
                            $errors[] = 'A termék már nem található.';
                        } else {
                            try {
                                $pdo->beginTransaction();
                                $stmt = $pdo->prepare('DELETE FROM termekek WHERE id = :id');
                                $stmt->execute([':id' => $productId]);
                                if ($stmt->rowCount() !== 1) {
                                    $pdo->rollBack();
                                    $errors[] = 'A terméket nem sikerült törölni.';
                                } else {
                                    $imagePath = (string)$productToDelete['image_url'];
                                    $imageUsage = $pdo->prepare(
                                        'SELECT COUNT(*) FROM termekek WHERE image_url = :image_url'
                                    );
                                    $imageUsage->execute([':image_url' => $imagePath]);
                                    $imageIsUnused = (int)$imageUsage->fetchColumn() === 0;
                                    $pdo->commit();

                                    if ($imageIsUnused) {
                                        deleteAdminImage($imagePath);
                                    }
                                    redirectAdmin(
                                        $adminUrl,
                                        'A termék sikeresen törölve.',
                                        $productSearch !== '' ? ['product_search' => $productSearch] : []
                                    );
                                }
                            } catch (PDOException $exception) {
                                if ($pdo->inTransaction()) {
                                    $pdo->rollBack();
                                }
                                throw $exception;
                            }
                        }
                    }
                } elseif ($action === 'save_offer') {
                    $offerEnabled = isset($_POST['offer_enabled']) && $_POST['offer_enabled'] === '1';
                    $offerText = trim((string)($_POST['offer_text'] ?? ''));
                    $removeOfferImage = isset($_POST['remove_offer_image']) && $_POST['remove_offer_image'] === '1';
                    $offerUpload = $_FILES['offer_image'] ?? null;
                    $offerUploadError = is_array($offerUpload)
                        ? (int)($offerUpload['error'] ?? UPLOAD_ERR_NO_FILE)
                        : UPLOAD_ERR_NO_FILE;
                    $hasOfferUpload = $offerUploadError !== UPLOAD_ERR_NO_FILE;
                    $newOfferImage = null;

                    if ($offerEnabled && $offerText === '') {
                        $errors[] = 'Bekapcsolt ajánlathoz adj meg szöveget.';
                    } elseif (strlen($offerText) > 8000) {
                        $errors[] = 'Az ajánlat szövege túl hosszú.';
                    } elseif ($removeOfferImage && $hasOfferUpload) {
                        $errors[] = 'Egyszerre új képet feltölteni és a jelenlegi képet eltávolítani nem lehet.';
                    }

                    $imageQuery = $pdo->prepare(
                        "SELECT setting_value FROM site_settings WHERE setting_key = 'offer_image'"
                    );
                    $imageQuery->execute();
                    $existingOfferImage = (string)($imageQuery->fetchColumn() ?: '');
                    $offerImageToSave = $removeOfferImage ? '' : $existingOfferImage;

                    if (!$errors && $hasOfferUpload) {
                        if (!is_array($offerUpload)) {
                            $errors[] = 'A kép feltöltése nem sikerült.';
                        } else {
                            try {
                                $newOfferImage = storeAdminImage($offerUpload);
                                $offerImageToSave = $newOfferImage;
                            } catch (AdminImageUploadException $exception) {
                                $errors[] = $exception->getMessage();
                            }
                        }
                    }

                    if (!$errors) {
                        try {
                            $pdo->beginTransaction();
                            $stmt = $pdo->prepare(
                                'INSERT INTO site_settings (setting_key, setting_value)
                                 VALUES (:setting_key, :setting_value)
                                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
                            );
                            foreach ([
                                'offer_enabled' => $offerEnabled ? '1' : '0',
                                'offer_text' => $offerText,
                                'offer_image' => $offerImageToSave,
                            ] as $key => $value) {
                                $stmt->execute([
                                    ':setting_key' => $key,
                                    ':setting_value' => $value,
                                ]);
                            }
                            $pdo->commit();
                        } catch (PDOException $exception) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            if ($newOfferImage !== null) {
                                deleteAdminImage($newOfferImage);
                            }
                            throw $exception;
                        }

                        if ($offerImageToSave !== $existingOfferImage) {
                            deleteAdminImage($existingOfferImage);
                        }
                        redirectAdmin($adminUrl, 'Az aktuális ajánlat beállításai mentve.');
                    }
                } elseif ($action === 'create_user') {
                    $newUsername = trim((string)($_POST['new_username'] ?? ''));
                    $newUserPassword = (string)($_POST['new_user_password'] ?? '');
                    $canLogin = isset($_POST['can_login']) && $_POST['can_login'] === '1';

                    if (!preg_match('/^[a-zA-Z0-9._-]{3,64}$/D', $newUsername)) {
                        $errors[] = 'A felhasználónév 3–64 karakteres lehet, betűket, számokat, pontot, kötőjelet és aláhúzást tartalmazhat.';
                    }
                    if (strlen($newUserPassword) < 6) {
                        $errors[] = 'Az új felhasználó jelszava legalább 6 karakter legyen.';
                    }

                    if (!$errors) {
                        try {
                            $stmt = $pdo->prepare(
                                'INSERT INTO admin_users (username, password_hash, can_login)
                                 VALUES (:username, :password_hash, :can_login)'
                            );
                            $stmt->execute([
                                ':username' => $newUsername,
                                ':password_hash' => password_hash($newUserPassword, PASSWORD_DEFAULT),
                                ':can_login' => $canLogin ? 1 : 0,
                            ]);
                            redirectAdmin($adminUrl, 'A felhasználó sikeresen létrejött.');
                        } catch (PDOException $exception) {
                            if ($exception->getCode() === '23000') {
                                $errors[] = 'Ez a felhasználónév már foglalt.';
                            } else {
                                error_log('Admin user creation failed: ' . $exception->getMessage());
                                $errors[] = 'Az adatbázis nem tudta létrehozni a felhasználót.';
                            }
                        }
                    }
                } elseif ($action === 'update_user') {
                    $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
                    $username = trim((string)($_POST['username'] ?? ''));
                    $newPassword = (string)($_POST['new_user_password'] ?? '');
                    $canLogin = isset($_POST['can_login']) && $_POST['can_login'] === '1';

                    if (!$targetId || (int)$targetId === (int)$currentAdmin['id']) {
                        $errors[] = 'Saját felhasználói fiókodat ezen az űrlapon nem módosíthatod.';
                    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,64}$/D', $username)) {
                        $errors[] = 'A felhasználónév 3–64 karakteres lehet, betűket, számokat, pontot, kötőjelet és aláhúzást tartalmazhat.';
                    } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
                        $errors[] = 'Az új jelszó legalább 6 karakter legyen.';
                    } else {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare(
                            'SELECT id, can_login
                             FROM admin_users
                             WHERE id = :id
                             FOR UPDATE'
                        );
                        $stmt->execute([':id' => $targetId]);
                        $targetUser = $stmt->fetch();
                        if (!$targetUser) {
                            $pdo->rollBack();
                            $errors[] = 'A felhasználó nem található.';
                        } else {
                            $otherEnabledUsers = [];
                            if (!$canLogin && (int)$targetUser['can_login'] === 1) {
                                $stmt = $pdo->prepare(
                                    'SELECT id
                                     FROM admin_users
                                     WHERE can_login = 1 AND id <> :id
                                     FOR UPDATE'
                                );
                                $stmt->execute([':id' => $targetId]);
                                $otherEnabledUsers = $stmt->fetchAll();
                            }

                            if (!$canLogin && (int)$targetUser['can_login'] === 1 && !$otherEnabledUsers) {
                                $pdo->rollBack();
                                $errors[] = 'Az utolsó bejelentkezésre jogosult felhasználó nem tiltható le.';
                            } else {
                                $resetLoginState = $newPassword !== ''
                                    || ((int)$targetUser['can_login'] === 0 && $canLogin);
                                if ($newPassword !== '') {
                                    $stmt = $pdo->prepare(
                                        'UPDATE admin_users
                                         SET username = :username, password_hash = :password_hash,
                                             can_login = :can_login, failed_login_attempts = 0,
                                             locked_until = NULL
                                         WHERE id = :id'
                                    );
                                    $stmt->execute([
                                        ':username' => $username,
                                        ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                                        ':can_login' => $canLogin ? 1 : 0,
                                        ':id' => $targetId,
                                    ]);
                                } elseif ($resetLoginState) {
                                    $stmt = $pdo->prepare(
                                        'UPDATE admin_users
                                         SET username = :username, can_login = :can_login,
                                             failed_login_attempts = 0, locked_until = NULL
                                         WHERE id = :id'
                                    );
                                    $stmt->execute([
                                        ':username' => $username,
                                        ':can_login' => $canLogin ? 1 : 0,
                                        ':id' => $targetId,
                                    ]);
                                } else {
                                    $stmt = $pdo->prepare(
                                        'UPDATE admin_users
                                         SET username = :username, can_login = :can_login
                                         WHERE id = :id'
                                    );
                                    $stmt->execute([
                                        ':username' => $username,
                                        ':can_login' => $canLogin ? 1 : 0,
                                        ':id' => $targetId,
                                    ]);
                                }
                                $pdo->commit();
                                redirectAdmin($adminUrl, 'A felhasználó adatai frissültek.');
                            }
                        }
                    }
                } elseif ($action === 'delete_user') {
                    $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
                    if (!$targetId || (int)$targetId === (int)$currentAdmin['id']) {
                        $errors[] = 'Saját felhasználói fiókodat nem törölheted.';
                    } else {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare(
                            'SELECT id, can_login
                             FROM admin_users
                             WHERE id = :id
                             FOR UPDATE'
                        );
                        $stmt->execute([':id' => $targetId]);
                        $targetUser = $stmt->fetch();
                        if (!$targetUser) {
                            $pdo->rollBack();
                            $errors[] = 'A felhasználó nem található.';
                        } else {
                            $otherEnabledUsers = [];
                            if ((int)$targetUser['can_login'] === 1) {
                                $stmt = $pdo->prepare(
                                    'SELECT id
                                     FROM admin_users
                                     WHERE can_login = 1 AND id <> :id
                                     FOR UPDATE'
                                );
                                $stmt->execute([':id' => $targetId]);
                                $otherEnabledUsers = $stmt->fetchAll();
                            }

                            if ((int)$targetUser['can_login'] === 1 && !$otherEnabledUsers) {
                                $pdo->rollBack();
                                $errors[] = 'Az utolsó bejelentkezésre jogosult felhasználó nem törölhető.';
                            } else {
                                $stmt = $pdo->prepare('DELETE FROM admin_users WHERE id = :id');
                                $stmt->execute([':id' => $targetId]);
                                $pdo->commit();
                                redirectAdmin($adminUrl, 'A felhasználó törölve lett.');
                            }
                        }
                    }
                } else {
                    $errors[] = 'Ismeretlen művelet.';
                }
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($action === 'update_user' && $exception->getCode() === '23000') {
                    $errors[] = 'Ez a felhasználónév már foglalt.';
                } else {
                    error_log('Admin action failed: ' . $exception->getMessage());
                    $errors[] = 'Az adatbázis nem tudta végrehajtani a műveletet.';
                }
            }
        }
    }
}

$userEditError = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_user')
    ? implode(' ', $errors)
    : '';
$userCreateError = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user')
    ? implode(' ', $errors)
    : '';
$userCreateUsername = ($_POST['action'] ?? '') === 'create_user'
    ? (string)($_POST['new_username'] ?? '')
    : '';
$currentAdmin = $schemaReady ? findCurrentAdmin($pdo) : null;
$csrfToken = adminCsrfToken();
$settings = [];
$users = [];
$products = [];

if ($schemaReady) {
    $stmt = $pdo->query(
        "SELECT setting_key, setting_value
         FROM site_settings
         WHERE setting_key IN ('offer_enabled', 'offer_text', 'offer_image')"
    );
    foreach ($stmt->fetchAll() as $setting) {
        $settings[(string)$setting['setting_key']] = (string)$setting['setting_value'];
    }
}

if ($currentAdmin) {
    $users = $pdo->query(
        'SELECT id, username, can_login, created_at
         FROM admin_users
         ORDER BY id ASC'
    )->fetchAll();
    if ($productSearch !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, nev, kategoria, ar, image_url
             FROM termekek
             WHERE nev LIKE :name_search
                OR kategoria LIKE :category_search
                OR CAST(id AS CHAR) LIKE :id_search
             ORDER BY id DESC'
        );
        $searchTerm = '%' . $productSearch . '%';
        $stmt->execute([
            ':name_search' => $searchTerm,
            ':category_search' => $searchTerm,
            ':id_search' => $searchTerm,
        ]);
        $products = $stmt->fetchAll();
    } else {
        $products = $pdo->query(
            'SELECT id, nev, kategoria, ar, image_url
             FROM termekek
             ORDER BY id DESC'
        )->fetchAll();
    }
}

$offerEnabled = ($settings['offer_enabled'] ?? '0') === '1';
$offerText = $settings['offer_text'] ?? '';
$offerImage = $settings['offer_image'] ?? '';
$offerImageFilename = $offerImage !== '' ? basename($offerImage) : '';
$offerImageUrl = preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $offerImageFilename)
    ? ($projectRoot !== '' ? $projectRoot : '') . '/backend/Uploads/' . rawurlencode($offerImageFilename)
    : '';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Adminisztráció – Füsti Cukrászda</title>
    <link rel="stylesheet" href="admin.css?v=18">
</head>
<body data-return-anchor="<?= adminEscape($returnAnchor) ?>">
    <header class="admin-header" id="admin-header">
        <a class="admin-brand" href="<?= adminEscape($homeUrl) ?>">Füsti Cukrászda <span>admin</span></a>
        <?php if ($currentAdmin): ?>
            <nav class="admin-nav" aria-label="Admin navigáció">
                <a href="#product-create">Új termék</a>
                <a href="#offer-settings">Ajánlat</a>
                <a href="#user-list">Hozzáférések</a>
                <a href="#product-list">Termékek</a>
            </nav>
        <?php endif; ?>
        <div class="admin-header-actions">
            <a class="admin-nav-home" href="<?= adminEscape($homeUrl) ?>">Főoldal</a>
        <?php if ($currentAdmin): ?>
            <div class="admin-session">
                <span><?= adminEscape((string)$currentAdmin['username']) ?></span>
                <form method="post" action="<?= adminEscape($adminUrl) ?>/">
                    <input type="hidden" name="_csrf" value="<?= adminEscape($csrfToken) ?>">
                    <input type="hidden" name="action" value="logout">
                    <button class="button button-quiet" type="submit">Kilépés</button>
                </form>
            </div>
        <?php endif; ?>
        </div>
    </header>

    <main class="admin-main">
        <?php if (!$schemaReady): ?>
            <section class="panel notice-panel">
                <h1>Az admin adatbázis beállítása szükséges</h1>
                <p>Futtasd le egyszer a <code>backend/sql_migrate_admin.sql</code> fájlt a <code>fusti_cukraszda</code> adatbázison, majd töltsd újra ezt az oldalt.</p>
            </section>
        <?php elseif (!$currentAdmin): ?>
            <section class="panel login-panel" id="admin-login">
                <p class="eyebrow">Adminisztráció</p>
                <h1>Bejelentkezés</h1>
                <?php if ($flash !== ''): ?><p class="notice"><?= adminEscape($flash) ?></p><?php endif; ?>
                <?php foreach ($errors as $error): ?><p class="error"><?= adminEscape($error) ?></p><?php endforeach; ?>
                <form method="post" action="<?= adminEscape($adminUrl) ?>/">
                    <input type="hidden" name="_csrf" value="<?= adminEscape($csrfToken) ?>">
                    <input type="hidden" name="action" value="login">
                    <label for="username">Felhasználónév</label>
                    <input id="username" name="username" autocomplete="username" required maxlength="64">
                    <label for="password">Jelszó</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button class="button" type="submit">Belépés</button>
                </form>
            </section>
        <?php else: ?>
            <div class="dashboard-heading">
                <div>
                    <p class="eyebrow">Vezérlőpult</p>
                    <h1>Admin menü</h1>
                </div>
            </div>
            <?php if ($flash !== ''): ?><p class="notice"><?= adminEscape($flash) ?></p><?php endif; ?>
            <?php foreach ($errors as $error): ?><p class="error"><?= adminEscape($error) ?></p><?php endforeach; ?>

            <div class="dashboard-grid">
                <section class="panel" id="product-create">
                    <p class="eyebrow">Katalógus</p>
                    <h2>Új termék felvétele</h2>
                    <form method="post" action="<?= adminEscape($adminUrl) ?>/" enctype="multipart/form-data">
                        <input type="hidden" name="_csrf" value="<?= adminEscape($csrfToken) ?>">
                        <input type="hidden" name="action" value="create_product">
                        <label for="product-name">Termék neve</label>
                        <input id="product-name" name="product_name" value="<?= adminEscape($productNameValue) ?>" maxlength="150" required>
                        <label for="category">Kategória</label>
                        <select id="category" name="category" required>
                            <option value="">Válassz kategóriát</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= adminEscape($category) ?>" <?= (($_POST['category'] ?? '') === $category) ? 'selected' : '' ?>><?= adminEscape($category) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="price">Ár (Ft)</label>
                        <input id="price" name="price" type="number" min="0" max="10000000" step="1" value="<?= adminEscape($productPriceValue) ?>" required>
                        <label for="product-image">Termékkép (JPG, PNG vagy WebP, max. 5 MB)</label>
                        <input id="product-image" name="product_image" type="file" accept="image/jpeg,image/png,image/webp" required>
                        <button class="button" type="submit">Termék mentése</button>
                    </form>
                </section>

                <section class="panel" id="offer-settings">
                    <p class="eyebrow">Főoldal</p>
                    <h2>Aktuális ajánlatunk</h2>
                    <form method="post" action="<?= adminEscape($adminUrl) ?>/" enctype="multipart/form-data">
                        <input type="hidden" name="_csrf" value="<?= adminEscape($csrfToken) ?>">
                        <input type="hidden" name="action" value="save_offer">
                        <label class="checkbox-label">
                            <input type="checkbox" name="offer_enabled" value="1" <?= $offerEnabled ? 'checked' : '' ?>>
                            Kártya megjelenítése a főoldalon
                        </label>
                        <label for="offer-text">Ajánlat szövege</label>
                        <textarea id="offer-text" name="offer_text" rows="7" maxlength="8000" placeholder="Írd ide az aktuális ajánlatot..."><?= adminEscape($offerText) ?></textarea>
                        <?php if ($offerImageUrl !== ''): ?>
                            <div class="offer-image-preview">
                                <img src="<?= adminEscape($offerImageUrl) ?>" alt="A jelenlegi ajánlat képe">
                                <span>Jelenlegi kép</span>
                            </div>
                        <?php endif; ?>
                        <label for="offer-image">Ajánlat képe (opcionális, JPG/PNG/WebP, max. 5 MB)</label>
                        <input id="offer-image" name="offer_image" type="file" accept="image/jpeg,image/png,image/webp">
                        <?php if ($offerImageUrl !== ''): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="remove_offer_image" value="1">
                                Jelenlegi kép eltávolítása
                            </label>
                        <?php endif; ?>
                        <button class="button" type="submit">Ajánlat mentése</button>
                    </form>
                </section>

                <section class="panel panel-wide" id="user-list">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">Hozzáférések</p>
                            <h2>Felhasználók</h2>
                        </div>
                        <button class="button user-create-toggle" type="button" aria-haspopup="dialog" aria-controls="user-create">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M12 5v14M5 12h14"></path>
                            </svg>
                            <span>Felhasználó létrehozása</span>
                        </button>
                        <dialog class="user-dialog" id="user-create" aria-labelledby="user-create-title" data-auto-open="<?= $userCreateError !== '' ? 'true' : 'false' ?>">
                            <div class="user-dialog-header">
                                <h2 id="user-create-title">Felhasználó létrehozása</h2>
                                <button class="button button-quiet user-dialog-close" type="button" aria-label="Ablak bezárása">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="m6 6 12 12M18 6 6 18"></path>
                                    </svg>
                                </button>
                            </div>
                            <form class="user-create-form" method="post" action="<?= adminEscape($adminUrl) ?>/">
                                <input type="hidden" name="_csrf" value="<?= adminEscape($csrfToken) ?>">
                                <input type="hidden" name="action" value="create_user">
                                <?php if ($userCreateError !== ''): ?><p class="error"><?= adminEscape($userCreateError) ?></p><?php endif; ?>
                                <label for="new-username">Felhasználónév</label>
                                <input id="new-username" name="new_username" value="<?= adminEscape($userCreateUsername) ?>" autocomplete="off" minlength="3" maxlength="64" required>
                                <label for="new-user-password">Jelszó (legalább 6 karakter)</label>
                                <input id="new-user-password" name="new_user_password" type="password" autocomplete="new-password" minlength="6" required>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="can_login" value="1" checked>
                                    Bejelentkezés engedélyezése
                                </label>
                                <button class="button" type="submit">Felhasználó létrehozása</button>
                            </form>
                        </dialog>
                    </div>
                    <?php if (!$users): ?>
                        <p>Még nincs felhasználó.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Felhasználónév</th><th>Belépés</th><th>Létrehozva</th><th>Műveletek</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <?php
                                            $isSubmittedUser = ($_POST['action'] ?? '') === 'update_user'
                                                && (string)($_POST['user_id'] ?? '') === (string)$user['id'];
                                            $displayUsername = $isSubmittedUser
                                                ? (string)($_POST['username'] ?? '')
                                                : (string)$user['username'];
                                            $displayCanLogin = $isSubmittedUser
                                                ? isset($_POST['can_login']) && $_POST['can_login'] === '1'
                                                : (int)$user['can_login'] === 1;
                                        ?>
                                        <tr id="user-<?= (int)$user['id'] ?>">
                                            <td><?= adminEscape((string)$user['username']) ?><?= (int)$user['id'] === (int)$currentAdmin['id'] ? ' (te)' : '' ?></td>
                                            <td>
                                                <span class="user-status <?= (int)$user['can_login'] === 1 ? 'is-enabled' : 'is-disabled' ?>">
                                                    <?= (int)$user['can_login'] === 1 ? 'Engedélyezve' : 'Tiltva' ?>
                                                </span>
                                            </td>
                                            <td><?= adminEscape((string)$user['created_at']) ?></td>
                                            <td>
                                                <?php if ((int)$user['id'] !== (int)$currentAdmin['id']): ?>
                                                    <div class="user-row-actions">
                                                        <button
                                                            class="button button-small icon-action icon-edit user-edit-toggle"
                                                            id="user-edit-trigger-<?= (int)$user['id'] ?>"
                                                            type="button"
                                                            aria-haspopup="dialog"
                                                            aria-controls="user-edit-dialog-<?= (int)$user['id'] ?>"
                                                            aria-label="Felhasználó módosítása: <?= adminEscape((string)$user['username']) ?>"
                                                            title="Felhasználó módosítása"
                                                        >
                                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                                <path d="M12 20h9"></path>
                                                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"></path>
                                                            </svg>
                                                        </button>
                                                        <dialog
                                                            class="user-dialog user-edit-dialog"
                                                            id="user-edit-dialog-<?= (int)$user['id'] ?>"
                                                            aria-labelledby="user-edit-title-<?= (int)$user['id'] ?>"
                                                            data-auto-open="<?= $isSubmittedUser && $userEditError !== '' ? 'true' : 'false' ?>"
                                                        >
                                                            <div class="user-dialog-header">
                                                                <h2 id="user-edit-title-<?= (int)$user['id'] ?>">Felhasználó módosítása</h2>
                                                                <button class="button button-quiet user-dialog-close" type="button" aria-label="Ablak bezárása">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                                        <path d="m6 6 12 12M18 6 6 18"></path>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                            <form class="user-edit-form" method="post" action="<?= adminEscape($adminUrl) ?>/">
                                                                <input type="hidden" name="_csrf" value="<?= adminEscape($csrfToken) ?>">
                                                                <input type="hidden" name="action" value="update_user">
                                                                <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                                                <?php if ($isSubmittedUser && $userEditError !== ''): ?><p class="error"><?= adminEscape($userEditError) ?></p><?php endif; ?>
                                                                <label for="username-<?= (int)$user['id'] ?>">Felhasználónév</label>
                                                                <input id="username-<?= (int)$user['id'] ?>" name="username" value="<?= adminEscape($displayUsername) ?>" minlength="3" maxlength="64" required>
                                                                <label for="new-user-password-<?= (int)$user['id'] ?>">Új jelszó (legalább 6 karakter)</label>
                                                                <input id="new-user-password-<?= (int)$user['id'] ?>" name="new_user_password" type="password" autocomplete="new-password" minlength="6">
                                                                <label class="checkbox-label">
                                                                    <input type="checkbox" name="can_login" value="1" <?= $displayCanLogin ? 'checked' : '' ?>>
                                                                    Bejelentkezés engedélyezése
                                                                </label>
                                                                <button class="button button-small" type="submit">Módosítások mentése</button>
                                                            </form>
                                                        </dialog>
                                                        <form class="user-delete-form" method="post" action="<?= adminEscape($adminUrl) ?>/" data-confirm-delete="Biztosan törlöd ezt a felhasználót? Ez a művelet nem vonható vissza.">
                                                            <input type="hidden" name="_csrf" value="<?= adminEscape($csrfToken) ?>">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                                            <button class="button button-small icon-action icon-delete" type="submit" aria-label="Felhasználó törlése: <?= adminEscape((string)$user['username']) ?>" title="Felhasználó törlése">
                                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                                    <path d="M3 6h18"></path>
                                                                    <path d="M8 6V4h8v2"></path>
                                                                    <path d="m19 6-1 14H6L5 6"></path>
                                                                    <path d="M10 11v5M14 11v5"></path>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="panel" id="password-settings">
                    <p class="eyebrow">Biztonság</p>
                    <h2>Jelszó módosítása</h2>
                    <form method="post" action="<?= adminEscape($adminUrl) ?>/">
                        <input type="hidden" name="_csrf" value="<?= adminEscape($csrfToken) ?>">
                        <input type="hidden" name="action" value="change_password">
                        <label for="current-password">Jelenlegi jelszó</label>
                        <input id="current-password" name="current_password" type="password" autocomplete="current-password" required>
                        <label for="new-password">Új jelszó</label>
                        <input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="6" required>
                        <label for="confirm-password">Új jelszó ismét</label>
                        <input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" minlength="6" required>
                        <button class="button" type="submit">Jelszó módosítása</button>
                    </form>
                </section>

                <section class="panel panel-wide" id="product-list">
                    <p class="eyebrow">Katalógus</p>
                    <h2>Összes termék</h2>
                    <form class="product-search" method="get" action="<?= adminEscape($adminUrl) ?>/?return_anchor=product-list#product-list">
                        <label for="product-search">Keresés név, kategória vagy azonosító alapján</label>
                        <div class="product-search-controls">
                            <input id="product-search" name="product_search" type="search" value="<?= adminEscape($productSearch) ?>" maxlength="150" placeholder="Például: Baklava vagy Pohárkrém">
                            <button class="button" type="submit">Keresés</button>
                            <?php if ($productSearch !== ''): ?>
                                <a class="button button-quiet" href="<?= adminEscape($adminUrl) ?>/?return_anchor=product-list#product-list">Keresés törlése</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <p class="product-result-count"><?= count($products) ?> találat</p>
                    <?php if (!$products): ?>
                        <p><?= $productSearch !== '' ? 'Nincs a keresésnek megfelelő termék.' : 'Még nincs megjeleníthető termék.' ?></p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th>Azonosító / kép</th>
                                        <th>Terméknév</th>
                                        <th>Kategória</th>
                                        <th>Ár (Ft)</th>
                                        <th>Kép cseréje</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <?php
                                        $productId = (int)$product['id'];
                                        $productFormId = 'update-product-' . $productId;
                                        $productImageUrl = adminProductImageUrl($projectRoot, (string)$product['image_url']);
                                        ?>
                                        <tr id="product-<?= $productId ?>">
                                            <td>
                                                <span class="product-id">#<?= $productId ?></span>
                                                <?php if ($productImageUrl !== ''): ?>
                                                    <img class="product-thumbnail" src="<?= adminEscape($productImageUrl) ?>" alt="<?= adminEscape((string)$product['nev']) ?>" width="68" height="54" loading="lazy">
                                                <?php else: ?>
                                                    <span class="product-thumbnail-empty">Nincs kép</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <input form="<?= adminEscape($productFormId) ?>" class="product-edit-field product-name-field" name="product_name" value="<?= adminEscape((string)$product['nev']) ?>" maxlength="150" aria-label="Terméknév: <?= adminEscape((string)$product['nev']) ?>" required>
                                            </td>
                                            <td>
                                                <select form="<?= adminEscape($productFormId) ?>" class="product-edit-field product-category-field" name="category" aria-label="Kategória: <?= adminEscape((string)$product['nev']) ?>" required>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?= adminEscape($category) ?>" <?= $category === (string)$product['kategoria'] ? 'selected' : '' ?>><?= adminEscape($category) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input form="<?= adminEscape($productFormId) ?>" class="product-edit-field product-price-field" name="price" type="number" min="0" max="10000000" step="1" value="<?= (int)$product['ar'] ?>" aria-label="Ár: <?= adminEscape((string)$product['nev']) ?>" required>
                                            </td>
                                            <td>
                                                <input form="<?= adminEscape($productFormId) ?>" class="product-image-field" name="product_image" type="file" accept="image/jpeg,image/png,image/webp" aria-label="Új kép: <?= adminEscape((string)$product['nev']) ?>">
                                            </td>
                                            <td>
                                                <div class="product-row-actions">
                                                    <form id="<?= adminEscape($productFormId) ?>" class="product-edit-form" method="post" action="<?= adminEscape($adminUrl) ?>/" enctype="multipart/form-data">
                                                        <input type="hidden" name="_csrf" value="<?= adminEscape($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="update_product">
                                                        <input type="hidden" name="product_id" value="<?= $productId ?>">
                                                        <input type="hidden" name="product_search" value="<?= adminEscape($productSearch) ?>">
                                                    </form>
                                                    <button form="<?= adminEscape($productFormId) ?>" class="button button-small icon-action icon-edit" type="submit" aria-label="Termékmódosítások mentése: <?= adminEscape((string)$product['nev']) ?>" title="Termékmódosítások mentése">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                            <path d="M12 20h9"></path>
                                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"></path>
                                                        </svg>
                                                    </button>
                                                    <form class="product-delete-form" method="post" action="<?= adminEscape($adminUrl) ?>/" data-confirm-delete="Biztosan törlöd ezt a terméket?" data-return-anchor="product-list">
                                                        <input type="hidden" name="_csrf" value="<?= adminEscape($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="delete_product">
                                                        <input type="hidden" name="product_id" value="<?= $productId ?>">
                                                        <input type="hidden" name="product_search" value="<?= adminEscape($productSearch) ?>">
                                                        <button class="button button-small icon-action icon-delete" type="submit" aria-label="Termék törlése: <?= adminEscape((string)$product['nev']) ?>" title="Termék törlése">
                                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                                <path d="M3 6h18"></path>
                                                                <path d="M8 6V4h8v2"></path>
                                                                <path d="m19 6-1 14H6L5 6"></path>
                                                                <path d="M10 11v5M14 11v5"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
    </main>
    <script nonce="<?= adminEscape($scriptNonce) ?>">
        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (
                form.hasAttribute('data-confirm-delete')
                && !window.confirm(form.dataset.confirmDelete || 'Biztosan törlöd ezt a terméket?')
            ) {
                event.preventDefault();
                return;
            }

            if (form.method.toLowerCase() === 'get') {
                return;
            }

            const row = form.closest('tr[id]');
            const section = form.closest('section[id]');
            const header = form.closest('header[id]');
            const returnAnchor = form.dataset.returnAnchor
                || (row ? row.id : '')
                || (section ? section.id : '')
                || (header ? header.id : '');

            if (returnAnchor) {
                let input = form.querySelector('input[name="return_anchor"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'return_anchor';
                    form.appendChild(input);
                }
                input.value = returnAnchor;
            }
        });

        const returnAnchor = document.body.getAttribute('data-return-anchor');
        if (returnAnchor) {
            const target = document.getElementById(returnAnchor);
            if (target) {
                target.scrollIntoView();
                if (window.location.hash !== `#${returnAnchor}`) {
                    window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${returnAnchor}`);
                }
            }
            document.body.dataset.scrollRestored = 'true';
        }

        document.querySelectorAll('button[aria-controls]').forEach((trigger) => {
            const dialogId = trigger.getAttribute('aria-controls');
            const dialog = dialogId ? document.getElementById(dialogId) : null;
            if (!(trigger instanceof HTMLButtonElement) || !(dialog instanceof HTMLDialogElement)) {
                return;
            }

            const closeButton = dialog.querySelector('.user-dialog-close');
            trigger.addEventListener('click', () => dialog.showModal());
            if (closeButton instanceof HTMLButtonElement) {
                closeButton.addEventListener('click', () => dialog.close());
            }
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    dialog.close();
                }
            });

            if (dialog.dataset.autoOpen === 'true') {
                dialog.showModal();
            }
        });
    </script>
</body>
</html>
