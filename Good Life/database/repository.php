<?php

require_once __DIR__ . '/connection.php';

function goodlife_media_url($assetPath)
{
    if (!is_string($assetPath) || $assetPath === '') {
        return '';
    }
    return 'media.php?path=' . rawurlencode($assetPath);
}

function goodlife_db_store()
{
    $pdo = goodlife_db();
    $store = $pdo->query(
        'SELECT nama_toko AS name, alamat_toko AS address, jam_buka AS opening_time,
                jam_tutup AS closing_time, nomor_whatsapp AS whatsapp, sedang_buka AS is_open,
                keterangan_aktivitas AS activity, menerima_tunai AS accepts_cash, status_diperbarui_pada AS status_updated_at
         FROM pengaturan_toko WHERE id_pengaturan_toko = 1'
    )->fetch();
    if (!$store) {
        throw new RuntimeException('Store settings have not been initialized in the database.');
    }

    $galleryQuery = $pdo->prepare(
        'SELECT jalur_gambar FROM galeri_toko WHERE id_pengaturan_toko = 1 ORDER BY urutan_tampil, id_galeri'
    );
    $galleryQuery->execute();

    return [
        'nama' => $store['name'],
        'alamat' => $store['address'],
        'jam_buka' => substr((string)$store['opening_time'], 0, 5),
        'jam_tutup' => substr((string)$store['closing_time'], 0, 5),
        'wa' => $store['whatsapp'],
        'gallery' => $galleryQuery->fetchAll(PDO::FETCH_COLUMN),
        'is_open' => (bool)$store['is_open'],
        'activity' => $store['activity'],
        'menerima_tunai' => (bool)$store['accepts_cash'],
        'status_updated_at' => $store['status_updated_at'],
    ];
}

function goodlife_db_categories()
{
    $rows = goodlife_db()->query(
        'SELECT id_kategori AS id, nama_kategori AS name, jenis_kelompok AS group_type
         FROM kategori ORDER BY urutan_tampil, id_kategori'
    )->fetchAll();

    return array_map(static function ($row) {
        return [
            'id' => $row['id'],
            'nama' => $row['name'],
            'kelompok' => $row['group_type'],
        ];
    }, $rows);
}

function goodlife_db_products()
{
    $rows = goodlife_db()->query(
        'SELECT id_produk AS id, jenis_produk AS product_type, id_kategori AS category_id,
                kelompok_topping AS topping_group, nama_produk AS name, harga AS price,
                favorit AS is_favorite, tersedia AS is_available, jalur_gambar AS image_path
         FROM produk ORDER BY urutan_tampil, id_produk'
    )->fetchAll();

    return array_map(static function ($row) {
        $category = $row['product_type'] === 'topping'
            ? 'extra_topping_' . $row['topping_group']
            : $row['category_id'];
        return [
            'id' => $row['id'],
            'nama' => $row['name'],
            'kategori' => $category,
            'harga' => (int)$row['price'],
            'favorit' => (bool)$row['is_favorite'],
            'tersedia' => (bool)$row['is_available'],
            'gambar' => goodlife_media_url($row['image_path']),
        ];
    }, $rows);
}

function goodlife_db_order_from_row($row)
{
    $pdo = goodlife_db();
    $itemsQuery = $pdo->prepare(
        'SELECT id_detail_pesanan AS id, id_produk AS product_id,
                nama_produk_saat_dipesan AS product_name_snapshot,
                harga_dasar_satuan AS base_unit_price, harga_satuan AS unit_price,
                jumlah AS quantity, catatan AS notes
         FROM detail_pesanan WHERE id_pesanan = ? ORDER BY id_detail_pesanan'
    );
    $itemsQuery->execute([$row['id']]);
    $items = [];
    foreach ($itemsQuery->fetchAll() as $item) {
        $toppingsQuery = $pdo->prepare(
            'SELECT id_produk AS product_id, nama_topping_saat_dipesan AS topping_name_snapshot,
                    harga_topping_saat_dipesan AS unit_price_snapshot
             FROM topping_detail_pesanan WHERE id_detail_pesanan = ? ORDER BY id_topping_pesanan'
        );
        $toppingsQuery->execute([$item['id']]);
        $toppings = [];
        foreach ($toppingsQuery->fetchAll() as $topping) {
            $toppings[] = [
                'id' => $topping['product_id'],
                'nama' => $topping['topping_name_snapshot'],
                'harga' => (int)$topping['unit_price_snapshot'],
            ];
        }
        $items[] = [
            'id' => $item['product_id'],
            'nama' => $item['product_name_snapshot'],
            'harga' => (int)$item['unit_price'],
            'base_harga' => (int)$item['base_unit_price'],
            'qty' => (int)$item['quantity'],
            'notes' => $item['notes'],
            'toppings' => $toppings,
        ];
    }

    $businessTime = new DateTimeImmutable($row['created_at'], new DateTimeZone('UTC'));
    $businessTime = $businessTime->setTimezone(new DateTimeZone('Asia/Makassar'));

    return [
        'id' => $row['order_code'],
        'nama_pemesan' => $row['customer_name'],
        'whatsapp' => $row['whatsapp'],
        'items' => $items,
        'pengiriman' => $row['fulfillment_type'],
        'waktu_pengambilan' => $row['pickup_time'] ? substr((string)$row['pickup_time'], 0, 5) : null,
        'alamat' => $row['address'] ?? '',
        'ongkir' => (int)$row['delivery_fee'],
        'latitude' => $row['latitude'] === null ? null : (float)$row['latitude'],
        'longitude' => $row['longitude'] === null ? null : (float)$row['longitude'],
        'jarak_lurus' => $row['straight_distance_km'] === null ? null : (float)$row['straight_distance_km'],
        'jarak_estimasi' => $row['estimated_distance_km'] === null ? null : (float)$row['estimated_distance_km'],
        'bayar_ongkir' => $row['shipping_payment_method'],
        'metode_bayar' => $row['payment_method'],
        'subtotal' => (int)$row['subtotal'],
        'total' => (int)$row['total'],
        'status' => $row['status'],
        'status_pembayaran' => $row['payment_status'],
        'tanggal' => $businessTime->format('Y-m-d H:i'),
        'zona_waktu' => 'Asia/Makassar',
        '_database_id' => (int)$row['id'],
    ];
}

function goodlife_db_orders($filters = [])
{
    $pdo = goodlife_db();
    $where = [];
    $params = [];

    if (isset($filters['whatsapp'])) {
        $where[] = 'nomor_whatsapp = ?';
        $params[] = $filters['whatsapp'];
    }
    if (isset($filters['order_code'])) {
        $where[] = 'kode_pesanan = ?';
        $params[] = $filters['order_code'];
    }
    if (isset($filters['created_from'])) {
        $where[] = 'dibuat_pada >= ?';
        $params[] = $filters['created_from'];
    }
    if (isset($filters['created_before'])) {
        $where[] = 'dibuat_pada < ?';
        $params[] = $filters['created_before'];
    }

    $sql = 'SELECT id_pesanan AS id, kode_pesanan AS order_code, nama_pelanggan AS customer_name,
                   nomor_whatsapp AS whatsapp, metode_pengiriman AS fulfillment_type,
                   jam_pengambilan AS pickup_time, alamat_pengiriman AS address,
                   garis_lintang AS latitude, garis_bujur AS longitude,
                   jarak_lurus_km AS straight_distance_km, jarak_estimasi_km AS estimated_distance_km,
                   ongkos_kirim AS delivery_fee, metode_bayar_ongkir AS shipping_payment_method,
                   metode_pembayaran AS payment_method, status_pembayaran AS payment_status,
                   status_pesanan AS status, subtotal_pesanan AS subtotal, total_pembayaran AS total,
                   zona_waktu_bisnis AS business_timezone,
                   dibuat_pada AS created_at
            FROM pesanan' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
        ' ORDER BY dibuat_pada DESC, id_pesanan DESC';
    $query = $pdo->prepare($sql);
    $query->execute($params);
    return array_map('goodlife_db_order_from_row', $query->fetchAll());
}

function goodlife_db_reviews()
{
    $rows = goodlife_db()->query(
        'SELECT u.nama_pelanggan_saat_ulasan AS customer_name_snapshot,
                u.nilai_ulasan AS rating,
                u.komentar AS comment, u.dibuat_pada AS created_at, p.kode_pesanan AS order_code
         FROM ulasan u JOIN pesanan p ON p.id_pesanan = u.id_pesanan
         ORDER BY u.dibuat_pada DESC, u.id_ulasan DESC'
    )->fetchAll();

    return array_map(static function ($row) {
        return [
            'order_id' => $row['order_code'],
            'nama' => $row['customer_name_snapshot'],
            'rating' => (int)$row['rating'],
            'komentar' => $row['comment'],
            'tanggal' => (new DateTimeImmutable($row['created_at'], new DateTimeZone('UTC')))
                ->setTimezone(new DateTimeZone('Asia/Makassar'))->format('Y-m-d'),
        ];
    }, $rows);
}

function goodlife_db_support_messages()
{
    $rows = goodlife_db()->query(
        'SELECT nama_pengirim AS name, kontak AS contact, kategori AS category,
                isi_pesan AS message, status_penanganan AS status, diterima_pada AS created_at
         FROM pesan_bantuan ORDER BY diterima_pada DESC, id_pesan_bantuan DESC'
    )->fetchAll();
    return array_map(static function ($row) {
        return [
            'nama' => $row['name'],
            'kontak' => $row['contact'],
            'kategori' => $row['category'],
            'pesan' => $row['message'],
            'status' => $row['status'],
            'tanggal' => (new DateTimeImmutable($row['created_at'], new DateTimeZone('UTC')))
                ->setTimezone(new DateTimeZone('Asia/Makassar'))->format('Y-m-d H:i'),
        ];
    }, $rows);
}

function goodlife_db_push_subscriptions()
{
    $rows = goodlife_db()->query(
        'SELECT alamat_endpoint AS endpoint, kunci_p256dh AS p256dh_key,
                kunci_otentikasi AS auth_key
         FROM langganan_notifikasi ORDER BY id_langganan'
    )->fetchAll();
    return array_map(static function ($row) {
        return [
            'endpoint' => $row['endpoint'],
            'keys' => ['p256dh' => $row['p256dh_key'], 'auth' => $row['auth_key']],
        ];
    }, $rows);
}

function goodlife_db_legacy_read($relativePath)
{
    switch (str_replace('\\', '/', $relativePath)) {
        case 'data/store.json':
            return goodlife_db_store();
        case 'data/categories.json':
            return goodlife_db_categories();
        case 'data/menu.json':
            return goodlife_db_products();
        case 'data/orders.json':
            return goodlife_db_orders();
        case 'data/reviews.json':
            return goodlife_db_reviews();
        case 'data/support_messages.json':
            return goodlife_db_support_messages();
        case 'data/push_subscriptions.json':
            return goodlife_db_push_subscriptions();
        default:
            throw new InvalidArgumentException('Unknown database-backed dataset: ' . $relativePath);
    }
}

function goodlife_db_create_order(array $order)
{
    $pdo = goodlife_db();
    $pdo->beginTransaction();
    try {
        $createdAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $businessDate = $createdAt->setTimezone(new DateTimeZone('Asia/Makassar'));
        $temporaryCode = 'TMP' . bin2hex(random_bytes(16));
        $insertOrder = $pdo->prepare(
            'INSERT INTO pesanan (
                kode_pesanan, nama_pelanggan, nomor_whatsapp, metode_pengiriman, jam_pengambilan, alamat_pengiriman,
                garis_lintang, garis_bujur, jarak_lurus_km, jarak_estimasi_km, ongkos_kirim,
                metode_bayar_ongkir, metode_pembayaran, status_pembayaran, status_pesanan,
                subtotal_pesanan, total_pembayaran,
                zona_waktu_bisnis, dibuat_pada
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertOrder->execute([
            $temporaryCode,
            $order['nama_pemesan'],
            $order['whatsapp'],
            $order['pengiriman'],
            $order['waktu_pengambilan'],
            $order['alamat'],
            $order['latitude'],
            $order['longitude'],
            $order['jarak_lurus'],
            $order['jarak_estimasi'],
            $order['ongkir'],
            $order['bayar_ongkir'],
            $order['metode_bayar'],
            'Belum dibayar',
            'Diterima',
            $order['subtotal'],
            $order['total'],
            'Asia/Makassar',
            $createdAt->format('Y-m-d H:i:s.u'),
        ]);
        $databaseOrderId = (int)$pdo->lastInsertId();
        $orderCode = 'ORD' . $businessDate->format('ymd') . str_pad((string)$databaseOrderId, 3, '0', STR_PAD_LEFT);
        $updateCode = $pdo->prepare('UPDATE pesanan SET kode_pesanan = ? WHERE id_pesanan = ?');
        $updateCode->execute([$orderCode, $databaseOrderId]);

        $insertItem = $pdo->prepare(
            'INSERT INTO detail_pesanan (id_pesanan, id_produk, nama_produk_saat_dipesan, harga_dasar_satuan, harga_satuan, jumlah, catatan, total_baris)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertTopping = $pdo->prepare(
            'INSERT INTO topping_detail_pesanan (id_detail_pesanan, id_produk, nama_topping_saat_dipesan, harga_topping_saat_dipesan, jumlah_per_item)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($order['items'] as $item) {
            $insertItem->execute([
                $databaseOrderId,
                $item['id'],
                $item['nama'],
                $item['base_harga'],
                $item['harga'],
                $item['qty'],
                $item['notes'],
                $item['harga'] * $item['qty'],
            ]);
            $databaseItemId = (int)$pdo->lastInsertId();
            foreach ($item['toppings'] as $topping) {
                $insertTopping->execute([
                    $databaseItemId,
                    $topping['id'],
                    $topping['nama'],
                    $topping['harga'],
                    1,
                ]);
            }
        }

        $pdo->commit();
        $order['id'] = $orderCode;
        $order['status'] = 'Diterima';
        $order['status_pembayaran'] = 'Belum dibayar';
        $order['tanggal'] = $businessDate->format('Y-m-d H:i');
        $order['zona_waktu'] = 'Asia/Makassar';
        return $order;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function goodlife_db_advance_order($orderCode, $action)
{
    $pdo = goodlife_db();
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare(
            'SELECT id_pesanan AS id, status_pembayaran AS payment_status,
                    metode_pengiriman AS fulfillment_type, metode_pembayaran AS payment_method,
                    status_pesanan AS status
             FROM pesanan WHERE kode_pesanan = ? FOR UPDATE'
        );
        $query->execute([$orderCode]);
        $order = $query->fetch();
        if (!$order) {
            throw new OutOfBoundsException('Pesanan tidak ditemukan.');
        }

        if ($action === 'paid') {
            if ($order['payment_status'] === 'Lunas') {
                throw new DomainException('Pesanan sudah ditandai lunas.');
            }
            $pdo->prepare('UPDATE pesanan SET status_pembayaran = ? WHERE id_pesanan = ?')
                ->execute(['Lunas', $order['id']]);
        } elseif ($action === 'reject') {
            if ($order['payment_method'] !== 'tunai') {
                throw new DomainException('Hanya pesanan tunai yang dapat ditolak.');
            }
            if ($order['status'] !== 'Diterima') {
                throw new DomainException('Hanya pesanan tunai yang belum diproses dapat ditolak.');
            }
            $pdo->prepare('UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?')->execute(['Ditolak', $order['id']]);
        } elseif ($action === 'status') {
            $isDelivery = $order['fulfillment_type'] === 'antar';
            $isPaid = $order['payment_status'] === 'Lunas';
            if ($order['payment_method'] === 'qris' && !$isPaid) {
                throw new DomainException('Pesanan QRIS harus dikonfirmasi lunas sebelum diproses.');
            }
            $transitions = [
                'Diterima' => 'Diproses',
                'Diproses' => $isDelivery ? 'Diantarkan' : 'Siap Diambil',
                'Diantarkan' => 'Selesai',
                'Siap Diambil' => 'Selesai',
            ];
            $currentStatus = $order['status'];
            if (!isset($transitions[$currentStatus])) {
                throw new DomainException('Pesanan sudah berada pada status akhir.');
            }
            $nextStatus = $transitions[$currentStatus];
            if ($nextStatus === 'Selesai' && !$isPaid) {
                throw new DomainException('Pembayaran tunai harus ditandai lunas sebelum pesanan diselesaikan.');
            }
            $pdo->prepare('UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?')
                ->execute([$nextStatus, $order['id']]);
        } else {
            throw new InvalidArgumentException('Unknown order action.');
        }

        $pdo->commit();
        $updatedQuery = $pdo->prepare(
            'SELECT id_pesanan AS id, kode_pesanan AS order_code, nama_pelanggan AS customer_name,
                    nomor_whatsapp AS whatsapp, metode_pengiriman AS fulfillment_type,
                    jam_pengambilan AS pickup_time, alamat_pengiriman AS address,
                    garis_lintang AS latitude, garis_bujur AS longitude,
                    jarak_lurus_km AS straight_distance_km, jarak_estimasi_km AS estimated_distance_km,
                    ongkos_kirim AS delivery_fee, metode_bayar_ongkir AS shipping_payment_method,
                    metode_pembayaran AS payment_method, status_pembayaran AS payment_status,
                    status_pesanan AS status, subtotal_pesanan AS subtotal,
                    total_pembayaran AS total, zona_waktu_bisnis AS business_timezone,
                    dibuat_pada AS created_at
             FROM pesanan WHERE id_pesanan = ?'
        );
        $updatedQuery->execute([$order['id']]);
        return goodlife_db_order_from_row($updatedQuery->fetch());
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function goodlife_db_review_order($orderCode, $whatsapp, $rating, $comment)
{
    $pdo = goodlife_db();
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare(
            'SELECT id_pesanan AS id, nama_pelanggan AS customer_name, nomor_whatsapp AS whatsapp,
                    status_pesanan AS status
             FROM pesanan WHERE kode_pesanan = ? FOR UPDATE'
        );
        $query->execute([$orderCode]);
        $order = $query->fetch();
        if (!$order || $order['whatsapp'] !== $whatsapp) {
            throw new OutOfBoundsException('Pesanan tidak ditemukan untuk nomor WhatsApp tersebut.');
        }
        if ($order['status'] !== 'Selesai') {
            throw new DomainException('Ulasan hanya dapat dikirim setelah pesanan selesai.');
        }

        $createdAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $insert = $pdo->prepare(
            'INSERT INTO ulasan
             (id_pesanan, nama_pelanggan_saat_ulasan, nilai_ulasan, komentar, dibuat_pada)
             VALUES (?, ?, ?, ?, ?)'
        );
        $insert->execute([
            $order['id'],
            $order['customer_name'],
            $rating,
            $comment,
            $createdAt->format('Y-m-d H:i:s.u'),
        ]);
        $pdo->commit();

        return [
            'order_id' => $orderCode,
            'nama' => $order['customer_name'],
            'rating' => (int)$rating,
            'komentar' => $comment,
            'tanggal' => $createdAt->setTimezone(new DateTimeZone('Asia/Makassar'))->format('Y-m-d'),
        ];
    } catch (PDOException $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($error->getCode() === '23000') {
            throw new DomainException('Pesanan ini sudah pernah diberi ulasan.');
        }
        throw $error;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function goodlife_db_insert_support_message(array $message)
{
    $createdAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $query = goodlife_db()->prepare(
        'INSERT INTO pesan_bantuan (nama_pengirim, kontak, kategori, isi_pesan, status_penanganan, diterima_pada)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $query->execute([
        $message['nama'],
        $message['kontak'],
        $message['kategori'],
        $message['pesan'],
        'Baru',
        $createdAt->format('Y-m-d H:i:s.u'),
    ]);
}

function goodlife_db_save_media($assetPath, $temporaryFile, $mimeType)
{
    if (!preg_match('#^assets/(?:menu|gallery)/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $assetPath) ||
        !in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new InvalidArgumentException('Invalid media asset path or MIME type.');
    }
    $imageData = file_get_contents($temporaryFile);
    if ($imageData === false || $imageData === '') {
        throw new RuntimeException('Uploaded media data could not be read.');
    }
    if (strlen($imageData) > 5 * 1024 * 1024) {
        throw new LengthException('Uploaded media exceeds the 5 MB limit.');
    }
    $pdo = goodlife_db();
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare(
            'INSERT INTO aset_media (jalur_aset, jenis_mime, ukuran_byte, hash_sha256, dibuat_pada)
             VALUES (?, ?, ?, ?, UTC_TIMESTAMP(6))'
        );
        $query->bindValue(1, $assetPath, PDO::PARAM_STR);
        $query->bindValue(2, $mimeType, PDO::PARAM_STR);
        $query->bindValue(3, strlen($imageData), PDO::PARAM_INT);
        $query->bindValue(4, hash('sha256', $imageData, true), PDO::PARAM_LOB);
        $query->execute();

        $insertChunk = $pdo->prepare(
            'INSERT INTO potongan_aset_media (jalur_aset, nomor_potongan, data_potongan) VALUES (?, ?, ?)'
        );
        $chunkSize = 256 * 1024;
        for ($offset = 0, $length = strlen($imageData), $index = 0; $offset < $length; $offset += $chunkSize, $index++) {
            $insertChunk->bindValue(1, $assetPath, PDO::PARAM_STR);
            $insertChunk->bindValue(2, $index, PDO::PARAM_INT);
            $insertChunk->bindValue(3, substr($imageData, $offset, $chunkSize), PDO::PARAM_LOB);
            $insertChunk->execute();
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function goodlife_db_get_media($assetPath)
{
    if (!is_string($assetPath) ||
        !preg_match('#^assets/(?:menu|gallery)/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $assetPath)) {
        return null;
    }
    $query = goodlife_db()->prepare(
        'SELECT jenis_mime AS mime_type, ukuran_byte AS byte_size, hash_sha256 AS sha256
         FROM aset_media WHERE jalur_aset = ?'
    );
    $query->execute([$assetPath]);
    return $query->fetch() ?: null;
}

function goodlife_db_get_media_chunk($assetPath, $offset, $length)
{
    $chunkSize = 256 * 1024;
    if (!is_string($assetPath) ||
        !preg_match('#^assets/(?:menu|gallery)/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $assetPath) ||
        !is_numeric($offset) || (int)$offset < 0 ||
        (int)$offset % $chunkSize !== 0 ||
        !is_numeric($length) || (int)$length < 1) {
        throw new InvalidArgumentException('Invalid media chunk request.');
    }
    $chunkIndex = intdiv((int)$offset, $chunkSize);
    $query = goodlife_db()->prepare(
        'SELECT SUBSTRING(data_potongan, 1, ?) FROM potongan_aset_media
         WHERE jalur_aset = ? AND nomor_potongan = ?'
    );
    $query->execute([min($chunkSize, (int)$length), $assetPath, $chunkIndex]);
    return $query->fetchColumn();
}

function goodlife_db_delete_media_if_unreferenced($assetPath)
{
    if (!is_string($assetPath) || $assetPath === '') {
        return;
    }
    $pdo = goodlife_db();
    $referenced = $pdo->prepare(
        'SELECT 1 FROM produk WHERE jalur_gambar = ?
         UNION ALL SELECT 1 FROM galeri_toko WHERE jalur_gambar = ? LIMIT 1'
    );
    $referenced->execute([$assetPath, $assetPath]);
    if (!$referenced->fetchColumn()) {
        $pdo->prepare('DELETE FROM aset_media WHERE jalur_aset = ?')->execute([$assetPath]);
    }
}

function goodlife_db_update_cash_acceptance($acceptsCash)
{
    $query = goodlife_db()->prepare('UPDATE pengaturan_toko SET menerima_tunai = ?, diperbarui_pada = UTC_TIMESTAMP() WHERE id_pengaturan_toko = 1');
    $query->execute([$acceptsCash ? 1 : 0]);
    return goodlife_db_store();
}
function goodlife_db_update_store_status($isOpen, $activity)
{
    $query = goodlife_db()->prepare(
        'UPDATE pengaturan_toko SET sedang_buka = ?, keterangan_aktivitas = ?,
         status_diperbarui_pada = UTC_TIMESTAMP(), diperbarui_pada = UTC_TIMESTAMP()
         WHERE id_pengaturan_toko = 1'
    );
    $query->execute([$isOpen ? 1 : 0, $activity]);
    return goodlife_db_store();
}

function goodlife_db_save_store(array $store, array $newImages)
{
    $pdo = goodlife_db();
    $pdo->beginTransaction();
    try {
        $current = $pdo->query(
            'SELECT id_pengaturan_toko FROM pengaturan_toko WHERE id_pengaturan_toko = 1 FOR UPDATE'
        )->fetch();
        if (!$current) {
            throw new RuntimeException('Store settings have not been initialized in the database.');
        }
        $countQuery = $pdo->query('SELECT COUNT(*) FROM galeri_toko WHERE id_pengaturan_toko = 1');
        if ((int)$countQuery->fetchColumn() + count($newImages) > 10) {
            throw new LengthException('Galeri maksimal berisi 10 foto.');
        }
        $update = $pdo->prepare(
            'UPDATE pengaturan_toko SET nama_toko = ?, alamat_toko = ?, nomor_whatsapp = ?,
             jam_buka = ?, jam_tutup = ?, diperbarui_pada = UTC_TIMESTAMP()
             WHERE id_pengaturan_toko = 1'
        );
        $update->execute([
            $store['nama'],
            $store['alamat'],
            $store['wa'],
            $store['jam_buka'],
            $store['jam_tutup'],
        ]);
        $insert = $pdo->prepare(
            'INSERT INTO galeri_toko (id_pengaturan_toko, jalur_gambar, urutan_tampil, dibuat_pada)
             SELECT 1, ?, COALESCE(MAX(urutan_tampil), 0) + 1, UTC_TIMESTAMP()
             FROM galeri_toko WHERE id_pengaturan_toko = 1'
        );
        foreach ($newImages as $image) {
            $insert->execute([$image]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function goodlife_db_delete_gallery_image($image)
{
    $query = goodlife_db()->prepare(
        'DELETE FROM galeri_toko WHERE id_pengaturan_toko = 1 AND jalur_gambar = ?'
    );
    $query->execute([$image]);
    if (!$query->rowCount()) {
        throw new OutOfBoundsException('Foto galeri tidak ditemukan.');
    }
    goodlife_db_delete_media_if_unreferenced($image);
}

function goodlife_db_save_product(array $product)
{
    $pdo = goodlife_db();
    $category = $product['kategori'];
    $type = strpos($category, 'extra_topping_') === 0 ? 'topping' : 'menu';
    $group = $type === 'topping' ? substr($category, strlen('extra_topping_')) : null;
    $categoryId = $type === 'menu' ? $category : null;
    $id = $product['id'] !== '' ? $product['id'] : 'adm' . bin2hex(random_bytes(5));
    $query = $pdo->prepare(
        'SELECT id_produk AS id, favorit AS is_favorite, jalur_gambar AS image_path,
                urutan_tampil AS sort_order FROM produk WHERE id_produk = ?'
    );
    $query->execute([$id]);
    $existing = $query->fetch();
    if ($product['id'] !== '' && !$existing) {
        throw new OutOfBoundsException('Menu yang akan diedit tidak ditemukan.');
    }

    $imagePath = $product['gambar'] ?? ($existing['image_path'] ?? null);
    if ($existing) {
        $update = $pdo->prepare(
            'UPDATE produk SET jenis_produk = ?, id_kategori = ?, kelompok_topping = ?, nama_produk = ?,
             harga = ?, tersedia = ?, jalur_gambar = ?, diperbarui_pada = UTC_TIMESTAMP()
             WHERE id_produk = ?'
        );
        $update->execute([
            $type, $categoryId, $group, $product['nama'], $product['harga'],
            $product['tersedia'] ? 1 : 0, $imagePath, $id,
        ]);
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO produk (id_produk, jenis_produk, id_kategori, kelompok_topping, nama_produk,
             harga, favorit, tersedia, jalur_gambar, urutan_tampil, dibuat_pada, diperbarui_pada)
             SELECT ?, ?, ?, ?, ?, ?, 0, ?, ?, COALESCE(MAX(urutan_tampil), 0) + 1,
             UTC_TIMESTAMP(), UTC_TIMESTAMP() FROM produk'
        );
        $insert->execute([
            $id, $type, $categoryId, $group, $product['nama'], $product['harga'],
            $product['tersedia'] ? 1 : 0, $imagePath,
        ]);
    }
    $oldImage = $existing && isset($product['gambar']) && $existing['image_path'] !== $imagePath
        ? $existing['image_path']
        : null;
    if ($oldImage !== null) {
        goodlife_db_delete_media_if_unreferenced($oldImage);
    }
    return [
        'product' => goodlife_db_product_by_id($id),
    ];
}

function goodlife_db_product_by_id($id)
{
    foreach (goodlife_db_products() as $product) {
        if ($product['id'] === $id) {
            return $product;
        }
    }
    throw new OutOfBoundsException('Menu tidak ditemukan.');
}

function goodlife_db_set_product_availability($id, $available)
{
    $query = goodlife_db()->prepare(
        'UPDATE produk SET tersedia = ?, diperbarui_pada = UTC_TIMESTAMP() WHERE id_produk = ?'
    );
    $query->execute([$available ? 1 : 0, $id]);
    if (!$query->rowCount()) {
        $exists = goodlife_db()->prepare('SELECT 1 FROM produk WHERE id_produk = ?');
        $exists->execute([$id]);
        if (!$exists->fetchColumn()) {
            throw new OutOfBoundsException('Menu tidak ditemukan.');
        }
    }
    return goodlife_db_product_by_id($id);
}

function goodlife_db_delete_product($id)
{
    $pdo = goodlife_db();
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare(
            'SELECT id_produk AS id, jalur_gambar AS image_path FROM produk WHERE id_produk = ? FOR UPDATE'
        );
        $query->execute([$id]);
        $product = $query->fetch();
        if (!$product) {
            throw new OutOfBoundsException('Menu tidak ditemukan.');
        }
        $reference = $pdo->prepare(
            'SELECT 1 FROM detail_pesanan WHERE id_produk = ?
             UNION ALL SELECT 1 FROM topping_detail_pesanan WHERE id_produk = ? LIMIT 1'
        );
        $reference->execute([$id, $id]);
        if ($reference->fetchColumn()) {
            throw new DomainException('Menu sudah ada di riwayat pesanan sehingga tidak dapat dihapus.');
        }
        $pdo->prepare('DELETE FROM produk WHERE id_produk = ?')->execute([$id]);
        $pdo->commit();
        goodlife_db_delete_media_if_unreferenced($product['image_path']);
        return $product;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function goodlife_db_category_name_exists($name, $exceptId = null)
{
    $sql = 'SELECT 1 FROM kategori WHERE nama_kategori = ?' .
        ($exceptId === null ? '' : ' AND id_kategori <> ?') . ' LIMIT 1';
    $query = goodlife_db()->prepare($sql);
    $query->execute($exceptId === null ? [$name] : [$name, $exceptId]);
    return (bool)$query->fetchColumn();
}

function goodlife_db_save_category($id, $name, $group)
{
    $pdo = goodlife_db();
    if (goodlife_db_category_name_exists($name, $id === '' ? null : $id)) {
        throw new DomainException('Nama kategori sudah digunakan.');
    }
    if ($id === '') {
        $id = 'cat_' . bin2hex(random_bytes(6));
        $query = $pdo->prepare(
            'INSERT INTO kategori (id_kategori, nama_kategori, jenis_kelompok, urutan_tampil, dibuat_pada, diperbarui_pada)
             SELECT ?, ?, ?, COALESCE(MAX(urutan_tampil), 0) + 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()
             FROM kategori WHERE jenis_kelompok = ?'
        );
        $query->execute([$id, $name, $group, $group]);
    } else {
        $query = $pdo->prepare(
            'UPDATE kategori SET nama_kategori = ?, jenis_kelompok = ?, diperbarui_pada = UTC_TIMESTAMP()
             WHERE id_kategori = ?'
        );
        $query->execute([$name, $group, $id]);
        if (!$query->rowCount()) {
            $exists = $pdo->prepare('SELECT 1 FROM kategori WHERE id_kategori = ?');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                throw new OutOfBoundsException('Kategori tidak ditemukan.');
            }
        }
    }
    foreach (goodlife_db_categories() as $category) {
        if ($category['id'] === $id) {
            return $category;
        }
    }
    throw new OutOfBoundsException('Kategori tidak ditemukan.');
}

function goodlife_db_delete_category($id)
{
    $pdo = goodlife_db();
    $query = $pdo->prepare('SELECT 1 FROM produk WHERE id_kategori = ? LIMIT 1');
    $query->execute([$id]);
    if ($query->fetchColumn()) {
        throw new DomainException('Kategori masih memiliki menu. Pindahkan atau hapus menu tersebut sebelum menghapus kategori.');
    }
    $delete = $pdo->prepare('DELETE FROM kategori WHERE id_kategori = ?');
    $delete->execute([$id]);
    if (!$delete->rowCount()) {
        throw new OutOfBoundsException('Kategori tidak ditemukan.');
    }
}

function goodlife_db_reorder_categories(array $ids, $group)
{
    $pdo = goodlife_db();
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare(
            'SELECT id_kategori FROM kategori WHERE jenis_kelompok = ?
             ORDER BY urutan_tampil, id_kategori FOR UPDATE'
        );
        $query->execute([$group]);
        $expected = $query->fetchAll(PDO::FETCH_COLUMN);
        $sortedExpected = $expected;
        $sortedProvided = $ids;
        sort($sortedExpected);
        sort($sortedProvided);
        if ($sortedProvided !== $sortedExpected) {
            throw new DomainException('Daftar kategori berubah. Muat ulang halaman lalu coba lagi.');
        }
        $update = $pdo->prepare(
            'UPDATE kategori SET urutan_tampil = ?, diperbarui_pada = UTC_TIMESTAMP()
             WHERE id_kategori = ?'
        );
        foreach ($ids as $index => $id) {
            $update->execute([$index + 1, $id]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function goodlife_db_save_push_subscription($endpoint, array $keys)
{
    $pdo = goodlife_db();
    $hash = hash('sha256', $endpoint, true);
    $query = $pdo->prepare(
        'INSERT INTO langganan_notifikasi
         (alamat_endpoint, hash_endpoint, kunci_p256dh, kunci_otentikasi, dibuat_pada, diperbarui_pada)
         VALUES (?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE alamat_endpoint = VALUES(alamat_endpoint),
         kunci_p256dh = VALUES(kunci_p256dh), kunci_otentikasi = VALUES(kunci_otentikasi),
         diperbarui_pada = UTC_TIMESTAMP()'
    );
    $query->execute([$endpoint, $hash, $keys['p256dh'], $keys['auth']]);
}

function goodlife_db_remove_push_subscription($endpoint)
{
    $query = goodlife_db()->prepare('DELETE FROM langganan_notifikasi WHERE hash_endpoint = ?');
    $query->execute([hash('sha256', $endpoint, true)]);
}

function goodlife_db_remove_push_subscriptions(array $endpoints)
{
    if (!$endpoints) {
        return;
    }
    $pdo = goodlife_db();
    $delete = $pdo->prepare('DELETE FROM langganan_notifikasi WHERE hash_endpoint = ?');
    foreach ($endpoints as $endpoint) {
        $delete->execute([hash('sha256', $endpoint, true)]);
    }
}

function goodlife_db_admin_authenticate($username, $password, $fallbackUsername, $fallbackHash)
{
    $pdo = goodlife_db();
    $query = $pdo->prepare(
        'SELECT id_admin AS id, nama_pengguna AS username, hash_kata_sandi AS password_hash
         FROM admin WHERE nama_pengguna = ? AND aktif = 1'
    );
    $query->execute([$username]);
    $user = $query->fetch();
    if ($user) {
        return hash_equals($user['username'], $username) && password_verify($password, $user['password_hash']);
    }
    if (!is_string($fallbackHash) || $fallbackHash === '' ||
        !password_get_info($fallbackHash)['algo']) {
        return null;
    }
    if ($username !== $fallbackUsername || !password_verify($password, $fallbackHash)) {
        return false;
    }
    $insert = $pdo->prepare(
        'INSERT INTO admin (nama_pengguna, hash_kata_sandi, aktif, dibuat_pada, diperbarui_pada)
         VALUES (?, ?, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
    );
    try {
        $insert->execute([$fallbackUsername, $fallbackHash]);
    } catch (PDOException $error) {
        if ($error->getCode() !== '23000') {
            throw $error;
        }
        $query->execute([$username]);
        $user = $query->fetch();
        return $user && hash_equals($user['username'], $username) &&
            password_verify($password, $user['password_hash']);
    }
    return true;
}
