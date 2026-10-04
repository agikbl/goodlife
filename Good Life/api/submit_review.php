<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database/repository.php';

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

try {
    $review = goodlife_db_review_order($orderId, $whatsapp, $rating, $comment);
    review_response(200, ['ok' => true, 'review' => $review]);
} catch (OutOfBoundsException $error) {
    review_response(404, ['ok' => false, 'error' => $error->getMessage()]);
} catch (DomainException $error) {
    review_response(409, ['ok' => false, 'already_reviewed' => true, 'error' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('Customer review submission failed: ' . $error->getMessage());
    review_response(500, ['ok' => false, 'error' => 'Ulasan gagal disimpan. Coba lagi.']);
}
