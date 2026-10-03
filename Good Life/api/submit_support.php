<?php
header('Content-Type: application/json; charset=utf-8');

function support_response($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    support_response(405, ['ok' => false, 'error' => 'Metode permintaan tidak diizinkan.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    support_response(400, ['ok' => false, 'error' => 'Data pesan tidak valid.']);
}

$nama = is_scalar($input['nama'] ?? null) ? trim((string)$input['nama']) : '';
$kontak = is_scalar($input['kontak'] ?? null) ? trim((string)$input['kontak']) : '';
$kategori = is_scalar($input['kategori'] ?? null) ? trim((string)$input['kategori']) : '';
$pesan = is_scalar($input['pesan'] ?? null) ? trim((string)$input['pesan']) : '';
$kategoriValid = ['Pesanan bermasalah', 'Pembayaran', 'Saran', 'Lainnya'];

if ($nama === '' || strlen($nama) > 100 || $kontak === '' || strlen($kontak) > 150 ||
    $pesan === '' || strlen($pesan) > 3000 || !in_array($kategori, $kategoriValid, true)) {
    support_response(400, ['ok' => false, 'error' => 'Lengkapi semua kolom dan pastikan panjang isian valid.']);
}

$path = __DIR__ . '/../data/support_messages.json';
$file = fopen($path, 'c+');
if ($file === false) {
    error_log('Support message storage could not be opened.');
    support_response(500, ['ok' => false, 'error' => 'Pesan gagal disimpan. Coba lagi nanti.']);
}
if (!flock($file, LOCK_EX)) {
    fclose($file);
    error_log('Support message storage could not be locked.');
    support_response(500, ['ok' => false, 'error' => 'Penyimpanan pesan sedang tidak tersedia. Coba lagi nanti.']);
}

$content = stream_get_contents($file);
$messages = $content === '' ? [] : json_decode($content, true);
if (!is_array($messages)) {
    flock($file, LOCK_UN);
    fclose($file);
    error_log('Support message storage contains invalid JSON.');
    support_response(500, ['ok' => false, 'error' => 'Data pesan tidak dapat dibaca. Hubungi admin.']);
}

$messages[] = [
    'nama' => htmlspecialchars($nama, ENT_QUOTES, 'UTF-8'),
    'kontak' => htmlspecialchars($kontak, ENT_QUOTES, 'UTF-8'),
    'kategori' => htmlspecialchars($kategori, ENT_QUOTES, 'UTF-8'),
    'pesan' => htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'),
    'status' => 'Baru',
    'tanggal' => date('Y-m-d H:i'),
];
$encoded = json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$serialized = $encoded === false ? false : $encoded . PHP_EOL;
$saved = $serialized !== false && rewind($file) &&
    fwrite($file, $serialized) === strlen($serialized) &&
    ftruncate($file, strlen($serialized)) && fflush($file);
flock($file, LOCK_UN);
fclose($file);

if (!$saved) {
    error_log('Support message could not be fully written to storage.');
    support_response(500, ['ok' => false, 'error' => 'Pesan gagal disimpan. Coba lagi nanti.']);
}

support_response(200, ['ok' => true]);
