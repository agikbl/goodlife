<?php
// pesan.php — Halaman pemesanan customer (semua digabung: HTML, CSS, JS, PHP)

$storeDefaults = ['wa' => '6285173087797'];
$storeConfig = json_decode(file_get_contents(__DIR__ . '/data/store.json'), true);
$toko = is_array($storeConfig) ? array_merge($storeDefaults, $storeConfig) : $storeDefaults;
$storeIsOpen = !array_key_exists('is_open', $toko) || $toko['is_open'] === true;
$storeActivity = is_string($toko['activity'] ?? null) ? $toko['activity'] : 'Tutup sementara';
$menu = json_decode(file_get_contents(__DIR__ . '/data/menu.json'), true) ?: [];
$menu = array_map(function ($item) {
    $item['tersedia'] = !array_key_exists('tersedia', $item) || (bool)$item['tersedia'];
    return $item;
}, $menu);

// Sapaan header berdasarkan jam saat ini
$jam = (int) date('G');
if ($jam >= 4 && $jam < 11)       { $sapaan = 'Selamat pagi'; $sub = 'Sarapan enak nggak harus ribet, biar Good Life yang urus.'; }
elseif ($jam >= 11 && $jam < 15)  { $sapaan = 'Selamat siang'; $sub = 'Waktunya istirahat makan siang. Mau kebab atau burger dulu?'; }
elseif ($jam >= 15 && $jam < 18)  { $sapaan = 'Selamat sore';  $sub = 'Sore-sore gini paling pas ngemil kebab hangat.'; }
else                              { $sapaan = 'Selamat malam'; $sub = 'Lapar tengah malam? Tenang, kami masih buka.'; }

$categoryConfig = json_decode(file_get_contents(__DIR__ . '/data/categories.json'), true);
$categoryRecords = is_array($categoryConfig) ? array_values(array_filter($categoryConfig, function ($category) {
    return is_array($category) && isset($category['id'], $category['nama']) &&
        in_array($category['kelompok'] ?? '', ['makanan', 'minuman'], true);
})) : [];
$kategoriLabel = [];
$categoryGroupById = [];
foreach ($categoryRecords as $category) {
    $kategoriLabel[$category['id']] = $category['nama'];
    $categoryGroupById[$category['id']] = $category['kelompok'];
}
$kategoriLabel['extra_topping_makanan'] = 'Extra Topping (Makanan)';
$kategoriLabel['extra_topping_minuman'] = 'Extra Topping (Minuman)';

  $kategoriGambar = [
    'kebab' => 'https://images.unsplash.com/photo-1529006557810-274b9b2fc783?auto=format&fit=crop&w=640&q=80',
    'kebab_pisang' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=640&q=80',
    'piscok' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=640&q=80',
    'burger' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=640&q=80',
    'cemilan_series' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=640&q=80',
    'aneka_nasi' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=640&q=80',
    'extra_topping_makanan' => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?auto=format&fit=crop&w=640&q=80',
    'basic_milk' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=640&q=80',
    'basic_coffee' => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=640&q=80',
    'tea_series' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=640&q=80',
    'signature_series' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=640&q=80',
    'extra_topping_minuman' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=640&q=80',
  ];

$kelompokMenu = [
  'makanan' => [
    'judul' => 'Aneka Makanan',
    'kategori' => [],
  ],
  'minuman' => [
    'judul' => 'Aneka Minuman',
    'kategori' => [],
  ],
];
foreach ($categoryRecords as $category) {
  $kelompokMenu[$category['kelompok']]['kategori'][] = $category['id'];
}

$opsiTopping = [
  'makanan' => array_values(array_filter($menu, function ($item) {
    return ($item['kategori'] ?? '') === 'extra_topping_makanan' && $item['tersedia'];
  })),
  'minuman' => array_values(array_filter($menu, function ($item) {
    return ($item['kategori'] ?? '') === 'extra_topping_minuman' && $item['tersedia'];
  })),
];

$menuTerbagi = [];
foreach ($kelompokMenu as $keyKelompok => $kelompok) {
  $menuTerbagi[$keyKelompok] = [];
  foreach ($kelompok['kategori'] as $kategori) {
    $menuTerbagi[$keyKelompok][$kategori] = array_values(array_filter($menu, function ($item) use ($kategori) {
      return ($item['kategori'] ?? '') === $kategori;
    }));
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesan — Good Life Parepare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Karla:wght@400;500;700&display=swap" rel="stylesheet">
<link href="assets/site-motion.css" rel="stylesheet">

<style>
:root{
  --green-900:#16321F;
  --green-700:#234A2E;
  --green-500:#3E7A4F;
  --grey-100:#F4F5F3;
  --grey-300:#DEE1DB;
  --grey-500:#9CA39B;
  --grey-700:#5B615C;
  --ink:#1A1D1B;
  --white:#FFFFFF;
  --font-display:'Fraunces', serif;
  --font-body:'Karla', sans-serif;
  --radius:14px;
  --shadow:0 12px 30px rgba(22,50,31,0.16);
}
*{box-sizing:border-box;}
html{scroll-behavior:smooth; scrollbar-gutter:stable;}
html.is-modal-open{overflow:hidden; overscroll-behavior:none;}
body{margin:0; font-family:var(--font-body); color:var(--ink); background:var(--white); -webkit-font-smoothing:antialiased; padding-bottom:76px;}
a{color:inherit; text-decoration:none;}
h1,h2,h3{font-family:var(--font-display); margin:0;}
button{font-family:var(--font-body);}

.btn{display:inline-flex; align-items:center; justify-content:center; padding:0.8rem 1.5rem; border-radius:999px; font-weight:500; font-size:0.92rem; border:1px solid transparent; cursor:pointer; transition:transform .15s ease, background .2s ease, color .2s ease; white-space:nowrap;}
.btn:active{transform:scale(0.97);}
.btn--solid{background:var(--green-900); color:var(--white);}
.btn--solid:hover{background:var(--green-700);}
.btn--solid:disabled{background:var(--grey-300); color:var(--grey-500); cursor:not-allowed;}
.btn--outline{background:transparent; color:var(--green-900); border-color:var(--green-900);}
.btn--outline:hover{background:var(--green-900); color:var(--white);}
.btn--outline-light{background:transparent; color:var(--white); border-color:rgba(255,255,255,0.6);}
.btn--outline-light:hover{background:var(--white); color:var(--green-900); border-color:var(--white);}
.btn--ghost{background:transparent; color:var(--grey-700);}
.btn--block{width:100%;}

/* Navbar */
.gl-navbar{position:sticky; top:0; z-index:60; background:rgba(255,255,255,0.9); backdrop-filter:blur(8px); border-bottom:1px solid var(--grey-300);}
.gl-navbar__inner{max-width:1180px; margin:0 auto; padding:0.9rem 1.5rem; display:flex; align-items:center; justify-content:space-between;}
.gl-navbar__logo{display:flex; align-items:center; gap:0.5rem; font-family:var(--font-display); font-weight:700; font-size:1.15rem; color:var(--green-900);}
.gl-navbar__logo-image{width:40px; height:40px; object-fit:contain;}
.gl-navbar__logo-name{width:112px; height:auto; object-fit:contain;}
.gl-navbar__links{list-style:none; display:flex; gap:2rem; margin:0; padding:0;}
.gl-navbar__links a{font-size:0.95rem; color:var(--grey-700); padding-bottom:4px; border-bottom:2px solid transparent;}
.gl-navbar__links a.is-active, .gl-navbar__links a:hover{color:var(--green-900); border-color:var(--green-500);}
.gl-navbar__toggle{display:none; background:none; border:none; flex-direction:column; gap:5px; cursor:pointer;}
.gl-navbar__toggle span{width:22px; height:2px; background:var(--green-900);}
@media (max-width:760px){
  .gl-navbar__toggle{display:flex;}
  .gl-navbar__links{position:absolute; top:100%; left:0; right:0; background:var(--white); flex-direction:column; gap:0; max-height:0; overflow:hidden; border-bottom:1px solid var(--grey-300); transition:max-height .25s ease;}
  .gl-navbar__links.is-open{max-height:260px;}
  .gl-navbar__links li{border-top:1px solid var(--grey-100);}
  .gl-navbar__links a{display:block; padding:0.9rem 1.5rem;}
}
@media (max-width:380px){
  .gl-navbar__inner{padding:0.65rem 1rem;}
  .gl-navbar__logo{gap:0.4rem;}
  .gl-navbar__logo-image{width:36px; height:36px;}
  .gl-navbar__logo-name{width:96px;}
}

/* Greeting header */
.greet{background:var(--green-900); color:var(--white); padding:2.6rem 1.5rem 2.2rem;}
.greet__inner{max-width:1180px; margin:0 auto; display:flex; align-items:flex-end; justify-content:space-between; gap:1.5rem; flex-wrap:wrap;}
.greet__title{font-size:clamp(1.6rem, 3.5vw, 2.2rem); font-weight:600; margin-bottom:0.4rem;}
.greet__sub{color:var(--grey-100); opacity:0.85; font-size:0.98rem; max-width:52ch;}
.greet__cek{flex-shrink:0;}
.store-closed-notice{max-width:1180px;margin:1rem auto 0;padding:.9rem 1.2rem;border:1px solid #e7c5c0;border-radius:12px;background:#fff4f2;color:#7a2c22;line-height:1.5}
.store-closed-notice strong{display:block}
body.is-store-closed .menu-card{opacity:.62;cursor:not-allowed}

/* Menu grid */
.menu-grid{max-width:1180px; margin:0 auto; padding:1rem 0 2.5rem; display:grid; grid-template-columns:repeat(auto-fill, minmax(185px, 210px)); justify-content:start; gap:0.85rem;}
.menu-jump{display:flex; justify-content:center; gap:2rem; padding:0 1.5rem; background:var(--white); border-bottom:1px solid var(--grey-300);}
.menu-jump button{padding:0.9rem 0.2rem 0.75rem; border:0; border-bottom:3px solid transparent; border-radius:0; background:transparent; color:var(--grey-700); text-align:center; font-weight:700; cursor:pointer; transition:color .15s ease, border-color .15s ease;}
.menu-jump button:hover,.menu-jump button.is-active{color:var(--green-900);}
.menu-jump button.is-active{border-bottom-color:var(--green-500);}
.menu-jump button:focus-visible{outline:2px solid var(--green-500); outline-offset:3px;}
.menu-section{max-width:1180px; margin:0 auto; padding:2rem 1.5rem 0; scroll-margin-top:145px;}
.menu-section__title{font-size:2rem; color:var(--green-900); padding-bottom:0.75rem; border-bottom:2px solid var(--grey-300);}
.menu-category{padding-top:1.35rem;}
.menu-category__title{font-family:var(--font-body); font-size:1.1rem; color:var(--ink); margin:0;}
.menu-category .menu-grid{padding:0.75rem 0 0.5rem;}
.menu-category:last-child .menu-grid{padding-bottom:1.5rem;}
.menu-card{min-width:0; background:var(--white); border:1px solid var(--grey-300); border-radius:10px; overflow:hidden; cursor:pointer; transition:border-color .15s ease, box-shadow .15s ease; display:flex; flex-direction:column;}
.menu-card:hover{border-color:var(--green-500); box-shadow:0 5px 14px rgba(22,50,31,0.08);}
.menu-card.is-unavailable{opacity:.62; cursor:not-allowed;}
.menu-card.is-unavailable:hover{border-color:var(--grey-300); box-shadow:none;}
.menu-card.is-selected{border:2px solid var(--green-700); box-shadow:0 0 0 2px rgba(35,74,46,0.12);}
.menu-card__image{display:block; width:100%; aspect-ratio:16 / 10; object-fit:cover; background:var(--grey-100);}
.menu-card__body{padding:0.85rem; display:flex; flex-direction:column; gap:0.35rem; flex:1;}
.menu-card__name{font-weight:700; font-size:0.95rem; line-height:1.35;}
.menu-card__desc{font-size:0.78rem; color:var(--grey-700); line-height:1.4; margin:0;}
.menu-card__footer{display:flex; align-items:center; justify-content:space-between; margin-top:0.3rem;}
.menu-card__price{font-weight:700; color:var(--green-900);}
.menu-card__badge{font-size:0.7rem; padding:0.2rem 0.55rem; border-radius:999px; background:var(--grey-100); color:var(--green-700); font-weight:700;}
@media (max-width:600px){
  .menu-jump{gap:1.5rem; padding:0 1rem;}
  .menu-jump button{padding:0.8rem 0.2rem 0.65rem;}
  .menu-section{padding:1.5rem 1rem 0; scroll-margin-top:120px;}
  .menu-section__title{font-size:1.6rem;}
  .menu-category .menu-grid{grid-template-columns:repeat(2, minmax(0, 1fr)); gap:0.65rem;}
  .menu-card__body{padding:0.75rem;}
  .menu-card__footer{align-items:flex-start; flex-direction:column; gap:0.35rem;}
}

/* Overlay umum (item modal, cart drawer, cek pesanan) */
.overlay-bg{position:fixed; inset:0; background:rgba(22,50,31,0.45); z-index:90; display:none;}
.overlay-bg.is-open{display:block;}

/* Item modal */
.item-modal{position:fixed; left:50%; top:50%; transform:translate(-50%,-50%); width:min(440px, 92vw); max-height:88vh; overflow-y:auto; overscroll-behavior:contain; -webkit-overflow-scrolling:touch; background:var(--white); border-radius:var(--radius); z-index:100; display:none; box-shadow:var(--shadow);}
.item-modal.is-open{display:block;}
.item-modal__img{display:block; width:100%; height:190px; object-fit:cover; background:var(--grey-100);}
.item-modal__body{padding:1.4rem 1.5rem 1.6rem; display:flex; flex-direction:column; gap:1rem;}
.item-modal__name{font-size:1.25rem;}
.item-modal__desc{font-size:0.88rem; color:var(--grey-700); line-height:1.5; margin:0;}
.item-modal__row{display:flex; align-items:center; justify-content:space-between; gap:1rem;}
.qty-stepper{display:flex; align-items:center; gap:0.7rem; background:var(--grey-100); border-radius:999px; padding:0.35rem 0.5rem;}
.qty-stepper button{width:30px; height:30px; border-radius:50%; border:none; background:var(--white); color:var(--green-900); font-size:1.1rem; cursor:pointer; display:flex; align-items:center; justify-content:center;}
.qty-stepper span{min-width:1.5rem; text-align:center; font-weight:700;}
.item-modal__subtotal{font-weight:700; color:var(--green-900); font-size:1.05rem;}
.item-toppings{display:flex; flex-direction:column; gap:0.65rem;}
.item-toppings[hidden]{display:none;}
.item-toppings__title{font-size:0.9rem; font-weight:700; color:var(--green-900);}
.item-toppings__option{display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding:0.65rem 0.75rem; border:1px solid var(--grey-300); border-radius:8px; background:var(--white); cursor:pointer;}
.item-toppings__option span{display:flex; align-items:center; gap:0.55rem;}
.item-toppings__option input{accent-color:var(--green-700); width:17px; height:17px;}
.item-toppings__price{font-size:0.85rem; color:var(--grey-700); white-space:nowrap;}
.item-modal__row label{font-size:0.85rem; font-weight:700; color:var(--green-900); display:block; margin-bottom:0.4rem;}
.item-modal textarea{width:100%; border:1px solid var(--grey-300); border-radius:10px; padding:0.7rem 0.9rem; font-family:var(--font-body); font-size:0.92rem; resize:vertical;}
.item-modal__close{position:absolute; top:0.8rem; right:0.9rem; background:rgba(255,255,255,0.95); border:1px solid #1A1D1B; width:30px; height:30px; border-radius:50%; cursor:pointer; font-size:1.1rem; z-index:2;}

/* Sticky cart bar */
.cart-bar{position:fixed; left:0; right:0; bottom:0; z-index:70; background:var(--green-900); color:var(--white); padding:0.9rem 1.5rem; display:none; align-items:center; justify-content:space-between; box-shadow:0 -6px 20px rgba(0,0,0,0.15); cursor:pointer;}
.cart-bar.is-visible{display:flex;}
.cart-bar__info{display:flex; flex-direction:column; gap:0.1rem;}
.cart-bar__count{font-size:0.78rem; opacity:0.8;}
.cart-bar__total{font-weight:700; font-size:1.1rem; font-family:var(--font-display);}
.cart-bar .btn{position:relative; z-index:2;}

/* Cart drawer */
.cart-drawer{position:fixed; right:0; top:0; bottom:0; width:min(420px, 100vw); background:var(--white); z-index:100; transform:translateX(100%); transition:transform .25s ease; display:flex; flex-direction:column;}
.cart-drawer.is-open{transform:translateX(0);}
.cart-drawer__head{padding:1.4rem 1.5rem; border-bottom:1px solid var(--grey-300); display:flex; align-items:center; justify-content:space-between;}
.cart-drawer__body{flex:1; overflow-y:auto; padding:1rem 1.5rem;}
.cart-drawer__foot{padding:1.2rem 1.5rem 1.5rem; border-top:1px solid var(--grey-300);}
.cart-drawer__empty{color:var(--grey-500); text-align:center; padding:3rem 1rem; font-size:0.92rem;}

.cart-line{display:flex; gap:0.9rem; padding:1rem 0; border-bottom:1px solid var(--grey-100);}
.cart-line__thumb{width:52px; height:52px; border-radius:10px; background:var(--grey-100); object-fit:cover; flex-shrink:0;}
.cart-line__body{flex:1; display:flex; flex-direction:column; gap:0.25rem;}
.cart-line__top{display:flex; justify-content:space-between; gap:0.5rem;}
.cart-line__name{font-weight:700; font-size:0.92rem;}
.cart-line__note{font-size:0.78rem; color:var(--grey-500); font-style:italic;}
.cart-line__bottom{display:flex; align-items:center; justify-content:space-between; margin-top:0.3rem;}
.cart-line__subtotal{font-weight:700; font-size:0.9rem; color:var(--green-900);}
.cart-line__remove{background:none; border:none; color:var(--grey-500); font-size:0.78rem; cursor:pointer; text-decoration:underline;}
.qty-stepper--sm button{width:24px; height:24px; font-size:0.95rem;}

.grand-total-row{display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; font-size:1.05rem;}
.grand-total-row strong{font-family:var(--font-display); color:var(--green-900); font-size:1.3rem;}

/* Checkout modal (multi-step) */
/* Cek Pesanan modal */
.cek-modal{position:fixed; left:50%; top:50%; transform:translate(-50%,-50%); width:min(480px, 92vw); max-height:88vh; overflow-y:auto; background:var(--white); border-radius:var(--radius); z-index:110; display:none; box-shadow:var(--shadow);}
.cek-modal.is-open{display:block;}
.cek-modal__head{padding:1.3rem 1.5rem 0.5rem; display:flex; align-items:center; justify-content:space-between;}
.cek-modal__body{padding:0.5rem 1.5rem 1.6rem;}
.cek-search{display:flex; gap:0.6rem; margin-bottom:0.4rem;}
.cek-search input{flex:1; border:1px solid var(--grey-300); border-radius:10px; padding:0.75rem 0.9rem; font-family:var(--font-body); font-size:0.92rem;}
.cek-hint{font-size:0.78rem; color:var(--grey-500); margin:0 0 1.2rem;}
.cek-msg{font-size:0.9rem; color:var(--grey-700); text-align:center; padding:1.5rem 0;}
.cek-msg.is-error{color:#8A3B2B;}

.cek-result{border:1px solid var(--grey-300); border-radius:var(--radius); padding:1.2rem; margin-bottom:1rem;}
.cek-result__head{display:flex; justify-content:space-between; align-items:center; gap:0.5rem; margin-bottom:0.3rem;}
.cek-result__id{font-family:var(--font-display); font-size:1.1rem; color:var(--green-900);}
.cek-result__date{font-size:0.78rem; color:var(--grey-500); margin-bottom:0.9rem;}
.cek-pay-badge{font-size:0.72rem; padding:0.25rem 0.6rem; border-radius:999px; font-weight:700; white-space:nowrap;}
.cek-pay-badge--lunas{background:#d1e7dd; color:#0f5132;}
.cek-pay-badge--pending{background:#fff3cd; color:#997404;}
.cek-result__items{font-size:0.85rem; color:var(--grey-700); margin:0 0 0.9rem; padding-left:1.1rem;}
.cek-result__items li{margin-bottom:0.2rem;}
.cek-result__total{display:flex; justify-content:space-between; font-size:0.95rem; font-weight:700; color:var(--green-900); border-top:1px solid var(--grey-100); padding-top:0.7rem;}
.cek-result__meta{display:grid; gap:0.45rem; margin:0.9rem 0; font-size:0.85rem;}
.cek-result__meta div{display:flex; justify-content:space-between; gap:1rem;}
.cek-result__meta dt{color:var(--grey-700);}
.cek-result__meta dd{margin:0; text-align:right; font-weight:700;}
.cek-results{margin-top:1rem;}
.order-tracker{display:flex; gap:.4rem; margin:.9rem 0 1.1rem;}
.order-tracker__step{flex:1; min-width:0; text-align:center; color:var(--grey-500); font-size:.72rem;}
.order-tracker__dot{width:16px; height:16px; margin:0 auto .35rem; border-radius:50%; background:var(--grey-300);}
.order-tracker__step.is-complete{color:var(--green-700); font-weight:700;}
.order-tracker__step.is-complete .order-tracker__dot{background:var(--green-500);}
.order-tracker__step.is-current{color:var(--green-900); font-weight:700;}
.order-tracker__step.is-current .order-tracker__dot{background:var(--green-900); box-shadow:0 0 0 3px rgba(62,122,79,.2);}
.order-review{border-top:1px solid var(--grey-100); padding-top:1rem; display:grid; gap:.65rem;}
.order-review label{font-size:.82rem; font-weight:700; color:var(--green-900);}
.order-review input,.order-review select,.order-review textarea{width:100%; border:1px solid var(--grey-300); border-radius:8px; padding:.65rem .75rem; font:inherit;}
.order-review textarea{min-height:76px; resize:vertical;}
.order-review__message{margin:0; font-size:.85rem;}
.order-review__message.is-error{color:#8A3B2B;}
.order-review__message.is-ok{color:var(--green-700); font-weight:700;}
.cek-refresh{font-size:.75rem; color:var(--grey-500); text-align:right; margin:.4rem 0;}
</style>
</head>
<body class="<?php echo $storeIsOpen ? '' : 'is-store-closed'; ?>">

<!-- NAVBAR -->
<nav class="gl-navbar" id="glNavbar">
  <div class="gl-navbar__inner">
    <a href="beranda.php" class="gl-navbar__logo">
      <img class="gl-navbar__logo-image" src="assets/logo.jpeg" alt="">
      <img class="gl-navbar__logo-name" src="assets/text_name.jpeg" alt="Good Life">
    </a>
    <button class="gl-navbar__toggle" id="glNavToggle" aria-label="Buka menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <ul class="gl-navbar__links" id="glNavLinks">
      <li><a href="beranda.php">Beranda</a></li>
      <li><a href="pesan.php" class="is-active">Pesan</a></li>
      <li><a href="faq.php">FAQ</a></li>
      <li><a href="support.php">Support</a></li>
    </ul>
  </div>
</nav>

<!-- GREETING HEADER -->
<section class="greet">
  <div class="greet__inner">
    <div>
      <h1 class="greet__title"><?php echo $sapaan; ?>! Mau pesan apa hari ini?</h1>
      <p class="greet__sub"><?php echo $sub; ?></p>
    </div>
    <button type="button" class="btn btn--outline-light greet__cek" id="btnCekPesanan">Cek Pesanan</button>
  </div>
</section>

<?php if (!$storeIsOpen): ?>
<aside class="store-closed-notice" role="status">
  <strong>Toko sedang tutup sementara</strong>
  <span><?php echo htmlspecialchars($storeActivity, ENT_QUOTES, 'UTF-8'); ?>. Kami belum menerima pesanan baru saat ini.</span>
</aside>
<?php endif; ?>

<!-- NAVIGASI SECTION MENU -->
<nav class="menu-jump" aria-label="Pilih bagian menu">
  <button type="button" class="is-active" data-menu-target="makanan" aria-pressed="true">Makanan</button>
  <button type="button" data-menu-target="minuman" aria-pressed="false">Minuman</button>
</nav>

<!-- MENU MAKANAN DAN MINUMAN -->
<?php foreach ($kelompokMenu as $keyKelompok => $kelompok): ?>
  <section class="menu-section" id="<?php echo $keyKelompok; ?>"<?php echo $keyKelompok === 'minuman' ? ' hidden' : ''; ?>>
    <h2 class="menu-section__title"><?php echo $kelompok['judul']; ?></h2>
    <?php foreach ($kelompok['kategori'] as $kategori): ?>
      <?php if (!$menuTerbagi[$keyKelompok][$kategori]) continue; ?>
      <section class="menu-category">
        <h3 class="menu-category__title"><?php echo htmlspecialchars($kategoriLabel[$kategori], ENT_QUOTES, 'UTF-8'); ?></h3>
        <div class="menu-grid">
          <?php foreach ($menuTerbagi[$keyKelompok][$kategori] as $item): ?>
            <div class="menu-card<?php echo $item['tersedia'] ? '' : ' is-unavailable'; ?>" data-id="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-nama="<?php echo htmlspecialchars($item['nama']); ?>"
                 data-harga="<?php echo $item['harga']; ?>"
                data-image="<?php echo htmlspecialchars($item['gambar'] ?? ($kategoriGambar[$item['kategori']] ?? ($keyKelompok === 'makanan' ? 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=640&q=80' : 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=640&q=80')), ENT_QUOTES, 'UTF-8'); ?>"
                 data-available="<?php echo $item['tersedia'] ? '1' : '0'; ?>"
                 data-topping-type="<?php echo $keyKelompok; ?>"
                 data-toppings="<?php echo htmlspecialchars(json_encode(array_map(function ($topping) { return ['id' => $topping['id'], 'nama' => $topping['nama'], 'harga' => (int)$topping['harga']]; }, $opsiTopping[$keyKelompok]), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"
                 data-desc="<?php echo htmlspecialchars($kategoriLabel[$item['kategori']] ?? ''); ?>">
              <img class="menu-card__image" src="<?php echo htmlspecialchars($item['gambar'] ?? ($kategoriGambar[$item['kategori']] ?? ($keyKelompok === 'makanan' ? 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=640&q=80' : 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=640&q=80')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
              <div class="menu-card__body">
                <span class="menu-card__name"><?php echo htmlspecialchars($item['nama']); ?></span>
                <div class="menu-card__footer">
                  <span class="menu-card__price">Rp<?php echo number_format($item['harga'], 0, ',', '.'); ?></span>
                  <?php if (!$item['tersedia']): ?><span class="menu-card__badge menu-card__badge--soldout">Habis</span><?php endif; ?>
                  <?php if ($item['favorit']): ?><span class="menu-card__badge">Favorit</span><?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </section>
<?php endforeach; ?>

<!-- OVERLAY UMUM -->
<div class="overlay-bg" id="overlayBg"></div>

<!-- ITEM MODAL -->
<div class="item-modal" id="itemModal">
  <button class="item-modal__close" id="itemModalClose">&times;</button>
  <img class="item-modal__img" id="imImage" alt="">
  <div class="item-modal__body">
    <h3 class="item-modal__name" id="imName"></h3>
    <p class="item-modal__desc" id="imDesc"></p>

    <div class="item-modal__row">
      <div class="qty-stepper" id="imQtyStepper">
        <button type="button" id="imQtyMinus">−</button>
        <span id="imQtyValue">1</span>
        <button type="button" id="imQtyPlus">+</button>
      </div>
      <span class="item-modal__subtotal" id="imSubtotal"></span>
    </div>

    <div class="item-toppings" id="imToppings" hidden>
      <span class="item-toppings__title">Tambah topping (opsional)</span>
      <div id="imToppingOptions"></div>
    </div>

    <div class="item-modal__row" style="display:block;">
      <label for="imNotes">Catatan (opsional)</label>
      <textarea id="imNotes" rows="2" placeholder="Contoh: kurangi gula, banyakin sambal, jangan pedas"></textarea>
    </div>

    <button class="btn btn--solid btn--block" id="imAddBtn">Tambah ke Keranjang</button>
  </div>
</div>

<!-- STICKY CART BAR -->
<div class="cart-bar" id="cartBar">
  <div class="cart-bar__info">
    <span class="cart-bar__count" id="cartBarCount">0 item</span>
    <span class="cart-bar__total" id="cartBarTotal">Rp0</span>
  </div>
  <button class="btn btn--light" id="cartBarViewBtn" style="background:#fff;color:var(--green-900);">Lihat pesanan</button>
</div>

<!-- CART DRAWER -->
<div class="cart-drawer" id="cartDrawer">
  <div class="cart-drawer__head">
    <h3>Pesananmu</h3>
    <button class="item-modal__close" id="cartDrawerClose" style="position:static;">&times;</button>
  </div>
  <div class="cart-drawer__body" id="cartDrawerBody"></div>
  <div class="cart-drawer__foot" id="cartDrawerFoot">
    <div class="grand-total-row">
      <span>Total belanja</span>
      <strong id="cartGrandTotal">Rp0</strong>
    </div>
    <button class="btn btn--solid btn--block" id="btnLanjutBayar">Lanjut ke Pembayaran</button>
  </div>
</div>

<!-- CEK PESANAN MODAL -->
<div class="cek-modal" id="cekModal" role="dialog" aria-modal="true" aria-labelledby="cekTitle" hidden>
  <div class="cek-modal__head">
    <h3 id="cekTitle">Cek Pesanan</h3>
    <button type="button" class="item-modal__close" id="cekModalClose" aria-label="Tutup" style="position:static;">&times;</button>
  </div>
  <div class="cek-modal__body">
    <form class="cek-search" id="cekForm">
      <input type="text" id="cekLookup" placeholder="Nomor WhatsApp atau ID pesanan" autocomplete="off" required>
      <button type="submit" class="btn btn--solid" id="cekSubmit">Cari</button>
    </form>
    <p class="cek-hint">Gunakan nomor WhatsApp yang dipakai saat memesan, atau ID pesanan.</p>
    <p class="cek-msg" id="cekMsg" role="status" aria-live="polite" hidden></p>
    <p class="cek-refresh" id="cekRefresh" hidden></p>
    <div class="cek-results" id="cekResults"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ================= State ================= */
  var cart = []; // { id, nama, harga, qty, notes }
  var storeIsOpen = <?php echo $storeIsOpen ? 'true' : 'false'; ?>;
  var currentItem = null; // item yang lagi dibuka di modal
  var currentQty = 1;
  var currentToppings = [];

  var rupiah = function (n) { return 'Rp' + Math.round(n).toLocaleString('id-ID'); };

  /* ================= Navbar mobile ================= */
  var toggle = document.getElementById('glNavToggle');
  var links = document.getElementById('glNavLinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      links.classList.toggle('is-open');
    });
  }

  var menuSections = document.querySelectorAll('.menu-section');
  document.querySelectorAll('.menu-jump button').forEach(function (button) {
    button.addEventListener('click', function () {
      var targetId = button.dataset.menuTarget;
      menuSections.forEach(function (section) {
        section.hidden = section.id !== targetId;
      });
      document.querySelectorAll('.menu-jump button').forEach(function (item) {
        var isActive = item === button;
        item.classList.toggle('is-active', isActive);
        item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    });
  });

  var cards = document.querySelectorAll('.menu-card');
  /* ================= Item modal ================= */
  var overlayBg = document.getElementById('overlayBg');
  var itemModal = document.getElementById('itemModal');
  var imImage = document.getElementById('imImage');
  var imName = document.getElementById('imName');
  var imDesc = document.getElementById('imDesc');
  var imQtyValue = document.getElementById('imQtyValue');
  var imSubtotal = document.getElementById('imSubtotal');
  var imNotes = document.getElementById('imNotes');
  var imToppings = document.getElementById('imToppings');
  var imToppingOptions = document.getElementById('imToppingOptions');
  var itemModalScrollLock = null;

  function lockPageScroll() {
    if (itemModalScrollLock) return;
    var body = document.body;
    var scrollY = window.scrollY;
    var scrollBehavior = document.documentElement.style.scrollBehavior;
    document.documentElement.style.scrollBehavior = 'auto';
    window.scrollTo(0, scrollY);
    document.documentElement.style.scrollBehavior = scrollBehavior;
    itemModalScrollLock = {
      scrollY: scrollY,
      position: body.style.position,
      top: body.style.top,
      left: body.style.left,
      right: body.style.right,
      width: body.style.width,
      scrollBehavior: document.documentElement.style.scrollBehavior
    };
    document.documentElement.classList.add('is-modal-open');
    body.style.position = 'fixed';
    body.style.top = '-' + itemModalScrollLock.scrollY + 'px';
    body.style.left = '0';
    body.style.right = '0';
    body.style.width = '100%';
  }

  function unlockPageScroll() {
    if (!itemModalScrollLock) return;
    var body = document.body;
    var previous = itemModalScrollLock;
    itemModalScrollLock = null;
    document.documentElement.classList.remove('is-modal-open');
    body.style.position = previous.position;
    body.style.top = previous.top;
    body.style.left = previous.left;
    body.style.right = previous.right;
    body.style.width = previous.width;
    document.documentElement.style.scrollBehavior = 'auto';
    window.scrollTo(0, previous.scrollY);
    document.documentElement.style.scrollBehavior = previous.scrollBehavior;
  }

  function syncSelectedCards() {
    cards.forEach(function (card) {
      var isSelected = cart.some(function (item) { return item.id === card.dataset.id; });
      card.classList.toggle('is-selected', isSelected);
      card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
    });
  }

  function openOverlay() { overlayBg.classList.add('is-open'); }
  function closeOverlayIfNothingOpen() {
    if (!itemModal.classList.contains('is-open') &&
        !document.getElementById('cartDrawer').classList.contains('is-open') &&
        !document.getElementById('cekModal').classList.contains('is-open')) {
      overlayBg.classList.remove('is-open');
    }
  }

  function updateItemModalSubtotal() {
    var toppingTotal = currentToppings.reduce(function (sum, topping) { return sum + topping.harga; }, 0);
    imSubtotal.textContent = rupiah((currentItem.harga + toppingTotal) * currentQty);
    imQtyValue.textContent = currentQty;
  }

  function renderToppingOptions(options) {
    imToppingOptions.innerHTML = '';
    imToppings.hidden = options.length === 0;
    currentToppings = [];
    options.forEach(function (topping) {
      var label = document.createElement('label');
      label.className = 'item-toppings__option';
      var nameGroup = document.createElement('span');
      var checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.value = topping.id;
      checkbox.dataset.name = topping.nama;
      checkbox.dataset.price = topping.harga;
      nameGroup.appendChild(checkbox);
      nameGroup.appendChild(document.createTextNode(topping.nama));
      var price = document.createElement('span');
      price.className = 'item-toppings__price';
      price.textContent = '+' + rupiah(topping.harga);
      label.appendChild(nameGroup);
      label.appendChild(price);
      imToppingOptions.appendChild(label);
    });
  }

  imToppingOptions.addEventListener('change', function (event) {
    if (event.target.type !== 'checkbox') return;
    var checkbox = event.target;
    if (checkbox.checked) {
      currentToppings.push({ id: checkbox.value, nama: checkbox.dataset.name, harga: Number(checkbox.dataset.price) });
    } else {
      currentToppings = currentToppings.filter(function (topping) { return topping.id !== checkbox.value; });
    }
    updateItemModalSubtotal();
  });

  cards.forEach(function (card) {
    card.addEventListener('click', function () {
      if (!storeIsOpen) return;
      if (card.dataset.available === '0') return;
      currentItem = {
        id: card.dataset.id,
        nama: card.dataset.nama,
        harga: parseFloat(card.dataset.harga),
        image: card.dataset.image,
        toppingType: card.dataset.toppingType,
        desc: card.dataset.desc
      };
      currentQty = 1;
      renderToppingOptions(JSON.parse(card.dataset.toppings || '[]'));
      imName.textContent = currentItem.nama;
      imDesc.textContent = currentItem.desc;
      imImage.src = currentItem.image;
      imImage.alt = currentItem.nama;
      imNotes.value = '';
      updateItemModalSubtotal();
      lockPageScroll();
      itemModal.classList.add('is-open');
      openOverlay();
    });
  });

  document.getElementById('imQtyMinus').addEventListener('click', function () {
    if (currentQty > 1) { currentQty--; updateItemModalSubtotal(); }
  });
  document.getElementById('imQtyPlus').addEventListener('click', function () {
    currentQty++; updateItemModalSubtotal();
  });

  function closeItemModal() {
    if (!itemModal.classList.contains('is-open')) return;
    itemModal.classList.remove('is-open');
    unlockPageScroll();
    closeOverlayIfNothingOpen();
  }
  document.getElementById('itemModalClose').addEventListener('click', closeItemModal);

  document.getElementById('imAddBtn').addEventListener('click', function () {
    var notes = imNotes.value.trim();
    var toppings = currentToppings.slice();
    var toppingIds = toppings.map(function (topping) { return topping.id; }).sort().join(',');
    var itemPrice = currentItem.harga + toppings.reduce(function (sum, topping) { return sum + topping.harga; }, 0);
    var existing = cart.find(function (c) {
      var existingToppingIds = (c.toppings || []).map(function (topping) { return topping.id; }).sort().join(',');
      return c.id === currentItem.id && c.notes === notes && existingToppingIds === toppingIds;
    });
    if (existing) {
      existing.qty += currentQty;
    } else {
      cart.push({ id: currentItem.id, nama: currentItem.nama, harga: itemPrice, base_harga: currentItem.harga, image: currentItem.image, qty: currentQty, notes: notes, toppings: toppings });
    }
    syncSelectedCards();
    closeItemModal();
    renderCartBar();
  });

  /* ================= Sticky cart bar ================= */
  var cartBar = document.getElementById('cartBar');
  var cartBarCount = document.getElementById('cartBarCount');
  var cartBarTotal = document.getElementById('cartBarTotal');

  function cartTotals() {
    var count = 0, total = 0;
    cart.forEach(function (c) { count += c.qty; total += c.qty * c.harga; });
    return { count: count, total: total };
  }

  function renderCartBar() {
    var t = cartTotals();
    if (t.count > 0) {
      cartBar.classList.add('is-visible');
      cartBarCount.textContent = t.count + ' item';
      cartBarTotal.textContent = rupiah(t.total);
    } else {
      cartBar.classList.remove('is-visible');
    }
  }

  /* ================= Cart drawer ================= */
  var cartDrawer = document.getElementById('cartDrawer');
  var cartDrawerBody = document.getElementById('cartDrawerBody');
  var cartGrandTotal = document.getElementById('cartGrandTotal');

  function renderCartDrawer() {
    cartDrawerBody.innerHTML = '';
    if (cart.length === 0) {
      cartDrawerBody.innerHTML = '<div class="cart-drawer__empty">Keranjang masih kosong, yuk pilih menu dulu.</div>';
    }
    cart.forEach(function (item, idx) {
      var line = document.createElement('div');
      line.className = 'cart-line';
      line.innerHTML =
        '<img class="cart-line__thumb" alt="">' +
        '<div class="cart-line__body">' +
          '<div class="cart-line__top"><span class="cart-line__name"></span></div>' +
          (item.toppings && item.toppings.length ? '<span class="cart-line__note"></span>' : '') +
          (item.notes ? '<span class="cart-line__note"></span>' : '') +
          '<div class="cart-line__bottom">' +
            '<div class="qty-stepper qty-stepper--sm">' +
              '<button type="button" data-act="minus" data-idx="' + idx + '">−</button>' +
              '<span></span>' +
              '<button type="button" data-act="plus" data-idx="' + idx + '">+</button>' +
            '</div>' +
            '<span class="cart-line__subtotal"></span>' +
          '</div>' +
          '<button class="cart-line__remove" data-idx="' + idx + '">Hapus</button>' +
        '</div>';
      line.querySelector('.cart-line__name').textContent = item.nama;
      line.querySelector('.cart-line__thumb').src = item.image;
      line.querySelector('.cart-line__thumb').alt = item.nama;
      var detailNotes = line.querySelectorAll('.cart-line__note');
      if (item.toppings && item.toppings.length) {
        detailNotes[0].textContent = 'Topping: ' + item.toppings.map(function (topping) { return topping.nama; }).join(', ');
      }
      if (item.notes) detailNotes[detailNotes.length - 1].textContent = 'Catatan: ' + item.notes;
      line.querySelector('.qty-stepper span').textContent = item.qty;
      line.querySelector('.cart-line__subtotal').textContent = rupiah(item.qty * item.harga);
      cartDrawerBody.appendChild(line);
    });

    cartDrawerBody.querySelectorAll('[data-act="minus"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var i = parseInt(btn.dataset.idx, 10);
        cart[i].qty = Math.max(1, cart[i].qty - 1);
        renderCartDrawer(); renderCartBar();
      });
    });
    cartDrawerBody.querySelectorAll('[data-act="plus"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var i = parseInt(btn.dataset.idx, 10);
        cart[i].qty += 1;
        renderCartDrawer(); renderCartBar();
      });
    });
    cartDrawerBody.querySelectorAll('.cart-line__remove').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var i = parseInt(btn.dataset.idx, 10);
        cart.splice(i, 1);
        syncSelectedCards();
        renderCartDrawer(); renderCartBar();
      });
    });

    cartGrandTotal.textContent = rupiah(cartTotals().total);
  }

  function openCartDrawer() {
    renderCartDrawer();
    cartDrawer.classList.add('is-open');
    openOverlay();
  }
  function closeCartDrawer() {
    cartDrawer.classList.remove('is-open');
    closeOverlayIfNothingOpen();
  }
  document.getElementById('cartDrawerClose').addEventListener('click', closeCartDrawer);

  cartBar.addEventListener('click', function (e) {
    openCartDrawer();
  });

  document.getElementById('btnLanjutBayar').addEventListener('click', function () {
    if (cart.length === 0) return;
    if (!storeIsOpen) {
      window.alert('Toko sedang tutup sementara dan belum menerima pesanan baru.');
      return;
    }
    var button = document.getElementById('btnLanjutBayar');
    button.disabled = true;
    button.textContent = 'Menyiapkan pesanan...';
    fetch('api/start_checkout.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: cart })
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) throw new Error(data.error || 'Gagal menyiapkan pembayaran.');
          return data;
        });
      })
      .then(function (data) { window.location.href = data.redirect; })
      .catch(function (error) {
        button.disabled = false;
        button.textContent = 'Lanjut ke Pembayaran';
        window.alert(error.message || 'Terjadi kesalahan jaringan, coba lagi.');
      });
  });

  /* ================= Cek pesanan ================= */
  var cekModal = document.getElementById('cekModal');
  var cekForm = document.getElementById('cekForm');
  var cekLookup = document.getElementById('cekLookup');
  var cekSubmit = document.getElementById('cekSubmit');
  var cekMsg = document.getElementById('cekMsg');
  var cekResults = document.getElementById('cekResults');
  var cekRefresh = document.getElementById('cekRefresh');
  var lastLookup = '';
  var refreshTimer = null;
  var reviewSequence = 0;

  function openCekModal() {
    lockPageScroll();
    cekModal.hidden = false;
    cekModal.classList.add('is-open');
    openOverlay();
    cekLookup.focus();
    if (lastLookup) {
      loadOrders(true);
      startOrderPolling();
    }
  }
  function closeCekModal() {
    if (!cekModal.classList.contains('is-open')) return;
    if (refreshTimer) {
      window.clearInterval(refreshTimer);
      refreshTimer = null;
    }
    cekModal.classList.remove('is-open');
    cekModal.hidden = true;
    unlockPageScroll();
    closeOverlayIfNothingOpen();
  }
  function setCekMessage(message, isError) {
    cekMsg.textContent = message;
    cekMsg.hidden = !message;
    cekMsg.classList.toggle('is-error', Boolean(isError));
  }
  function renderOrder(order) {
    var card = document.createElement('article');
    card.className = 'cek-result';
    var heading = document.createElement('div');
    heading.className = 'cek-result__head';
    var id = document.createElement('strong');
    id.className = 'cek-result__id';
    id.textContent = order.id;
    heading.appendChild(id);
    card.appendChild(heading);
    var statusSequence = order.pengiriman === 'antar'
      ? ['Diterima', 'Diproses', 'Diantarkan', 'Selesai']
      : ['Diterima', 'Diproses', 'Siap Diambil', 'Selesai'];
    var currentStatusIndex = statusSequence.indexOf(order.status);
    var tracker = document.createElement('div');
    tracker.className = 'order-tracker';
    tracker.setAttribute('aria-label', 'Progres pesanan');
    statusSequence.forEach(function (status, index) {
      var step = document.createElement('div');
      step.className = 'order-tracker__step';
      if (currentStatusIndex >= 0 && index < currentStatusIndex) step.classList.add('is-complete');
      if (index === currentStatusIndex) step.classList.add('is-current');
      var dot = document.createElement('div');
      dot.className = 'order-tracker__dot';
      step.appendChild(dot);
      step.appendChild(document.createTextNode(status));
      tracker.appendChild(step);
    });
    card.appendChild(tracker);

    var items = document.createElement('ul');
    items.className = 'cek-result__items';
    order.items.forEach(function (item) {
      var listItem = document.createElement('li');
      var description = item.qty + '× ' + item.nama;
      if (item.toppings && item.toppings.length) {
        description += ' (Topping: ' + item.toppings.map(function (topping) { return topping.nama; }).join(', ') + ')';
      }
      if (item.notes) description += ' — Catatan: ' + item.notes;
      listItem.textContent = description;
      items.appendChild(listItem);
    });
    card.appendChild(items);

    var meta = document.createElement('dl');
    meta.className = 'cek-result__meta';
    [
      ['Tanggal & waktu', order.tanggal],
      ['Status pesanan', order.status],
      ['Status pembayaran', order.status_pembayaran || 'Belum dibayar'],
      ['Metode pembayaran', order.metode_bayar === 'qris' ? 'QRIS' : 'Tunai'],
      ['Pengambilan', order.pengiriman === 'antar' ? 'Diantarkan' : 'Ambil di toko'],
      ...(order.waktu_pengambilan ? [['Perkiraan waktu ambil', order.waktu_pengambilan]] : [])
    ].forEach(function (entry) {
      var row = document.createElement('div');
      var label = document.createElement('dt');
      label.textContent = entry[0];
      var value = document.createElement('dd');
      value.textContent = entry[1];
      row.appendChild(label);
      row.appendChild(value);
      meta.appendChild(row);
    });
    card.appendChild(meta);

    var total = document.createElement('div');
    total.className = 'cek-result__total';
    var totalLabel = document.createElement('span');
    totalLabel.textContent = 'Total pembayaran';
    var totalValue = document.createElement('strong');
    totalValue.textContent = rupiah(order.total);
    total.appendChild(totalLabel);
    total.appendChild(totalValue);
    card.appendChild(total);
    if (order.ulasan_tersedia) {
      var reviewForm = document.createElement('form');
      reviewForm.className = 'order-review';
      reviewForm.dataset.reviewOrder = order.id;
      var phoneId = 'reviewPhone' + (++reviewSequence);
      var ratingId = 'reviewRating' + reviewSequence;
      var commentId = 'reviewComment' + reviewSequence;
      reviewForm.innerHTML =
        '<label for="' + phoneId + '">Nomor WhatsApp untuk verifikasi</label>' +
        '<input id="' + phoneId + '" type="tel" inputmode="tel" autocomplete="tel" data-review-field="phone" placeholder="08xxxxxxxxxx" required>' +
        '<label for="' + ratingId + '">Rating</label>' +
        '<select id="' + ratingId + '" data-review-field="rating" required><option value="">Pilih rating</option><option value="5">5 - Sangat puas</option><option value="4">4 - Puas</option><option value="3">3 - Cukup</option><option value="2">2 - Kurang puas</option><option value="1">1 - Tidak puas</option></select>' +
        '<label for="' + commentId + '">Ulasan</label>' +
        '<textarea id="' + commentId + '" data-review-field="comment" maxlength="2000" required placeholder="Ceritakan pengalamanmu"></textarea>' +
        '<button class="btn btn--solid" type="submit">Kirim ulasan</button>' +
        '<p class="order-review__message" data-review-message role="status" aria-live="polite"></p>';
      card.appendChild(reviewForm);
    } else if (order.ulasan_dikirim) {
      var reviewed = document.createElement('p');
      reviewed.className = 'cek-hint';
      reviewed.textContent = 'Terima kasih, ulasan untuk pesanan ini sudah dikirim.';
      card.appendChild(reviewed);
    }
    cekResults.appendChild(card);
  }

  function renderOrders(orders, preserveReviewFields) {
    var savedFields = {};
    var focusedField = document.activeElement;
    var focusedOrder = focusedField && focusedField.closest('[data-review-order]');
    var focusedKey = focusedField && focusedField.dataset ? focusedField.dataset.reviewField : '';
    if (preserveReviewFields) {
      cekResults.querySelectorAll('[data-review-order]').forEach(function (form) {
        var fields = {};
        form.querySelectorAll('[data-review-field]').forEach(function (field) {
          fields[field.dataset.reviewField] = field.value;
        });
        savedFields[form.dataset.reviewOrder] = fields;
      });
    }
    cekResults.innerHTML = '';
    orders.forEach(renderOrder);
    Object.keys(savedFields).forEach(function (orderId) {
      var form = cekResults.querySelector('[data-review-order="' + CSS.escape(orderId) + '"]');
      if (!form) return;
      form.querySelectorAll('[data-review-field]').forEach(function (field) {
        if (Object.prototype.hasOwnProperty.call(savedFields[orderId], field.dataset.reviewField)) {
          field.value = savedFields[orderId][field.dataset.reviewField];
        }
      });
    });
    if (focusedOrder && focusedKey) {
      var focusOrderId = focusedOrder.dataset.reviewOrder;
      var focusForm = cekResults.querySelector('[data-review-order="' + CSS.escape(focusOrderId) + '"]');
      var nextFocus = focusForm && focusForm.querySelector('[data-review-field="' + CSS.escape(focusedKey) + '"]');
      if (nextFocus) nextFocus.focus();
    }
  }

  function loadOrders(quiet) {
    if (!lastLookup || !cekModal.classList.contains('is-open')) return;
    fetch('api/check_order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ lookup: lastLookup }),
      cache: 'no-store'
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) throw new Error(data.error || 'Gagal memperbarui status pesanan.');
          return data;
        });
      })
      .then(function (data) {
        renderOrders(data.orders, true);
        cekRefresh.textContent = 'Status diperbarui ' + new Date().toLocaleTimeString('id-ID');
        cekRefresh.hidden = false;
        if (!quiet) setCekMessage('', false);
      })
      .catch(function (error) {
        if (quiet) {
          cekRefresh.textContent = 'Pembaruan gagal: ' + (error.message || 'coba lagi sebentar.');
          cekRefresh.hidden = false;
        } else {
          setCekMessage(error.message || 'Gagal memperbarui status pesanan.', true);
        }
      });
  }

  function startOrderPolling() {
    if (refreshTimer) window.clearInterval(refreshTimer);
    refreshTimer = window.setInterval(function () { loadOrders(true); }, 15000);
  }

  document.getElementById('btnCekPesanan').addEventListener('click', openCekModal);
  document.getElementById('cekModalClose').addEventListener('click', closeCekModal);
  cekForm.addEventListener('submit', function (event) {
    event.preventDefault();
    var lookup = cekLookup.value.trim();
    if (!lookup) {
      setCekMessage('Masukkan nomor WhatsApp atau ID pesanan.', true);
      return;
    }
    cekSubmit.disabled = true;
    cekSubmit.textContent = 'Mencari...';
    cekResults.innerHTML = '';
    setCekMessage('Sedang mencari pesanan...', false);

    lastLookup = lookup;
    fetch('api/check_order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ lookup: lookup }),
      cache: 'no-store'
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) throw new Error(data.error || 'Gagal mencari pesanan.');
          return data;
        });
      })
      .then(function (data) {
        setCekMessage('', false);
        renderOrders(data.orders, false);
        cekRefresh.textContent = 'Status diperbarui ' + new Date().toLocaleTimeString('id-ID');
        cekRefresh.hidden = false;
        startOrderPolling();
      })
      .catch(function (error) {
        setCekMessage(error.message || 'Terjadi kesalahan jaringan, coba lagi.', true);
      })
      .finally(function () {
        cekSubmit.disabled = false;
        cekSubmit.textContent = 'Cari';
      });
  });
  cekResults.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-review-order]');
    if (!form) return;
    event.preventDefault();
    var submitButton = form.querySelector('[type="submit"]');
    var message = form.querySelector('[data-review-message]');
    var fields = form.querySelectorAll('[data-review-field]');
    var payload = {
      order_id: form.dataset.reviewOrder,
      whatsapp: fields[0].value,
      rating: fields[1].value,
      komentar: fields[2].value
    };
    submitButton.disabled = true;
    message.className = 'order-review__message';
    message.textContent = 'Mengirim ulasan...';
    fetch('api/submit_review.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) throw new Error(data.error || 'Ulasan gagal dikirim.');
          return data;
        });
      })
      .then(function () { loadOrders(true); })
      .catch(function (error) {
        message.className = 'order-review__message is-error';
        message.textContent = error.message || 'Terjadi kesalahan jaringan, coba lagi.';
        submitButton.disabled = false;
      });
  });

  overlayBg.addEventListener('click', function () {
    closeItemModal();
    closeCartDrawer();
    closeCekModal();
  });
});
</script>
</body>
</html>