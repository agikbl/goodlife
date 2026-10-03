<?php
// faq.php — Halaman pertanyaan yang sering diajukan (semua digabung: HTML, CSS, JS, PHP)

$toko = [
    'wa' => '6285173087797',
];

$faqs = [
    [
        'q' => 'Berapa lama proses pesanan sampai diantar?',
        'a' => 'Rata-rata 20–35 menit tergantung antrean dapur dan jarak lokasi. Kamu bisa pantau statusnya langsung di halaman Pesan: Diterima → Diproses → Diantarkan → Selesai.'
    ],
    [
        'q' => 'Metode pembayaran apa saja yang tersedia?',
        'a' => 'Untuk pesanan bisa bayar pakai QRIS atau tunai. Jika diantar, tandai lokasi pada peta di halaman pembayaran; ongkir memakai estimasi jarak rute (jarak lurus dikali 1,3), Rp8.000 hingga 2 km, naik bertahap sampai Rp15.000 pada 5 km, dan maksimal Rp15.000 untuk jarak lebih jauh. Ongkir dapat dibayar via QRIS atau tunai ke kurir.'
    ],
    [
        'q' => 'Bisa ambil sendiri tanpa diantar?',
        'a' => 'Bisa. Di halaman pembayaran pilih opsi "Ambil di Toko" — pesanan tetap bisa dibayar QRIS atau tunai saat pengambilan.'
    ],
    [
        'q' => 'Bagaimana kalau saya mau minta menu tanpa sambal atau extra pedas?',
        'a' => 'Setiap menu yang kamu tambahkan ke keranjang punya kolom catatan sendiri, tulis saja permintaanmu di sana (misalnya "kurangi gula" atau "banyakin sambal").'
    ],
    [
        'q' => 'Pesanan saya salah atau belum datang, harus gimana?',
        'a' => 'Langsung hubungi kami lewat halaman Support atau chat WhatsApp toko, sertakan detail pesananmu supaya cepat kami bantu.'
    ],
    [
        'q' => 'Jam berapa toko buka?',
        'a' => 'Setiap hari mulai jam 15:00 sampai 23:00. Pemesanan online mengikuti jam operasional ini.'
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FAQ — Good Life Parepare</title>
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
.btn--outline{background:transparent; color:var(--green-900); border-color:var(--green-900); border-style:solid; border-width:1px;}
.btn--outline:hover{background:var(--green-900); color:var(--white);}

/* Navbar (identik dengan halaman lain) */
.gl-navbar{position:sticky; top:0; z-index:50; background:rgba(255,255,255,0.9); backdrop-filter:blur(8px); border-bottom:1px solid var(--grey-300);}
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

/* Mini-hero (identik pola dengan support.php) */
.mini-hero{position:relative; min-height:34vh; display:flex; align-items:flex-end; overflow:hidden; isolation:isolate;}
.mini-hero__bg{position:absolute; inset:0; background-size:cover; background-position:center; background-color:var(--green-900); z-index:-2;}
.mini-hero__overlay{position:absolute; inset:0; background:var(--green-900); opacity:0.3; z-index:-1;}
.mini-hero__content{position:relative; max-width:1180px; margin:0 auto; padding:2.5rem 1.5rem 3.2rem; color:var(--white); width:100%;}
.mini-hero__title{font-size:clamp(2rem, 4.5vw, 3rem); font-weight:600; margin-bottom:0.5rem;}
.mini-hero__sub{max-width:44ch; font-size:1rem; line-height:1.6; color:var(--grey-100);}

/* FAQ list */
.faq-wrap{max-width:820px; margin:0 auto; padding:4rem 1.5rem 5rem;}
.faq-item{border-bottom:1px solid var(--grey-300);}
.faq-item:first-child{border-top:1px solid var(--grey-300);}
.faq-question{
  display:flex; align-items:center; justify-content:space-between; gap:1rem;
  padding:1.4rem 0.2rem; cursor:pointer; background:none; border:none; width:100%;
  text-align:left; font-family:var(--font-body); font-size:1.05rem; font-weight:700; color:var(--ink);
}
.faq-question__icon{
  flex-shrink:0; width:26px; height:26px; border-radius:50%;
  border:1px solid var(--green-900); color:var(--green-900);
  display:flex; align-items:center; justify-content:center;
  font-size:1.1rem; line-height:1; transition:transform .2s ease;
}
.faq-item.is-open .faq-question__icon{transform:rotate(45deg);}
.faq-answer{
  max-height:0; overflow:hidden; transition:max-height .25s ease;
}
.faq-answer__inner{padding:0 0.2rem 1.4rem; color:var(--grey-700); line-height:1.65; font-size:0.96rem; max-width:62ch;}

/* CTA di bawah — "masih ada pertanyaan?" */
.faq-cta{
  max-width:820px; margin:0 auto 6rem; padding:0 1.5rem;
}
.faq-cta__box{
  background:var(--grey-100); border-radius:var(--radius);
  padding:2rem; display:flex; align-items:center; justify-content:space-between;
  gap:1.5rem; flex-wrap:wrap;
}
.faq-cta__box h3{font-size:1.3rem; margin-bottom:0.3rem;}
.faq-cta__box p{margin:0; color:var(--grey-700); font-size:0.92rem;}

/* Footer (identik dengan halaman lain) */
.gl-footer{background:var(--green-900); color:var(--grey-100); padding:3rem 1.5rem 1.5rem;}
.gl-footer__inner{max-width:1180px; margin:0 auto; display:flex; justify-content:space-between; gap:2rem; flex-wrap:wrap;}
.gl-footer__logo{font-family:var(--font-display); font-size:1.2rem; font-weight:700;}
.gl-footer__inner p{color:var(--grey-300); max-width:32ch; font-size:0.9rem;}
.gl-footer__cols{display:flex; gap:3rem;}
.gl-footer__cols h4{font-size:0.85rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--grey-300); margin-bottom:0.7rem; font-family:var(--font-body);}
.gl-footer__cols a{display:block; font-size:0.9rem; margin-bottom:0.5rem; color:var(--grey-100); opacity:0.85;}
.gl-footer__cols a:hover{opacity:1; text-decoration:underline;}
.gl-footer__copy{max-width:1180px; margin:2.5rem auto 0; font-size:0.78rem; color:var(--grey-500); border-top:1px solid rgba(255,255,255,0.1); padding-top:1.2rem;}
</style>
</head>
<body>

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
      <li><a href="pesan.php">Pesan</a></li>
      <li><a href="faq.php" class="is-active">FAQ</a></li>
      <li><a href="support.php">Support</a></li>
    </ul>
  </div>
</nav>

<!-- MINI-HERO -->
<header class="mini-hero">
  <div class="mini-hero__bg" style="background-image:url('assets/img/faq-hero.jpg');"></div>
  <div class="mini-hero__overlay"></div>
  <div class="mini-hero__content">
    <h1 class="mini-hero__title">Pertanyaan yang sering ditanyakan</h1>
    <p class="mini-hero__sub">Sebelum chat kami, coba cek dulu — siapa tahu jawabannya sudah ada di sini.</p>
  </div>
</header>

<!-- FAQ LIST -->
<section class="faq-wrap" id="faqList">
  <?php foreach ($faqs as $i => $item): ?>
    <div class="faq-item" data-index="<?php echo $i; ?>">
      <button class="faq-question" type="button">
        <span><?php echo htmlspecialchars($item['q']); ?></span>
        <span class="faq-question__icon">+</span>
      </button>
      <div class="faq-answer">
        <div class="faq-answer__inner"><?php echo htmlspecialchars($item['a']); ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</section>

<!-- CTA -->
<section class="faq-cta">
  <div class="faq-cta__box">
    <div>
      <h3>Masih ada yang mau ditanyakan?</h3>
      <p>Tim kami siap bantu langsung lewat WhatsApp atau halaman Support.</p>
    </div>
    <a href="support.php" class="btn btn--solid">Hubungi kami</a>
  </div>
</section>

<!-- FOOTER -->
<footer class="gl-footer">
  <div class="gl-footer__inner">
    <div>
      <span class="gl-footer__logo">Good Life</span>
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
  <p class="gl-footer__copy">&copy; <?php echo date('Y'); ?> Good Life Parepare.</p>
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

  // Accordion FAQ — cuma satu yang terbuka dalam satu waktu
  var items = document.querySelectorAll('.faq-item');
  items.forEach(function (item) {
    var question = item.querySelector('.faq-question');
    var answer = item.querySelector('.faq-answer');
    var inner = item.querySelector('.faq-answer__inner');

    question.addEventListener('click', function () {
      var isOpen = item.classList.contains('is-open');

      // tutup semua item lain
      items.forEach(function (other) {
        other.classList.remove('is-open');
        other.querySelector('.faq-answer').style.maxHeight = null;
      });

      // buka item ini kalau sebelumnya tertutup
      if (!isOpen) {
        item.classList.add('is-open');
        answer.style.maxHeight = inner.offsetHeight + 'px';
      }
    });
  });
});
</script>
</body>
</html>
