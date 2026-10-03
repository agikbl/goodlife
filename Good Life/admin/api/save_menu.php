<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();

function menu_error($message, $status = 400)
{
    admin_json_response(['ok' => false, 'error' => $message], $status);
}

function remove_uploaded_menu_image($relativePath)
{
    if (!is_string($relativePath) || !preg_match('#^assets/menu/[a-f0-9]{32}\.(jpg|png|webp)$#', $relativePath)) {
        return;
    }
    $path = ADMIN_ROOT . $relativePath;
    if (is_file($path) && !unlink($path)) {
        error_log('Unable to remove old menu image: ' . $path);
    }
}

$isJson = stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
$input = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;
if (!is_array($input)) {
    menu_error('Data menu tidak valid.');
}

if (($input['action'] ?? '') === 'availability') {
    $id = (string)($input['id'] ?? '');
    try {
        $item = admin_mutate_json('data/menu.json', function (&$menu) use ($id, $input) {
            foreach ($menu as &$entry) {
                if (($entry['id'] ?? '') === $id) {
                    $entry['tersedia'] = filter_var($input['tersedia'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    return $entry;
                }
            }
            unset($entry);
            throw new OutOfBoundsException('Menu tidak ditemukan.');
        });
        admin_json_response(['ok' => true, 'menu' => $item]);
    } catch (OutOfBoundsException $error) {
        menu_error($error->getMessage(), 404);
    } catch (Throwable $error) {
        error_log('Admin menu stock update failed: ' . $error->getMessage());
        menu_error('Ketersediaan menu gagal diperbarui.', 500);
    }
}

$categoryRecords = admin_read_json('data/categories.json');
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
    $directory = ADMIN_ROOT . 'assets/menu';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        menu_error('Folder gambar menu gagal disiapkan.', 500);
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        menu_error('Gambar menu gagal disimpan.', 500);
    }
    $imagePath = 'assets/menu/' . $filename;
}

try {
    $saved = admin_mutate_json('data/menu.json', function (&$menu) use ($id, $name, $category, $price, $input, $imagePath) {
        $existingIndex = null;
        foreach ($menu as $index => $entry) {
            if (($entry['id'] ?? '') === $id && $id !== '') {
                $existingIndex = $index;
                break;
            }
        }
        if ($id !== '' && $existingIndex === null) {
            throw new OutOfBoundsException('Menu yang akan diedit tidak ditemukan.');
        }
        if ($existingIndex === null) {
            $id = 'adm' . bin2hex(random_bytes(5));
        }
        $oldImage = $existingIndex === null ? null : ($menu[$existingIndex]['gambar'] ?? null);
        $entry = $existingIndex === null ? [
            'id' => $id,
            'favorit' => false,
        ] : $menu[$existingIndex];
        $entry['nama'] = $name;
        $entry['kategori'] = $category;
        $entry['harga'] = $price;
        $entry['tersedia'] = filter_var($input['tersedia'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($imagePath !== null) {
            $entry['gambar'] = $imagePath;
        }
        if ($existingIndex === null) {
            $menu[] = $entry;
        } else {
            $menu[$existingIndex] = $entry;
        }
        return ['entry' => $entry, 'old_image' => $imagePath === null ? null : $oldImage];
    });
    if ($saved['old_image']) {
        remove_uploaded_menu_image($saved['old_image']);
    }
    admin_json_response(['ok' => true, 'menu' => $saved['entry']]);
} catch (OutOfBoundsException $error) {
    if ($imagePath !== null) remove_uploaded_menu_image($imagePath);
    menu_error($error->getMessage(), 404);
} catch (Throwable $error) {
    error_log('Admin menu save failed: ' . $error->getMessage());
    if ($imagePath !== null) remove_uploaded_menu_image($imagePath);
    menu_error('Menu gagal disimpan.', 500);
}
