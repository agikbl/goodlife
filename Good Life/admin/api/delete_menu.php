<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();
$input = json_decode(file_get_contents('php://input'), true);
$id = is_array($input) ? trim((string)($input['id'] ?? '')) : '';
if ($id === '') {
    admin_json_response(['ok' => false, 'error' => 'ID menu tidak valid.'], 400);
}
try {
    $deleted = admin_mutate_json('data/menu.json', function (&$menu) use ($id) {
        foreach ($menu as $index => $item) {
            if (($item['id'] ?? '') !== $id) continue;
            $orders = admin_read_json('data/orders.json');
            foreach ($orders as $order) {
                foreach (($order['items'] ?? []) as $orderedItem) {
                    if (($orderedItem['id'] ?? '') === $id) {
                        throw new DomainException('Menu sudah ada di riwayat pesanan sehingga tidak dapat dihapus.');
                    }
                }
            }
            array_splice($menu, $index, 1);
            return $item;
        }
        throw new OutOfBoundsException('Menu tidak ditemukan.');
    });
    $image = $deleted['gambar'] ?? '';
    if (preg_match('#^assets/menu/[a-f0-9]{32}\.(jpg|png|webp)$#', $image)) {
        $path = ADMIN_ROOT . $image;
        if (is_file($path) && !unlink($path)) {
            error_log('Unable to remove deleted menu image: ' . $path);
        }
    }
    admin_json_response(['ok' => true]);
} catch (DomainException $error) {
    admin_json_response(['ok' => false, 'error' => $error->getMessage()], 409);
} catch (OutOfBoundsException $error) {
    admin_json_response(['ok' => false, 'error' => $error->getMessage()], 404);
} catch (Throwable $error) {
    error_log('Admin menu deletion failed: ' . $error->getMessage());
    admin_json_response(['ok' => false, 'error' => 'Menu gagal dihapus.'], 500);
}
