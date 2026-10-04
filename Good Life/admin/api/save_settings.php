<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_api_guard();

function settings_error($message, $status = 400)
{
    admin_json_response(['ok' => false, 'error' => $message], $status);
}

$isJson = stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
$input = $isJson ? json_decode(file_get_contents('php://input'), true) : $_POST;
if (!is_array($input)) settings_error('Data pengaturan tidak valid.');

if (($input['action'] ?? '') === 'delete_gallery') {
    $image = (string)($input['image'] ?? '');
    try {
        if (!preg_match('#^assets/gallery/[a-f0-9]{32}\.(jpg|png|webp)$#', $image)) {
            settings_error('Path foto galeri tidak valid.', 400);
        }
        goodlife_db_delete_gallery_image($image);
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
if ($name === '' || strlen($name) > 100 || $address === '' || strlen($address) > 900 ||
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
        $uploads[] = ['tmp_name' => $tmpName, 'extension' => $extensions[$mime], 'mime' => $mime];
    }
}

$storedImages = [];
try {
    foreach ($uploads as $upload) {
        $filename = bin2hex(random_bytes(16)) . '.' . $upload['extension'];
        $imagePath = 'assets/gallery/' . $filename;
        goodlife_db_save_media($imagePath, $upload['tmp_name'], $upload['mime']);
        $storedImages[] = $imagePath;
    }
} catch (Throwable $error) {
    foreach ($storedImages as $storedImage) {
        goodlife_db_delete_media_if_unreferenced($storedImage);
    }
    error_log('Admin gallery image database save failed: ' . $error->getMessage());
    settings_error('Foto galeri gagal disimpan ke database.', 500);
}

try {
    goodlife_db_save_store([
        'nama' => $name,
        'alamat' => $address,
        'wa' => $whatsapp,
        'jam_buka' => $open,
        'jam_tutup' => $close,
    ], $storedImages);
    admin_json_response(['ok' => true]);
} catch (LengthException $error) {
    foreach ($storedImages as $storedImage) {
        goodlife_db_delete_media_if_unreferenced($storedImage);
    }
    settings_error($error->getMessage(), 400);
} catch (Throwable $error) {
    error_log('Admin store settings update failed: ' . $error->getMessage());
    foreach ($storedImages as $storedImage) {
        goodlife_db_delete_media_if_unreferenced($storedImage);
    }
    settings_error('Pengaturan toko gagal disimpan.', 500);
}
