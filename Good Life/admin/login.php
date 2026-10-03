<?php
require __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['admin_authenticated'])) {
    header('Location: index.php');
    exit;
}

$error = admin_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin — Good Life</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Karla:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{--green-900:#16321F;--green-500:#3E7A4F;--grey-100:#F4F5F3;--grey-300:#DEE1DB;--grey-700:#5B615C;--red:#9c3428;--font-display:'Fraunces',serif;--font-body:'Karla',sans-serif}*{box-sizing:border-box}body{margin:0;font-family:var(--font-body)}.login-page{min-height:100vh;display:grid;place-items:center;padding:1rem;background:linear-gradient(135deg,rgba(22,50,31,.82),rgba(49,95,61,.78)),url('../assets/gud.png') center/cover no-repeat}.login-card{width:min(430px,100%);background:#fff;border-radius:18px;box-shadow:0 25px 70px rgba(0,0,0,.2);padding:clamp(1.5rem,5vw,2.5rem)}.login-brand{display:flex;align-items:center;justify-content:center;gap:.7rem;margin:0 auto 1.5rem}.login-brand__mark{width:56px;height:56px;object-fit:contain}.login-brand__name{display:block;width:min(180px,55vw);height:auto}.field{display:grid;gap:.4rem;margin:1rem 0}.field label{font-weight:700;color:var(--green-900)}.field input{width:100%;border:1px solid var(--grey-300);border-radius:9px;padding:.75rem .8rem;font:inherit}.btn{width:100%;border:0;border-radius:999px;padding:.8rem;background:var(--green-900);color:#fff;font:700 1rem var(--font-body);cursor:pointer;margin-top:.5rem}.alert{padding:.8rem 1rem;border-radius:10px;margin:1rem 0;background:#f8dedb;color:var(--red);line-height:1.45}.back{display:inline-block;margin-top:1.2rem;color:var(--green-900);font-weight:700}
</style>
</head>
<body>
<main class="login-page">
  <form class="login-card" action="api/login.php" method="post">
    <div class="login-brand" role="img" aria-label="Good Life">
      <img class="login-brand__mark" src="../assets/logo.jpeg" alt="">
      <img class="login-brand__name" src="../assets/text_name.jpeg" alt="">
    </div>
    <?php if ($error): ?><div class="alert" role="alert"><?php echo admin_h($error['message']); ?></div><?php endif; ?>
    <div class="field">
      <label for="username">Username</label>
      <input type="text" name="username" id="username" autocomplete="username" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" name="password" id="password" autocomplete="current-password" required>
    </div>
    <input type="hidden" name="csrf_token" value="<?php echo admin_h(admin_csrf_token()); ?>">
    <button class="btn" type="submit">Masuk</button>
    <a class="back" href="../beranda.php">Kembali ke situs</a>
  </form>
</main>
</body>
</html>
