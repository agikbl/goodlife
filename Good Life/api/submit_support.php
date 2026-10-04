<?php
require_once __DIR__ . '/../database/repository.php';
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

try {
    goodlife_db_insert_support_message([
        'nama' => $nama,
        'kontak' => $kontak,
        'kategori' => $kategori,
        'pesan' => $pesan,
    ]);
} catch (Throwable $error) {
    error_log('Support message database write failed: ' . $error->getMessage());
    support_response(500, ['ok' => false, 'error' => 'Pesan gagal disimpan. Coba lagi nanti.']);
}

support_response(200, ['ok' => true]);
