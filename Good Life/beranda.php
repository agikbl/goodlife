<?php
// beranda.php — Halaman utama sisi customer (semua digabung: HTML, CSS, JS, PHP)
require_once __DIR__ . '/database/repository.php';

$storeDefaults = [
    'nama'      => 'Good Life Parepare',
    'alamat'    => 'Jl. H. Jamil Ismail, Parepare (Perempatan Ablam, samping Waterboom)',
    'jam_buka'  => '15:00',
    'jam_tutup' => '23:00',
    'wa'        => '6285173087797',
    'gallery'   => [],
];
$storeConfig = goodlife_db_store();
$toko = is_array($storeConfig) ? array_merge($storeDefaults, $storeConfig) : $storeDefaults;

$reviews = goodlife_db_reviews();

$avg = 0;
if (count($reviews) > 0) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $avg = round($sum / count($reviews), 1);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Good Life Parepare — Kebab &amp; Burger</title>
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
.btn--light{background:var(--white); color:var(--green-900);}
.btn--light:hover{background:var(--grey-100);}
.btn--solid{background:var(--green-900); color:var(--white);}
.btn--solid:hover{background:var(--green-700);}
.btn--outline{background:transparent; color:var(--green-900); border-color:var(--green-900);}
.btn--outline:hover{background:var(--green-900); color:var(--white);}
.btn--ghost{background:transparent; color:var(--grey-700);}

/* Navbar */
.gl-navbar{position:sticky; top:0; z-index:50; background:rgba(255,255,255,0.9); backdrop-filter:blur(8px); border-bottom:1px solid var(--grey-300);}
.gl-navbar__inner{max-width:1180px; margin:0 auto; padding:0.9rem 1.5rem; display:flex; align-items:center; justify-content:space-between;}
.gl-navbar__logo{display:flex; align-items:center; gap:0.5rem; font-family:var(--font-display); font-weight:700; font-size:1.15rem; color:var(--green-900);}
.gl-navbar__logo-image{width:40px; height:40px; object-fit:contain;}
.gl-navbar__logo-name{width:112px; height:auto; object-fit:contain;}
.gl-navbar__links{list-style:none; display:flex; gap:2rem; margin:0; padding:0;}
.gl-navbar__links a{font-size:0.95rem; color:var(--grey-700); padding-bottom:4px; border-bottom:2px solid transparent;}
.gl-navbar__links a.is-active, .gl-navbar__links a:hover{color:var(--green-900); border-color:var(--green-500);}
.gl-navbar__toggle{display:none; width:44px; height:44px; padding:0; background:none; border:none; flex-direction:column; align-items:center; justify-content:center; gap:5px; cursor:pointer;}
.gl-navbar__toggle span{width:22px; height:2px; background:var(--green-900);}
@media (max-width:760px){
  .gl-navbar__toggle{display:flex;}
  .gl-navbar__links{position:absolute; top:100%; left:0; right:0; background:var(--white); flex-direction:column; gap:0; max-height:0; overflow:hidden; border-bottom:1px solid var(--grey-300); transition:max-height .25s ease;}
  .gl-navbar__links.is-open{max-height:260px;}
  .gl-navbar__links li{border-top:1px solid var(--grey-100);}
  .gl-navbar__links a{display:block; padding:0.9rem 1.5rem;}
}

/* Hero */
.hero{position:relative; min-height:clamp(540px, 82svh, 760px); display:flex; align-items:flex-end; overflow:hidden; isolation:isolate;}
.hero__bg{position:absolute; inset:0; background-size:cover; background-position:center; background-color:var(--green-900); will-change:transform; z-index:-2;}
.hero__overlay{position:absolute; inset:0; background:var(--green-900); opacity:0.80; z-index:-1;}
.hero__content{position:relative; max-width:1180px; margin:0 auto; padding:3.5rem 1.5rem 4.5rem; color:var(--white); width:100%;}
.hero__eyebrow{font-size:0.9rem; letter-spacing:0.02em; color:var(--grey-100); opacity:0.85; margin:0 0 0.75rem;}
.hero__title{font-size:clamp(2.4rem, 5.5vw, 4rem); line-height:1.05; font-weight:600; max-width:14ch; margin-bottom:1.1rem;}
.hero__sub{max-width:44ch; font-size:1.05rem; line-height:1.6; color:var(--grey-100); margin-bottom:1.8rem;}

/* Kartu info */
.info-card-wrap{max-width:1180px; margin:0 auto; padding:0 1.5rem; position:relative; z-index:5;}
.info-card{background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow); margin-top:-3.2rem; padding:1.6rem 2rem; display:flex; flex-wrap:wrap; gap:1.5rem; align-items:center;}
.info-card__item{display:flex; flex-direction:column; gap:0.3rem; flex:1; min-width:180px;}
.info-card__label{font-size:0.78rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--grey-500);}
.info-card__value{font-size:0.98rem; color:var(--ink); font-weight:500; overflow-wrap:anywhere;}
.info-card__link{color:var(--green-700);}
.info-card__link:hover{text-decoration:underline;}
.info-card__divider{width:1px; align-self:stretch; background:var(--grey-300);}
@media (max-width:700px){.info-card{flex-direction:column; align-items:flex-start;} .info-card__item{width:100%;} .info-card__divider{display:none;}}

/* Ulasan */
.reviews{max-width:1180px; margin:0 auto; padding:5rem 1.5rem 6rem;}
.reviews__head{display:flex; justify-content:space-between; align-items:center; gap:1.5rem; flex-wrap:wrap; margin-bottom:2.5rem;}
.reviews__score{display:flex; align-items:center; gap:1rem;}
.reviews__score-num{font-family:var(--font-display); font-size:3rem; color:var(--green-900); line-height:1;}
.reviews__stars{color:var(--green-500); font-size:1.1rem; letter-spacing:2px;}
.reviews__count{font-size:0.88rem; color:var(--grey-700);}
.reviews__carousel{position:relative;}
.reviews__viewport{overflow:hidden;}
.reviews__grid{display:flex; gap:1.25rem; overflow-x:auto; scroll-behavior:smooth; scroll-snap-type:x mandatory; scrollbar-width:none; overscroll-behavior-x:contain;}
.reviews__grid::-webkit-scrollbar{display:none;}
.review-card{flex:0 0 calc((100% - 2.5rem) / 3); scroll-snap-align:start;}
.reviews__controls{display:flex; gap:0.5rem;}
.reviews__controls[hidden]{display:none;}
.reviews__arrow{width:42px; height:42px; border:1px solid var(--green-900); border-radius:50%; background:var(--white); color:var(--green-900); font-size:1.35rem; line-height:1; cursor:pointer; transition:background .15s ease, color .15s ease;}
.reviews__arrow:hover:not(:disabled){background:var(--green-900); color:var(--white);}
.reviews__arrow:disabled{opacity:0.35; cursor:not-allowed;}
.review-card{background:var(--grey-100); border-radius:var(--radius); padding:1.4rem; display:flex; flex-direction:column; gap:0.6rem;}
.review-card__top{display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem;}
.review-card__name{font-weight:700;}
.review-card__stars{color:var(--green-500); letter-spacing:1px; white-space:nowrap;}
.review-card__text{color:var(--grey-700); line-height:1.55; font-size:0.95rem; margin:0;}
.review-card__date{font-size:0.78rem; color:var(--grey-500);}
.store-gallery{max-width:1180px; margin:2.5rem auto 0; padding:0 1.5rem 1rem;}
.store-gallery__head{display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem;}
.store-gallery h2{font:600 1.8rem var(--font-display); color:var(--green-900); margin:0;}
.store-gallery__track{display:flex; gap:1rem; overflow-x:auto; scroll-snap-type:x mandatory; scrollbar-width:none; overscroll-behavior-x:contain;}
.store-gallery__track::-webkit-scrollbar{display:none;}
.store-gallery__track img{flex:0 0 min(78%, 340px); width:min(78%, 340px); height:220px; object-fit:cover; border-radius:var(--radius); scroll-snap-align:start;}
.store-gallery__controls{display:flex; gap:.5rem;}
.store-gallery__controls button{width:40px;height:40px;border:1px solid var(--green-900);border-radius:50%;background:var(--white);color:var(--green-900);font-size:1.3rem;cursor:pointer;}

/* Footer */
.gl-footer{background:var(--green-900); color:var(--grey-100); padding:3rem 1.5rem 1.5rem;}
.gl-footer__inner{max-width:1180px; margin:0 auto; display:flex; justify-content:space-between; gap:2rem; flex-wrap:wrap;}
.gl-footer__logo{font-family:var(--font-display); font-size:1.2rem; font-weight:700;}
.gl-footer__inner p{color:var(--grey-300); max-width:32ch; font-size:0.9rem;}
.gl-footer__cols{display:flex; gap:clamp(1.5rem, 5vw, 3rem);}
.gl-footer__cols h4{font-size:0.85rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--grey-300); margin-bottom:0.7rem; font-family:var(--font-body);}
.gl-footer__cols a{display:block; font-size:0.9rem; margin-bottom:0.5rem; color:var(--grey-100); opacity:0.85; overflow-wrap:anywhere;}
.gl-footer__cols a:hover{opacity:1; text-decoration:underline;}
.gl-footer__copy{max-width:1180px; margin:2.5rem auto 0; font-size:0.78rem; color:var(--grey-500); border-top:1px solid rgba(255,255,255,0.1); padding-top:1.2rem;}
@media (max-width:760px){
  .hero{min-height:clamp(520px, 78svh, 680px);}
  .hero__content{padding:3rem 1.25rem 4rem;}
  .info-card-wrap{padding:0 1.25rem;}
  .info-card{width:100%; margin-top:-2rem; padding:1.25rem; gap:1rem;}
  .info-card__item{min-width:0;}
  .reviews{padding:4rem 1.25rem 4.5rem;}
  .store-gallery{margin-top:2rem; padding:0 1.25rem .5rem;}
  .store-gallery h2{font-size:1.6rem;}
  .store-gallery__track img{height:180px;}
  .reviews__head{margin-bottom:1.75rem;}
  .review-card{flex-basis:100%;}
  .gl-footer{padding:2.5rem 1.25rem 1.25rem;}
  .gl-footer__inner{flex-direction:column; gap:1.5rem;}
  .gl-footer__cols{gap:clamp(2rem, 10vw, 3rem);}
  .gl-footer__copy{margin-top:2rem;}
}
@media (min-width:761px) and (max-width:1000px){
  .review-card{flex-basis:calc((100% - 1.25rem) / 2);}
}
@media (max-width:380px){
  .gl-navbar__inner{padding:0.65rem 1rem;}
  .gl-navbar__logo{gap:0.4rem; font-size:1.05rem;}
  .gl-navbar__logo-image{width:36px; height:36px;}
  .gl-navbar__logo-name{width:96px;}
  .hero__content{padding-right:1rem; padding-left:1rem;}
  .hero__title{font-size:2.4rem;}
  .hero__sub{font-size:1rem;}
  .info-card-wrap,.reviews{padding-right:1rem; padding-left:1rem;}
  .gl-footer{padding-right:1rem; padding-left:1rem;}
  .reviews__score{gap:0.75rem;}
  .reviews__score-num{font-size:2.5rem;}
  .reviews__head{gap:1rem;}
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="gl-navbar" id="glNavbar">
  <div class="gl-navbar__inner">
    <a href="beranda.php" class="gl-navbar__logo">
      <img class="gl-navbar__logo-image" src="assets/logo.jpeg" alt="Logo Good Life">
      <img class="gl-navbar__logo-name" src="assets/text_name.jpeg" alt="Good Life">
    </a>
    <button class="gl-navbar__toggle" id="glNavToggle" aria-label="Buka menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <ul class="gl-navbar__links" id="glNavLinks">
      <li><a href="beranda.php" class="is-active">Beranda</a></li>
      <li><a href="pesan.php">Pesan</a></li>
      <li><a href="faq.php">FAQ</a></li>
      <li><a href="support.php">Support</a></li>
    </ul>
  </div>
</nav>

<!-- HERO -->
<header class="hero" id="hero">
  <div class="hero__bg" id="heroBg" style="background-image:url('assets/gud.png');"></div>
  <div class="hero__overlay"></div>
  <div class="hero__content">
    <p class="hero__eyebrow">Parepare, tiap sore jam 3</p>
    <h1 class="hero__title">Being Good,<br>Better Life.</h1>
    <p class="hero__sub">Kebab dan burger racikan sendiri, dari daging sampai sambel — pesan online, tinggal duduk manis nunggu diantar.</p>
    <a href="pesan.php" class="btn btn--light">Pesan sekarang</a>
  </div>
</header>

<!-- KARTU ALAMAT -->
<section class="info-card-wrap">
  <div class="info-card">
    <div class="info-card__item">
      <span class="info-card__label">Lokasi</span>
      <span class="info-card__value"><?php echo htmlspecialchars($toko['alamat']); ?></span>
    </div>
    <div class="info-card__divider"></div>
    <div class="info-card__item">
      <span class="info-card__label">Jam operasional</span>
      <span class="info-card__value"><?php echo htmlspecialchars($toko['jam_buka'] . ' – ' . $toko['jam_tutup']); ?></span>
      <span class="info-card__label">Status toko</span>
      <span class="info-card__value"><?php echo !array_key_exists('is_open', $toko) || $toko['is_open'] === true ? 'Buka — menerima pesanan' : 'Tutup sementara — ' . htmlspecialchars((string)($toko['activity'] ?? 'Tutup sementara'), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="info-card__divider"></div>
    <div class="info-card__item">
      <span class="info-card__label">Hubungi</span>
      <a class="info-card__value info-card__link" href="https://wa.me/<?php echo $toko['wa']; ?>">WhatsApp toko</a>
    </div>
  </div>
</section>

<?php if (!empty($toko['gallery'])): ?>
<section class="store-gallery" aria-labelledby="galleryTitle">
  <div class="store-gallery__head">
    <h2 id="galleryTitle">Galeri Good Life</h2>
    <?php if (count($toko['gallery']) > 1): ?>
    <div class="store-gallery__controls">
      <button type="button" id="galleryPrev" aria-label="Foto sebelumnya">&lsaquo;</button>
      <button type="button" id="galleryNext" aria-label="Foto berikutnya">&rsaquo;</button>
    </div>
    <?php endif; ?>
  </div>
  <div class="store-gallery__track" id="galleryTrack">
    <?php foreach ($toko['gallery'] as $image): ?>
      <img src="<?php echo htmlspecialchars(goodlife_media_url($image), ENT_QUOTES, 'UTF-8'); ?>" alt="Galeri Good Life" loading="lazy">
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ULASAN -->
<section class="reviews" id="ulasan">
  <div class="reviews__head">
    <div class="reviews__score">
      <span class="reviews__score-num"><?php echo $avg ?: '–'; ?></span>
      <div>
        <div class="reviews__stars" aria-hidden="true"><?php echo str_repeat('★', round($avg)) . str_repeat('☆', 5 - round($avg)); ?></div>
        <span class="reviews__count"><?php echo count($reviews); ?> ulasan pelanggan</span>
      </div>
    </div>
    <div class="reviews__controls" id="reviewsControls" <?php echo count($reviews) <= 3 ? 'hidden' : ''; ?>>
      <button class="reviews__arrow" type="button" id="reviewsPrev" aria-label="Lihat ulasan sebelumnya" disabled>&lsaquo;</button>
      <button class="reviews__arrow" type="button" id="reviewsNext" aria-label="Lihat ulasan berikutnya">&rsaquo;</button>
    </div>
  </div>

  <div class="reviews__carousel">
    <div class="reviews__viewport">
      <div class="reviews__grid" id="reviewsGrid">
        <?php foreach (array_reverse($reviews) as $r): ?>
          <article class="review-card">
            <div class="review-card__top">
              <span class="review-card__name"><?php echo htmlspecialchars($r['nama']); ?></span>
              <span class="review-card__stars"><?php echo str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']); ?></span>
            </div>
            <p class="review-card__text"><?php echo htmlspecialchars($r['komentar']); ?></p>
            <span class="review-card__date"><?php echo htmlspecialchars($r['tanggal']); ?></span>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
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

  var reviewsGrid = document.getElementById('reviewsGrid');
  var reviewsPrev = document.getElementById('reviewsPrev');
  var reviewsNext = document.getElementById('reviewsNext');
  if (reviewsGrid && reviewsPrev && reviewsNext) {
    function updateReviewControls() {
      var maxScroll = reviewsGrid.scrollWidth - reviewsGrid.clientWidth;
      reviewsPrev.disabled = reviewsGrid.scrollLeft <= 1;
      reviewsNext.disabled = reviewsGrid.scrollLeft >= maxScroll - 1;
    }
    function scrollReviews(direction) {
      var card = reviewsGrid.querySelector('.review-card');
      if (!card) return;
      var gap = parseFloat(getComputedStyle(reviewsGrid).columnGap) || 0;
      reviewsGrid.scrollBy({ left: direction * (card.getBoundingClientRect().width + gap), behavior: 'smooth' });
    }
    reviewsPrev.addEventListener('click', function () { scrollReviews(-1); });
    reviewsNext.addEventListener('click', function () { scrollReviews(1); });
    reviewsGrid.addEventListener('scroll', updateReviewControls);
    window.addEventListener('resize', updateReviewControls);
    updateReviewControls();
  }

  var galleryTrack = document.getElementById('galleryTrack');
  if (galleryTrack) {
    ['galleryPrev', 'galleryNext'].forEach(function (id, index) {
      var button = document.getElementById(id);
      if (!button) return;
      button.addEventListener('click', function () {
        var image = galleryTrack.querySelector('img');
        if (!image) return;
        var gap = parseFloat(getComputedStyle(galleryTrack).gap) || 0;
        galleryTrack.scrollBy({ left: (index === 0 ? -1 : 1) * (image.getBoundingClientRect().width + gap), behavior: 'smooth' });
      });
    });
  }

});
</script>
</body>
</html>