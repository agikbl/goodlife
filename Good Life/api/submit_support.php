<?php
// api/submit_support.php — menerima pesan dari support.php via fetch()
header('Content-Type: application/json');

$dataPath = __DIR__ . '/../data/support_messages.json';
$input = json_decode(file_get_contents('php://input'), true);

$nama     = trim($input['nama'] ?? '');
$kontak   = trim($input['kontak'] ?? '');
$kategori = trim($input['kategori'] ?? '');
$pesan    = trim($input['pesan'] ?? '');

$kategoriValid = ['Pesanan bermasalah', 'Pembayaran', 'Saran', 'Lainnya'];

if ($nama === '' || $kontak === '' || $pesan === '' || !in_array($kategori, $kategoriValid, true)) {
    echo json_encode(['ok' => false, 'error' => 'Lengkapi semua kolom dulu ya.']);
    exit;
}

$messages = json_decode(file_get_contents($dataPath), true) ?: [];

$newMessage = [
    'nama'     => htmlspecialchars($nama, ENT_QUOTES, 'UTF-8'),
    'kontak'   => htmlspecialchars($kontak, ENT_QUOTES, 'UTF-8'),
    'kategori' => htmlspecialchars($kategori, ENT_QUOTES, 'UTF-8'),
    'pesan'    => htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'),
    'status'   => 'Baru',
    'tanggal'  => date('Y-m-d H:i'),
];

$messages[] = $newMessage;

file_put_contents($dataPath, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['ok' => true]);
