<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_require_post();
if (!admin_verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Sesi formulir tidak valid.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => 'Lax',
    ]);
}
session_destroy();
header('Location: ../login.php');
exit;
