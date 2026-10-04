<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/connection.php';

function goodlife_import_read_json($path)
{
    if (!is_file($path)) {
        throw new RuntimeException('Required source data file is missing: ' . basename($path));
    }

    $contents = file_get_contents($path);
    $data = $contents === false ? null : json_decode($contents, true);
    if (!is_array($data)) {
        throw new RuntimeException('Source JSON is invalid: ' . basename($path));
    }

    return $data;
}

function goodlife_import_legacy_datetime($value, $dateOnly = false)
{
    if (!is_string($value)) {
        return null;
    }

    $format = $dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s';
    $normalized = $dateOnly ? $value : (strlen($value) === 16 ? $value . ':00' : $value);
    $date = DateTimeImmutable::createFromFormat('!' . $format, $normalized);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
        return null;
    }

    return $date->format($dateOnly ? 'Y-m-d 00:00:00' : 'Y-m-d H:i:s');
}

try {
    $pdo = goodlife_db();
    $migrationVersion = '002_initial_data_import';
    $existingMigration = $pdo->prepare('SELECT COUNT(*) FROM catatan_migrasi WHERE versi = ?');
    $existingMigration->execute([$migrationVersion]);
    if ((int)$existingMigration->fetchColumn() !== 0) {
        throw new RuntimeException('Initial data import is already recorded; refusing to run it again.');
    }
    $sortOrderMigration = $pdo->prepare('SELECT COUNT(*) FROM catatan_migrasi WHERE versi = ?');
    $sortOrderMigration->execute(['003_product_sort_order']);
    if ((int)$sortOrderMigration->fetchColumn() !== 1) {
        throw new RuntimeException('Apply database/migrations/003_product_sort_order.sql before importing initial data.');
    }

    $targetTables = [
        'kategori',
        'produk',
        'pengaturan_toko',
        'galeri_toko',
        'pesan_bantuan',
        'pesanan',
        'detail_pesanan',
        'topping_detail_pesanan',
        'ulasan',
    ];
    foreach ($targetTables as $table) {
        $count = (int)$pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        if ($count !== 0) {
            throw new RuntimeException('Target table is not empty (' . $table . '); import cancelled without changing data.');
        }
    }

    $dataDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    $categories = goodlife_import_read_json($dataDirectory . DIRECTORY_SEPARATOR . 'categories.json');
    $products = goodlife_import_read_json($dataDirectory . DIRECTORY_SEPARATOR . 'menu.json');
    $storeRecords = goodlife_import_read_json($dataDirectory . DIRECTORY_SEPARATOR . 'store.json');
    $supportMessages = goodlife_import_read_json($dataDirectory . DIRECTORY_SEPARATOR . 'support_messages.json');

    $store = $storeRecords;
    if (isset($storeRecords[0]) && is_array($storeRecords[0])) {
        if (count($storeRecords) !== 1 || !is_array($storeRecords[0])) {
            throw new RuntimeException('Store settings JSON must contain one settings object.');
        }
        $store = $storeRecords[0];
    }

    $categoryById = [];
    foreach ($categories as $position => $category) {
        if (!is_array($category) || !is_string($category['id'] ?? null) ||
            !is_string($category['nama'] ?? null) ||
            !in_array($category['kelompok'] ?? null, ['makanan', 'minuman'], true)) {
            throw new RuntimeException('A source menu category has invalid fields.');
        }
        if (isset($categoryById[$category['id']])) {
            throw new RuntimeException('Duplicate source category ID: ' . $category['id']);
        }
        $categoryById[$category['id']] = $category['kelompok'];
    }

    if (!is_string($store['nama'] ?? null) || !is_string($store['alamat'] ?? null) ||
        !is_string($store['wa'] ?? null) ||
        !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', (string)($store['jam_buka'] ?? '')) ||
        !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', (string)($store['jam_tutup'] ?? '')) ||
        !is_array($store['gallery'] ?? null)) {
        throw new RuntimeException('Source store settings contain invalid fields.');
    }

    $pdo->beginTransaction();
    $nowUtc = gmdate('Y-m-d H:i:s');
    $insertCategory = $pdo->prepare(
        'INSERT INTO kategori (id_kategori, nama_kategori, jenis_kelompok, urutan_tampil, dibuat_pada, diperbarui_pada)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($categories as $position => $category) {
        $insertCategory->execute([
            $category['id'],
            $category['nama'],
            $category['kelompok'],
            $position,
            $nowUtc,
            $nowUtc,
        ]);
    }

    $insertProduct = $pdo->prepare(
        'INSERT INTO produk
         (id_produk, jenis_produk, id_kategori, kelompok_topping, nama_produk, harga, favorit, tersedia,
          jalur_gambar, urutan_tampil, dibuat_pada, diperbarui_pada)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($products as $productPosition => $product) {
        if (!is_array($product) || !is_string($product['id'] ?? null) ||
            !is_string($product['nama'] ?? null) || !is_string($product['kategori'] ?? null) ||
            !isset($product['harga']) || !is_numeric($product['harga']) ||
            (int)$product['harga'] < 0 || (string)(int)$product['harga'] !== (string)$product['harga']) {
            throw new RuntimeException('A source product has invalid fields.');
        }

        $categoryId = $product['kategori'];
        if (isset($categoryById[$categoryId])) {
            $productType = 'menu';
            $toppingGroup = null;
        } elseif (in_array($categoryId, ['extra_topping_makanan', 'extra_topping_minuman'], true)) {
            $productType = 'topping';
            $toppingGroup = $categoryId === 'extra_topping_makanan' ? 'makanan' : 'minuman';
            $categoryId = null;
        } else {
            throw new RuntimeException('A source product refers to an unknown category: ' . $categoryId);
        }

        $imagePath = $product['gambar'] ?? null;
        if ($imagePath !== null && !is_string($imagePath)) {
            throw new RuntimeException('A source product has an invalid image path.');
        }

        $insertProduct->execute([
            $product['id'],
            $productType,
            $categoryId,
            $toppingGroup,
            $product['nama'],
            (int)$product['harga'],
            !empty($product['favorit']) ? 1 : 0,
            !array_key_exists('tersedia', $product) || !empty($product['tersedia']) ? 1 : 0,
            $imagePath,
            $productPosition,
            $nowUtc,
            $nowUtc,
        ]);
    }

    $statusUpdatedAt = goodlife_import_legacy_datetime($store['status_updated_at'] ?? null);
    $insertStore = $pdo->prepare(
        'INSERT INTO pengaturan_toko
         (id_pengaturan_toko, nama_toko, alamat_toko, nomor_whatsapp, jam_buka, jam_tutup, sedang_buka,
          keterangan_aktivitas, status_diperbarui_pada, diperbarui_pada)
         VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insertStore->execute([
        $store['nama'],
        $store['alamat'],
        $store['wa'],
        $store['jam_buka'],
        $store['jam_tutup'],
        !array_key_exists('is_open', $store) || $store['is_open'] === true ? 1 : 0,
        is_string($store['activity'] ?? null) ? $store['activity'] : 'Tutup sementara',
        $statusUpdatedAt,
        $nowUtc,
    ]);

    $insertGalleryImage = $pdo->prepare(
        'INSERT INTO galeri_toko (id_pengaturan_toko, jalur_gambar, urutan_tampil, dibuat_pada)
         VALUES (1, ?, ?, ?)'
    );
    foreach (array_values($store['gallery']) as $position => $imagePath) {
        if (!is_string($imagePath) || $imagePath === '') {
            throw new RuntimeException('A source gallery image path is invalid.');
        }
        $insertGalleryImage->execute([$imagePath, $position, $nowUtc]);
    }

    $insertSupportMessage = $pdo->prepare(
        'INSERT INTO pesan_bantuan
         (nama_pengirim, kontak, kategori, isi_pesan, status_penanganan, diterima_pada)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($supportMessages as $message) {
        if (!is_array($message) || !is_string($message['nama'] ?? null) ||
            !is_string($message['kontak'] ?? null) || !is_string($message['kategori'] ?? null) ||
            !is_string($message['pesan'] ?? null) || !is_string($message['status'] ?? null)) {
            throw new RuntimeException('A source support message has invalid fields.');
        }
        $createdAt = goodlife_import_legacy_datetime($message['tanggal'] ?? null);
        if ($createdAt === null) {
            throw new RuntimeException('A source support message has an invalid timestamp.');
        }
        $insertSupportMessage->execute([
            $message['nama'],
            $message['kontak'],
            $message['kategori'],
            $message['pesan'],
            $message['status'],
            $createdAt,
        ]);
    }

    $recordMigration = $pdo->prepare('INSERT INTO catatan_migrasi (versi) VALUES (?)');
    $recordMigration->execute([$migrationVersion]);
    $pdo->commit();

    echo 'Initial data import completed.' . PHP_EOL;
    echo 'Categories: ' . count($categories) . PHP_EOL;
    echo 'Products and toppings: ' . count($products) . PHP_EOL;
    echo 'Store settings: 1' . PHP_EOL;
    echo 'Store gallery images: ' . count($store['gallery']) . PHP_EOL;
    echo 'Support messages: ' . count($supportMessages) . PHP_EOL;
    echo 'Orders and reviews imported: 0' . PHP_EOL;
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Initial data import failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
