<?php

function admin_start_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

admin_start_session();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const ADMIN_ROOT = __DIR__ . '/../../';

function admin_require_auth()
{
    if (empty($_SESSION['admin_authenticated'])) {
        header('Location: login.php');
        exit;
    }
}

function admin_csrf_token()
{
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

function admin_verify_csrf($token)
{
    return is_string($token) && !empty($_SESSION['admin_csrf_token']) &&
        hash_equals($_SESSION['admin_csrf_token'], $token);
}

function admin_json_response($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function admin_read_json($relativePath)
{
    $path = ADMIN_ROOT . $relativePath;
    if (!is_file($path)) {
        throw new RuntimeException('File data tidak ditemukan: ' . $relativePath);
    }
    $content = file_get_contents($path);
    $data = $content === false ? null : json_decode($content, true);
    if (!is_array($data)) {
        throw new RuntimeException('Format data JSON tidak valid: ' . $relativePath);
    }
    return $data;
}

function admin_write_json($relativePath, $data)
{
    $path = ADMIN_ROOT . $relativePath;
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false || file_put_contents($path, $encoded . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Data gagal disimpan: ' . $relativePath);
    }
}

function admin_mutate_json($relativePath, $mutator)
{
    $path = ADMIN_ROOT . $relativePath;
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Data gagal dibuka: ' . $relativePath);
    }
    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Data gagal dikunci: ' . $relativePath);
        }
        $content = stream_get_contents($handle);
        $data = $content === '' ? [] : json_decode($content, true);
        if (!is_array($data)) {
            throw new RuntimeException('Format data JSON tidak valid: ' . $relativePath);
        }
        $result = $mutator($data);
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false || !ftruncate($handle, 0) || !rewind($handle) ||
            fwrite($handle, $encoded . PHP_EOL) === false || !fflush($handle)) {
            throw new RuntimeException('Data gagal disimpan: ' . $relativePath);
        }
        flock($handle, LOCK_UN);
        return $result;
    } finally {
        fclose($handle);
    }
}

function admin_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_flash($message = null, $type = 'success')
{
    if ($message !== null) {
        $_SESSION['admin_flash'] = ['message' => $message, 'type' => $type];
        return;
    }
    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    return $flash;
}

function admin_money($amount)
{
    return 'Rp' . number_format((float)$amount, 0, ',', '.');
}

function admin_require_post()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Metode tidak diizinkan.');
    }
}

function admin_api_guard()
{
    admin_require_post();
    if (empty($_SESSION['admin_authenticated'])) {
        admin_json_response(['ok' => false, 'error' => 'Silakan login kembali.'], 401);
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!admin_verify_csrf($token)) {
        admin_json_response(['ok' => false, 'error' => 'Sesi formulir tidak valid. Muat ulang halaman.'], 403);
    }
}
