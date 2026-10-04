<?php

require_once __DIR__ . '/database/repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}

$assetPath = $_GET['path'] ?? '';
try {
    $media = goodlife_db_get_media($assetPath);
} catch (Throwable $error) {
    error_log('Database media read failed: ' . $error->getMessage());
    http_response_code(500);
    exit;
}
if (!$media) {
    http_response_code(404);
    exit;
}

$etag = '"' . bin2hex($media['sha256']) . '"';
header('Content-Type: ' . $media['mime_type']);
header('Content-Length: ' . (int)$media['byte_size']);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: ' . $etag);
if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
    http_response_code(304);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    $chunkSize = 256 * 1024;
    for ($offset = 0, $size = (int)$media['byte_size']; $offset < $size; $offset += $chunkSize) {
        $expectedLength = min($chunkSize, $size - $offset);
        $chunk = goodlife_db_get_media_chunk(
            $assetPath,
            $offset,
            $expectedLength
        );
        if ($chunk === false || strlen($chunk) !== $expectedLength) {
            error_log('Database media stream ended before the recorded byte size.');
            exit;
        }
        echo $chunk;
    }
}
