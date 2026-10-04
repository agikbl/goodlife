<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'error' => 'Metode permintaan tidak diizinkan.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$items = is_array($input) ? ($input['items'] ?? null) : null;
if (!is_array($items) || !$items) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Keranjang pesanan masih kosong.']);
    exit;
}

try {
    $store = goodlife_db_store();
    $menu = goodlife_db_products();
    $categoryRecords = goodlife_db_categories();
} catch (Throwable $error) {
    error_log('Checkout database read failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Data toko atau menu tidak dapat dibaca. Silakan coba lagi nanti.']);
    exit;
}
if (array_key_exists('is_open', $store) && $store['is_open'] !== true) {
    http_response_code(423);
    $activity = is_string($store['activity'] ?? null) ? $store['activity'] : 'Tutup sementara';
    echo json_encode(['ok' => false, 'error' => 'Toko sedang tutup (' . $activity . ') dan belum menerima pesanan baru.']);
    exit;
}

$menuById = [];
foreach ($menu as $menuItem) {
    if (isset($menuItem['id'])) {
        $menuById[$menuItem['id']] = $menuItem;
    }
}

$categoryGroups = [];
foreach ($categoryRecords as $categoryRecord) {
    if (is_array($categoryRecord) && isset($categoryRecord['id']) &&
        in_array($categoryRecord['kelompok'] ?? '', ['makanan', 'minuman'], true)) {
        $categoryGroups[$categoryRecord['id']] = $categoryRecord['kelompok'];
    }
}
$draftItems = [];
$subtotal = 0;

foreach ($items as $item) {
    if (!is_array($item)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Detail item pesanan tidak valid.']);
        exit;
    }

    $menuId = is_scalar($item['id'] ?? null) ? (string)$item['id'] : '';
    $menuItem = $menuById[$menuId] ?? null;
    $quantity = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT);
    $category = $menuItem['kategori'] ?? '';
    $isFood = ($categoryGroups[$category] ?? '') === 'makanan';
    $isDrink = ($categoryGroups[$category] ?? '') === 'minuman';
    if (!$menuItem || (array_key_exists('tersedia', $menuItem) && !$menuItem['tersedia']) ||
        (!$isFood && !$isDrink) || $quantity === false || $quantity < 1 || $quantity > 99) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Menu atau jumlah item tidak valid.']);
        exit;
    }

    $toppingIds = $item['toppings'] ?? [];
    if (!is_array($toppingIds)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Pilihan topping tidak valid.']);
        exit;
    }

    $allowedToppingCategory = $isDrink ? 'extra_topping_minuman' : 'extra_topping_makanan';
    $selectedToppings = [];
    $seenToppings = [];
    $toppingTotal = 0;
    foreach ($toppingIds as $topping) {
        $toppingId = is_array($topping) && is_scalar($topping['id'] ?? null)
            ? (string)$topping['id']
            : (is_scalar($topping) ? (string)$topping : '');
        $toppingItem = $menuById[$toppingId] ?? null;
        if (!$toppingItem || (array_key_exists('tersedia', $toppingItem) && !$toppingItem['tersedia']) ||
            ($toppingItem['kategori'] ?? '') !== $allowedToppingCategory || isset($seenToppings[$toppingId])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Pilihan topping tidak cocok dengan menu.']);
            exit;
        }
        $seenToppings[$toppingId] = true;
        $toppingPrice = (int)$toppingItem['harga'];
        $toppingTotal += $toppingPrice;
        $selectedToppings[] = [
            'id' => $toppingId,
            'nama' => $toppingItem['nama'],
            'harga' => $toppingPrice,
        ];
    }

    $basePrice = (int)$menuItem['harga'];
    $itemPrice = $basePrice + $toppingTotal;
    $subtotal += $itemPrice * $quantity;
    $draftItems[] = [
        'id' => $menuId,
        'nama' => $menuItem['nama'],
        'harga' => $itemPrice,
        'base_harga' => $basePrice,
        'qty' => $quantity,
        'notes' => substr(trim((string)($item['notes'] ?? '')), 0, 500),
        'toppings' => $selectedToppings,
    ];
}

$_SESSION['checkout_draft'] = [
    'token' => bin2hex(random_bytes(32)),
    'items' => $draftItems,
    'subtotal' => $subtotal,
    'expires_at' => time() + 1800,
];

echo json_encode(['ok' => true, 'redirect' => 'pembayaran.php']);
