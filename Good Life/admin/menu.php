<?php
require __DIR__ . '/includes/bootstrap.php';
admin_require_auth();

$menu = admin_read_json('data/menu.json');
$categoryRecords = admin_read_json('data/categories.json');
$categories = [];
foreach ($categoryRecords as $category) {
    if (!is_array($category) || !isset($category['id'], $category['nama'])) {
        continue;
    }
    $categories[$category['id']] = $category['nama'];
}
$categories += [
    'extra_topping_makanan' => 'Extra Topping (Makanan)',
    'extra_topping_minuman' => 'Extra Topping (Minuman)',
];
$editId = (string)($_GET['edit'] ?? '');
$editing = null;
foreach ($menu as $item) {
    if (($item['id'] ?? '') === $editId) {
        $editing = $item;
        break;
    }
}
$pageTitle = 'Katalog Menu';
$activePage = 'menu';
$csrf = admin_csrf_token();
require __DIR__ . '/includes/header.php';
?>
<style>
#categoryManager{margin-bottom:1.2rem}
#formMenu{margin-top:1.2rem}
.category-groups{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;min-width:0}
.category-groups>.card{min-width:0;container-type:inline-size}
.category-list{display:grid;gap:.55rem;min-height:54px}
.category-row{display:grid;grid-template-columns:auto minmax(120px,1fr) minmax(115px,.8fr) auto auto;align-items:center;gap:.55rem;padding:.65rem;border:1px solid var(--grey-300);border-radius:10px;background:#fff}
.category-row.is-dragging{opacity:.45}
.category-row.is-drop-target{border-color:var(--green-500);background:#f1f7f2}
.category-row input,.category-row select{min-width:0;width:100%;border:1px solid var(--grey-300);border-radius:8px;padding:.55rem}
.category-drag{cursor:grab;color:var(--grey-700);font-size:1.2rem;touch-action:none}
.category-add{display:flex;align-items:end;gap:.7rem;flex-wrap:wrap;margin-top:1rem}
.category-add .field{flex:1 1 180px}
.category-help{margin:.2rem 0 1rem;color:var(--grey-700);font-size:.88rem}
.category-empty{color:var(--grey-500);font-size:.88rem}
@container (max-width:620px){
  .category-row{grid-template-columns:auto minmax(0,1fr)}
  .category-row select{grid-column:2;grid-row:2}
  .category-row .category-save{grid-column:1;grid-row:3}
  .category-row .category-delete{grid-column:2;grid-row:3}
  .category-row .btn{width:100%;min-width:0;white-space:normal;padding-inline:.5rem}
}
@media(max-width:850px){
  .category-groups{grid-template-columns:1fr}
  .category-row{grid-template-columns:auto minmax(0,1fr)}
  .category-row select{grid-column:2;grid-row:2}
  .category-row .category-save{grid-column:1;grid-row:3}
  .category-row .category-delete{grid-column:2;grid-row:3}
  .category-row .btn{width:100%;white-space:normal}
}
</style>
<header class="page-head">
  <div><h1>Katalog Menu</h1><p>Kelola harga, foto, dan ketersediaan menu.</p></div>
  <a class="btn btn--primary" href="menu.php#formMenu">Tambah menu</a>
</header>
<section class="card" id="categoryManager">
  <div class="card-head"><h2>Kelola kategori</h2></div>
  <p class="category-help">Tarik kategori untuk mengubah urutannya. Kategori yang masih memiliki menu harus dikosongkan sebelum dihapus.</p>
  <p class="alert alert--error" id="categoryMessage" hidden role="alert"></p>
  <div class="category-groups">
    <?php foreach (['makanan' => 'Makanan', 'minuman' => 'Minuman'] as $groupId => $groupLabel): ?>
      <div class="card" style="padding:1rem">
        <div class="card-head"><h2><?php echo admin_h($groupLabel); ?></h2></div>
        <div class="category-list" data-category-list="<?php echo admin_h($groupId); ?>">
          <?php foreach ($categoryRecords as $category): ?>
            <?php if (($category['kelompok'] ?? '') !== $groupId) continue; ?>
            <div class="category-row" draggable="true" data-category-id="<?php echo admin_h($category['id']); ?>">
              <span class="category-drag" aria-label="Tarik untuk mengurutkan">⋮⋮</span>
              <input type="text" maxlength="60" value="<?php echo admin_h($category['nama']); ?>" aria-label="Nama kategori">
              <select aria-label="Kelompok kategori">
                <option value="makanan" <?php echo $groupId === 'makanan' ? 'selected' : ''; ?>>Makanan</option>
                <option value="minuman" <?php echo $groupId === 'minuman' ? 'selected' : ''; ?>>Minuman</option>
              </select>
              <button type="button" class="btn btn--secondary category-save">Simpan</button>
              <button type="button" class="btn btn--danger category-delete">Hapus</button>
            </div>
          <?php endforeach; ?>
          <?php if (!array_filter($categoryRecords, function ($category) use ($groupId) { return ($category['kelompok'] ?? '') === $groupId; })): ?>
            <span class="category-empty">Belum ada kategori.</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <form class="category-add" id="addCategoryForm">
    <div class="field"><label for="newCategoryName">Nama kategori baru</label><input id="newCategoryName" maxlength="60" required></div>
    <div class="field"><label for="newCategoryGroup">Kelompok</label><select id="newCategoryGroup" required><option value="makanan">Makanan</option><option value="minuman">Minuman</option></select></div>
    <button type="submit" class="btn btn--primary">Tambah kategori</button>
  </form>
</section>
<section class="card" id="formMenu">
  <div class="card-head"><h2><?php echo $editing ? 'Edit menu ' . admin_h($editing['nama']) : 'Tambah menu baru'; ?></h2></div>
  <form id="menuForm" class="form-grid" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo admin_h($csrf); ?>">
    <input type="hidden" name="id" value="<?php echo admin_h($editing['id'] ?? ''); ?>">
    <div class="field"><label for="name">Nama menu</label><input id="name" name="nama" maxlength="100" required value="<?php echo admin_h($editing['nama'] ?? ''); ?>"></div>
    <div class="field"><label for="category">Kategori</label><select id="category" name="kategori" required>
      <?php foreach ($categories as $value => $label): ?><option value="<?php echo admin_h($value); ?>" <?php echo ($editing['kategori'] ?? '') === $value ? 'selected' : ''; ?>><?php echo admin_h($label); ?></option><?php endforeach; ?>
    </select></div>
    <div class="field"><label for="price">Harga (Rp)</label><input id="price" type="number" name="harga" min="0" max="10000000" step="500" required value="<?php echo admin_h($editing['harga'] ?? ''); ?>"></div>
    <div class="field"><label for="image">Foto menu</label><input id="image" type="file" name="gambar" accept="image/jpeg,image/png,image/webp">
      <?php if (!empty($editing['gambar'])): ?><p class="help">Foto saat ini: <?php echo admin_h($editing['gambar']); ?></p><?php endif; ?>
      <p class="help">JPG, PNG, atau WebP; maksimal 5 MB. Kosongkan untuk mempertahankan foto.</p>
    </div>
    <label class="field field--full"><span><input type="checkbox" name="tersedia" value="1" <?php echo !array_key_exists('tersedia', $editing ?? []) || !empty($editing['tersedia']) ? 'checked' : ''; ?>> Tersedia untuk dipesan</span></label>
    <div class="field--full actions">
      <button class="btn btn--primary" type="submit"><?php echo $editing ? 'Simpan perubahan' : 'Tambah menu'; ?></button>
      <?php if ($editing): ?><a class="btn btn--secondary" href="menu.php">Batal edit</a><?php endif; ?>
    </div>
    <p class="field--full alert alert--error" id="menuMessage" hidden role="alert"></p>
  </form>
</section>
<section class="card" style="margin-top:1.2rem">
  <div class="card-head"><h2>Menu aktif (<?php echo count($menu); ?>)</h2></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Foto</th><th>Menu</th><th>Kategori</th><th>Harga</th><th>Ketersediaan</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($menu as $item): ?>
      <tr>
        <td><?php if (!empty($item['gambar'])): ?><img class="thumb" src="../<?php echo admin_h($item['gambar']); ?>" alt=""><?php else: ?><span class="badge">Default</span><?php endif; ?></td>
        <td><strong><?php echo admin_h($item['nama']); ?></strong><br><span class="muted"><?php echo admin_h($item['id']); ?></span></td>
        <td><?php echo admin_h($categories[$item['kategori']] ?? $item['kategori']); ?></td>
        <td><?php echo admin_money($item['harga']); ?></td>
        <td><span class="badge <?php echo array_key_exists('tersedia', $item) && !$item['tersedia'] ? 'badge--danger' : 'badge--success'; ?>"><?php echo array_key_exists('tersedia', $item) && !$item['tersedia'] ? 'Habis' : 'Tersedia'; ?></span></td>
        <td><div class="actions">
          <a class="btn btn--secondary" href="menu.php?edit=<?php echo rawurlencode($item['id']); ?>#formMenu">Edit</a>
          <button type="button" class="btn btn--secondary" data-toggle-stock="<?php echo admin_h($item['id']); ?>" data-available="<?php echo !array_key_exists('tersedia', $item) || $item['tersedia'] ? '1' : '0'; ?>"><?php echo array_key_exists('tersedia', $item) && !$item['tersedia'] ? 'Tandai tersedia' : 'Tandai habis'; ?></button>
          <button type="button" class="btn btn--danger" data-delete-menu="<?php echo admin_h($item['id']); ?>">Hapus</button>
        </div></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<script>
var csrfToken = <?php echo json_encode($csrf); ?>;
var menuMessage = document.getElementById('menuMessage');
var categoryMessage = document.getElementById('categoryMessage');
function showCategoryMessage(message, error) {
  categoryMessage.textContent = message;
  categoryMessage.hidden = !message;
  categoryMessage.classList.toggle('alert--error', !!error);
}
function updateCategoryOrder(list) {
  var ids = Array.from(list.querySelectorAll('[data-category-id]')).map(function (row) { return row.dataset.categoryId; });
  fetch('api/manage_categories.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({ action: 'reorder', kelompok: list.dataset.categoryList, ids: ids })
  })
    .then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok || !data.ok) throw new Error(data.error || 'Urutan kategori gagal disimpan.');
        return data;
      });
    })
    .catch(function (error) {
      showCategoryMessage(error.message, true);
      window.setTimeout(function () { window.location.reload(); }, 1200);
    });
}
document.getElementById('addCategoryForm').addEventListener('submit', function (event) {
  event.preventDefault();
  var button = this.querySelector('[type="submit"]');
  button.disabled = true;
  fetch('api/manage_categories.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({
      action: 'add',
      nama: document.getElementById('newCategoryName').value.trim(),
      kelompok: document.getElementById('newCategoryGroup').value
    })
  })
    .then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok || !data.ok) throw new Error(data.error || 'Kategori gagal ditambahkan.');
        return data;
      });
    })
    .then(function () { window.location.reload(); })
    .catch(function (error) { showCategoryMessage(error.message, true); button.disabled = false; });
});
document.querySelectorAll('.category-row').forEach(function (row) {
  row.addEventListener('dragstart', function (event) {
    event.dataTransfer.setData('text/plain', row.dataset.categoryId);
    event.dataTransfer.effectAllowed = 'move';
    row.classList.add('is-dragging');
  });
  row.addEventListener('dragend', function () {
    row.classList.remove('is-dragging');
    document.querySelectorAll('.category-row').forEach(function (item) { item.classList.remove('is-drop-target'); });
  });
  row.addEventListener('dragover', function (event) {
    event.preventDefault();
    row.classList.add('is-drop-target');
  });
  row.addEventListener('dragleave', function () { row.classList.remove('is-drop-target'); });
  row.addEventListener('drop', function (event) {
    event.preventDefault();
    row.classList.remove('is-drop-target');
    var draggedId = event.dataTransfer.getData('text/plain');
    var dragged = document.querySelector('[data-category-id="' + CSS.escape(draggedId) + '"]');
    if (!dragged || dragged === row || dragged.parentElement !== row.parentElement) return;
    var bounds = row.getBoundingClientRect();
    row.parentElement.insertBefore(dragged, event.clientY < bounds.top + bounds.height / 2 ? row : row.nextSibling);
    updateCategoryOrder(row.parentElement);
  });
  row.querySelector('.category-save').addEventListener('click', function () {
    var button = this;
    button.disabled = true;
    fetch('api/manage_categories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify({
        action: 'update',
        id: row.dataset.categoryId,
        nama: row.querySelector('input').value.trim(),
        kelompok: row.querySelector('select').value
      })
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) throw new Error(data.error || 'Kategori gagal diperbarui.');
          return data;
        });
      })
      .then(function () { window.location.reload(); })
      .catch(function (error) { showCategoryMessage(error.message, true); button.disabled = false; });
  });
  row.querySelector('.category-delete').addEventListener('click', function () {
    if (!window.confirm('Hapus kategori ini? Kategori yang masih berisi menu tidak dapat dihapus.')) return;
    var button = this;
    button.disabled = true;
    fetch('api/manage_categories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify({ action: 'delete', id: row.dataset.categoryId })
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) throw new Error(data.error || 'Kategori gagal dihapus.');
          return data;
        });
      })
      .then(function () { window.location.reload(); })
      .catch(function (error) { showCategoryMessage(error.message, true); button.disabled = false; });
  });
});
function showMenuMessage(message, error) {
  menuMessage.textContent = message;
  menuMessage.hidden = !message;
  menuMessage.classList.toggle('alert--error', !!error);
}
document.getElementById('menuForm').addEventListener('submit', function (event) {
  event.preventDefault();
  var button = this.querySelector('[type="submit"]');
  button.disabled = true;
  var body = new FormData(this);
  fetch('api/save_menu.php', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken }, body: body })
    .then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok || !data.ok) throw new Error(data.error || 'Menu gagal disimpan.');
        return data;
      });
    })
    .then(function () { window.location.href = 'menu.php'; })
    .catch(function (error) { showMenuMessage(error.message, true); button.disabled = false; });
});
document.addEventListener('click', function (event) {
  var stockButton = event.target.closest('[data-toggle-stock]');
  var deleteButton = event.target.closest('[data-delete-menu]');
  var button = stockButton || deleteButton;
  if (!button) return;
  if (deleteButton && !window.confirm('Hapus menu ini? Tindakan ini tidak dapat dibatalkan.')) return;
  button.disabled = true;
  var url = stockButton ? 'api/save_menu.php' : 'api/delete_menu.php';
  var payload = stockButton
    ? { action: 'availability', id: stockButton.dataset.toggleStock, tersedia: stockButton.dataset.available !== '1' }
    : { id: deleteButton.dataset.deleteMenu };
  fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify(payload) })
    .then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok || !data.ok) throw new Error(data.error || 'Perubahan gagal disimpan.');
        return data;
      });
    })
    .then(function () { window.location.reload(); })
    .catch(function (error) { window.alert(error.message); button.disabled = false; });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
