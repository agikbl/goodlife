<?php
require_once __DIR__ . '/../../database/repository.php';

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

function admin_read_dataset($relativePath)
{
    return goodlife_db_legacy_read($relativePath);
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
