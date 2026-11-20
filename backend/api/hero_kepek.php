<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Csak GET kérés engedélyezett.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->prepare(
        "SELECT setting_value FROM site_settings WHERE setting_key = 'hero_images'"
    );
    $stmt->execute();
    $storedImages = json_decode((string)($stmt->fetchColumn() ?: '[]'), true);

    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $projectRoot = preg_replace('#/backend/api$#', '', $scriptDirectory) ?: '';
    if ($projectRoot === '.') {
        $projectRoot = '';
    }

    $imageUrls = [];
    if (is_array($storedImages)) {
        foreach ($storedImages as $imagePath) {
            if (
                !is_string($imagePath)
                || !preg_match('#^Uploads/([a-f0-9]{32}\.(?:jpg|png|webp))$#D', $imagePath, $matches)
            ) {
                continue;
            }

            $imageUrls[] = $projectRoot . '/backend/Uploads/' . rawurlencode($matches[1]);
        }
    }

    echo json_encode(array_slice($imageUrls, 0, 10), JSON_UNESCAPED_UNICODE);
} catch (PDOException $exception) {
    error_log('Hero images API failed: ' . $exception->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'A hero képek jelenleg nem érhetők el.'], JSON_UNESCAPED_UNICODE);
}
