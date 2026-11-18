<?php
// Backend/api/termekek.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../db.php';

$kategoria = $_GET['kategoria'] ?? null;

$ervenyesKategoriak = [
    'Szeletes sütemények',
    'Alkalmi Torták',
    'Aprósütemények',
    'Fagylalt',
];

try {
    if ($kategoria && in_array($kategoria, $ervenyesKategoriak, true)) {
        $stmt = $pdo->prepare(
            'SELECT id, nev, kategoria, ar, image_url
             FROM termekek
             WHERE kategoria = :kategoria
             ORDER BY nev ASC'
        );
        $stmt->execute([':kategoria' => $kategoria]);
    } else {
        $stmt = $pdo->query(
            'SELECT id, nev, kategoria, ar, image_url
             FROM termekek
             ORDER BY kategoria ASC, nev ASC'
        );
    }

    $termekek = $stmt->fetchAll();

    // A képek a backend/uploads mappában vannak a projektben.
    // Visszaadunk egy kliens-oldalról értelmezhető relatív elérési utat
    // és URL-encode-oljuk a fájlnevet (szóközök, ékezetek miatt).
    foreach ($termekek as &$t) {
        $t['ar'] = (int)$t['ar'];
        $filename = basename((string)$t['image_url']);

        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $projectRoot = preg_replace('#/backend(?:/api)?$#', '', dirname($scriptPath) ?: '/');
        if ($projectRoot === '') {
            $projectRoot = '';
        }

        $t['image_url'] = $projectRoot . '/backend/Uploads/' . rawurlencode($filename);
    }
    unset($t);

    echo json_encode($termekek, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Lekérdezési hiba',
        'details' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
