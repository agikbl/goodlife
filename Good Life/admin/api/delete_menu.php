<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();
$input = json_decode(file_get_contents('php://input'), true);
$id = is_array($input) ? trim((string)($input['id'] ?? '')) : '';
if ($id === '') {
    admin_json_response(['ok' => false, 'error' => 'ID menu tidak valid.'], 400);
}
try {
    $deleted = goodlife_db_delete_product($id);
    admin_json_response(['ok' => true]);
} catch (DomainException $error) {
    admin_json_response(['ok' => false, 'error' => $error->getMessage()], 409);
} catch (OutOfBoundsException $error) {
    admin_json_response(['ok' => false, 'error' => $error->getMessage()], 404);
} catch (Throwable $error) {
    error_log('Admin menu deletion failed: ' . $error->getMessage());
    admin_json_response(['ok' => false, 'error' => 'Menu gagal dihapus.'], 500);
}
