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
    $stmt = $pdo->query(
        "SELECT setting_key, setting_value
         FROM site_settings
         WHERE setting_key IN ('offer_enabled', 'offer_text', 'offer_image')"
    );
    $settings = [];
    foreach ($stmt->fetchAll() as $setting) {
        $settings[(string)$setting['setting_key']] = (string)$setting['setting_value'];
    }

    $imagePath = (string)($settings['offer_image'] ?? '');
    $imageFilename = $imagePath !== '' ? basename($imagePath) : '';
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $projectRoot = preg_replace('#/backend/api$#', '', $scriptDirectory) ?: '';
    if ($projectRoot === '.') {
        $projectRoot = '';
    }
    $imageUrl = preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $imageFilename)
        ? $projectRoot . '/backend/Uploads/' . rawurlencode($imageFilename)
        : null;

    echo json_encode([
        'enabled' => ($settings['offer_enabled'] ?? '0') === '1',
        'text' => $settings['offer_text'] ?? '',
        'image_url' => $imageUrl,
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $exception) {
    error_log('Offer API failed: ' . $exception->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Az ajánlat jelenleg nem érhető el.'], JSON_UNESCAPED_UNICODE);
}
