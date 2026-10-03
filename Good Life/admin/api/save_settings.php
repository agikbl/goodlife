<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();

function settings_error($message, $status = 400)
{
    admin_json_response(['ok' => false, 'error' => $message], $status);
}

function remove_gallery_image($image)
{
    if (!is_string($image) || !preg_match('#^assets/gallery/[a-f0-9]{32}\.(jpg|png|webp)$#', $image)) {
        return false;
    }
    $path = ADMIN_ROOT . $image;
    if (is_file($path) && !unlink($path)) {
        throw new RuntimeException('File foto galeri gagal dihapus.');
    }
    return true;
}

$isJson = stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
$input = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;
if (!is_array($input)) settings_error('Data pengaturan tidak valid.');

if (($input['action'] ?? '') === 'delete_gallery') {
    $image = (string)($input['image'] ?? '');
    try {
        $deleted = admin_mutate_json('data/store.json', function (&$store) use ($image) {
            $gallery = is_array($store['gallery'] ?? null) ? $store['gallery'] : [];
            $index = array_search($image, $gallery, true);
            if ($index === false) throw new OutOfBoundsException('Foto galeri tidak ditemukan.');
            array_splice($gallery, $index, 1);
            $store['gallery'] = $gallery;
            return $image;
        });
        if (!remove_gallery_image($deleted)) settings_error('Path foto galeri tidak valid.', 400);
        admin_json_response(['ok' => true]);
    } catch (OutOfBoundsException $error) {
        settings_error($error->getMessage(), 404);
    } catch (Throwable $error) {
        error_log('Admin gallery deletion failed: ' . $error->getMessage());
        settings_error('Foto galeri gagal dihapus.', 500);
    }
}

$name = trim((string)($input['nama'] ?? ''));
$address = trim((string)($input['alamat'] ?? ''));
$whatsapp = preg_replace('/\D+/', '', (string)($input['wa'] ?? ''));
if (strpos($whatsapp, '0') === 0) $whatsapp = '62' . substr($whatsapp, 1);
if (strpos($whatsapp, '8') === 0) $whatsapp = '62' . $whatsapp;
$open = (string)($input['jam_buka'] ?? '');
$close = (string)($input['jam_tutup'] ?? '');
if ($name === '' || strlen($name) > 300 || $address === '' || strlen($address) > 900 ||
    !preg_match('/^628[0-9]{7,11}$/', $whatsapp) ||
    !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $open) ||
    !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $close)) {
    settings_error('Nama, alamat, nomor WhatsApp, atau jam operasional tidak valid.');
}

$uploads = [];
$files = $_FILES['gallery'] ?? null;
if (is_array($files) && isset($files['name']) && is_array($files['name'])) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    foreach ($files['name'] as $index => $unusedName) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            ($files['size'][$index] ?? 0) > 5 * 1024 * 1024) {
            settings_error('Unggah galeri gagal atau ada file melebihi 5 MB.');
        }
        $tmpName = $files['tmp_name'][$index];
        $mime = $finfo->file($tmpName);
        $dimensions = @getimagesize($tmpName);
        if (!isset($extensions[$mime]) || !$dimensions || $dimensions[0] > 6000 || $dimensions[1] > 6000) {
            settings_error('Foto galeri harus JPG, PNG, atau WebP dengan dimensi maksimal 6000×6000.');
        }
        $uploads[] = ['tmp_name' => $tmpName, 'extension' => $extensions[$mime]];
    }
}

$directory = ADMIN_ROOT . 'assets/gallery';
if ($uploads && !is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    settings_error('Folder galeri gagal disiapkan.', 500);
}
$storedImages = [];
foreach ($uploads as $upload) {
    $filename = bin2hex(random_bytes(16)) . '.' . $upload['extension'];
    if (!move_uploaded_file($upload['tmp_name'], $directory . '/' . $filename)) {
        foreach ($storedImages as $storedImage) remove_gallery_image($storedImage);
        settings_error('Foto galeri gagal disimpan.', 500);
    }
    $storedImages[] = 'assets/gallery/' . $filename;
}

try {
    admin_mutate_json('data/store.json', function (&$store) use ($name, $address, $whatsapp, $open, $close, $storedImages) {
        $gallery = is_array($store['gallery'] ?? null) ? $store['gallery'] : [];
        if (count($gallery) + count($storedImages) > 10) {
            throw new LengthException('Galeri maksimal berisi 10 foto.');
        }
        $store['nama'] = $name;
        $store['alamat'] = $address;
        $store['wa'] = $whatsapp;
        $store['jam_buka'] = $open;
        $store['jam_tutup'] = $close;
        $store['gallery'] = array_merge($gallery, $storedImages);
    });
    admin_json_response(['ok' => true]);
} catch (LengthException $error) {
    foreach ($storedImages as $storedImage) remove_gallery_image($storedImage);
    settings_error($error->getMessage(), 400);
} catch (Throwable $error) {
    error_log('Admin store settings update failed: ' . $error->getMessage());
    foreach ($storedImages as $storedImage) remove_gallery_image($storedImage);
    settings_error('Pengaturan toko gagal disimpan.', 500);
}
