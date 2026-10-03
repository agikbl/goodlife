<?php
session_start();

$draft = $_SESSION['checkout_draft'] ?? null;
if (!is_array($draft) || !isset($draft['token'], $draft['items'], $draft['subtotal'], $draft['expires_at']) || $draft['expires_at'] < time()) {
    unset($_SESSION['checkout_draft']);
    header('Location: pesan.php');
    exit;
}

$storeLatitude = -4.0077714;
$storeLongitude = 119.632276;
$draftJson = json_encode($draft['items'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembayaran — Good Life Parepare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Karla:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
:root{--green-900:#16321F;--green-700:#234A2E;--green-500:#3E7A4F;--grey-100:#F4F5F3;--grey-300:#DEE1DB;--grey-500:#9CA39B;--grey-700:#5B615C;--ink:#1A1D1B;--white:#fff;--font-display:'Fraunces',serif;--font-body:'Karla',sans-serif;--radius:14px;--shadow:0 12px 30px rgba(22,50,31,.14)}
*{box-sizing:border-box}body{margin:0;background:var(--grey-100);color:var(--ink);font-family:var(--font-body);-webkit-font-smoothing:antialiased}button,input{font:inherit}button{cursor:pointer}.topbar{background:var(--white);border-bottom:1px solid var(--grey-300);padding:1rem 1.5rem}.topbar__inner{max-width:900px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:1rem}.brand{font:700 1.3rem var(--font-display);color:var(--green-900)}.back-link{color:var(--green-700);font-weight:700;text-decoration:underline;text-underline-offset:3px}
.page{max-width:760px;margin:2rem auto;padding:0 1rem 4rem}.page h1{font:600 clamp(1.8rem,5vw,2.4rem) var(--font-display);color:var(--green-900);margin:0 0 .4rem}.intro{color:var(--grey-700);margin:0 0 1.5rem}.panel{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);padding:clamp(1.2rem,4vw,2rem)}.steps{display:flex;gap:.4rem;margin:0 0 1.5rem}.steps span{height:5px;flex:1;border-radius:99px;background:var(--grey-300)}.steps span.is-done{background:var(--green-500)}.step{display:none;flex-direction:column;gap:1.1rem}.step.is-active{display:flex}.step h2{font:600 1.35rem var(--font-display);color:var(--green-900);margin:0}.summary{display:flex;flex-direction:column;gap:.45rem}.summary-row{display:flex;justify-content:space-between;gap:1rem;padding:.35rem 0;color:var(--grey-700)}.summary-row strong{color:var(--ink);text-align:right}.summary-row--total{border-top:1px solid var(--grey-300);padding-top:.8rem;margin-top:.4rem;font-size:1.05rem}.summary-row--total strong{color:var(--green-900)}
.field label{display:block;margin-bottom:.4rem;font-size:.9rem;font-weight:700;color:var(--green-900)}.field input{width:100%;border:1px solid var(--grey-300);border-radius:10px;padding:.75rem .9rem}.hint{font-size:.82rem;color:var(--grey-500);margin:.4rem 0 0;line-height:1.5}.choice{display:flex;flex-direction:column;gap:.3rem;padding:1rem 1.1rem;border:1.5px solid var(--grey-300);border-radius:var(--radius);cursor:pointer}.choice.is-selected{border-color:var(--green-900);background:var(--grey-100)}.choice strong{color:var(--green-900)}.choice small{color:var(--grey-700);font-size:.86rem}.map-wrap{display:flex;flex-direction:column;gap:.6rem}.map-actions{display:flex;justify-content:space-between;align-items:center;gap:.8rem;flex-wrap:wrap}.map-help{font-size:.86rem;color:var(--grey-700);margin:0}.map{height:340px;border-radius:12px;z-index:0}.distance-info{display:flex;justify-content:space-between;gap:1rem;align-items:center;padding:.85rem 1rem;border-radius:10px;background:var(--grey-100)}.distance-info span{color:var(--grey-700);font-size:.9rem}.distance-info strong{color:var(--green-900)}.field--address{margin-top:.1rem}.error{color:#8A3B2B;font-size:.9rem;margin:0}.error[hidden]{display:none}.actions{display:flex;gap:.75rem;margin-top:.4rem}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:.75rem 1.2rem;border:1px solid transparent;border-radius:999px;font-weight:700}.btn--solid{background:var(--green-900);color:var(--white)}.btn--ghost{background:transparent;border-color:var(--green-900);color:var(--green-900)}.btn--block{flex:1}.btn:disabled{opacity:.5;cursor:not-allowed}.confirm{text-align:center;padding:.7rem 0}.confirm__id{font:600 1.6rem var(--font-display);color:var(--green-900)}.status-tracker{display:flex;justify-content:space-between;margin:0 0 1.5rem}.status-step{flex:1;text-align:center;font-size:.75rem;color:var(--grey-500)}.status-step__dot{width:20px;height:20px;border-radius:50%;background:var(--grey-300);margin:0 auto .4rem}.status-step.is-active{color:var(--green-900);font-weight:700}.status-step.is-active .status-step__dot{background:var(--green-900)}.leaflet-container{font:14px var(--font-body)}@media(max-width:540px){.topbar{padding:.9rem 1rem}.page{margin:1.2rem auto}.map{height:300px}.actions .btn{padding:.7rem .8rem}.distance-info{align-items:flex-start;flex-direction:column;gap:.25rem}}
</style>
</head>
<body>
<header class="topbar"><div class="topbar__inner"><a class="brand" href="pesan.php">Good Life</a><a class="back-link" href="pesan.php">Kembali ke menu</a></div></header>
<main class="page">
  <h1>Pembayaran</h1>
  <p class="intro">Selesaikan detail pesananmu dalam beberapa langkah.</p>
  <section class="panel">
    <div class="steps" id="steps" aria-label="Tahapan pemesanan"><span class="is-done"></span><span></span><span></span><span></span></div>
    <div class="step is-active" data-step="1">
      <h2>Ringkasan &amp; kontak</h2>
      <div class="summary" id="orderSummary"></div>
      <div class="summary-row summary-row--total"><span>Subtotal pesanan</span><strong id="subtotal"></strong></div>
      <div class="field">
        <label for="customerName">Nama pemesan</label>
        <input type="text" id="customerName" maxlength="100" autocomplete="name" required>
      </div>
      <div class="field">
        <label for="whatsapp">Nomor WhatsApp kamu</label>
        <input type="tel" id="whatsapp" placeholder="08xxxxxxxxxx" inputmode="tel" autocomplete="tel" required>
        <p class="hint">Dipakai untuk mengecek pesanan dan menghubungimu jika diperlukan.</p>
      </div>
      <p class="error" id="step1Error" hidden></p>
      <div class="actions"><button type="button" class="btn btn--solid btn--block" id="continue1" disabled>Lanjut</button></div>
    </div>

    <div class="step" data-step="2">
      <h2>Pengiriman</h2>
      <button type="button" class="choice" data-delivery="ambil"><strong>Ambil di toko</strong><small>Kamu jemput sendiri pesanan ke Good Life Parepare.</small></button>
      <button type="button" class="choice" data-delivery="antar"><strong>Diantarkan ke lokasi</strong><small>Tandai lokasi pada peta. Jarak rute diperkirakan dari jarak lurus × 1,3.</small></button>
      <div class="field" id="pickupTimeField" hidden>
        <label for="pickupTime">Perkiraan waktu pengambilan</label>
        <input type="time" id="pickupTime">
      </div>
      <div id="deliveryDetails" hidden>
        <div class="map-wrap">
          <div class="map-actions">
            <p class="map-help">Ketuk peta untuk menandai lokasi tujuan.</p>
            <button class="btn btn--ghost" type="button" id="locateMe">Gunakan lokasi saya</button>
          </div>
          <div class="map" id="deliveryMap" aria-label="Peta untuk memilih lokasi pengantaran"></div>
          <div class="distance-info"><span id="distanceText">Pilih lokasi pengantaran pada peta</span><strong id="shippingFee">Ongkir —</strong></div>
        </div>
        <div class="field field--address">
          <label for="address">Alamat atau patokan tambahan (opsional)</label>
          <input type="text" id="address" placeholder="Nama jalan, nomor rumah, atau patokan">
        </div>
        <div>
          <label style="display:block;margin-bottom:.5rem;font-weight:700;color:var(--green-900)">Pembayaran ongkir</label>
          <button type="button" class="choice" data-shipping-pay="qris"><strong>QRIS</strong></button>
          <button type="button" class="choice" data-shipping-pay="tunai" style="margin-top:.5rem"><strong>Tunai ke kurir</strong></button>
        </div>
      </div>
      <p class="error" id="step2Error" hidden></p>
      <div class="actions"><button type="button" class="btn btn--ghost" id="back2">Kembali</button><button type="button" class="btn btn--solid btn--block" id="continue2" disabled>Lanjut</button></div>
    </div>

    <div class="step" data-step="3">
      <h2>Metode pembayaran</h2>
      <button type="button" class="choice" data-payment="qris"><strong>QRIS</strong><small>Scan &amp; bayar lewat aplikasi e-wallet atau m-banking.</small></button>
      <button type="button" class="choice" data-payment="tunai"><strong>Tunai</strong><small>Bayar cash saat ambil atau pesanan diantar.</small></button>
      <div id="finalSummary"></div>
      <p class="error" id="step3Error" hidden></p>
      <div class="actions"><button type="button" class="btn btn--ghost" id="back3">Kembali</button><button type="button" class="btn btn--solid btn--block" id="placeOrder" disabled>Buat pesanan</button></div>
    </div>

    <div class="step" data-step="4">
      <div class="status-tracker" id="confirmationTracker"></div>
      <div class="confirm"><h2>Pesanan berhasil dibuat!</h2><p class="confirm__id" id="orderId"></p><p class="hint">Simpan ID pesanan ini untuk mengecek status di halaman Pesan.</p></div>
      <a class="btn btn--solid btn--block" href="pesan.php">Selesai</a>
    </div>
  </section>
</main>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var items = <?php echo $draftJson; ?>;
  var draftToken = <?php echo json_encode($draft['token']); ?>;
  var store = [<?php echo json_encode($storeLatitude); ?>, <?php echo json_encode($storeLongitude); ?>];
  var subtotal = <?php echo (int)$draft['subtotal']; ?>;
  var state = { name: '', whatsapp: '', delivery: null, pickupTime: '', location: null, address: '', shippingMethod: null, paymentMethod: null, distance: null, fee: 0 };
  var map = null;
  var marker = null;
  var routeLine = null;
  var rupiah = function (value) { return 'Rp' + Math.round(value).toLocaleString('id-ID'); };
  var steps = document.querySelectorAll('.step');

  function normalizeWhatsApp(value) {
    var digits = value.replace(/\D/g, '');
    if (digits.indexOf('0') === 0) return '62' + digits.slice(1);
    if (digits.indexOf('8') === 0) return '62' + digits;
    return digits;
  }
  function validWhatsApp(value) { return /^628[0-9]{7,11}$/.test(value); }
  function goToStep(number) {
    steps.forEach(function (step) { step.classList.toggle('is-active', Number(step.dataset.step) === number); });
    document.querySelectorAll('#steps span').forEach(function (step, index) { step.classList.toggle('is-done', index < number); });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  function showError(id, message) {
    var error = document.getElementById(id);
    error.textContent = message;
    error.hidden = !message;
  }
  function renderSummary() {
    var summary = document.getElementById('orderSummary');
    items.forEach(function (item) {
      var row = document.createElement('div');
      row.className = 'summary-row';
      var description = item.qty + '× ' + item.nama;
      if (item.toppings.length) description += ' + ' + item.toppings.map(function (topping) { return topping.nama; }).join(', ');
      var name = document.createElement('span');
      name.textContent = description;
      var price = document.createElement('strong');
      price.textContent = rupiah(item.qty * item.harga);
      row.appendChild(name);
      row.appendChild(price);
      summary.appendChild(row);
    });
    document.getElementById('subtotal').textContent = rupiah(subtotal);
  }
  function calculateFee(distance) {
    if (distance <= 2) return 8000;
    var uncappedFee = distance <= 5 ? 8000 + (distance - 2) * (7000 / 3) : 15000;
    return Math.min(15000, Math.max(8000, Math.round(uncappedFee / 1000) * 1000));
  }
  function haversineKm(first, second) {
    var radians = function (degrees) { return degrees * Math.PI / 180; };
    var latitudeDelta = radians(second[0] - first[0]);
    var longitudeDelta = radians(second[1] - first[1]);
    var a = Math.sin(latitudeDelta / 2) * Math.sin(latitudeDelta / 2) +
      Math.cos(radians(first[0])) * Math.cos(radians(second[0])) *
      Math.sin(longitudeDelta / 2) * Math.sin(longitudeDelta / 2);
    return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }
  function updateLocation(latitude, longitude) {
    state.location = [latitude, longitude];
    if (!marker) {
      marker = L.marker(state.location, { draggable: true }).addTo(map);
      marker.on('dragend', function (event) {
        var position = event.target.getLatLng();
        updateLocation(position.lat, position.lng);
      });
    } else {
      marker.setLatLng(state.location);
    }
    if (routeLine) routeLine.setLatLngs([store, state.location]);
    else routeLine = L.polyline([store, state.location], { color: '#3E7A4F', dashArray: '7 7' }).addTo(map);

    var straightDistance = haversineKm(store, state.location);
    state.distance = straightDistance * 1.3;
    state.fee = calculateFee(state.distance);
    document.getElementById('distanceText').textContent =
      'Estimasi rute ' + state.distance.toFixed(2) + ' km (jarak lurus ' + straightDistance.toFixed(2) + ' km)';
    document.getElementById('shippingFee').textContent = 'Ongkir ' + rupiah(state.fee);
    checkDeliveryStep();
  }
  function initializeMap() {
    if (map) {
      map.invalidateSize();
      return;
    }
    map = L.map('deliveryMap').setView(store, 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    L.marker(store).addTo(map).bindPopup('Good Life Parepare');
    map.on('click', function (event) { updateLocation(event.latlng.lat, event.latlng.lng); });
  }
  function checkDeliveryStep() {
    var isValid = (state.delivery === 'ambil' && Boolean(state.pickupTime)) ||
      (state.delivery === 'antar' && state.location !== null && state.shippingMethod !== null);
    document.getElementById('continue2').disabled = !isValid;
  }
  function renderFinalSummary() {
    var final = document.getElementById('finalSummary');
    final.innerHTML = '';
    var orderTotal = subtotal + (state.delivery === 'antar' ? state.fee : 0);
    [
      ['Subtotal', rupiah(subtotal)],
      ['Pengiriman', state.delivery === 'antar' ? rupiah(state.fee) : 'Ambil di toko'],
      ['Total pembayaran', rupiah(orderTotal)]
    ].forEach(function (entry, index) {
      var row = document.createElement('div');
      row.className = 'summary-row' + (index === 2 ? ' summary-row--total' : '');
      var label = document.createElement('span');
      label.textContent = entry[0];
      var value = document.createElement('strong');
      value.textContent = entry[1];
      row.appendChild(label);
      row.appendChild(value);
      final.appendChild(row);
    });
  }

  renderSummary();
  var whatsappInput = document.getElementById('whatsapp');
  var nameInput = document.getElementById('customerName');
  nameInput.addEventListener('input', function () {
    state.name = nameInput.value.trim();
    document.getElementById('continue1').disabled = !state.name || !validWhatsApp(state.whatsapp);
  });
  whatsappInput.addEventListener('input', function () {
    state.whatsapp = normalizeWhatsApp(whatsappInput.value);
    document.getElementById('continue1').disabled = !state.name || !validWhatsApp(state.whatsapp);
  });
  document.getElementById('continue1').addEventListener('click', function () {
    if (!state.name || !validWhatsApp(state.whatsapp)) {
      showError('step1Error', 'Masukkan nama pemesan dan nomor WhatsApp Indonesia yang valid.');
      return;
    }
    showError('step1Error', '');
    goToStep(2);
  });

  document.querySelectorAll('[data-delivery]').forEach(function (button) {
    button.addEventListener('click', function () {
      document.querySelectorAll('[data-delivery]').forEach(function (choice) { choice.classList.toggle('is-selected', choice === button); });
      state.delivery = button.dataset.delivery;
      var details = document.getElementById('deliveryDetails');
      details.hidden = state.delivery !== 'antar';
      var pickupTimeField = document.getElementById('pickupTimeField');
      pickupTimeField.hidden = state.delivery !== 'ambil';
      if (state.delivery !== 'ambil') {
        state.pickupTime = '';
        document.getElementById('pickupTime').value = '';
      }
      if (state.delivery === 'antar') {
        initializeMap();
      }
      checkDeliveryStep();
    });
  });
  document.getElementById('pickupTime').addEventListener('input', function (event) {
    state.pickupTime = event.target.value;
    checkDeliveryStep();
  });
  document.querySelectorAll('[data-shipping-pay]').forEach(function (button) {
    button.addEventListener('click', function () {
      document.querySelectorAll('[data-shipping-pay]').forEach(function (choice) { choice.classList.toggle('is-selected', choice === button); });
      state.shippingMethod = button.dataset.shippingPay;
      checkDeliveryStep();
    });
  });
  document.getElementById('locateMe').addEventListener('click', function () {
    if (!navigator.geolocation) {
      showError('step2Error', 'Browser ini tidak mendukung akses lokasi.');
      return;
    }
    navigator.geolocation.getCurrentPosition(function (position) {
      showError('step2Error', '');
      updateLocation(position.coords.latitude, position.coords.longitude);
      map.setView(state.location, 16);
    }, function () {
      showError('step2Error', 'Lokasi tidak dapat diakses. Izinkan akses lokasi atau tandai titik pada peta.');
    }, { enableHighAccuracy: true, timeout: 10000 });
  });
  document.getElementById('address').addEventListener('input', function (event) { state.address = event.target.value.trim(); });
  document.getElementById('back2').addEventListener('click', function () { goToStep(1); });
  document.getElementById('continue2').addEventListener('click', function () {
    if (!state.delivery || (state.delivery === 'ambil' && !state.pickupTime) ||
        (state.delivery === 'antar' && (!state.location || !state.shippingMethod))) {
      showError('step2Error', 'Pilih metode pengiriman dan lengkapi waktu pengambilan atau lokasi serta pembayaran ongkir.');
      return;
    }
    showError('step2Error', '');
    renderFinalSummary();
    goToStep(3);
  });
  document.querySelectorAll('[data-payment]').forEach(function (button) {
    button.addEventListener('click', function () {
      document.querySelectorAll('[data-payment]').forEach(function (choice) { choice.classList.toggle('is-selected', choice === button); });
      state.paymentMethod = button.dataset.payment;
      document.getElementById('placeOrder').disabled = false;
    });
  });
  document.getElementById('back3').addEventListener('click', function () { goToStep(2); });
  document.getElementById('placeOrder').addEventListener('click', function () {
    var button = document.getElementById('placeOrder');
    button.disabled = true;
    button.textContent = 'Memproses...';
    showError('step3Error', '');
    fetch('api/submit_order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        checkout_token: draftToken,
        nama: state.name,
        whatsapp: state.whatsapp,
        pengiriman: state.delivery,
        waktu_pengambilan: state.delivery === 'ambil' ? state.pickupTime : null,
        alamat: state.address,
        latitude: state.location ? state.location[0] : null,
        longitude: state.location ? state.location[1] : null,
        bayar_ongkir: state.delivery === 'antar' ? state.shippingMethod : null,
        metode_bayar: state.paymentMethod
      })
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) throw new Error(data.error || 'Gagal membuat pesanan.');
          return data;
        });
      })
      .then(function (data) {
        document.getElementById('orderId').textContent = data.order.id;
        var statuses = data.order.pengiriman === 'antar'
          ? ['Diterima', 'Diproses', 'Diantarkan', 'Selesai']
          : ['Diterima', 'Diproses', 'Siap Diambil', 'Selesai'];
        var tracker = document.getElementById('confirmationTracker');
        tracker.innerHTML = '';
        statuses.forEach(function (status, index) {
          var step = document.createElement('div');
          step.className = 'status-step' + (index === 0 ? ' is-active' : '');
          var dot = document.createElement('div');
          dot.className = 'status-step__dot';
          step.appendChild(dot);
          step.appendChild(document.createTextNode(status));
          tracker.appendChild(step);
        });
        goToStep(4);
      })
      .catch(function (error) {
        showError('step3Error', error.message || 'Terjadi kesalahan jaringan, coba lagi.');
        button.disabled = false;
        button.textContent = 'Buat pesanan';
      });
  });
});
</script>
</body>
</html>
