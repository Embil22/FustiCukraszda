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

    // A képek a Backend/uploads/ mappában vannak.
    // Abszolút útvonalat adunk vissza a webszerver gyökeréhez képest:
    foreach ($termekek as &$t) {
        $t['ar'] = (int)$t['ar'];
        $t['image_url'] = '/FustiCukraszda/Backend/' . ltrim((string)$t['image_url'], '/');
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