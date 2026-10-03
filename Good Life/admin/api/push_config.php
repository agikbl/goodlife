<?php
require __DIR__ . '/../includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    admin_json_response(['ok' => false, 'configured' => false, 'public_key' => null], 405);
}
if (empty($_SESSION['admin_authenticated'])) {
    admin_json_response(['ok' => false, 'configured' => false, 'public_key' => null], 401);
}
require_once __DIR__ . '/../includes/push.php';

try {
    $config = admin_push_vapid_config();
    admin_json_response([
        'ok' => true,
        'configured' => true,
        'public_key' => $config['publicKey'],
    ]);
} catch (Throwable $error) {
    error_log('Admin Web Push configuration unavailable: ' . $error->getMessage());
    admin_json_response([
        'ok' => true,
        'configured' => false,
        'public_key' => null,
    ]);
}
