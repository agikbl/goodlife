<?php
require __DIR__ . '/includes/bootstrap.php';
admin_require_auth();
$pageTitle = 'Pesanan';
$activePage = 'pesanan';
$csrf = admin_csrf_token();
require __DIR__ . '/includes/header.php';
?>
<header class="page-head">
  <div><h1>Pesanan</h1><p id="ordersDescription">Pesanan hari ini diperbarui otomatis setiap 15 detik.</p></div>
  <div class="actions">
    <button class="btn btn--secondary" id="historyToggle" type="button">Lihat riwayat pesanan</button>
    <span class="badge" id="lastUpdated">Memuat pesanan...</span>
  </div>
</header>
<nav class="order-status-nav" aria-label="Filter status pesanan">
  <button class="order-status-tab is-active" type="button" data-status-filter="all" aria-pressed="true">Semua <span data-status-count="all">0</span></button>
  <button class="order-status-tab" type="button" data-status-filter="Diterima" aria-pressed="false">Diterima <span data-status-count="Diterima">0</span></button>
  <button class="order-status-tab" type="button" data-status-filter="Diproses" aria-pressed="false">Diproses <span data-status-count="Diproses">0</span></button>
  <button class="order-status-tab" type="button" data-status-filter="Siap Diambil" aria-pressed="false">Siap Diambil <span data-status-count="Siap Diambil">0</span></button>
  <button class="order-status-tab" type="button" data-status-filter="Diantarkan" aria-pressed="false">Diantarkan <span data-status-count="Diantarkan">0</span></button>
  <button class="order-status-tab" type="button" data-status-filter="Selesai" aria-pressed="false">Selesai <span data-status-count="Selesai">0</span></button>
</nav>
<section class="card">
  <div class="card-head"><h2 id="ordersTitle">Pesanan hari ini</h2></div>
  <p class="alert alert--error" id="ordersError" hidden role="alert"></p>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Pesanan</th><th>Waktu</th><th>Pelanggan</th><th>Detail</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody id="ordersBody"><tr><td colspan="8" class="empty">Memuat pesanan...</td></tr></tbody>
    </table>
  </div>
</section>
<style>
.order-status-nav{display:flex;gap:.45rem;overflow-x:auto;margin:-.55rem 0 1.2rem;padding:.25rem 0 .65rem;scrollbar-width:thin}
.order-status-tab{display:inline-flex;align-items:center;gap:.45rem;flex:none;border:0;border-radius:999px;background:transparent;color:var(--grey-700);padding:.65rem .9rem;font-weight:700;white-space:nowrap}
.order-status-tab:hover{background:#e8ece7;color:var(--green-900)}
.order-status-tab.is-active{background:var(--green-900);color:#fff}
.order-status-tab span{display:inline-grid;place-items:center;min-width:1.35rem;height:1.35rem;padding:0 .35rem;border-radius:99px;background:rgba(22,50,31,.1);font-size:.75rem}
.order-status-tab.is-active span{background:rgba(255,255,255,.2)}
</style>
<script>
var csrfToken = <?php echo json_encode($csrf); ?>;
var ordersBody = document.getElementById('ordersBody');
var ordersError = document.getElementById('ordersError');
var lastUpdated = document.getElementById('lastUpdated');
var historyToggle = document.getElementById('historyToggle');
var ordersDescription = document.getElementById('ordersDescription');
var ordersTitle = document.getElementById('ordersTitle');
var showingHistory = false;
var activeStatusFilter = 'all';
var currentOrders = [];
function escapeText(value) {
  var element = document.createElement('span');
  element.textContent = value == null ? '' : String(value);
  return element.innerHTML;
}
function nextOrderStatus(order) {
  if (order.status === 'Diterima') return 'Diproses';
  if (order.status === 'Diproses') return order.pengiriman === 'antar' ? 'Diantarkan' : 'Siap Diambil';
  if (order.status === 'Diantarkan' || order.status === 'Siap Diambil') return 'Selesai';
  return '';
}
function canAdvanceOrder(order, nextStatus) {
  var isPaid = order.status_pembayaran === 'Lunas';
  if (order.metode_bayar === 'qris' && !isPaid) return false;
  return nextStatus !== 'Selesai' || isPaid;
}
function orderStatus(order) {
  return order.status || 'Diterima';
}
function renderStatusCounts(orders) {
  var counts = { all: orders.length };
  orders.forEach(function (order) {
    var status = orderStatus(order);
    counts[status] = (counts[status] || 0) + 1;
  });
  document.querySelectorAll('[data-status-count]').forEach(function (element) {
    element.textContent = counts[element.dataset.statusCount] || 0;
  });
}
function renderOrders(orders) {
  currentOrders = orders;
  renderStatusCounts(orders);
  var visibleOrders = activeStatusFilter === 'all'
    ? orders
    : orders.filter(function (order) { return orderStatus(order) === activeStatusFilter; });
  ordersBody.innerHTML = '';
  if (!visibleOrders.length) {
    var emptyMessage = activeStatusFilter === 'all'
      ? (showingHistory ? 'Belum ada riwayat pesanan.' : 'Belum ada pesanan hari ini.')
      : 'Tidak ada pesanan berstatus ' + activeStatusFilter + (showingHistory ? ' di riwayat.' : ' hari ini.');
    ordersBody.innerHTML = '<tr><td colspan="8" class="empty">' + escapeText(emptyMessage) + '</td></tr>';
    return;
  }
  visibleOrders.forEach(function (order) {
    var row = document.createElement('tr');
    var items = (order.items || []).map(function (item) {
      var detail = escapeText(item.qty) + '\u00d7 ' + escapeText(item.nama);
      if (item.toppings && item.toppings.length) detail += ' + ' + item.toppings.map(function (topping) { return escapeText(topping.nama); }).join(', ');
      if (item.notes) detail += '<br><span class="muted">Catatan: ' + escapeText(item.notes) + '</span>';
      return detail;
    }).join('<br>');
    var latitude = Number(order.latitude);
    var longitude = Number(order.longitude);
    var hasRoute = order.pengiriman === 'antar' && order.latitude != null && order.longitude != null &&
      Number.isFinite(latitude) && Number.isFinite(longitude) &&
      latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180;
    var route = hasRoute ? '<a class="btn btn--secondary" data-route-link target="_blank" rel="noopener">Buka Rute Maps</a>' : '';
    var nextStatus = nextOrderStatus(order);
    var statusButton = nextStatus
      ? '<button type="button" class="btn btn--primary" data-order-action="status" data-order-id="' + escapeText(order.id) + '"' +
        (canAdvanceOrder(order, nextStatus) ? '' : ' disabled title="Konfirmasi pembayaran sebelum melanjutkan status."') +
        '>&rarr; ' + escapeText(nextStatus) + '</button>'
      : '';
    var rejectButton = order.metode_bayar === 'tunai' && order.status === 'Diterima'
      ? '<button type="button" class="btn btn--danger" data-order-action="reject" data-order-id="' + escapeText(order.id) + '">Tolak pesanan</button>'
      : '';
    var paymentStatus = order.status_pembayaran || 'Belum dibayar';
    var paidButton = paymentStatus !== 'Lunas'
      ? '<button type="button" class="btn btn--secondary" data-order-action="paid" data-order-id="' + escapeText(order.id) + '">Tandai lunas</button>'
      : '';
    row.innerHTML = '<td><strong>' + escapeText(order.id) + '</strong><br><span class="badge">' + escapeText(order.pengiriman === 'antar' ? 'Diantar' : 'Ambil') + '</span></td>' +
      '<td>' + escapeText(order.tanggal || '-') +
        (order.waktu_pengambilan ? '<br><span class="muted">Perkiraan ambil: ' + escapeText(order.waktu_pengambilan) + '</span>' : '') +
      '</td>' +
      '<td>' + escapeText(order.nama_pemesan || 'Data lama') +
        '<br><span class="muted">' + escapeText(order.whatsapp || 'Nomor tidak tersedia') + '</span>' +
        (order.alamat ? '<br><span class="muted">' + escapeText(order.alamat) + '</span>' : '') +
      '</td>' +
      '<td>' + (items || '-') + '</td>' +
      '<td>' + escapeText(formatMoney(order.total)) + '</td>' +
      '<td><span class="badge ' + (paymentStatus === 'Lunas' ? 'badge--success' : 'badge--warning') + '">' + escapeText(paymentStatus) + '</span></td>' +
      '<td><span class="badge">' + escapeText(orderStatus(order)) + '</span></td>' +
      '<td><div class="actions">' + route + statusButton + paidButton + rejectButton + '</div></td>';
    var routeLink = row.querySelector('[data-route-link]');
    if (routeLink) {
      routeLink.href = 'https://www.google.com/maps/dir/?api=1&destination=' + latitude + ',' + longitude;
    }
    ordersBody.appendChild(row);
  });
}
function formatMoney(value) { return 'Rp' + Number(value || 0).toLocaleString('id-ID'); }
function setStatusFilter(status) {
  activeStatusFilter = status;
  document.querySelectorAll('[data-status-filter]').forEach(function (button) {
    var isActive = button.dataset.statusFilter === status;
    button.classList.toggle('is-active', isActive);
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });
  renderOrders(currentOrders);
}
function loadOrders() {
  fetch('api/orders.php' + (showingHistory ? '?history=1' : ''), { cache: 'no-store' })
    .then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok || !data.ok) throw new Error(data.error || 'Pesanan gagal dimuat.');
        return data;
      });
    })
    .then(function (data) {
      ordersError.hidden = true;
      renderOrders(data.orders);
      lastUpdated.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');
    })
    .catch(function (error) {
      ordersError.textContent = error.message;
      ordersError.hidden = false;
    });
}
historyToggle.addEventListener('click', function () {
  showingHistory = !showingHistory;
  historyToggle.textContent = showingHistory ? 'Kembali ke pesanan hari ini' : 'Lihat riwayat pesanan';
  ordersTitle.textContent = showingHistory ? 'Riwayat pesanan' : 'Pesanan hari ini';
  ordersDescription.textContent = showingHistory ? 'Menampilkan pesanan dari hari-hari sebelumnya.' : 'Pesanan hari ini diperbarui otomatis setiap 15 detik.';
  loadOrders();
});
document.querySelector('.order-status-nav').addEventListener('click', function (event) {
  var button = event.target.closest('[data-status-filter]');
  if (button) setStatusFilter(button.dataset.statusFilter);
});
ordersBody.addEventListener('click', function (event) {
  var button = event.target.closest('[data-order-action]');
  if (!button) return;
  button.disabled = true;
  fetch('api/update_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({ order_id: button.dataset.orderId, action: button.dataset.orderAction })
  })
    .then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok || !data.ok) throw new Error(data.error || 'Pembaruan pesanan gagal.');
        return data;
      });
    })
    .then(loadOrders)
    .catch(function (error) {
      window.alert(error.message);
      button.disabled = false;
    });
});
loadOrders();
window.setInterval(function () {
  loadOrders();
}, 15000);
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
