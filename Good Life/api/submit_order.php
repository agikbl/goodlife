<?php
// api/submit_order.php — menerima pesanan baru via fetch() dari pesan.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/repository.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$draft = $_SESSION['checkout_draft'] ?? null;
$checkoutToken = is_scalar($input['checkout_token'] ?? null) ? (string)$input['checkout_token'] : '';
if (!is_array($draft) || !isset($draft['token'], $draft['items'], $draft['expires_at']) ||
    $draft['expires_at'] < time() || !hash_equals((string)$draft['token'], $checkoutToken)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sesi pembayaran tidak valid atau sudah kedaluwarsa. Mulai kembali dari halaman pesan.']);
    exit;
}

try {
    $store = goodlife_db_store();
} catch (Throwable $error) {
    error_log('Order submission store read failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Status toko tidak dapat dibaca. Pesanan belum dibuat.']);
    exit;
}
if (array_key_exists('is_open', $store) && $store['is_open'] !== true) {
    http_response_code(423);
    $activity = is_string($store['activity'] ?? null) ? $store['activity'] : 'Tutup sementara';
    echo json_encode(['ok' => false, 'error' => 'Toko sedang tutup (' . $activity . ') dan belum menerima pesanan baru.']);
    exit;
}

$rawWhatsApp = $input['whatsapp'] ?? '';
$whatsapp = is_scalar($rawWhatsApp) ? preg_replace('/\D+/', '', (string)$rawWhatsApp) : '';
$rawName = $input['nama'] ?? '';
$customerName = is_scalar($rawName) ? trim((string)$rawName) : '';
$pickupTime = is_scalar($input['waktu_pengambilan'] ?? null) ? (string)$input['waktu_pengambilan'] : '';
if (strpos($whatsapp, '0') === 0) {
    $whatsapp = '62' . substr($whatsapp, 1);
} elseif (strpos($whatsapp, '8') === 0) {
    $whatsapp = '62' . $whatsapp;
}
$items         = $draft['items'];
$pengiriman    = $input['pengiriman'] ?? '';       // 'ambil' | 'antar'
$rawAddress    = $input['alamat'] ?? '';
$alamat        = is_scalar($rawAddress) ? trim((string)$rawAddress) : '';
$latitude      = is_numeric($input['latitude'] ?? null) ? (float)$input['latitude'] : null;
$longitude     = is_numeric($input['longitude'] ?? null) ? (float)$input['longitude'] : null;
$bayarOngkir   = $input['bayar_ongkir'] ?? null;    // 'qris' | 'tunai' | null (kalau ambil sendiri)
$metodeBayar   = $input['metode_bayar'] ?? '';       // 'qris' | 'tunai'

if (!preg_match('/^628[0-9]{7,11}$/', $whatsapp)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Nomor WhatsApp tidak valid.']);
    exit;
}

if ($customerName === '' || strlen($customerName) > 100 ||
    !is_array($items) || empty($items) ||
    !in_array($pengiriman, ['ambil', 'antar'], true) ||
    !in_array($metodeBayar, ['qris', 'tunai'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Data pesanan tidak lengkap.']);
    exit;
}
if (($pengiriman === 'ambil' && !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $pickupTime)) ||
    ($pengiriman === 'antar' && $pickupTime !== '')) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Perkiraan waktu pengambilan tidak valid.']);
    exit;
}

$ongkir = 0;
$jarakLurus = null;
$jarakEstimasi = null;
if ($pengiriman === 'antar') {
    if ($latitude === null || $longitude === null ||
        $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180 ||
        !in_array($bayarOngkir, ['qris', 'tunai'], true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Data pengantaran tidak valid.']);
        exit;
    }
    $storeLatitude = -4.0077714;
    $storeLongitude = 119.632276;
    $latitudeDelta = deg2rad($latitude - $storeLatitude);
    $longitudeDelta = deg2rad($longitude - $storeLongitude);
    $haversine = sin($latitudeDelta / 2) ** 2 +
        cos(deg2rad($storeLatitude)) * cos(deg2rad($latitude)) *
        sin($longitudeDelta / 2) ** 2;
    $haversine = min(1, max(0, $haversine));
    $jarakLurus = 6371 * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));
    $jarakEstimasi = $jarakLurus * 1.3;
    if ($jarakEstimasi <= 2) {
        $ongkir = 8000;
    } elseif ($jarakEstimasi <= 5) {
        $ongkir = (int)(round((8000 + ($jarakEstimasi - 2) * (7000 / 3)) / 1000) * 1000);
    } else {
        $ongkir = 15000;
    }
}

try {
    $menu = goodlife_db_products();
    $categoryRecords = goodlife_db_categories();
} catch (Throwable $error) {
    error_log('Order submission catalog read failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Data menu atau kategori tidak dapat dibaca.']);
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
$categoryGroups = [];
foreach ($categoryRecords as $categoryRecord) {
    if (is_array($categoryRecord) && isset($categoryRecord['id']) &&
        in_array($categoryRecord['kelompok'] ?? '', ['makanan', 'minuman'], true)) {
        $categoryGroups[$categoryRecord['id']] = $categoryRecord['kelompok'];
    }
}
foreach ($items as $it) {
    if (!is_array($it)) {
        echo json_encode(['ok' => false, 'error' => 'Detail item pesanan tidak valid.']);
        exit;
    }

    $menuId = (string)($it['id'] ?? '');
    $menuItem = $menuById[$menuId] ?? null;
    $quantity = filter_var($it['qty'] ?? null, FILTER_VALIDATE_INT);
    $category = $menuItem['kategori'] ?? '';
    $isFood = ($categoryGroups[$category] ?? '') === 'makanan';
    $isDrink = ($categoryGroups[$category] ?? '') === 'minuman';

    if (!$menuItem || (array_key_exists('tersedia', $menuItem) && !$menuItem['tersedia']) ||
        (!$isFood && !$isDrink) || $quantity === false || $quantity < 1 || $quantity > 99) {
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
        if (!$toppingItem || (array_key_exists('tersedia', $toppingItem) && !$toppingItem['tersedia']) ||
            ($toppingItem['kategori'] ?? '') !== $allowedToppingCategory || isset($seenToppings[$toppingId])) {
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
    $normalizedItems[] = [
        'id' => $menuId,
        'nama' => $menuItem['nama'],
        'harga' => $itemPrice,
        'base_harga' => $basePrice,
        'qty' => $quantity,
        'notes' => substr(trim((string)($it['notes'] ?? '')), 0, 500),
        'toppings' => $selectedToppings,
    ];
}
$total = $subtotal + ($pengiriman === 'antar' ? $ongkir : 0);

$newOrder = [
    'nama_pemesan' => $customerName,
    'whatsapp'     => $whatsapp,
    'items'        => $normalizedItems,
    'pengiriman'   => $pengiriman,
    'waktu_pengambilan' => $pengiriman === 'ambil' ? $pickupTime : null,
    'alamat'       => $alamat,
    'ongkir'       => $ongkir,
    'latitude'     => $pengiriman === 'antar' ? $latitude : null,
    'longitude'    => $pengiriman === 'antar' ? $longitude : null,
    'jarak_lurus'  => $jarakLurus === null ? null : round($jarakLurus, 3),
    'jarak_estimasi' => $jarakEstimasi === null ? null : round($jarakEstimasi, 3),
    'bayar_ongkir' => $bayarOngkir,
    'metode_bayar' => $metodeBayar,
    'subtotal'     => $subtotal,
    'total'        => $total,
    'status'       => 'Diterima',
    'status_pembayaran' => 'Belum dibayar',
];

try {
    $newOrder = goodlife_db_create_order($newOrder);
} catch (Throwable $error) {
    error_log('Order submission database write failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Pesanan gagal disimpan.']);
    exit;
}

try {
    require_once __DIR__ . '/../admin/includes/push.php';
    admin_push_send_new_order($newOrder);
} catch (Throwable $error) {
    error_log('New order Web Push failed for ' . $newOrder['id'] . ': ' . $error->getMessage());
    http_response_code(200);
}

unset($_SESSION['checkout_draft']);
echo json_encode(['ok' => true, 'order' => $newOrder]);
