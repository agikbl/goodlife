<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/repository.php';

$pdo = goodlife_db();
$suffix = bin2hex(random_bytes(5));
$category = null;
$productId = null;
$orderCode = null;
$supportContact = 'integration-' . $suffix . '@example.invalid';
$supportMessage = 'Good Life integration test ' . $suffix;
$temporaryImage = null;
$mediaPath = 'assets/menu/' . bin2hex(random_bytes(16)) . '.jpg';
$assertions = 0;

$assert = static function ($condition, $message) use (&$assertions) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $assertions++;
};

try {
    $category = goodlife_db_save_category('', 'Integration ' . $suffix, 'makanan');
    $savedProduct = goodlife_db_save_product([
        'id' => '',
        'nama' => 'Integration product ' . $suffix,
        'kategori' => $category['id'],
        'harga' => 1234,
        'tersedia' => true,
    ]);
    $productId = $savedProduct['product']['id'];
    $assert($savedProduct['product']['harga'] === 1234, 'New product did not persist.');

    $updatedProduct = goodlife_db_set_product_availability($productId, false);
    $assert($updatedProduct['tersedia'] === false, 'Product availability update did not persist.');
    goodlife_db_set_product_availability($productId, true);

    $sourceImage = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
        'menu' . DIRECTORY_SEPARATOR . '4889408158e23784b54ac81696f30a78.jpg';
    $temporaryImage = tempnam(sys_get_temp_dir(), 'goodlife-integration-');
    if ($temporaryImage === false || !copy($sourceImage, $temporaryImage)) {
        throw new RuntimeException('Could not stage the existing image for the BLOB test.');
    }
    goodlife_db_save_media($mediaPath, $temporaryImage, 'image/jpeg');
    $storedMedia = goodlife_db_get_media($mediaPath);
    $assert($storedMedia !== null, 'Media BLOB could not be read back.');
    $assert((int)$storedMedia['byte_size'] === filesize($sourceImage), 'Media BLOB byte count differs.');
    $mediaHash = hash_init('sha256');
    for ($offset = 0, $size = (int)$storedMedia['byte_size']; $offset < $size; $offset += 256 * 1024) {
        $chunk = goodlife_db_get_media_chunk($mediaPath, $offset, min(256 * 1024, $size - $offset));
        if ($chunk === false) {
            throw new RuntimeException('Could not read the stored media BLOB in chunks.');
        }
        hash_update($mediaHash, $chunk);
    }
    $assert(hash_equals(hash_final($mediaHash, true), $storedMedia['sha256']), 'Media BLOB checksum differs.');

    $order = goodlife_db_create_order([
        'nama_pemesan' => 'Integration Test',
        'whatsapp' => '6281234567890',
        'pengiriman' => 'ambil',
        'waktu_pengambilan' => '19:00',
        'alamat' => '',
        'latitude' => null,
        'longitude' => null,
        'jarak_lurus' => null,
        'jarak_estimasi' => null,
        'ongkir' => 0,
        'bayar_ongkir' => null,
        'metode_bayar' => 'qris',
        'subtotal' => 1234,
        'total' => 1234,
        'items' => [[
            'id' => $productId,
            'nama' => 'Integration product ' . $suffix,
            'harga' => 1234,
            'base_harga' => 1234,
            'qty' => 1,
            'notes' => 'temporary integration test',
            'toppings' => [],
        ]],
    ]);
    $orderCode = $order['id'];
    $savedOrders = goodlife_db_orders(['order_code' => $orderCode]);
    $assert(count($savedOrders) === 1 && $savedOrders[0]['total'] === 1234, 'Order lookup did not return the created order.');
    $assert(count($savedOrders[0]['items']) === 1, 'Order item snapshot was not persisted.');

    $order = goodlife_db_advance_order($orderCode, 'paid');
    $assert($order['status_pembayaran'] === 'Lunas', 'Order payment status update failed.');
    $order = goodlife_db_advance_order($orderCode, 'status');
    $assert($order['status'] === 'Diproses', 'First order status transition failed.');
    $order = goodlife_db_advance_order($orderCode, 'status');
    $assert($order['status'] === 'Siap Diambil', 'Pickup order status transition failed.');
    $order = goodlife_db_advance_order($orderCode, 'status');
    $assert($order['status'] === 'Selesai', 'Final order status transition failed.');

    $review = goodlife_db_review_order($orderCode, '6281234567890', 5, 'Temporary integration review');
    $assert($review['rating'] === 5, 'Review was not persisted.');
    $duplicateReviewRejected = false;
    try {
        goodlife_db_review_order($orderCode, '6281234567890', 5, 'Duplicate integration review');
    } catch (DomainException $error) {
        $duplicateReviewRejected = true;
    }
    $assert($duplicateReviewRejected, 'A second review for the same order was not rejected.');

    goodlife_db_insert_support_message([
        'nama' => 'Integration Test',
        'kontak' => $supportContact,
        'kategori' => 'Saran',
        'pesan' => $supportMessage,
    ]);
    $supportQuery = $pdo->prepare(
        'SELECT COUNT(*) FROM pesan_bantuan WHERE kontak = ? AND isi_pesan = ?'
    );
    $supportQuery->execute([$supportContact, $supportMessage]);
    $assert((int)$supportQuery->fetchColumn() === 1, 'Support message did not persist.');

    echo 'Integration checks passed: ' . $assertions . PHP_EOL;
} finally {
    if ($orderCode !== null) {
        $orderIdQuery = $pdo->prepare('SELECT id_pesanan FROM pesanan WHERE kode_pesanan = ?');
        $orderIdQuery->execute([$orderCode]);
        $databaseOrderId = $orderIdQuery->fetchColumn();
        if ($databaseOrderId) {
            $pdo->prepare('DELETE FROM ulasan WHERE id_pesanan = ?')->execute([$databaseOrderId]);
            $pdo->prepare('DELETE FROM pesanan WHERE id_pesanan = ?')->execute([$databaseOrderId]);
        }
    }
    $pdo->prepare('DELETE FROM pesan_bantuan WHERE kontak = ? AND isi_pesan = ?')
        ->execute([$supportContact, $supportMessage]);
    if ($productId !== null) {
        $pdo->prepare('DELETE FROM produk WHERE id_produk = ?')->execute([$productId]);
    }
    if ($category !== null) {
        $pdo->prepare('DELETE FROM kategori WHERE id_kategori = ?')->execute([$category['id']]);
    }
    goodlife_db_delete_media_if_unreferenced($mediaPath);
    if (is_string($temporaryImage) && is_file($temporaryImage)) {
        unlink($temporaryImage);
    }
}
