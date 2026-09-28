<?php
// api/submit_order.php — menerima pesanan baru via fetch() dari pesan.php
header('Content-Type: application/json');

$dataPath = __DIR__ . '/../data/orders.json';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$items         = $input['items'] ?? [];
$pengiriman    = $input['pengiriman'] ?? '';       // 'ambil' | 'antar'
$alamat        = trim($input['alamat'] ?? '');
$jarak         = (int)($input['jarak'] ?? 0);
$bayarOngkir   = $input['bayar_ongkir'] ?? null;    // 'qris' | 'tunai' | null (kalau ambil sendiri)
$metodeBayar   = $input['metode_bayar'] ?? '';       // 'qris' | 'tunai'

if (!is_array($items) || empty($items) || !in_array($pengiriman, ['ambil', 'antar'], true) || !in_array($metodeBayar, ['qris', 'tunai'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Data pesanan tidak lengkap.']);
    exit;
}

if ($pengiriman === 'antar' && $alamat === '') {
    echo json_encode(['ok' => false, 'error' => 'Lokasi pengantaran belum diisi.']);
    exit;
}

$ongkir = 0;
if ($pengiriman === 'antar') {
    if ($jarak < 1 || $jarak > 10 || !in_array($bayarOngkir, ['qris', 'tunai'], true)) {
        echo json_encode(['ok' => false, 'error' => 'Data pengantaran tidak valid.']);
        exit;
    }
    $ongkir = 5000 + ($jarak * 2000);
}

$menuPath = __DIR__ . '/../data/menu.json';
$menu = json_decode(file_get_contents($menuPath), true);
if (!is_array($menu)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Data menu tidak dapat dibaca.']);
    exit;
}

$menuById = [];
foreach ($menu as $menuItem) {
    if (isset($menuItem['id'])) {
        $menuById[$menuItem['id']] = $menuItem;
    }
}

$subtotal = 0;
$normalizedItems = [];
$drinkCategories = ['basic_milk', 'basic_coffee', 'tea_series', 'signature_series'];
foreach ($items as $it) {
    if (!is_array($it)) {
        echo json_encode(['ok' => false, 'error' => 'Detail item pesanan tidak valid.']);
        exit;
    }

    $menuId = (string)($it['id'] ?? '');
    $menuItem = $menuById[$menuId] ?? null;
    $quantity = filter_var($it['qty'] ?? null, FILTER_VALIDATE_INT);
    $category = $menuItem['kategori'] ?? '';
    $isFood = in_array($category, ['kebab', 'kebab_pisang', 'piscok', 'burger', 'cemilan_series', 'aneka_nasi'], true);
    $isDrink = in_array($category, $drinkCategories, true);

    if (!$menuItem || (!$isFood && !$isDrink) || $quantity === false || $quantity < 1 || $quantity > 99) {
        echo json_encode(['ok' => false, 'error' => 'Menu atau jumlah item tidak valid.']);
        exit;
    }

    $toppingIds = $it['toppings'] ?? [];
    if (!is_array($toppingIds)) {
        echo json_encode(['ok' => false, 'error' => 'Pilihan topping tidak valid.']);
        exit;
    }

    $allowedToppingCategory = $isDrink ? 'extra_topping_minuman' : 'extra_topping_makanan';
    $selectedToppings = [];
    $toppingTotal = 0;
    $seenToppings = [];
    foreach ($toppingIds as $topping) {
        $toppingId = is_array($topping) ? (string)($topping['id'] ?? '') : (string)$topping;
        $toppingItem = $menuById[$toppingId] ?? null;
        if (!$toppingItem || ($toppingItem['kategori'] ?? '') !== $allowedToppingCategory || isset($seenToppings[$toppingId])) {
            echo json_encode(['ok' => false, 'error' => 'Pilihan topping tidak cocok dengan menu.']);
            exit;
        }
        $seenToppings[$toppingId] = true;
        $toppingPrice = (int)$toppingItem['harga'];
        $toppingTotal += $toppingPrice;
        $selectedToppings[] = [
            'id' => $toppingId,
            'nama' => htmlspecialchars($toppingItem['nama'], ENT_QUOTES, 'UTF-8'),
            'harga' => $toppingPrice,
        ];
    }

    $basePrice = (int)$menuItem['harga'];
    $itemPrice = $basePrice + $toppingTotal;
    $subtotal += $itemPrice * $quantity;
    $normalizedItems[] = [
        'id' => $menuId,
        'nama' => htmlspecialchars($menuItem['nama'], ENT_QUOTES, 'UTF-8'),
        'harga' => $itemPrice,
        'base_harga' => $basePrice,
        'qty' => $quantity,
        'notes' => htmlspecialchars(substr(trim((string)($it['notes'] ?? '')), 0, 500), ENT_QUOTES, 'UTF-8'),
        'toppings' => $selectedToppings,
    ];
}
$total = $subtotal + ($pengiriman === 'antar' ? $ongkir : 0);

$orders = json_decode(file_get_contents($dataPath), true) ?: [];

$newOrder = [
    'id'           => 'ORD' . date('ymd') . str_pad((string)(count($orders) + 1), 3, '0', STR_PAD_LEFT),
    'items'        => $normalizedItems,
    'pengiriman'   => $pengiriman,
    'alamat'       => htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8'),
    'ongkir'       => $ongkir,
    'jarak'        => $pengiriman === 'antar' ? $jarak : null,
    'bayar_ongkir' => $bayarOngkir,
    'metode_bayar' => $metodeBayar,
    'subtotal'     => $subtotal,
    'total'        => $total,
    'status'       => 'Diterima',
    'tanggal'      => date('Y-m-d H:i'),
];

$orders[] = $newOrder;
file_put_contents($dataPath, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['ok' => true, 'order' => $newOrder]);
