<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();

function category_error($message, $status = 400)
{
    admin_json_response(['ok' => false, 'error' => $message], $status);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    category_error('Data kategori tidak valid.');
}

$action = $input['action'] ?? '';
try {
    if ($action === 'add') {
        $name = is_scalar($input['nama'] ?? null) ? trim((string)$input['nama']) : '';
        $group = $input['kelompok'] ?? '';
        if ($name === '' || strlen($name) > 60 || !in_array($group, ['makanan', 'minuman'], true)) {
            category_error('Nama kategori maksimal 60 karakter dan kelompok harus dipilih.');
        }
        $created = goodlife_db_save_category('', $name, $group);
        admin_json_response(['ok' => true, 'category' => $created]);
    }

    if ($action === 'update') {
        $id = is_scalar($input['id'] ?? null) ? trim((string)$input['id']) : '';
        $name = is_scalar($input['nama'] ?? null) ? trim((string)$input['nama']) : '';
        $group = $input['kelompok'] ?? '';
        if ($id === '' || $name === '' || strlen($name) > 60 ||
            !in_array($group, ['makanan', 'minuman'], true)) {
            category_error('ID, nama kategori, atau kelompok tidak valid.');
        }
        $updated = goodlife_db_save_category($id, $name, $group);
        admin_json_response(['ok' => true, 'category' => $updated]);
    }

    if ($action === 'delete') {
        $id = is_scalar($input['id'] ?? null) ? trim((string)$input['id']) : '';
        if ($id === '' || !preg_match('/^(?:[a-z0-9_]+|cat_[a-f0-9]{12})$/', $id)) {
            category_error('ID kategori tidak valid.');
        }
        goodlife_db_delete_category($id);
        admin_json_response(['ok' => true]);
    }

    if ($action === 'reorder') {
        $ids = $input['ids'] ?? null;
        if (!is_array($ids) || array_filter($ids, function ($id) { return !is_string($id); }) ||
            count($ids) !== count(array_unique($ids))) {
            category_error('Urutan kategori tidak valid.');
        }
        $group = $input['kelompok'] ?? '';
        if (!in_array($group, ['makanan', 'minuman'], true)) {
            category_error('Kelompok kategori tidak valid.');
        }
        goodlife_db_reorder_categories($ids, $group);
        admin_json_response(['ok' => true]);
    }

    category_error('Aksi kategori tidak dikenal.');
} catch (DomainException $error) {
    category_error($error->getMessage(), 409);
} catch (OutOfBoundsException $error) {
    category_error($error->getMessage(), 404);
} catch (Throwable $error) {
    error_log('Admin category update failed: ' . $error->getMessage());
    category_error('Kategori gagal diperbarui.', 500);
}
