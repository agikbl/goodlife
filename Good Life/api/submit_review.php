<?php
header('Content-Type: application/json; charset=utf-8');

function review_response($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    review_response(405, ['ok' => false, 'error' => 'Metode permintaan tidak diizinkan.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    review_response(400, ['ok' => false, 'error' => 'Data ulasan tidak valid.']);
}

$orderId = is_scalar($input['order_id'] ?? null) ? strtoupper(trim((string)$input['order_id'])) : '';
$whatsapp = is_scalar($input['whatsapp'] ?? null)
    ? preg_replace('/\D+/', '', (string)$input['whatsapp'])
    : '';
$rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT);
$comment = is_scalar($input['komentar'] ?? null) ? trim((string)$input['komentar']) : '';

if (strpos($whatsapp, '0') === 0) {
    $whatsapp = '62' . substr($whatsapp, 1);
} elseif (strpos($whatsapp, '8') === 0) {
    $whatsapp = '62' . $whatsapp;
}
if (!preg_match('/^ORD[0-9]{9,}$/', $orderId) ||
    !preg_match('/^628[0-9]{7,11}$/', $whatsapp) ||
    $rating === false || $rating < 1 || $rating > 5 ||
    $comment === '' || strlen($comment) > 2000) {
    review_response(400, ['ok' => false, 'error' => 'ID pesanan, nomor WhatsApp, rating, atau komentar tidak valid.']);
}

$ordersContent = file_get_contents(__DIR__ . '/../data/orders.json');
$orders = $ordersContent === false ? null : json_decode($ordersContent, true);
if (!is_array($orders)) {
    review_response(500, ['ok' => false, 'error' => 'Data pesanan tidak dapat dibaca.']);
}

$eligibleOrder = null;
foreach ($orders as $order) {
    if (!is_array($order) || strtoupper((string)($order['id'] ?? '')) !== $orderId) {
        continue;
    }
    $storedWhatsApp = preg_replace('/\D+/', '', (string)($order['whatsapp'] ?? ''));
    if (strpos($storedWhatsApp, '0') === 0) {
        $storedWhatsApp = '62' . substr($storedWhatsApp, 1);
    } elseif (strpos($storedWhatsApp, '8') === 0) {
        $storedWhatsApp = '62' . $storedWhatsApp;
    }
    if ($storedWhatsApp !== $whatsapp) {
        review_response(404, ['ok' => false, 'error' => 'Pesanan tidak ditemukan untuk nomor WhatsApp tersebut.']);
    }
    if (($order['status'] ?? '') !== 'Selesai') {
        review_response(409, ['ok' => false, 'error' => 'Ulasan hanya dapat dikirim setelah pesanan selesai.']);
    }
    $eligibleOrder = $order;
    break;
}
if ($eligibleOrder === null) {
    review_response(404, ['ok' => false, 'error' => 'Pesanan tidak ditemukan.']);
}

$file = fopen(__DIR__ . '/../data/reviews.json', 'c+');
if ($file === false) {
    review_response(500, ['ok' => false, 'error' => 'Ulasan gagal disimpan. Coba lagi.']);
}
if (!flock($file, LOCK_EX)) {
    fclose($file);
    review_response(500, ['ok' => false, 'error' => 'Data ulasan gagal dikunci. Coba lagi.']);
}

$content = stream_get_contents($file);
$reviews = $content === '' ? [] : json_decode($content, true);
if (!is_array($reviews)) {
    flock($file, LOCK_UN);
    fclose($file);
    review_response(500, ['ok' => false, 'error' => 'Data ulasan tidak valid.']);
}
foreach ($reviews as $review) {
    if (is_array($review) && strtoupper((string)($review['order_id'] ?? '')) === $orderId) {
        flock($file, LOCK_UN);
        fclose($file);
        review_response(409, ['ok' => false, 'already_reviewed' => true, 'error' => 'Pesanan ini sudah pernah diberi ulasan.']);
    }
}

$newReview = [
    'order_id' => $orderId,
    'nama' => (string)($eligibleOrder['nama_pemesan'] ?? 'Pelanggan Good Life'),
    'rating' => $rating,
    'komentar' => $comment,
    'tanggal' => date('Y-m-d'),
];
$reviews[] = $newReview;
$encoded = json_encode($reviews, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$saved = $encoded !== false && rewind($file) &&
    fwrite($file, $encoded . PHP_EOL) === strlen($encoded . PHP_EOL) &&
    ftruncate($file, strlen($encoded . PHP_EOL)) && fflush($file);
flock($file, LOCK_UN);
fclose($file);

if (!$saved) {
    review_response(500, ['ok' => false, 'error' => 'Ulasan gagal disimpan. Coba lagi.']);
}

review_response(200, ['ok' => true, 'review' => $newReview]);
