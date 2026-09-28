<?php
// api/submit_review.php — menerima ulasan baru dari beranda.php via fetch()
header('Content-Type: application/json');

$dataPath = __DIR__ . '/../data/reviews.json';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$nama     = trim($input['nama'] ?? '');
$rating   = (int)($input['rating'] ?? 0);
$komentar = trim($input['komentar'] ?? '');
$deviceId = trim($input['device_id'] ?? '');

if ($nama === '' || $komentar === '' || $rating < 1 || $rating > 5 || !preg_match('/\A[a-f0-9]{32}\z/i', $deviceId)) {
    echo json_encode(['ok' => false, 'error' => 'Data ulasan tidak lengkap atau tidak valid.']);
    exit;
}

$deviceIdHash = hash('sha256', strtolower($deviceId));
$file = fopen($dataPath, 'c+');
if ($file === false || !flock($file, LOCK_EX)) {
    if ($file !== false) {
        fclose($file);
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Ulasan gagal disimpan. Coba lagi.']);
    exit;
}

$reviews = json_decode(stream_get_contents($file), true);
if (!is_array($reviews)) {
    $reviews = [];
}

foreach ($reviews as $review) {
    $savedDeviceHash = $review['device_id_hash'] ?? '';
    if (is_string($savedDeviceHash) && $savedDeviceHash !== '' && hash_equals($savedDeviceHash, $deviceIdHash)) {
        flock($file, LOCK_UN);
        fclose($file);
        echo json_encode(['ok' => false, 'already_reviewed' => true, 'error' => 'Perangkat ini sudah pernah mengirim ulasan.']);
        exit;
    }
}

$newReview = [
    'nama'     => htmlspecialchars($nama, ENT_QUOTES, 'UTF-8'),
    'rating'   => $rating,
    'komentar' => htmlspecialchars($komentar, ENT_QUOTES, 'UTF-8'),
    'tanggal'  => date('Y-m-d'),
    'device_id_hash' => $deviceIdHash,
];

$reviews[] = $newReview;
$encodedReviews = json_encode($reviews, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
rewind($file);
$written = fwrite($file, $encodedReviews);
$saved = $written === strlen($encodedReviews) && ftruncate($file, $written) && fflush($file);
flock($file, LOCK_UN);
fclose($file);

if (!$saved) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Ulasan gagal disimpan. Coba lagi.']);
    exit;
}

unset($newReview['device_id_hash']);

echo json_encode(['ok' => true, 'review' => $newReview]);