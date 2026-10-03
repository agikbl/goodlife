<?php
require __DIR__ . '/../includes/bootstrap.php';
admin_require_post();

if (!admin_verify_csrf($_POST['csrf_token'] ?? '')) {
    admin_flash('Form login kedaluwarsa. Coba lagi.', 'error');
    header('Location: ../login.php');
    exit;
}

$now = time();
if (!empty($_SESSION['admin_login_locked_until']) && $_SESSION['admin_login_locked_until'] > $now) {
    admin_flash('Terlalu banyak percobaan. Coba lagi beberapa menit lagi.', 'error');
    header('Location: ../login.php');
    exit;
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$expectedUsername = getenv('ADMIN_USERNAME') ?: 'admin';
$passwordHash = getenv('ADMIN_PASSWORD_HASH');

if (!$passwordHash || !password_get_info($passwordHash)['algo']) {
    error_log('Admin login disabled: ADMIN_PASSWORD_HASH is missing or invalid.');
    admin_flash('Login admin belum dikonfigurasi. Atur ADMIN_PASSWORD_HASH di konfigurasi server.', 'error');
    header('Location: ../login.php');
    exit;
}

$valid = hash_equals($expectedUsername, $username) && password_verify($password, $passwordHash);
if (!$valid) {
    $_SESSION['admin_login_attempts'] = ($_SESSION['admin_login_attempts'] ?? 0) + 1;
    if ($_SESSION['admin_login_attempts'] >= 5) {
        $_SESSION['admin_login_locked_until'] = $now + 300;
        $_SESSION['admin_login_attempts'] = 0;
    }
    admin_flash('Username atau password salah.', 'error');
    header('Location: ../login.php');
    exit;
}

session_regenerate_id(true);
$_SESSION['admin_authenticated'] = true;
$_SESSION['admin_username'] = $expectedUsername;
$_SESSION['admin_login_attempts'] = 0;
unset($_SESSION['admin_login_locked_until'], $_SESSION['admin_csrf_token']);
admin_csrf_token();
header('Location: ../index.php');
exit;
