<?php
require __DIR__ . '/includes/bootstrap.php';
admin_require_auth();
$store = admin_read_dataset('data/store.json');
$pageTitle = 'Pengaturan';
$activePage = 'pengaturan';
$csrf = admin_csrf_token();
require __DIR__ . '/includes/header.php';
?>
<header class="page-head"><div><h1>Pengaturan toko</h1><p>Perubahan profil dan galeri akan tampil di halaman pelanggan.</p></div></header>
<section class="card">
  <div class="card-head"><h2>Profil toko</h2></div>
  <form id="settingsForm" class="form-grid" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo admin_h($csrf); ?>">
    <div class="field"><label for="name">Nama toko</label><input id="name" name="nama" maxlength="100" required value="<?php echo admin_h($store['nama'] ?? ''); ?>"></div>
    <div class="field"><label for="whatsapp">Nomor WhatsApp</label><input id="whatsapp" name="wa" type="tel" inputmode="tel" required value="<?php echo admin_h($store['wa'] ?? ''); ?>"></div>
    <div class="field"><label for="open">Jam buka</label><input id="open" name="jam_buka" type="time" required value="<?php echo admin_h($store['jam_buka'] ?? '15:00'); ?>"></div>
    <div class="field"><label for="close">Jam tutup</label><input id="close" name="jam_tutup" type="time" required value="<?php echo admin_h($store['jam_tutup'] ?? '23:00'); ?>"></div>
    <div class="field field--full"><label for="address">Alamat</label><textarea id="address" name="alamat" maxlength="300" required><?php echo admin_h($store['alamat'] ?? ''); ?></textarea></div>
    <div class="field field--full"><label><input type="checkbox" id="cashAcceptance" <?php echo !empty($store['menerima_tunai']) ? 'checked' : ''; ?>> Terima pembayaran tunai</label><p class="help">Jika dimatikan, pelanggan tidak dapat memilih tunai untuk pembayaran makanan. Pembayaran ongkir tunai tetap tersedia.</p></div>
    <div class="field field--full"><label for="gallery">Tambah foto galeri</label><input id="gallery" type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple><p class="help">Maksimal 10 foto; JPG, PNG, atau WebP; setiap file maksimal 5 MB.</p></div>
    <div class="field--full actions"><button class="btn btn--primary" type="submit">Simpan pengaturan</button></div>
    <p class="field--full alert alert--error" id="settingsMessage" hidden role="alert"></p>
  </form>
</section>
<section class="card" style="margin-top:1.2rem">
  <div class="card-head"><h2>Galeri carousel (<?php echo count($store['gallery'] ?? []); ?>/10)</h2></div>
  <?php if (empty($store['gallery'])): ?><p class="empty">Belum ada foto galeri. Foto yang ditambahkan akan tampil di carousel Beranda.</p><?php else: ?>
    <div class="gallery-grid">
      <?php foreach ($store['gallery'] as $image): ?>
        <div class="gallery-item">
          <img src="../<?php echo admin_h(goodlife_media_url($image)); ?>" alt="Foto galeri toko">
          <button class="btn btn--danger" type="button" data-delete-gallery="<?php echo admin_h($image); ?>">Hapus foto</button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<script>
var csrfToken = <?php echo json_encode($csrf); ?>;
var message = document.getElementById('settingsMessage');
var cashAcceptance = document.getElementById('cashAcceptance');
cashAcceptance.addEventListener('change', function () {
  cashAcceptance.disabled = true;
  fetch('api/update_cash_acceptance.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({ menerima_tunai: cashAcceptance.checked })
  }).then(function (response) { return response.json().then(function (data) { if (!response.ok || !data.ok) throw new Error(data.error || 'Pengaturan tunai gagal disimpan.'); }); })
    .catch(function (error) { cashAcceptance.checked = !cashAcceptance.checked; showMessage(error.message); })
    .finally(function () { cashAcceptance.disabled = false; });
});
function showMessage(text) { message.textContent = text; message.hidden = !text; }
document.getElementById('settingsForm').addEventListener('submit', function (event) {
  event.preventDefault();
  var button = this.querySelector('[type="submit"]');
  button.disabled = true;
  fetch('api/save_settings.php', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body: new FormData(this) })
    .then(function (response) { return response.json().then(function (data) { if (!response.ok || !data.ok) throw new Error(data.error || 'Pengaturan gagal disimpan.'); return data; }); })
    .then(function () { window.location.reload(); })
    .catch(function (error) { showMessage(error.message); button.disabled = false; });
});
document.addEventListener('click', function (event) {
  var button = event.target.closest('[data-delete-gallery]');
  if (!button || !window.confirm('Hapus foto galeri ini?')) return;
  button.disabled = true;
  fetch('api/save_settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({ action: 'delete_gallery', image: button.dataset.deleteGallery })
  })
    .then(function (response) { return response.json().then(function (data) { if (!response.ok || !data.ok) throw new Error(data.error || 'Foto gagal dihapus.'); return data; }); })
    .then(function () { window.location.reload(); })
    .catch(function (error) { window.alert(error.message); button.disabled = false; });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
