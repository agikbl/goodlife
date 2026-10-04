<?php
require __DIR__ . '/../includes/bootstrap.php';
if (empty($_SESSION['admin_authenticated'])) {
    admin_json_response(['ok' => false, 'error' => 'Silakan login kembali.'], 401);
}

try {
    $showHistory = ($_GET['history'] ?? '') === '1';
    $businessTimezone = new DateTimeZone('Asia/Makassar');
    $today = (new DateTimeImmutable('now', $businessTimezone))->format('Y-m-d');
    $orders = array_values(array_filter(admin_read_dataset('data/orders.json'), 'is_array'));
    $orders = array_values(array_filter($orders, static function ($order) { return ($order['status'] ?? '') !== 'Ditolak'; }));
    $orders = array_values(array_filter($orders, function ($order) use ($showHistory, $today) {
        $timestamp = (string)($order['tanggal'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}(?: \d{2}:\d{2})?$/', $timestamp)) {
            error_log('Admin orders fetch skipped an order with an invalid timestamp.');
            return false;
        }
        try {
            $sourceTimezone = new DateTimeZone((string)($order['zona_waktu'] ?? 'UTC'));
            $format = strlen($timestamp) === 10 ? '!Y-m-d' : '!Y-m-d H:i';
            $parsedDate = DateTimeImmutable::createFromFormat($format, $timestamp, $sourceTimezone);
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (!$parsedDate || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count']))) {
                error_log('Admin orders fetch skipped an order with an invalid calendar date.');
                return false;
            }
            $orderDate = $parsedDate->setTimezone(new DateTimeZone('Asia/Makassar'))->format('Y-m-d');
        } catch (Throwable $error) {
            error_log('Admin orders fetch skipped an order with invalid timezone data.');
            return false;
        }
        return $showHistory ? $orderDate < $today : $orderDate === $today;
    }));
    usort($orders, function ($first, $second) {
        return strcmp((string)($second['tanggal'] ?? ''), (string)($first['tanggal'] ?? ''));
    });
    admin_json_response(['ok' => true, 'orders' => $orders, 'history' => $showHistory]);
} catch (Throwable $error) {
    error_log('Admin orders fetch failed: ' . $error->getMessage());
    admin_json_response(['ok' => false, 'error' => 'Daftar pesanan gagal dimuat.'], 500);
}
