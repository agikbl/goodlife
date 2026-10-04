<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !isset($input['menerima_tunai']) || !is_bool($input['menerima_tunai'])) {
    admin_json_response(['ok' => false, 'error' => 'Pengaturan tunai tidak valid.'], 400);
}
try {
    $store = goodlife_db_update_cash_acceptance($input['menerima_tunai']);
    admin_json_response(['ok' => true, 'menerima_tunai' => $store['menerima_tunai']]);
} catch (Throwable $error) {
    error_log('Cash payment setting failed: ' . $error->getMessage());
    admin_json_response(['ok' => false, 'error' => 'Pengaturan tunai gagal disimpan.'], 500);
}