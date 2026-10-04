<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();

function menu_error($message, $status = 400)
{
    admin_json_response(['ok' => false, 'error' => $message], $status);
}

$isJson = stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
$input = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;
if (!is_array($input)) {
    menu_error('Data menu tidak valid.');
}

if (($input['action'] ?? '') === 'availability') {
    $id = (string)($input['id'] ?? '');
    try {
        $item = goodlife_db_set_product_availability(
            $id,
            filter_var($input['tersedia'] ?? false, FILTER_VALIDATE_BOOLEAN)
        );
        admin_json_response(['ok' => true, 'menu' => $item]);
    } catch (OutOfBoundsException $error) {
        menu_error($error->getMessage(), 404);
    } catch (Throwable $error) {
        error_log('Admin menu stock update failed: ' . $error->getMessage());
        menu_error('Ketersediaan menu gagal diperbarui.', 500);
    }
}

$categoryRecords = admin_read_dataset('data/categories.json');
$categories = array_column($categoryRecords, 'id');
$categories = array_merge($categories, ['extra_topping_makanan', 'extra_topping_minuman']);
$name = trim((string)($input['nama'] ?? ''));
$category = (string)($input['kategori'] ?? '');
$price = filter_var($input['harga'] ?? null, FILTER_VALIDATE_INT);
$id = trim((string)($input['id'] ?? ''));
if ($name === '' || strlen($name) > 300 || !in_array($category, $categories, true) ||
    $price === false || $price < 0 || $price > 10000000) {
    menu_error('Nama, kategori, atau harga menu tidak valid.');
}

$imagePath = null;
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['gambar'];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        menu_error('Unggah gambar gagal atau ukuran melebihi 5 MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $dimensions = @getimagesize($file['tmp_name']);
    if (!isset($extensions[$mime]) || !$dimensions || $dimensions[0] > 6000 || $dimensions[1] > 6000) {
        menu_error('Format gambar harus JPG, PNG, atau WebP dengan dimensi maksimal 6000×6000.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $imagePath = 'assets/menu/' . $filename;
    try {
        goodlife_db_save_media($imagePath, $file['tmp_name'], $mime);
    } catch (Throwable $error) {
        error_log('Admin menu image database save failed: ' . $error->getMessage());
        menu_error('Gambar menu gagal disimpan ke database.', 500);
    }
}

try {
    $product = [
        'id' => $id,
        'nama' => $name,
        'kategori' => $category,
        'harga' => $price,
        'tersedia' => filter_var($input['tersedia'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ];
    if ($imagePath !== null) {
        $product['gambar'] = $imagePath;
    }
    $saved = goodlife_db_save_product($product);
    admin_json_response(['ok' => true, 'menu' => $saved['product']]);
} catch (OutOfBoundsException $error) {
    if ($imagePath !== null) goodlife_db_delete_media_if_unreferenced($imagePath);
    menu_error($error->getMessage(), 404);
} catch (Throwable $error) {
    error_log('Admin menu save failed: ' . $error->getMessage());
    if ($imagePath !== null) goodlife_db_delete_media_if_unreferenced($imagePath);
    menu_error('Menu gagal disimpan.', 500);
}
