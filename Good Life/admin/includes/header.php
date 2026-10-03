<?php
$pageTitle = $pageTitle ?? 'Admin Good Life';
$activePage = $activePage ?? '';
$flash = admin_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo admin_h($pageTitle); ?> — Good Life Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Karla:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{--green-900:#16321F;--green-700:#234A2E;--green-500:#3E7A4F;--grey-100:#F4F5F3;--grey-300:#DEE1DB;--grey-500:#9CA39B;--grey-700:#5B615C;--ink:#1A1D1B;--white:#fff;--red:#9c3428;--amber:#936915;--font-display:'Fraunces',serif;--font-body:'Karla',sans-serif;--radius:14px;--shadow:0 12px 30px rgba(22,50,31,.08)}
*{box-sizing:border-box}body{margin:0;background:var(--grey-100);color:var(--ink);font-family:var(--font-body);-webkit-font-smoothing:antialiased}button,input,select,textarea{font:inherit}a{color:inherit;text-decoration:none}button{cursor:pointer}.admin-shell{min-height:100vh;display:grid;grid-template-columns:245px minmax(0,1fr)}.sidebar{background:var(--green-900);color:var(--white);padding:1.5rem 1rem;display:flex;flex-direction:column;gap:2rem}.brand{display:flex;align-items:center;gap:.55rem;min-width:0;padding:.55rem .65rem;background:rgba(255,255,255,.88);border-radius:12px;box-shadow:0 2px 8px rgba(255,255,255,.18)}.brand__mark{width:38px;height:38px;flex:none;object-fit:contain}.brand__copy{display:grid;gap:.15rem;min-width:0}.brand__name{display:block;width:118px;height:auto;object-fit:contain;object-position:left center}.brand small{display:block;font:500 .8rem var(--font-body);color:var(--green-700)}.nav{display:grid;gap:.4rem}.nav a{padding:.8rem .9rem;border-radius:10px;color:#d8e2da}.nav a:hover,.nav a.is-active{background:rgba(255,255,255,.12);color:#fff}.sidebar-bottom{margin-top:auto;display:grid;gap:.7rem;padding:.6rem}.sidebar-bottom a,.sidebar-bottom button{color:#d8e2da;background:none;border:0;text-align:left;padding:.4rem;font-weight:700}.main{min-width:0;padding:2rem clamp(1rem,3vw,2.5rem) 3rem}.page-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.6rem}.page-head h1{font:600 clamp(1.8rem,3vw,2.5rem) var(--font-display);color:var(--green-900);margin:0}.page-head p{color:var(--grey-700);margin:.35rem 0 0}.grid{display:grid;gap:1.1rem}.stats{grid-template-columns:repeat(3,minmax(0,1fr));margin-bottom:1.4rem}.card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);padding:1.25rem}.stat-label{color:var(--grey-700);font-size:.9rem}.stat-value{display:block;font:600 clamp(1.5rem,3vw,2rem) var(--font-display);color:var(--green-900);margin-top:.45rem}.card-head{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem}.card h2{font:600 1.35rem var(--font-display);color:var(--green-900);margin:0}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:700px}th,td{text-align:left;padding:.8rem .65rem;border-bottom:1px solid var(--grey-300);vertical-align:middle}th{font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:var(--grey-700)}td{font-size:.91rem}.muted{color:var(--grey-700)}.badge{display:inline-flex;padding:.3rem .65rem;border-radius:99px;background:var(--grey-100);font-size:.78rem;font-weight:700;color:var(--green-700);white-space:nowrap}.badge--warning{background:#fff3cd;color:var(--amber)}.badge--danger{background:#f8dedb;color:var(--red)}.badge--success{background:#d9eee0;color:#1b6232}.btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid transparent;border-radius:999px;padding:.65rem 1rem;font-weight:700;white-space:nowrap}.btn--primary{background:var(--green-900);color:var(--white)}.btn--secondary{background:var(--white);border-color:var(--grey-300);color:var(--green-900)}.btn--danger{background:#fff;color:var(--red);border-color:#e7c5c0}.btn:disabled{opacity:.55;cursor:not-allowed}.actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.field{display:grid;gap:.4rem}.field--full{grid-column:1/-1}.field label{font-size:.88rem;font-weight:700;color:var(--green-900)}.field input,.field select,.field textarea{width:100%;border:1px solid var(--grey-300);border-radius:9px;background:#fff;padding:.7rem .8rem}.field textarea{min-height:100px;resize:vertical}.field input[type=checkbox]{width:auto}.help{font-size:.8rem;color:var(--grey-700);margin:0}.alert{padding:.8rem 1rem;border-radius:10px;margin-bottom:1rem;background:#d9eee0;color:#1b6232}.alert--error{background:#f8dedb;color:var(--red)}.empty{padding:2rem;text-align:center;color:var(--grey-700)}.thumb{width:66px;height:54px;object-fit:cover;border-radius:8px;background:var(--grey-100)}.inline-form{display:inline}.login-page{min-height:100vh;display:grid;place-items:center;padding:1rem;background:linear-gradient(135deg,var(--green-900),#315f3d)}.login-card{width:min(430px,100%);background:#fff;border-radius:18px;box-shadow:0 25px 70px rgba(0,0,0,.2);padding:clamp(1.5rem,5vw,2.5rem)}.login-card h1{font:600 2rem var(--font-display);color:var(--green-900);margin:.2rem 0}.login-card>p{color:var(--grey-700);margin:.4rem 0 1.5rem}.login-card .field{margin:1rem 0}.login-card .btn{width:100%;margin-top:.5rem}.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.8rem}.gallery-item{position:relative}.gallery-item img{display:block;width:100%;height:130px;object-fit:cover;border-radius:10px}.gallery-item button{margin-top:.4rem}@media(max-width:850px){.admin-shell{grid-template-columns:1fr}.sidebar{padding:.8rem 1rem;gap:.7rem}.brand{padding:.1rem}.brand small{display:inline;margin-left:.4rem}.nav{display:flex;overflow:auto}.nav a{white-space:nowrap;padding:.6rem .8rem}.sidebar-bottom{display:flex;justify-content:flex-end;margin:0;padding:0}.main{padding:1.2rem 1rem 2rem}}@media(max-width:600px){.stats{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.field--full{grid-column:auto}.page-head{flex-direction:column}.card{padding:1rem}}
.sidebar{position:sticky;top:0;align-self:start;height:100vh;min-height:0;overflow-y:auto;background:linear-gradient(rgba(22,50,31,.8),rgba(22,50,31,.8)),url('../assets/gud.png') center/cover no-repeat}
.admin-notifications{display:grid;gap:.5rem;padding:.6rem}
.admin-notifications button{width:100%;white-space:normal}
.admin-notifications__status{margin:0;color:#e3ebe4;font-size:.78rem;line-height:1.4}
.admin-order-toast{position:fixed;z-index:1000;top:1rem;right:1rem;max-width:min(380px,calc(100vw - 2rem));padding:1rem 1.2rem;border-radius:12px;background:var(--green-900);color:#fff;box-shadow:0 12px 30px rgba(0,0,0,.22);font-weight:700}
@media(max-width:850px){.sidebar{position:static;height:auto;min-height:0;overflow:visible}}
</style>
</head>
<body>
<div class="admin-shell">
  <aside class="sidebar">
    <a class="brand" href="index.php">
      <img class="brand__mark" src="../assets/logo.jpeg" alt="">
      <span class="brand__copy">
        <img class="brand__name" src="../assets/text_name.jpeg" alt="Good Life">
        <small>Panel Admin</small>
      </span>
    </a>
    <nav class="nav" aria-label="Navigasi admin">
      <a href="index.php" class="<?php echo $activePage === 'dashboard' ? 'is-active' : ''; ?>">Dashboard</a>
      <a href="pesanan.php" class="<?php echo $activePage === 'pesanan' ? 'is-active' : ''; ?>">Pesanan</a>
      <a href="menu.php" class="<?php echo $activePage === 'menu' ? 'is-active' : ''; ?>">Katalog Menu</a>
      <a href="pengaturan.php" class="<?php echo $activePage === 'pengaturan' ? 'is-active' : ''; ?>">Pengaturan</a>
    </nav>
    <div class="sidebar-bottom">
      <div class="admin-notifications">
        <button class="btn btn--secondary" id="adminNotificationToggle" type="button" data-csrf="<?php echo admin_h(admin_csrf_token()); ?>">Aktifkan notifikasi &amp; suara</button>
        <p class="admin-notifications__status" id="adminNotificationStatus" role="status">Aktifkan untuk menerima pemberitahuan pesanan baru di seluruh halaman admin.</p>
      </div>
      <a href="../beranda.php" target="_blank" rel="noopener">Lihat situs</a>
      <form action="api/logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo admin_h(admin_csrf_token()); ?>">
        <button type="submit">Keluar</button>
      </form>
    </div>
  </aside>
  <main class="main">
    <div class="admin-order-toast" id="adminOrderToast" role="status" aria-live="assertive" hidden></div>
    <?php if ($flash): ?>
      <div class="alert <?php echo $flash['type'] === 'error' ? 'alert--error' : ''; ?>" role="status"><?php echo admin_h($flash['message']); ?></div>
    <?php endif; ?>
