<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !is_bool($input['is_open'] ?? null)) {
    admin_json_response(['ok' => false, 'error' => 'Status toko tidak valid.'], 400);
}

$isOpen = $input['is_open'];
$activity = is_scalar($input['activity'] ?? null) ? trim((string)$input['activity']) : '';
if (strlen($activity) > 100 || (!$isOpen && $activity === '')) {
    admin_json_response(['ok' => false, 'error' => 'Isi keterangan aktivitas saat toko ditutup (maksimal 100 karakter).'], 400);
}

try {
    $store = admin_mutate_json('data/store.json', function (&$store) use ($isOpen, $activity) {
        $store['is_open'] = $isOpen;
        $store['activity'] = $isOpen ? 'Menerima pesanan' : $activity;
        $store['status_updated_at'] = date('Y-m-d H:i:s');
        return $store;
    });
    admin_json_response(['ok' => true, 'store_status' => [
        'is_open' => $store['is_open'],
        'activity' => $store['activity'],
        'updated_at' => $store['status_updated_at'],
    ]]);
} catch (Throwable $error) {
    error_log('Admin store status update failed: ' . $error->getMessage());
    admin_json_response(['ok' => false, 'error' => 'Status toko gagal diperbarui.'], 500);
}
