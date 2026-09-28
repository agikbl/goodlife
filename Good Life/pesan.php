<?php
// pesan.php — Halaman pemesanan customer (semua digabung: HTML, CSS, JS, PHP)

$toko = ['wa' => '6285173087797'];

$menu = json_decode(file_get_contents(__DIR__ . '/data/menu.json'), true) ?: [];

// Sapaan header berdasarkan jam saat ini
$jam = (int) date('G');
if ($jam >= 4 && $jam < 11)       { $sapaan = 'Selamat pagi'; $sub = 'Sarapan enak nggak harus ribet, biar GoodLife yang urus.'; }
elseif ($jam >= 11 && $jam < 15)  { $sapaan = 'Selamat siang'; $sub = 'Waktunya istirahat makan siang. Mau kebab atau burger dulu?'; }
elseif ($jam >= 15 && $jam < 18)  { $sapaan = 'Selamat sore';  $sub = 'Sore-sore gini paling pas ngemil kebab hangat.'; }
else                              { $sapaan = 'Selamat malam'; $sub = 'Lapar tengah malam? Tenang, kami masih buka.'; }

$kategoriLabel = [
    'kebab'                  => 'Kebab',
    'kebab_pisang'           => 'Kebab Pisang',
    'piscok'                 => 'Piscok',
    'burger'                 => 'Burger',
    'cemilan_series'         => 'Cemilan Series',
    'aneka_nasi'             => 'Aneka Nasi',
    'extra_topping_makanan'  => 'Extra Topping (Makanan)',
    'basic_milk'             => 'Basic Milk',
    'basic_coffee'           => 'Basic Coffee',
    'tea_series'             => 'Tea Series',
    'signature_series'       => 'Signature Series',
    'extra_topping_minuman'  => 'Extra Topping (Minuman)',
];

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
    'kategori' => ['kebab', 'kebab_pisang', 'piscok', 'burger', 'cemilan_series', 'aneka_nasi'],
  ],
  'minuman' => [
    'judul' => 'Aneka Minuman',
    'kategori' => ['basic_milk', 'basic_coffee', 'tea_series', 'signature_series'],
  ],
];

$opsiTopping = [
  'makanan' => array_values(array_filter($menu, function ($item) {
    return ($item['kategori'] ?? '') === 'extra_topping_makanan';
  })),
  'minuman' => array_values(array_filter($menu, function ($item) {
    return ($item['kategori'] ?? '') === 'extra_topping_minuman';
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
<title>Pesan — GoodLife Parepare</title>
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
html.is-item-modal-open{overflow:hidden; overscroll-behavior:none;}
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
.greet__inner{max-width:1180px; margin:0 auto;}
.greet__title{font-size:clamp(1.6rem, 3.5vw, 2.2rem); font-weight:600; margin-bottom:0.4rem;}
.greet__sub{color:var(--grey-100); opacity:0.85; font-size:0.98rem; max-width:52ch;}

/* Menu grid */
.menu-grid{max-width:1180px; margin:0 auto; padding:1rem 0 2.5rem; display:grid; grid-template-columns:repeat(auto-fill, minmax(185px, 210px)); justify-content:start; gap:0.85rem;}
.menu-jump{display:flex; justify-content:center; gap:0.75rem; padding:0.8rem 1.5rem; background:var(--white); border-bottom:1px solid var(--grey-300);}
.menu-jump button{min-width:132px; padding:0.7rem 1.2rem; border:1px solid var(--green-900); border-radius:999px; background:var(--white); color:var(--green-900); text-align:center; font-weight:700; cursor:pointer; transition:background .15s ease, color .15s ease;}
.menu-jump button:hover,.menu-jump button.is-active{background:var(--green-900); color:var(--white);}
.menu-section{max-width:1180px; margin:0 auto; padding:2rem 1.5rem 0; scroll-margin-top:145px;}
.menu-section__title{font-size:2rem; color:var(--green-900); padding-bottom:0.75rem; border-bottom:2px solid var(--grey-300);}
.menu-category{padding-top:1.35rem;}
.menu-category__title{font-family:var(--font-body); font-size:1.1rem; color:var(--ink); margin:0;}
.menu-category .menu-grid{padding:0.75rem 0 0.5rem;}
.menu-category:last-child .menu-grid{padding-bottom:1.5rem;}
.menu-card{min-width:0; background:var(--white); border:1px solid var(--grey-300); border-radius:10px; overflow:hidden; cursor:pointer; transition:border-color .15s ease, box-shadow .15s ease; display:flex; flex-direction:column;}
.menu-card:hover{border-color:var(--green-500); box-shadow:0 5px 14px rgba(22,50,31,0.08);}
.menu-card.is-selected{border:2px solid var(--green-700); box-shadow:0 0 0 2px rgba(35,74,46,0.12);}
.menu-card__image{display:block; width:100%; aspect-ratio:16 / 10; object-fit:cover; background:var(--grey-100);}
.menu-card__body{padding:0.85rem; display:flex; flex-direction:column; gap:0.35rem; flex:1;}
.menu-card__name{font-weight:700; font-size:0.95rem; line-height:1.35;}
.menu-card__desc{font-size:0.78rem; color:var(--grey-700); line-height:1.4; margin:0;}
.menu-card__footer{display:flex; align-items:center; justify-content:space-between; margin-top:0.3rem;}
.menu-card__price{font-weight:700; color:var(--green-900);}
.menu-card__badge{font-size:0.7rem; padding:0.2rem 0.55rem; border-radius:999px; background:var(--grey-100); color:var(--green-700); font-weight:700;}
@media (max-width:600px){
  .menu-jump{gap:0.5rem; padding:0.65rem 1rem;}
  .menu-jump button{min-width:0; flex:1; padding:0.65rem 0.8rem;}
  .menu-section{padding:1.5rem 1rem 0; scroll-margin-top:120px;}
  .menu-section__title{font-size:1.6rem;}
  .menu-category .menu-grid{grid-template-columns:repeat(2, minmax(0, 1fr)); gap:0.65rem;}
  .menu-card__body{padding:0.75rem;}
  .menu-card__footer{align-items:flex-start; flex-direction:column; gap:0.35rem;}
}

/* Overlay umum (item modal, cart drawer, checkout) */
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
.checkout-modal{position:fixed; left:50%; top:50%; transform:translate(-50%,-50%); width:min(480px, 92vw); max-height:90vh; overflow-y:auto; background:var(--white); border-radius:var(--radius); z-index:110; display:none; box-shadow:var(--shadow);}
.checkout-modal.is-open{display:block;}
.checkout-head{padding:1.3rem 1.5rem 0.5rem; display:flex; align-items:center; justify-content:space-between;}
.checkout-steps{display:flex; gap:0.35rem; padding:0 1.5rem 1rem;}
.checkout-steps span{height:4px; flex:1; border-radius:999px; background:var(--grey-300);}
.checkout-steps span.is-done{background:var(--green-900);}
.checkout-body{padding:0.3rem 1.5rem 1.6rem;}
.checkout-step{display:none; flex-direction:column; gap:1.1rem;}
.checkout-step.is-active{display:flex;}

.option-card{border:1.5px solid var(--grey-300); border-radius:var(--radius); padding:1.1rem 1.2rem; cursor:pointer; display:flex; flex-direction:column; gap:0.3rem;}
.option-card.is-selected{border-color:var(--green-900); background:var(--grey-100);}
.option-card__title{font-weight:700;}
.option-card__desc{font-size:0.85rem; color:var(--grey-700);}

.field label{font-size:0.85rem; font-weight:700; color:var(--green-900); display:block; margin-bottom:0.4rem;}
.field input[type=text], .field textarea{width:100%; border:1px solid var(--grey-300); border-radius:10px; padding:0.7rem 0.9rem; font-family:var(--font-body); font-size:0.92rem;}
.field input[type=range]{width:100%;}
.range-value{font-size:0.85rem; color:var(--grey-700); margin-top:0.3rem;}

.summary-row{display:flex; justify-content:space-between; font-size:0.92rem; padding:0.35rem 0; color:var(--grey-700);}
.summary-row strong{color:var(--ink);}
.summary-row--total{border-top:1px solid var(--grey-300); margin-top:0.5rem; padding-top:0.7rem; font-size:1.05rem;}
.summary-row--total strong{font-family:var(--font-display); color:var(--green-900); font-size:1.25rem;}

.checkout-nav{display:flex; gap:0.7rem; margin-top:0.2rem;}

.status-tracker{display:flex; justify-content:space-between; margin:0.5rem 0 1.5rem;}
.status-step{flex:1; text-align:center; position:relative; font-size:0.75rem; color:var(--grey-500);}
.status-step:not(:last-child)::after{content:''; position:absolute; top:9px; left:55%; width:90%; height:2px; background:var(--grey-300);}
.status-step.is-active:not(:last-child)::after{background:var(--green-900);}
.status-step__dot{width:20px; height:20px; border-radius:50%; background:var(--grey-300); margin:0 auto 0.4rem; position:relative; z-index:1;}
.status-step.is-active .status-step__dot{background:var(--green-900);}
.status-step.is-active{color:var(--green-900); font-weight:700;}
.confirm-box{text-align:center; padding:0.5rem 0 0.5rem;}
.confirm-box__id{font-family:var(--font-display); font-size:1.5rem; color:var(--green-900); margin:0.3rem 0 1rem;}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="gl-navbar" id="glNavbar">
  <div class="gl-navbar__inner">
    <a href="beranda.php" class="gl-navbar__logo">
      <img class="gl-navbar__logo-image" src="assets/logo.jpeg" alt="">
      <img class="gl-navbar__logo-name" src="assets/text_name.jpeg" alt="GoodLife">
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
    <h1 class="greet__title"><?php echo $sapaan; ?>! Mau pesan apa hari ini?</h1>
    <p class="greet__sub"><?php echo $sub; ?></p>
  </div>
</section>

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
        <h3 class="menu-category__title"><?php echo $kategoriLabel[$kategori]; ?></h3>
        <div class="menu-grid">
          <?php foreach ($menuTerbagi[$keyKelompok][$kategori] as $item): ?>
            <div class="menu-card" data-id="<?php echo $item['id']; ?>"
                 data-nama="<?php echo htmlspecialchars($item['nama']); ?>"
                 data-harga="<?php echo $item['harga']; ?>"
                data-image="<?php echo htmlspecialchars($kategoriGambar[$item['kategori']] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                 data-topping-type="<?php echo $keyKelompok; ?>"
                 data-toppings="<?php echo htmlspecialchars(json_encode(array_map(function ($topping) { return ['id' => $topping['id'], 'nama' => $topping['nama'], 'harga' => (int)$topping['harga']]; }, $opsiTopping[$keyKelompok]), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>"
                 data-desc="<?php echo htmlspecialchars($kategoriLabel[$item['kategori']] ?? ''); ?>">
              <img class="menu-card__image" src="<?php echo htmlspecialchars($kategoriGambar[$item['kategori']] ?? '', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
              <div class="menu-card__body">
                <span class="menu-card__name"><?php echo htmlspecialchars($item['nama']); ?></span>
                <div class="menu-card__footer">
                  <span class="menu-card__price">Rp<?php echo number_format($item['harga'], 0, ',', '.'); ?></span>
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

<!-- CHECKOUT MODAL (multi-step) -->
<div class="checkout-modal" id="checkoutModal">
  <div class="checkout-head">
    <h3 id="checkoutTitle">Ringkasan Pesanan</h3>
    <button class="item-modal__close" id="checkoutClose" style="position:static;">&times;</button>
  </div>
  <div class="checkout-steps" id="checkoutSteps">
    <span data-s="1"></span><span data-s="2"></span><span data-s="3"></span><span data-s="4"></span>
  </div>
  <div class="checkout-body">

    <!-- Step 1: Ringkasan -->
    <div class="checkout-step is-active" data-step="1">
      <div id="checkoutSummaryList"></div>
      <div class="summary-row summary-row--total">
        <span>Total</span><strong id="ckSubtotal">Rp0</strong>
      </div>
      <div class="checkout-nav">
        <button class="btn btn--solid btn--block" id="toStep2">Lanjut</button>
      </div>
    </div>

    <!-- Step 2: Opsi pengiriman -->
    <div class="checkout-step" data-step="2">
      <div class="option-card" data-delivery="ambil">
        <span class="option-card__title">Ambil di Toko</span>
        <span class="option-card__desc">Kamu jemput sendiri pesanan ke GoodLife Parepare.</span>
      </div>
      <div class="option-card" data-delivery="antar">
        <span class="option-card__title">Diantar ke Lokasimu</span>
        <span class="option-card__desc">Kurir kami antar langsung, ongkir dihitung dari jarak.</span>
      </div>

      <!-- Muncul kalau pilih "Diantar" -->
      <div id="deliveryDetail" style="display:none; flex-direction:column; gap:1.1rem;">
        <div class="field">
          <label for="ckAlamat">Lokasi pengantaran</label>
          <input type="text" id="ckAlamat" placeholder="Nama jalan / patokan lokasi">
        </div>
        <div class="field">
          <label for="ckJarak">Perkiraan jarak dari toko</label>
          <input type="range" id="ckJarak" min="1" max="10" value="2">
          <div class="range-value"><span id="ckJarakValue">2</span> km — ongkir sekitar <strong id="ckOngkirPreview">Rp0</strong></div>
        </div>
        <div class="field">
          <label>Bayar ongkir pakai</label>
          <div class="option-card" data-ongkir="qris" style="padding:0.8rem 1rem;">
            <span class="option-card__title" style="font-size:0.92rem;">QRIS</span>
          </div>
          <div class="option-card" data-ongkir="tunai" style="padding:0.8rem 1rem; margin-top:0.5rem;">
            <span class="option-card__title" style="font-size:0.92rem;">Tunai ke kurir</span>
          </div>
        </div>
      </div>

      <div class="checkout-nav">
        <button class="btn btn--ghost" id="toStep1From2">Kembali</button>
        <button class="btn btn--solid btn--block" id="toStep3" disabled>Lanjut</button>
      </div>
    </div>

    <!-- Step 3: Metode pembayaran -->
    <div class="checkout-step" data-step="3">
      <div class="option-card" data-pay="qris">
        <span class="option-card__title">QRIS</span>
        <span class="option-card__desc">Scan &amp; bayar lewat aplikasi e-wallet atau m-banking.</span>
      </div>
      <div class="option-card" data-pay="tunai">
        <span class="option-card__title">Tunai</span>
        <span class="option-card__desc">Bayar cash saat ambil / pesanan diantar.</span>
      </div>

      <div id="ckFinalSummary"></div>

      <div class="checkout-nav">
        <button class="btn btn--ghost" id="toStep2From3">Kembali</button>
        <button class="btn btn--solid btn--block" id="toStep4" disabled>Buat Pesanan</button>
      </div>
    </div>

    <!-- Step 4: Konfirmasi -->
    <div class="checkout-step" data-step="4">
      <div class="status-tracker">
        <div class="status-step is-active"><div class="status-step__dot"></div>Diterima</div>
        <div class="status-step"><div class="status-step__dot"></div>Diproses</div>
        <div class="status-step"><div class="status-step__dot"></div>Diantarkan</div>
        <div class="status-step"><div class="status-step__dot"></div>Selesai</div>
      </div>
      <div class="confirm-box">
        <p>Pesanan berhasil dibuat!</p>
        <p class="confirm-box__id" id="ckOrderId">—</p>
        <p style="color:var(--grey-700); font-size:0.9rem;">Pantau status pesananmu di halaman ini, atau hubungi toko kalau ada kendala.</p>
      </div>
      <button class="btn btn--solid btn--block" id="btnSelesaiPesan">Selesai</button>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ================= State ================= */
  var cart = []; // { id, nama, harga, qty, notes }
  var checkout = { delivery: null, alamat: '', jarak: 2, ongkir: 0, bayarOngkir: null, metodeBayar: null };
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
    document.documentElement.classList.add('is-item-modal-open');
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
    document.documentElement.classList.remove('is-item-modal-open');
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
        !document.getElementById('checkoutModal').classList.contains('is-open')) {
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

  /* ================= Checkout ================= */
  var checkoutModal = document.getElementById('checkoutModal');
  var stepEls = document.querySelectorAll('.checkout-step');
  var stepDots = document.querySelectorAll('#checkoutSteps span');

  function goToStep(n) {
    stepEls.forEach(function (el) { el.classList.toggle('is-active', el.dataset.step == n); });
    stepDots.forEach(function (d) { d.classList.toggle('is-done', parseInt(d.dataset.s, 10) <= n); });
  }

  function renderCheckoutSummary() {
    var list = document.getElementById('checkoutSummaryList');
    list.innerHTML = '';
    cart.forEach(function (item) {
      var row = document.createElement('div');
      row.className = 'summary-row';
      var itemDescription = item.qty + '× ' + item.nama;
      if (item.toppings && item.toppings.length) {
        itemDescription += ' + ' + item.toppings.map(function (topping) { return topping.nama; }).join(', ');
      }
      row.innerHTML = '<span>' + itemDescription + '</span><strong>' + rupiah(item.qty * item.harga) + '</strong>';
      list.appendChild(row);
    });
    document.getElementById('ckSubtotal').textContent = rupiah(cartTotals().total);
  }

  function openCheckout() {
    if (cart.length === 0) return;
    checkout = { delivery: null, alamat: '', jarak: 2, ongkir: 0, bayarOngkir: null, metodeBayar: null };
    document.querySelectorAll('[data-delivery]').forEach(function (c) { c.classList.remove('is-selected'); });
    document.querySelectorAll('[data-ongkir]').forEach(function (c) { c.classList.remove('is-selected'); });
    document.querySelectorAll('[data-pay]').forEach(function (c) { c.classList.remove('is-selected'); });
    document.getElementById('deliveryDetail').style.display = 'none';
    document.getElementById('toStep3').disabled = true;
    document.getElementById('toStep4').disabled = true;
    renderCheckoutSummary();
    goToStep(1);
    closeCartDrawer();
    checkoutModal.classList.add('is-open');
    openOverlay();
  }
  function closeCheckout() {
    checkoutModal.classList.remove('is-open');
    closeOverlayIfNothingOpen();
  }
  document.getElementById('checkoutClose').addEventListener('click', closeCheckout);
  document.getElementById('btnLanjutBayar').addEventListener('click', openCheckout);

  document.getElementById('toStep2').addEventListener('click', function () { goToStep(2); });
  document.getElementById('toStep1From2').addEventListener('click', function () { goToStep(1); });
  document.getElementById('toStep2From3').addEventListener('click', function () { goToStep(2); });

  /* --- Step 2: opsi pengiriman --- */
  var ongkirPreview = document.getElementById('ckOngkirPreview');
  var jarakSlider = document.getElementById('ckJarak');
  var jarakValue = document.getElementById('ckJarakValue');

  function hitungOngkir(km) { return 5000 + km * 2000; }
  function updateOngkirPreview() {
    var km = parseInt(jarakSlider.value, 10);
    jarakValue.textContent = km;
    checkout.jarak = km;
    checkout.ongkir = hitungOngkir(km);
    ongkirPreview.textContent = rupiah(checkout.ongkir);
  }
  jarakSlider.addEventListener('input', updateOngkirPreview);

  function checkStep2Complete() {
    var ok = checkout.delivery === 'ambil' || (checkout.delivery === 'antar' && checkout.alamat.trim() !== '' && checkout.bayarOngkir);
    document.getElementById('toStep3').disabled = !ok;
  }

  document.querySelectorAll('[data-delivery]').forEach(function (card) {
    card.addEventListener('click', function () {
      document.querySelectorAll('[data-delivery]').forEach(function (c) { c.classList.remove('is-selected'); });
      card.classList.add('is-selected');
      checkout.delivery = card.dataset.delivery;
      var detail = document.getElementById('deliveryDetail');
      if (checkout.delivery === 'antar') {
        detail.style.display = 'flex';
        updateOngkirPreview();
      } else {
        detail.style.display = 'none';
        checkout.alamat = ''; checkout.bayarOngkir = null;
      }
      checkStep2Complete();
    });
  });
  document.getElementById('ckAlamat').addEventListener('input', function (e) {
    checkout.alamat = e.target.value;
    checkStep2Complete();
  });
  document.querySelectorAll('[data-ongkir]').forEach(function (card) {
    card.addEventListener('click', function () {
      document.querySelectorAll('[data-ongkir]').forEach(function (c) { c.classList.remove('is-selected'); });
      card.classList.add('is-selected');
      checkout.bayarOngkir = card.dataset.ongkir;
      checkStep2Complete();
    });
  });

  document.getElementById('toStep3').addEventListener('click', function () {
    renderFinalSummary();
    goToStep(3);
  });

  /* --- Step 3: metode pembayaran --- */
  document.querySelectorAll('[data-pay]').forEach(function (card) {
    card.addEventListener('click', function () {
      document.querySelectorAll('[data-pay]').forEach(function (c) { c.classList.remove('is-selected'); });
      card.classList.add('is-selected');
      checkout.metodeBayar = card.dataset.pay;
      document.getElementById('toStep4').disabled = false;
    });
  });

  function renderFinalSummary() {
    var box = document.getElementById('ckFinalSummary');
    var t = cartTotals();
    var ongkir = checkout.delivery === 'antar' ? checkout.ongkir : 0;
    box.innerHTML =
      '<div class="summary-row"><span>Subtotal pesanan</span><strong>' + rupiah(t.total) + '</strong></div>' +
      (checkout.delivery === 'antar'
        ? '<div class="summary-row"><span>Ongkir (' + checkout.jarak + ' km, bayar ' + (checkout.bayarOngkir === 'qris' ? 'QRIS' : 'tunai') + ')</span><strong>' + rupiah(ongkir) + '</strong></div>'
        : '<div class="summary-row"><span>Pengambilan</span><strong>Ambil di toko</strong></div>') +
      '<div class="summary-row summary-row--total"><span>Total bayar</span><strong>' + rupiah(t.total + ongkir) + '</strong></div>';
  }

  /* --- Step 4: submit pesanan --- */
  document.getElementById('toStep4').addEventListener('click', function () {
    var payload = {
      items: cart,
      pengiriman: checkout.delivery,
      alamat: checkout.alamat,
      jarak: checkout.delivery === 'antar' ? checkout.jarak : 0,
      bayar_ongkir: checkout.delivery === 'antar' ? checkout.bayarOngkir : null,
      metode_bayar: checkout.metodeBayar
    };
    var btn = document.getElementById('toStep4');
    btn.disabled = true; btn.textContent = 'Memproses...';

    fetch('api/submit_order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        btn.disabled = false; btn.textContent = 'Buat Pesanan';
        if (!data.ok) { alert(data.error || 'Gagal membuat pesanan.'); return; }
        document.getElementById('ckOrderId').textContent = data.order.id;
        goToStep(4);
      })
      .catch(function () {
        btn.disabled = false; btn.textContent = 'Buat Pesanan';
        alert('Terjadi kesalahan jaringan, coba lagi.');
      });
  });

  document.getElementById('btnSelesaiPesan').addEventListener('click', function () {
    cart = [];
    syncSelectedCards();
    renderCartBar();
    closeCheckout();
  });

  overlayBg.addEventListener('click', function () {
    closeItemModal();
    closeCartDrawer();
    // checkout sengaja tidak ditutup klik luar, biar nggak ke-cancel nggak sengaja pas isi form
  });
});
</script>
</body>
</html>
