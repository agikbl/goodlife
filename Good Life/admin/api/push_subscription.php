<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();
require_once __DIR__ . '/../includes/push.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    admin_json_response(['ok' => false, 'error' => 'Data langganan push tidak valid.'], 400);
}

$subscription = is_array($input['subscription'] ?? null) ? $input['subscription'] : $input;
$action = $input['action'] ?? (isset($input['subscription']) ? 'subscribe' : '');
$endpoint = $subscription['endpoint'] ?? null;
if (!in_array($action, ['subscribe', 'remove'], true) ||
    !admin_push_validate_endpoint($endpoint)) {
    admin_json_response(['ok' => false, 'error' => 'Data langganan push tidak valid.'], 400);
}

if ($action === 'subscribe') {
    $keys = $subscription['keys'] ?? null;
    if (!is_array($keys) ||
        !is_string($keys['p256dh'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{20,200}$/', $keys['p256dh']) ||
        !is_string($keys['auth'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{16,100}$/', $keys['auth'])) {
        admin_json_response(['ok' => false, 'error' => 'Kunci langganan push tidak valid.'], 400);
    }
}

try {
    if ($action === 'subscribe') {
        admin_push_vapid_config();
        goodlife_db_save_push_subscription($endpoint, $keys);
    } else {
        goodlife_db_remove_push_subscription($endpoint);
    }
    admin_json_response(['ok' => true]);
} catch (Throwable $error) {
    error_log('Admin Web Push subscription update failed: ' . $error->getMessage());
    admin_json_response(['ok' => false, 'error' => 'Langganan notifikasi gagal disimpan.'], 500);
}
