<?php
header('Content-Type: application/json');

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

$dataPath = __DIR__ . '/../data/orders.json';
$ordersContent = file_get_contents($dataPath);
$orders = $ordersContent === false ? null : json_decode($ordersContent, true);
if (!is_array($orders)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Data pesanan tidak dapat dibaca.']);
    exit;
}

$matches = [];
if (preg_match('/^ORD[0-9]{9}$/i', $lookup)) {
    foreach ($orders as $order) {
        if (is_array($order) && strtoupper((string)($order['id'] ?? '')) === strtoupper($lookup)) {
            $matches[] = $order;
            break;
        }
    }
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

    foreach ($orders as $order) {
        if (!is_array($order)) {
            continue;
        }
        $storedWhatsApp = preg_replace('/\D+/', '', (string)($order['whatsapp'] ?? ''));
        if (strpos($storedWhatsApp, '0') === 0) {
            $storedWhatsApp = '62' . substr($storedWhatsApp, 1);
        } elseif (strpos($storedWhatsApp, '8') === 0) {
            $storedWhatsApp = '62' . $storedWhatsApp;
        }
        if ($storedWhatsApp === $whatsapp) {
            $matches[] = $order;
        }
    }
}

if (!$matches) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Pesanan tidak ditemukan. Periksa nomor WhatsApp atau ID pesanan.']);
    exit;
}

$result = [];
$reviewsContent = file_get_contents(__DIR__ . '/../data/reviews.json');
$reviews = $reviewsContent === false ? null : json_decode($reviewsContent, true);
if (!is_array($reviews)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Data ulasan tidak dapat dibaca.']);
    exit;
}
$reviewedOrderIds = [];
foreach ($reviews as $review) {
    if (is_array($review) && !empty($review['order_id'])) {
        $reviewedOrderIds[(string)$review['order_id']] = true;
    }
}
foreach ($matches as $order) {
    $items = [];
    $orderItems = is_array($order['items'] ?? null) ? $order['items'] : [];
    foreach ($orderItems as $item) {
        if (!is_array($item)) {
            continue;
        }
        $itemToppings = is_array($item['toppings'] ?? null) ? $item['toppings'] : [];
        $items[] = [
            'nama' => htmlspecialchars_decode((string)($item['nama'] ?? 'Menu'), ENT_QUOTES),
            'qty' => (int)($item['qty'] ?? 0),
            'notes' => htmlspecialchars_decode((string)($item['notes'] ?? ''), ENT_QUOTES),
            'toppings' => array_values(array_map(function ($topping) {
                return ['nama' => htmlspecialchars_decode((string)($topping['nama'] ?? ''), ENT_QUOTES)];
            }, array_filter($itemToppings, 'is_array'))),
        ];
    }
    $orderId = (string)($order['id'] ?? '');
    $isReviewed = isset($reviewedOrderIds[$orderId]);
    $result[] = [
        'id' => $orderId,
        'nama_pemesan' => (string)($order['nama_pemesan'] ?? ''),
        'items' => $items,
        'total' => (int)($order['total'] ?? 0),
        'status' => (string)($order['status'] ?? 'Diterima'),
        'status_pembayaran' => (string)($order['status_pembayaran'] ?? 'Belum dibayar'),
        'metode_bayar' => (string)($order['metode_bayar'] ?? ''),
        'pengiriman' => (string)($order['pengiriman'] ?? ''),
        'waktu_pengambilan' => (string)($order['waktu_pengambilan'] ?? ''),
        'tanggal' => (string)($order['tanggal'] ?? ''),
        'ulasan_tersedia' => ($order['status'] ?? '') === 'Selesai' && !$isReviewed,
        'ulasan_dikirim' => $isReviewed,
    ];
}

echo json_encode(['ok' => true, 'orders' => $result], JSON_UNESCAPED_UNICODE);
