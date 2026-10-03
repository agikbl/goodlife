<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    admin_json_response(['ok' => false, 'error' => 'Data permintaan tidak valid.'], 400);
}
$orderId = trim((string)($input['order_id'] ?? ''));
$action = $input['action'] ?? 'status';
$allowedActions = ['status', 'paid'];
if ($orderId === '' || !in_array($action, $allowedActions, true)) {
    admin_json_response(['ok' => false, 'error' => 'Data pembaruan tidak valid.'], 400);
}

try {
    $updated = admin_mutate_json('data/orders.json', function (&$orders) use ($orderId, $action) {
        foreach ($orders as &$order) {
            if (!is_array($order) || ($order['id'] ?? '') !== $orderId) {
                continue;
            }
            if ($action === 'paid') {
                if (($order['status_pembayaran'] ?? 'Belum dibayar') === 'Lunas') {
                    throw new DomainException('Pesanan sudah ditandai lunas.');
                }
                $order['status_pembayaran'] = 'Lunas';
            } else {
                $current = $order['status'] ?? 'Diterima';
                $isDelivery = ($order['pengiriman'] ?? '') === 'antar';
                $paymentMethod = $order['metode_bayar'] ?? '';
                $isPaid = ($order['status_pembayaran'] ?? '') === 'Lunas';
                if ($paymentMethod === 'qris' && !$isPaid) {
                    throw new DomainException('Pesanan QRIS harus dikonfirmasi lunas sebelum diproses.');
                }
                $transitions = [
                    'Diterima' => 'Diproses',
                    'Diproses' => $isDelivery ? 'Diantarkan' : 'Siap Diambil',
                    'Diantarkan' => 'Selesai',
                    'Siap Diambil' => 'Selesai',
                ];
                if (!isset($transitions[$current])) {
                    throw new DomainException('Pesanan sudah berada pada status akhir.');
                }
                if ($transitions[$current] === 'Selesai' && !$isPaid) {
                    throw new DomainException('Pembayaran tunai harus ditandai lunas sebelum pesanan diselesaikan.');
                }
                $order['status'] = $transitions[$current];
            }
            return $order;
        }
        unset($order);
        throw new OutOfBoundsException('Pesanan tidak ditemukan.');
    });
    admin_json_response(['ok' => true, 'order' => $updated]);
} catch (DomainException $error) {
    admin_json_response(['ok' => false, 'error' => $error->getMessage()], 409);
} catch (OutOfBoundsException $error) {
    admin_json_response(['ok' => false, 'error' => $error->getMessage()], 404);
} catch (Throwable $error) {
    error_log('Admin order status update failed: ' . $error->getMessage());
    admin_json_response(['ok' => false, 'error' => 'Status pesanan gagal diperbarui.'], 500);
}
