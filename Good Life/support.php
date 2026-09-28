<?php
// support.php — Halaman bantuan/kontak pelanggan (semua digabung: HTML, CSS, JS, PHP)

$toko = [
    'wa'    => '6285173087797',
    'wa2'   => '085141368994',
    'ig'    => 'goodlife_parepare',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support — GoodLife Parepare</title>
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
  --shadow:0 12px 30px rgba(22,50,31,0.14);
}
*{box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{margin:0; font-family:var(--font-body); color:var(--ink); background:var(--white); -webkit-font-smoothing:antialiased;}
a{color:inherit; text-decoration:none;}
h1,h2,h3{font-family:var(--font-display); margin:0;}
img{max-width:100%; display:block;}

.btn{display:inline-flex; align-items:center; justify-content:center; padding:0.85rem 1.6rem; border-radius:999px; font-weight:500; font-size:0.95rem; border:1px solid transparent; cursor:pointer; transition:transform .15s ease, background .2s ease, color .2s ease;}
.btn:active{transform:scale(0.97);}
.btn--solid{background:var(--green-900); color:var(--white);}
.btn--solid:hover{background:var(--green-700);}
.btn--ghost{background:transparent; color:var(--grey-700);}

/* Navbar (identik dengan beranda.php) */
.gl-navbar{position:sticky; top:0; z-index:50; background:rgba(255,255,255,0.9); backdrop-filter:blur(8px); border-bottom:1px solid var(--grey-300);}
.gl-navbar__inner{max-width:1180px; margin:0 auto; padding:0.9rem 1.5rem; display:flex; align-items:center; justify-content:space-between;}
.gl-navbar__logo{display:flex; align-items:center; gap:0.5rem; font-family:var(--font-display); font-weight:700; font-size:1.15rem; color:var(--green-900);}
.gl-navbar__logo-image{width:40px; height:40px; object-fit:contain;}
.gl-navbar__logo-name{width:112px; height:auto; object-fit:contain;}
.gl-navbar__logo-mark{width:32px; height:32px; border-radius:9px; background:var(--green-900); color:var(--white); display:flex; align-items:center; justify-content:center; font-size:0.8rem; font-family:var(--font-body); font-weight:700;}
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

/* Mini-hero — lebih pendek dari beranda, tetap overlay hijau 30% */
.mini-hero{position:relative; min-height:38vh; display:flex; align-items:flex-end; overflow:hidden; isolation:isolate;}
.mini-hero__bg{position:absolute; inset:0; background-size:cover; background-position:center; background-color:var(--green-900); z-index:-2;}
.mini-hero__overlay{position:absolute; inset:0; background:var(--green-900); opacity:0.3; z-index:-1;}
.mini-hero__content{position:relative; max-width:1180px; margin:0 auto; padding:2.5rem 1.5rem 3.5rem; color:var(--white); width:100%;}
.mini-hero__title{font-size:clamp(2rem, 4.5vw, 3rem); font-weight:600; margin-bottom:0.5rem;}
.mini-hero__sub{max-width:44ch; font-size:1rem; line-height:1.6; color:var(--grey-100);}

/* Kartu kontak cepat — menjorok, sama pola dengan .info-card di beranda */
.contact-wrap{max-width:1180px; margin:0 auto; padding:0 1.5rem; position:relative; z-index:5;}
.contact-grid{background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow); margin-top:-2.6rem; padding:0.5rem; display:grid; grid-template-columns:repeat(3, 1fr); gap:0.5rem;}
.contact-item{display:flex; flex-direction:column; gap:0.4rem; padding:1.3rem 1.4rem; border-radius:10px; transition:background .15s ease;}
.contact-item:hover{background:var(--grey-100);}
.contact-item__label{font-size:0.78rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--grey-500);}
.contact-item__value{font-size:1rem; font-weight:700; color:var(--green-900);}
.location-section{max-width:1180px; margin:2.5rem auto 0; padding:0 1.5rem 1rem;}
.location-section__head{display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:1rem;}
.location-section h2{font-size:1.8rem;}
.location-map{height:360px; overflow:hidden; border-radius:var(--radius); background:var(--grey-100); box-shadow:var(--shadow);}
.location-map iframe{width:100%; height:100%; border:0;}
.location-link{flex:none; color:var(--green-700); font-weight:700; text-decoration:underline; text-underline-offset:3px;}
@media (max-width:760px){
  .contact-grid{grid-template-columns:1fr;}
  .location-section{padding:0 1.25rem 0.5rem;}
  .location-section__head{align-items:flex-start; flex-direction:column;}
  .location-section h2{font-size:1.6rem;}
  .location-map{height:300px;}
}

/* Form pesan */
.support-form-section{max-width:720px; margin:0 auto; padding:3.5rem 1.5rem 6rem;}
.support-form-section h2{font-size:1.9rem; margin-bottom:0.5rem;}
.support-form-section > p{color:var(--grey-700); margin-bottom:2rem;}
.support-form{background:var(--grey-100); border-radius:var(--radius); padding:1.8rem; display:flex; flex-direction:column; gap:1.1rem;}
.support-form__row{display:flex; flex-direction:column; gap:0.4rem;}
.support-form__row label{font-size:0.85rem; font-weight:700; color:var(--green-900);}
.support-form__row input,
.support-form__row select,
.support-form__row textarea{border:1px solid var(--grey-300); border-radius:10px; padding:0.75rem 0.9rem; font-family:var(--font-body); font-size:0.95rem; background:var(--white); resize:vertical;}
.support-form__two{display:grid; grid-template-columns:1fr 1fr; gap:1.1rem;}
@media (max-width:600px){.support-form__two{grid-template-columns:1fr;}}
.support-form__msg{font-size:0.9rem; margin:0; min-height:1.2em;}
.support-form__msg.is-ok{color:var(--green-700); font-weight:700;}
.support-form__msg.is-error{color:#8A3B2B;}

/* Footer (identik dengan beranda.php) */
.gl-footer{background:var(--green-900); color:var(--grey-100); padding:3rem 1.5rem 1.5rem;}
.gl-footer__inner{max-width:1180px; margin:0 auto; display:flex; justify-content:space-between; gap:2rem; flex-wrap:wrap;}
.gl-footer__logo{font-family:var(--font-display); font-size:1.2rem; font-weight:700;}
.gl-footer__inner p{color:var(--grey-300); max-width:32ch; font-size:0.9rem;}
.gl-footer__cols{display:flex; gap:3rem;}
.gl-footer__cols h4{font-size:0.85rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--grey-300); margin-bottom:0.7rem; font-family:var(--font-body);}
.gl-footer__cols a{display:block; font-size:0.9rem; margin-bottom:0.5rem; color:var(--grey-100); opacity:0.85;}
.gl-footer__cols a:hover{opacity:1; text-decoration:underline;}
.gl-footer__copy{max-width:1180px; margin:2.5rem auto 0; font-size:0.78rem; color:var(--grey-500); border-top:1px solid rgba(255,255,255,0.1); padding-top:1.2rem;}
@media (max-width:380px){
  .gl-navbar__inner{padding:0.65rem 1rem;}
  .gl-navbar__logo{gap:0.4rem; font-size:1.05rem;}
  .gl-navbar__logo-image{width:36px; height:36px;}
  .gl-navbar__logo-name{width:96px;}
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="gl-navbar" id="glNavbar">
  <div class="gl-navbar__inner">
    <a href="beranda.php" class="gl-navbar__logo">
      <img class="gl-navbar__logo-image" src="assets/logo.jpeg" alt="Logo GoodLife">
      <img class="gl-navbar__logo-name" src="assets/text_name.jpeg" alt="GoodLife">
    </a>
    <button class="gl-navbar__toggle" id="glNavToggle" aria-label="Buka menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <ul class="gl-navbar__links" id="glNavLinks">
      <li><a href="beranda.php">Beranda</a></li>
      <li><a href="pesan.php">Pesan</a></li>
      <li><a href="faq.php">FAQ</a></li>
      <li><a href="support.php" class="is-active">Support</a></li>
    </ul>
  </div>
</nav>

<!-- MINI-HERO -->
<header class="mini-hero">
  <div class="mini-hero__bg" style="background-image:url('assets/img/support-hero.jpg');"></div>
  <div class="mini-hero__overlay"></div>
  <div class="mini-hero__content">
    <h1 class="mini-hero__title">Butuh bantuan?</h1>
    <p class="mini-hero__sub">Kami balas secepat kilat, biasanya di bawah 1 jam pada jam operasional toko.</p>
  </div>
</header>

<!-- KONTAK CEPAT -->
<section class="contact-wrap">
  <div class="contact-grid">
    <a class="contact-item" href="https://wa.me/<?php echo $toko['wa']; ?>" target="_blank" rel="noopener noreferrer">
      <span class="contact-item__label">WhatsApp 1</span>
      <span class="contact-item__value">0851-7308-7797</span>
    </a>
    <a class="contact-item" href="https://wa.me/62<?php echo ltrim($toko['wa2'], '0'); ?>" target="_blank" rel="noopener noreferrer">
      <span class="contact-item__label">WhatsApp 2</span>
      <span class="contact-item__value"><?php echo htmlspecialchars($toko['wa2']); ?></span>
    </a>
    <a class="contact-item" href="https://instagram.com/<?php echo $toko['ig']; ?>" target="_blank" rel="noopener noreferrer">
      <span class="contact-item__label">Instagram</span>
      <span class="contact-item__value">@<?php echo $toko['ig']; ?></span>
    </a>
  </div>
</section>

<!-- LOKASI -->
<section class="location-section" aria-labelledby="locationTitle">
  <div class="location-section__head">
    <h2 id="locationTitle">Lokasi GoodLife</h2>
    <a class="location-link" href="https://maps.app.goo.gl/HmHDcLf9RcshKSFV8" target="_blank" rel="noopener noreferrer">Buka di Google Maps</a>
  </div>
  <div class="location-map">
    <iframe
      src="https://www.google.com/maps?q=-4.0077714%2C119.632276&z=17&output=embed"
      title="Peta lokasi GoodLife Parepare"
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
      allowfullscreen>
    </iframe>
  </div>
</section>

<!-- FORM PESAN -->
<section class="support-form-section">
  <h2>Kirim pesan langsung</h2>
  <p>Ceritakan kendalanya, tim GoodLife akan tindak lanjuti lewat kontak yang kamu kasih.</p>

  <form class="support-form" id="supportForm">
    <div class="support-form__two">
      <div class="support-form__row">
        <label for="sfNama">Nama</label>
        <input type="text" id="sfNama" name="nama" placeholder="Nama kamu" required>
      </div>
      <div class="support-form__row">
        <label for="sfKontak">Nomor WA / Email</label>
        <input type="text" id="sfKontak" name="kontak" placeholder="08xx atau email" required>
      </div>
    </div>

    <div class="support-form__row">
      <label for="sfKategori">Kategori</label>
      <select id="sfKategori" name="kategori" required>
        <option value="" disabled selected>Pilih kategori</option>
        <option value="Pesanan bermasalah">Pesanan bermasalah</option>
        <option value="Pembayaran">Pembayaran</option>
        <option value="Saran">Saran</option>
        <option value="Lainnya">Lainnya</option>
      </select>
    </div>

    <div class="support-form__row">
      <label for="sfPesan">Pesan</label>
      <textarea id="sfPesan" name="pesan" rows="4" placeholder="Jelaskan kendalamu di sini..." required></textarea>
    </div>

    <button type="submit" class="btn btn--solid">Kirim pesan</button>
    <p class="support-form__msg" id="supportFormMsg"></p>
  </form>
</section>

<!-- FOOTER -->
<footer class="gl-footer">
  <div class="gl-footer__inner">
    <div>
      <span class="gl-footer__logo">GoodLife</span>
      <p>Kebab &amp; burger, dibuat segar setiap hari di Parepare.</p>
    </div>
    <div class="gl-footer__cols">
      <div>
        <h4>Jelajah</h4>
        <a href="beranda.php">Beranda</a>
        <a href="pesan.php">Pesan</a>
        <a href="faq.php">FAQ</a>
      </div>
      <div>
        <h4>Kontak</h4>
        <a href="https://wa.me/<?php echo $toko['wa']; ?>">WA <?php echo $toko['wa']; ?></a>
        <a href="support.php">Support</a>
      </div>
    </div>
  </div>
  <p class="gl-footer__copy">&copy; <?php echo date('Y'); ?> GoodLife Parepare.</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // Navbar mobile toggle
  var toggle = document.getElementById('glNavToggle');
  var links = document.getElementById('glNavLinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      var open = links.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Submit form support
  var form = document.getElementById('supportForm');
  var msg = document.getElementById('supportFormMsg');

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var payload = {
        nama: document.getElementById('sfNama').value.trim(),
        kontak: document.getElementById('sfKontak').value.trim(),
        kategori: document.getElementById('sfKategori').value,
        pesan: document.getElementById('sfPesan').value.trim()
      };

      msg.className = 'support-form__msg';
      msg.textContent = 'Mengirim...';

      fetch('api/submit_support.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (!data.ok) {
            msg.className = 'support-form__msg is-error';
            msg.textContent = data.error || 'Gagal mengirim pesan.';
            return;
          }
          msg.className = 'support-form__msg is-ok';
          msg.textContent = 'Terkirim! Tim kami akan hubungi kamu lewat kontak yang kamu kasih.';
          form.reset();
        })
        .catch(function () {
          msg.className = 'support-form__msg is-error';
          msg.textContent = 'Terjadi kesalahan jaringan, coba lagi.';
        });
    });
  }
});
</script>
</body>
</html>
