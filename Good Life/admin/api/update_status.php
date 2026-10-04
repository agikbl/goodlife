<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    admin_json_response(['ok' => false, 'error' => 'Data permintaan tidak valid.'], 400);
}
$orderId = trim((string)($input['order_id'] ?? ''));
$action = $input['action'] ?? 'status';
$allowedActions = ['status', 'paid', 'reject'];
if ($orderId === '' || !in_array($action, $allowedActions, true)) {
    admin_json_response(['ok' => false, 'error' => 'Data pembaruan tidak valid.'], 400);
}

try {
    $updated = goodlife_db_advance_order($orderId, $action);
    admin_json_response(['ok' => true, 'order' => $updated]);
} catch (DomainException $error) {
    admin_json_response(['ok' => false, 'error' => $error->getMessage()], 409);
} catch (OutOfBoundsException $error) {
    admin_json_response(['ok' => false, 'error' => $error->getMessage()], 404);
} catch (Throwable $error) {
    error_log('Admin order status update failed: ' . $error->getMessage());
    admin_json_response(['ok' => false, 'error' => 'Status pesanan gagal diperbarui.'], 500);
}
