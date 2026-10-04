<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/connection.php';

$tableNames = [
    'schema_migrations' => 'catatan_migrasi',
    'categories' => 'kategori',
    'products' => 'produk',
    'store_settings' => 'pengaturan_toko',
    'store_gallery' => 'galeri_toko',
    'admin_users' => 'admin',
    'orders' => 'pesanan',
    'order_items' => 'detail_pesanan',
    'order_item_toppings' => 'topping_detail_pesanan',
    'reviews' => 'ulasan',
    'support_messages' => 'pesan_bantuan',
    'push_subscriptions' => 'langganan_notifikasi',
    'payment_transactions' => 'transaksi_pembayaran',
    'payment_webhook_events' => 'catatan_webhook_pembayaran',
    'media_assets' => 'aset_media',
    'media_asset_chunks' => 'potongan_aset_media',
];

$columnNames = [
    'schema_migrations' => [
        'version' => 'versi',
        'applied_at' => 'diterapkan_pada',
    ],
    'categories' => [
        'id' => 'id_kategori',
        'name' => 'nama_kategori',
        'group_type' => 'jenis_kelompok',
        'sort_order' => 'urutan_tampil',
        'created_at' => 'dibuat_pada',
        'updated_at' => 'diperbarui_pada',
    ],
    'products' => [
        'id' => 'id_produk',
        'product_type' => 'jenis_produk',
        'category_id' => 'id_kategori',
        'topping_group' => 'kelompok_topping',
        'name' => 'nama_produk',
        'price' => 'harga',
        'is_favorite' => 'favorit',
        'is_available' => 'tersedia',
        'image_path' => 'jalur_gambar',
        'sort_order' => 'urutan_tampil',
        'created_at' => 'dibuat_pada',
        'updated_at' => 'diperbarui_pada',
    ],
    'store_settings' => [
        'id' => 'id_pengaturan_toko',
        'name' => 'nama_toko',
        'address' => 'alamat_toko',
        'whatsapp' => 'nomor_whatsapp',
        'opening_time' => 'jam_buka',
        'closing_time' => 'jam_tutup',
        'is_open' => 'sedang_buka',
        'activity' => 'keterangan_aktivitas',
        'status_updated_at' => 'status_diperbarui_pada',
        'updated_at' => 'diperbarui_pada',
    ],
    'store_gallery' => [
        'id' => 'id_galeri',
        'store_settings_id' => 'id_pengaturan_toko',
        'image_path' => 'jalur_gambar',
        'sort_order' => 'urutan_tampil',
        'created_at' => 'dibuat_pada',
    ],
    'admin_users' => [
        'id' => 'id_admin',
        'username' => 'nama_pengguna',
        'password_hash' => 'hash_kata_sandi',
        'is_active' => 'aktif',
        'created_at' => 'dibuat_pada',
        'updated_at' => 'diperbarui_pada',
    ],
    'orders' => [
        'id' => 'id_pesanan',
        'order_code' => 'kode_pesanan',
        'customer_name' => 'nama_pelanggan',
        'whatsapp' => 'nomor_whatsapp',
        'fulfillment_type' => 'metode_pengiriman',
        'pickup_time' => 'jam_pengambilan',
        'address' => 'alamat_pengiriman',
        'latitude' => 'garis_lintang',
        'longitude' => 'garis_bujur',
        'straight_distance_km' => 'jarak_lurus_km',
        'estimated_distance_km' => 'jarak_estimasi_km',
        'delivery_fee' => 'ongkos_kirim',
        'shipping_payment_method' => 'metode_bayar_ongkir',
        'payment_method' => 'metode_pembayaran',
        'payment_status' => 'status_pembayaran',
        'status' => 'status_pesanan',
        'subtotal' => 'subtotal_pesanan',
        'total' => 'total_pembayaran',
        'business_timezone' => 'zona_waktu_bisnis',
        'created_at' => 'dibuat_pada',
    ],
    'order_items' => [
        'id' => 'id_detail_pesanan',
        'order_id' => 'id_pesanan',
        'product_id' => 'id_produk',
        'product_name_snapshot' => 'nama_produk_saat_dipesan',
        'base_unit_price' => 'harga_dasar_satuan',
        'unit_price' => 'harga_satuan',
        'quantity' => 'jumlah',
        'notes' => 'catatan',
        'line_total' => 'total_baris',
    ],
    'order_item_toppings' => [
        'id' => 'id_topping_pesanan',
        'order_item_id' => 'id_detail_pesanan',
        'product_id' => 'id_produk',
        'topping_name_snapshot' => 'nama_topping_saat_dipesan',
        'unit_price_snapshot' => 'harga_topping_saat_dipesan',
        'quantity_per_item' => 'jumlah_per_item',
    ],
    'reviews' => [
        'id' => 'id_ulasan',
        'order_id' => 'id_pesanan',
        'customer_name_snapshot' => 'nama_pelanggan_saat_ulasan',
        'rating' => 'nilai_ulasan',
        'comment' => 'komentar',
        'created_at' => 'dibuat_pada',
    ],
    'support_messages' => [
        'id' => 'id_pesan_bantuan',
        'name' => 'nama_pengirim',
        'contact' => 'kontak',
        'category' => 'kategori',
        'message' => 'isi_pesan',
        'status' => 'status_penanganan',
        'created_at' => 'diterima_pada',
    ],
    'push_subscriptions' => [
        'id' => 'id_langganan',
        'admin_user_id' => 'id_admin',
        'endpoint' => 'alamat_endpoint',
        'endpoint_hash' => 'hash_endpoint',
        'p256dh_key' => 'kunci_p256dh',
        'auth_key' => 'kunci_otentikasi',
        'created_at' => 'dibuat_pada',
        'updated_at' => 'diperbarui_pada',
    ],
    'payment_transactions' => [
        'id' => 'id_transaksi_pembayaran',
        'order_id' => 'id_pesanan',
        'provider' => 'penyedia_pembayaran',
        'provider_transaction_id' => 'id_transaksi_penyedia',
        'idempotency_key' => 'kunci_idempotensi',
        'amount' => 'jumlah_bayar',
        'currency' => 'mata_uang',
        'status' => 'status_transaksi',
        'checkout_url' => 'url_pembayaran',
        'expires_at' => 'kedaluwarsa_pada',
        'paid_at' => 'dibayar_pada',
        'created_at' => 'dibuat_pada',
        'updated_at' => 'diperbarui_pada',
    ],
    'payment_webhook_events' => [
        'id' => 'id_event_webhook',
        'provider' => 'penyedia_pembayaran',
        'provider_event_id' => 'id_event_penyedia',
        'payload' => 'muatan_event',
        'processing_status' => 'status_pemrosesan',
        'received_at' => 'diterima_pada',
        'processed_at' => 'diproses_pada',
        'error_message' => 'pesan_kesalahan',
    ],
    'media_assets' => [
        'asset_path' => 'jalur_aset',
        'mime_type' => 'jenis_mime',
        'byte_size' => 'ukuran_byte',
        'sha256' => 'hash_sha256',
        'created_at' => 'dibuat_pada',
    ],
    'media_asset_chunks' => [
        'asset_path' => 'jalur_aset',
        'chunk_index' => 'nomor_potongan',
        'chunk_data' => 'data_potongan',
    ],
];

try {
    $pdo = goodlife_db();
    $databaseName = $pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($databaseName !== 'goodlife') {
        throw new RuntimeException('Expected database goodlife; refusing to rename another database.');
    }

    $tableExists = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
    );

    foreach ($tableNames as $oldName => $newName) {
        $tableExists->execute([$oldName]);
        $oldExists = (int)$tableExists->fetchColumn() === 1;
        $tableExists->execute([$newName]);
        $newExists = (int)$tableExists->fetchColumn() === 1;
        if ($oldExists && $newExists) {
            throw new RuntimeException('Both old and new tables exist; refusing ambiguous rename: ' . $oldName);
        }
        if (!$oldExists && !$newExists) {
            throw new RuntimeException('Neither old nor new table exists: ' . $oldName);
        }
    }

    $tableExists->execute(['schema_migrations']);
    if ((int)$tableExists->fetchColumn() === 1) {
        $baseMigration = $pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
        foreach (['001_initial_schema', '006_media_asset_chunks'] as $requiredVersion) {
            $baseMigration->execute([$requiredVersion]);
            if ((int)$baseMigration->fetchColumn() !== 1) {
                throw new RuntimeException(
                    'Required base migration is missing: ' . $requiredVersion . '. Apply existing SQL migrations first.'
                );
            }
        }
    }

    $renameParts = [];
    foreach ($tableNames as $oldName => $newName) {
        $tableExists->execute([$oldName]);
        if ((int)$tableExists->fetchColumn() === 1) {
            $renameParts[] = '`' . $oldName . '` TO `' . $newName . '`';
        }
    }
    if ($renameParts) {
        $pdo->exec('RENAME TABLE ' . implode(', ', $renameParts));
    }


    $columnInfo = $pdo->prepare(
        'SELECT column_type, is_nullable, column_default, extra, character_set_name, collation_name, column_comment
         FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
    );
    foreach ($columnNames as $oldTable => $columns) {
        $tableName = $tableNames[$oldTable];
        $changes = [];
        foreach ($columns as $oldColumn => $newColumn) {
            $columnInfo->execute([$tableName, $oldColumn]);
            $oldInfo = $columnInfo->fetch(PDO::FETCH_ASSOC);
            $columnInfo->execute([$tableName, $newColumn]);
            $newInfo = $columnInfo->fetch(PDO::FETCH_ASSOC);
            if ($oldInfo && $newInfo) {
                throw new RuntimeException('Both old and new columns exist: ' . $tableName . '.' . $oldColumn);
            }
            if ($newInfo) {
                continue;
            }
            if (!$oldInfo) {
                throw new RuntimeException('Column not found: ' . $tableName . '.' . $oldColumn);
            }

            $definition = $oldInfo['column_type'];
            if ($oldInfo['character_set_name'] !== null) {
                $definition .= ' CHARACTER SET ' . $oldInfo['character_set_name'];
                if ($oldInfo['collation_name'] !== null) {
                    $definition .= ' COLLATE ' . $oldInfo['collation_name'];
                }
            }
            $definition .= $oldInfo['is_nullable'] === 'YES' ? ' NULL' : ' NOT NULL';
            if ($oldInfo['column_default'] !== null) {
                $default = (string)$oldInfo['column_default'];
                if (preg_match('/^(CURRENT_TIMESTAMP(?:\(\d*\))?|NULL)$/i', $default)) {
                    $definition .= ' DEFAULT ' . $default;
                } elseif (preg_match('/^(?:[-+]?\d+(?:\.\d+)?|0x[0-9a-f]+)$/i', $default)) {
                    $definition .= ' DEFAULT ' . $default;
                } elseif (preg_match("/^'.*'$/s", $default)) {
                    $definition .= ' DEFAULT ' . $default;
                } else {
                    $definition .= ' DEFAULT ' . $pdo->quote($default);
                }
            }
            if ($oldInfo['extra'] !== '') {
                $definition .= ' ' . $oldInfo['extra'];
            }
            if ($oldInfo['column_comment'] !== '') {
                $definition .= ' COMMENT ' . $pdo->quote($oldInfo['column_comment']);
            }
            $changes[] = 'CHANGE COLUMN `' . $oldColumn . '` `' . $newColumn . '` ' . $definition;
        }
        if ($changes) {
            $pdo->exec('ALTER TABLE `' . $tableName . '` ' . implode(', ', $changes));
        }
    }

    $recordMigration = $pdo->prepare('INSERT INTO catatan_migrasi (versi) VALUES (?)');
    $migrationVersion = '008_nama_tabel_dan_kolom_indonesia';
    $migrationCheck = $pdo->prepare('SELECT COUNT(*) FROM catatan_migrasi WHERE versi = ?');
    $migrationCheck->execute([$migrationVersion]);
    if ((int)$migrationCheck->fetchColumn() !== 0) {
        echo 'Indonesian database names are already applied.' . PHP_EOL;
        exit(0);
    }

    $recordMigration->execute([$migrationVersion]);
    echo 'Renamed ' . count($tableNames) . ' tables and their columns to Indonesian.' . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'Schema rename failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
