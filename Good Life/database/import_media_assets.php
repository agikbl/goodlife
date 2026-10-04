<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/connection.php';

try {
    $pdo = goodlife_db();
    $requiredMigration = $pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
    $requiredMigration->execute(['006_media_asset_chunks']);
    if ((int)$requiredMigration->fetchColumn() !== 1) {
        throw new RuntimeException('Apply database/migrations/006_media_asset_chunks.sql before importing images.');
    }
    $importVersion = '007_media_asset_chunks_import';
    $requiredMigration->execute([$importVersion]);
    if ((int)$requiredMigration->fetchColumn() !== 0) {
        throw new RuntimeException('Image import is already recorded; refusing to run it again.');
    }
    if ((int)$pdo->query('SELECT COUNT(*) FROM media_asset_chunks')->fetchColumn() !== 0) {
        throw new RuntimeException('media_asset_chunks is not empty; image import cancelled.');
    }

    $paths = $pdo->query(
        'SELECT image_path FROM products WHERE image_path IS NOT NULL
         UNION SELECT image_path FROM store_gallery ORDER BY image_path'
    )->fetchAll(PDO::FETCH_COLUMN);
    $baseDirectory = dirname(__DIR__);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $extensionByMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $findAsset = $pdo->prepare('SELECT 1 FROM media_assets WHERE asset_path = ?');
    $insertAsset = $pdo->prepare(
        'INSERT INTO media_assets (asset_path, mime_type, byte_size, sha256, created_at)
         VALUES (?, ?, ?, ?, UTC_TIMESTAMP(6))'
    );
    $updateAsset = $pdo->prepare(
        'UPDATE media_assets SET mime_type = ?, byte_size = ?, sha256 = ? WHERE asset_path = ?'
    );
    $insertChunk = $pdo->prepare(
        'INSERT INTO media_asset_chunks (asset_path, chunk_index, chunk_data) VALUES (?, ?, ?)'
    );
    $readChunk = $pdo->prepare(
        'SELECT chunk_data FROM media_asset_chunks WHERE asset_path = ? AND chunk_index = ?'
    );

    $pdo->beginTransaction();
    foreach ($paths as $assetPath) {
        if (!is_string($assetPath) ||
            !preg_match('#^assets/(?:menu|gallery)/[a-f0-9]{32}\.(jpg|png|webp)$#', $assetPath)) {
            throw new RuntimeException('Unexpected image path in database: ' . (string)$assetPath);
        }
        $filePath = $baseDirectory . DIRECTORY_SEPARATOR .
            str_replace('/', DIRECTORY_SEPARATOR, $assetPath);
        if (!is_file($filePath)) {
            throw new RuntimeException('Referenced image file is missing: ' . $assetPath);
        }
        $size = filesize($filePath);
        $mimeType = $finfo->file($filePath);
        $extension = $extensionByMime[$mimeType] ?? null;
        if ($size === false || $size < 1 || $size > 5 * 1024 * 1024 ||
            $extension !== pathinfo($assetPath, PATHINFO_EXTENSION) ||
            !getimagesize($filePath)) {
            throw new RuntimeException('Referenced image is invalid or exceeds 5 MB: ' . $assetPath);
        }
        $imageData = file_get_contents($filePath);
        if ($imageData === false) {
            throw new RuntimeException('Referenced image could not be read: ' . $assetPath);
        }
        $sha256 = hash('sha256', $imageData, true);
        $findAsset->execute([$assetPath]);
        if ($findAsset->fetchColumn()) {
            $updateAsset->bindValue(1, $mimeType, PDO::PARAM_STR);
            $updateAsset->bindValue(2, strlen($imageData), PDO::PARAM_INT);
            $updateAsset->bindValue(3, $sha256, PDO::PARAM_LOB);
            $updateAsset->bindValue(4, $assetPath, PDO::PARAM_STR);
            $updateAsset->execute();
        } else {
            $insertAsset->bindValue(1, $assetPath, PDO::PARAM_STR);
            $insertAsset->bindValue(2, $mimeType, PDO::PARAM_STR);
            $insertAsset->bindValue(3, strlen($imageData), PDO::PARAM_INT);
            $insertAsset->bindValue(4, $sha256, PDO::PARAM_LOB);
            $insertAsset->execute();
        }

        $chunkSize = 256 * 1024;
        for ($offset = 0, $length = strlen($imageData), $chunkIndex = 0; $offset < $length; $offset += $chunkSize, $chunkIndex++) {
            $chunk = substr($imageData, $offset, $chunkSize);
            $insertChunk->bindValue(1, $assetPath, PDO::PARAM_STR);
            $insertChunk->bindValue(2, $chunkIndex, PDO::PARAM_INT);
            $insertChunk->bindValue(3, $chunk, PDO::PARAM_LOB);
            $insertChunk->execute();
        }

        $storedHash = hash_init('sha256');
        $storedLength = 0;
        for ($offset = 0, $chunkIndex = 0; $offset < strlen($imageData); $offset += $chunkSize, $chunkIndex++) {
            $readChunk->execute([$assetPath, $chunkIndex]);
            $chunk = $readChunk->fetchColumn();
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('A stored image chunk could not be read back: ' . $assetPath);
            }
            hash_update($storedHash, $chunk);
            $storedLength += strlen($chunk);
        }
        if ($storedLength !== strlen($imageData) ||
            !hash_equals(hash_final($storedHash, true), $sha256)) {
            throw new RuntimeException('Stored image checksum verification failed: ' . $assetPath);
        }
    }

    $record = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
    $record->execute([$importVersion]);
    $pdo->commit();

    echo 'Referenced media files imported: ' . count($paths) . PHP_EOL;
    echo 'Total database image bytes: ' . (int)$pdo->query('SELECT SUM(byte_size) FROM media_assets')->fetchColumn() . PHP_EOL;
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Throwable $rollbackError) {
            error_log('Image import rollback failed: ' . $rollbackError->getMessage());
        }
    }
    fwrite(STDERR, 'Media import failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
