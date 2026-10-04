<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database/repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'error' => 'Metode permintaan tidak diizinkan.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$rawLookup = is_array($input) ? ($input['lookup'] ?? '') : '';
$lookup = is_scalar($rawLookup) ? trim((string)$rawLookup) : '';
if ($lookup === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Masukkan nomor WhatsApp atau ID pesanan.']);
    exit;
}

try {
    if (preg_match('/^ORD[0-9]{9,}$/i', $lookup)) {
        $matches = goodlife_db_orders(['order_code' => strtoupper($lookup)]);
    } else {
        $whatsapp = preg_replace('/\D+/', '', $lookup);
        if (strpos($whatsapp, '0') === 0) {
            $whatsapp = '62' . substr($whatsapp, 1);
        } elseif (strpos($whatsapp, '8') === 0) {
            $whatsapp = '62' . $whatsapp;
        }
        if (!preg_match('/^628[0-9]{7,11}$/', $whatsapp)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Masukkan nomor WhatsApp Indonesia yang valid atau ID pesanan.']);
            exit;
        }
        $matches = goodlife_db_orders(['whatsapp' => $whatsapp]);
    }

    if (!$matches) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Pesanan tidak ditemukan. Periksa nomor WhatsApp atau ID pesanan.']);
        exit;
    }

    $reviewsByOrderId = [];
    foreach (goodlife_db_reviews() as $review) {
        if (!empty($review['order_id'])) {
            $reviewsByOrderId[$review['order_id']] = true;
        }
    }

    $result = [];
    foreach ($matches as $order) {
        $items = [];
        foreach ($order['items'] as $item) {
            $items[] = [
                'nama' => $item['nama'],
                'qty' => (int)$item['qty'],
                'notes' => $item['notes'],
                'toppings' => array_map(static function ($topping) {
                    return ['nama' => $topping['nama']];
                }, $item['toppings']),
            ];
        }

        $isReviewed = isset($reviewsByOrderId[$order['id']]);
        $result[] = [
            'id' => $order['id'],
            'nama_pemesan' => $order['nama_pemesan'],
            'items' => $items,
            'total' => (int)$order['total'],
            'status' => $order['status'],
            'status_pembayaran' => $order['status_pembayaran'],
            'metode_bayar' => $order['metode_bayar'],
            'pengiriman' => $order['pengiriman'],
            'waktu_pengambilan' => $order['waktu_pengambilan'],
            'tanggal' => $order['tanggal'],
            'ulasan_tersedia' => $order['status'] === 'Selesai' && !$isReviewed,
            'ulasan_dikirim' => $isReviewed,
        ];
    }

    echo json_encode(['ok' => true, 'orders' => $result], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('Customer order lookup failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Pesanan gagal diperiksa. Silakan coba lagi nanti.']);
}
