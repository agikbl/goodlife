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
    $store = $pdo->query('SELECT * FROM store_settings WHERE id = 1')->fetch();
    if (!$store) {
        throw new RuntimeException('Store settings have not been initialized in the database.');
    }

    $galleryQuery = $pdo->prepare(
        'SELECT image_path FROM store_gallery WHERE store_settings_id = 1 ORDER BY sort_order, id'
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
        'status_updated_at' => $store['status_updated_at'],
    ];
}

function goodlife_db_categories()
{
    $rows = goodlife_db()->query(
        'SELECT id, name, group_type FROM categories ORDER BY sort_order, id'
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
        'SELECT id, product_type, category_id, topping_group, name, price, is_favorite, is_available, image_path
         FROM products
         ORDER BY sort_order, id'
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
        'SELECT id, product_id, product_name_snapshot, base_unit_price, unit_price, quantity, notes
         FROM order_items WHERE order_id = ? ORDER BY id'
    );
    $itemsQuery->execute([$row['id']]);
    $items = [];
    foreach ($itemsQuery->fetchAll() as $item) {
        $toppingsQuery = $pdo->prepare(
            'SELECT product_id, topping_name_snapshot, unit_price_snapshot
             FROM order_item_toppings WHERE order_item_id = ? ORDER BY id'
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
        $where[] = 'whatsapp = ?';
        $params[] = $filters['whatsapp'];
    }
    if (isset($filters['order_code'])) {
        $where[] = 'order_code = ?';
        $params[] = $filters['order_code'];
    }
    if (isset($filters['created_from'])) {
        $where[] = 'created_at >= ?';
        $params[] = $filters['created_from'];
    }
    if (isset($filters['created_before'])) {
        $where[] = 'created_at < ?';
        $params[] = $filters['created_before'];
    }

    $sql = 'SELECT * FROM orders' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC, id DESC';
    $query = $pdo->prepare($sql);
    $query->execute($params);
    return array_map('goodlife_db_order_from_row', $query->fetchAll());
}

function goodlife_db_reviews()
{
    $rows = goodlife_db()->query(
        'SELECT r.customer_name_snapshot, r.rating, r.comment, r.created_at, o.order_code
         FROM reviews r JOIN orders o ON o.id = r.order_id
         ORDER BY r.created_at DESC, r.id DESC'
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
        'SELECT name, contact, category, message, status, created_at
         FROM support_messages ORDER BY created_at DESC, id DESC'
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
        'SELECT endpoint, p256dh_key, auth_key FROM push_subscriptions ORDER BY id'
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
            'INSERT INTO orders (
                order_code, customer_name, whatsapp, fulfillment_type, pickup_time, address,
                latitude, longitude, straight_distance_km, estimated_distance_km, delivery_fee,
                shipping_payment_method, payment_method, payment_status, status, subtotal, total,
                business_timezone, created_at
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
        $updateCode = $pdo->prepare('UPDATE orders SET order_code = ? WHERE id = ?');
        $updateCode->execute([$orderCode, $databaseOrderId]);

        $insertItem = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name_snapshot, base_unit_price, unit_price, quantity, notes, line_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertTopping = $pdo->prepare(
            'INSERT INTO order_item_toppings (order_item_id, product_id, topping_name_snapshot, unit_price_snapshot, quantity_per_item)
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
        $query = $pdo->prepare('SELECT * FROM orders WHERE order_code = ? FOR UPDATE');
        $query->execute([$orderCode]);
        $order = $query->fetch();
        if (!$order) {
            throw new OutOfBoundsException('Pesanan tidak ditemukan.');
        }

        if ($action === 'paid') {
            if ($order['payment_status'] === 'Lunas') {
                throw new DomainException('Pesanan sudah ditandai lunas.');
            }
            $pdo->prepare('UPDATE orders SET payment_status = ? WHERE id = ?')
                ->execute(['Lunas', $order['id']]);
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
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')
                ->execute([$nextStatus, $order['id']]);
        } else {
            throw new InvalidArgumentException('Unknown order action.');
        }

        $pdo->commit();
        $updatedQuery = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
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
        $query = $pdo->prepare('SELECT id, customer_name, whatsapp, status FROM orders WHERE order_code = ? FOR UPDATE');
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
            'INSERT INTO reviews (order_id, customer_name_snapshot, rating, comment, created_at) VALUES (?, ?, ?, ?, ?)'
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
        'INSERT INTO support_messages (name, contact, category, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?)'
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
            'INSERT INTO media_assets (asset_path, mime_type, byte_size, sha256, created_at)
             VALUES (?, ?, ?, ?, UTC_TIMESTAMP(6))'
        );
        $query->bindValue(1, $assetPath, PDO::PARAM_STR);
        $query->bindValue(2, $mimeType, PDO::PARAM_STR);
        $query->bindValue(3, strlen($imageData), PDO::PARAM_INT);
        $query->bindValue(4, hash('sha256', $imageData, true), PDO::PARAM_LOB);
        $query->execute();

        $insertChunk = $pdo->prepare(
            'INSERT INTO media_asset_chunks (asset_path, chunk_index, chunk_data) VALUES (?, ?, ?)'
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
        'SELECT mime_type, byte_size, sha256 FROM media_assets WHERE asset_path = ?'
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
        'SELECT SUBSTRING(chunk_data, 1, ?) FROM media_asset_chunks WHERE asset_path = ? AND chunk_index = ?'
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
        'SELECT 1 FROM products WHERE image_path = ?
         UNION ALL SELECT 1 FROM store_gallery WHERE image_path = ? LIMIT 1'
    );
    $referenced->execute([$assetPath, $assetPath]);
    if (!$referenced->fetchColumn()) {
        $pdo->prepare('DELETE FROM media_assets WHERE asset_path = ?')->execute([$assetPath]);
    }
}

function goodlife_db_update_store_status($isOpen, $activity)
{
    $query = goodlife_db()->prepare(
        'UPDATE store_settings SET is_open = ?, activity = ?, status_updated_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = 1'
    );
    $query->execute([$isOpen ? 1 : 0, $activity]);
    return goodlife_db_store();
}

function goodlife_db_save_store(array $store, array $newImages)
{
    $pdo = goodlife_db();
    $pdo->beginTransaction();
    try {
        $current = $pdo->query('SELECT id FROM store_settings WHERE id = 1 FOR UPDATE')->fetch();
        if (!$current) {
            throw new RuntimeException('Store settings have not been initialized in the database.');
        }
        $countQuery = $pdo->query('SELECT COUNT(*) FROM store_gallery WHERE store_settings_id = 1');
        if ((int)$countQuery->fetchColumn() + count($newImages) > 10) {
            throw new LengthException('Galeri maksimal berisi 10 foto.');
        }
        $update = $pdo->prepare(
            'UPDATE store_settings SET name = ?, address = ?, whatsapp = ?, opening_time = ?, closing_time = ?, updated_at = UTC_TIMESTAMP() WHERE id = 1'
        );
        $update->execute([
            $store['nama'],
            $store['alamat'],
            $store['wa'],
            $store['jam_buka'],
            $store['jam_tutup'],
        ]);
        $insert = $pdo->prepare(
            'INSERT INTO store_gallery (store_settings_id, image_path, sort_order, created_at)
             SELECT 1, ?, COALESCE(MAX(sort_order), 0) + 1, UTC_TIMESTAMP() FROM store_gallery WHERE store_settings_id = 1'
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
        'DELETE FROM store_gallery WHERE store_settings_id = 1 AND image_path = ?'
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
    $query = $pdo->prepare('SELECT id, is_favorite, image_path, sort_order FROM products WHERE id = ?');
    $query->execute([$id]);
    $existing = $query->fetch();
    if ($product['id'] !== '' && !$existing) {
        throw new OutOfBoundsException('Menu yang akan diedit tidak ditemukan.');
    }

    $imagePath = $product['gambar'] ?? ($existing['image_path'] ?? null);
    if ($existing) {
        $update = $pdo->prepare(
            'UPDATE products SET product_type = ?, category_id = ?, topping_group = ?, name = ?, price = ?, is_available = ?, image_path = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?'
        );
        $update->execute([
            $type, $categoryId, $group, $product['nama'], $product['harga'],
            $product['tersedia'] ? 1 : 0, $imagePath, $id,
        ]);
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO products (id, product_type, category_id, topping_group, name, price, is_favorite, is_available, image_path, sort_order, created_at, updated_at)
             SELECT ?, ?, ?, ?, ?, ?, 0, ?, ?, COALESCE(MAX(sort_order), 0) + 1, UTC_TIMESTAMP(), UTC_TIMESTAMP() FROM products'
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
        'UPDATE products SET is_available = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?'
    );
    $query->execute([$available ? 1 : 0, $id]);
    if (!$query->rowCount()) {
        $exists = goodlife_db()->prepare('SELECT 1 FROM products WHERE id = ?');
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
        $query = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
        $query->execute([$id]);
        $product = $query->fetch();
        if (!$product) {
            throw new OutOfBoundsException('Menu tidak ditemukan.');
        }
        $reference = $pdo->prepare(
            'SELECT 1 FROM order_items WHERE product_id = ?
             UNION ALL SELECT 1 FROM order_item_toppings WHERE product_id = ? LIMIT 1'
        );
        $reference->execute([$id, $id]);
        if ($reference->fetchColumn()) {
            throw new DomainException('Menu sudah ada di riwayat pesanan sehingga tidak dapat dihapus.');
        }
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
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
    $sql = 'SELECT 1 FROM categories WHERE name = ?' . ($exceptId === null ? '' : ' AND id <> ?') . ' LIMIT 1';
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
            'INSERT INTO categories (id, name, group_type, sort_order, created_at, updated_at)
             SELECT ?, ?, ?, COALESCE(MAX(sort_order), 0) + 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()
             FROM categories WHERE group_type = ?'
        );
        $query->execute([$id, $name, $group, $group]);
    } else {
        $query = $pdo->prepare(
            'UPDATE categories SET name = ?, group_type = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?'
        );
        $query->execute([$name, $group, $id]);
        if (!$query->rowCount()) {
            $exists = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
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
    $query = $pdo->prepare('SELECT 1 FROM products WHERE category_id = ? LIMIT 1');
    $query->execute([$id]);
    if ($query->fetchColumn()) {
        throw new DomainException('Kategori masih memiliki menu. Pindahkan atau hapus menu tersebut sebelum menghapus kategori.');
    }
    $delete = $pdo->prepare('DELETE FROM categories WHERE id = ?');
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
        $query = $pdo->prepare('SELECT id FROM categories WHERE group_type = ? ORDER BY sort_order, id FOR UPDATE');
        $query->execute([$group]);
        $expected = $query->fetchAll(PDO::FETCH_COLUMN);
        $sortedExpected = $expected;
        $sortedProvided = $ids;
        sort($sortedExpected);
        sort($sortedProvided);
        if ($sortedProvided !== $sortedExpected) {
            throw new DomainException('Daftar kategori berubah. Muat ulang halaman lalu coba lagi.');
        }
        $update = $pdo->prepare('UPDATE categories SET sort_order = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?');
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
        'INSERT INTO push_subscriptions (endpoint, endpoint_hash, p256dh_key, auth_key, created_at, updated_at)
         VALUES (?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE endpoint = VALUES(endpoint), p256dh_key = VALUES(p256dh_key),
         auth_key = VALUES(auth_key), updated_at = UTC_TIMESTAMP()'
    );
    $query->execute([$endpoint, $hash, $keys['p256dh'], $keys['auth']]);
}

function goodlife_db_remove_push_subscription($endpoint)
{
    $query = goodlife_db()->prepare('DELETE FROM push_subscriptions WHERE endpoint_hash = ?');
    $query->execute([hash('sha256', $endpoint, true)]);
}

function goodlife_db_remove_push_subscriptions(array $endpoints)
{
    if (!$endpoints) {
        return;
    }
    $pdo = goodlife_db();
    $delete = $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint_hash = ?');
    foreach ($endpoints as $endpoint) {
        $delete->execute([hash('sha256', $endpoint, true)]);
    }
}

function goodlife_db_admin_authenticate($username, $password, $fallbackUsername, $fallbackHash)
{
    $pdo = goodlife_db();
    $query = $pdo->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ? AND is_active = 1');
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
        'INSERT INTO admin_users (username, password_hash, is_active, created_at, updated_at)
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
