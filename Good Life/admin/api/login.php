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
$localConfigPath = ADMIN_ROOT . 'admin-config.local.php';
$localConfig = [];
if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (is_array($loadedConfig)) {
        $localConfig = $loadedConfig;
    } else {
        error_log('Admin login disabled: admin-config.local.php must return an array.');
    }
}

$configuredUsername = getenv('ADMIN_USERNAME');
$configuredPasswordHash = getenv('ADMIN_PASSWORD_HASH');
$localUsername = $localConfig['username'] ?? 'admin';
$localPasswordHash = $localConfig['password_hash'] ?? '';
$expectedUsername = is_string($configuredUsername) && $configuredUsername !== ''
    ? $configuredUsername
    : (is_string($localUsername) && $localUsername !== '' ? $localUsername : 'admin');
$passwordHash = is_string($configuredPasswordHash) && $configuredPasswordHash !== ''
    ? $configuredPasswordHash
    : (is_string($localPasswordHash) ? $localPasswordHash : '');

$valid = false;
try {
    if (strlen($expectedUsername) > 64 || strlen($username) > 64) {
        throw new RuntimeException('Configured admin username exceeds the database limit.');
    }
    $valid = goodlife_db_admin_authenticate($username, $password, $expectedUsername, $passwordHash);
} catch (Throwable $error) {
    error_log('Admin authentication database check failed: ' . $error->getMessage());
    admin_flash('Login admin sedang tidak tersedia. Coba lagi nanti.', 'error');
    header('Location: ../login.php');
    exit;
}
if ($valid === null) {
    error_log('Admin login disabled: no SQL admin user or valid fallback password hash is configured.');
    admin_flash('Login admin belum dikonfigurasi. Atur ADMIN_PASSWORD_HASH atau buat admin di database.', 'error');
    header('Location: ../login.php');
    exit;
}
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
