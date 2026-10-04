<?php
require __DIR__ . '/includes/bootstrap.php';
admin_require_auth();

$period = $_GET['periode'] ?? 'hari';
if (!in_array($period, ['hari', 'minggu', 'bulan'], true)) {
    $period = 'hari';
}
$today = new DateTimeImmutable('today');
if ($period === 'minggu') {
    $rangeStart = $today->modify('monday this week');
    $rangeEnd = $rangeStart->modify('+6 days');
    $rangeLabel = 'Minggu ini (' . $rangeStart->format('d M') . '–' . $rangeEnd->format('d M Y') . ')';
} elseif ($period === 'bulan') {
    $rangeStart = $today->modify('first day of this month');
    $rangeEnd = $today->modify('last day of this month');
    $rangeLabel = $today->format('F Y');
} else {
    $rangeStart = $today;
    $rangeEnd = $today;
    $rangeLabel = $today->format('d M Y');
}

$allOrders = admin_read_dataset('data/orders.json');
$store = admin_read_dataset('data/store.json');
$storeIsOpen = !array_key_exists('is_open', $store) || $store['is_open'] === true;
$storeActivity = (string)($store['activity'] ?? ($storeIsOpen ? 'Menerima pesanan' : 'Tutup sementara'));
$csrf = admin_csrf_token();
$ordersInRange = [];
$revenue = 0;
$orderCount = 0;
$portionCount = 0;
$activeCount = 0;
$activeStatuses = ['Diterima', 'Diproses', 'Diantarkan', 'Siap Diambil'];
$dailyTotals = [];
$hourlyTotals = [];
$bestSellers = [];
$openingTime = (string)($store['jam_buka'] ?? '15:00');
$closingTime = (string)($store['jam_tutup'] ?? '23:00');
if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $openingTime)) {
    $openingTime = '15:00';
}
if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $closingTime)) {
    $closingTime = '23:00';
}
$openingMinutes = ((int)substr($openingTime, 0, 2) * 60) + (int)substr($openingTime, 3, 2);
$closingMinutes = ((int)substr($closingTime, 0, 2) * 60) + (int)substr($closingTime, 3, 2);
$operatingMinutes = $closingMinutes - $openingMinutes;
if ($operatingMinutes <= 0) {
    $operatingMinutes += 24 * 60;
}
if ($period === 'hari') {
    for ($offset = 0; $offset < $operatingMinutes; $offset += 60) {
        $hourlyTotals[intdiv($offset, 60)] = [
            'revenue' => 0,
            'orders' => 0,
            'start' => $offset,
            'end' => min($offset + 60, $operatingMinutes),
        ];
    }
} else {
    for ($date = $rangeStart; $date <= $rangeEnd; $date = $date->modify('+1 day')) {
        $dailyTotals[$date->format('Y-m-d')] = ['revenue' => 0, 'orders' => 0];
    }
}

foreach ($allOrders as $order) {
    if (!is_array($order)) {
        continue;
    }
    if (in_array($order['status'] ?? '', $activeStatuses, true)) {
        $activeCount++;
    }
    $orderDate = substr((string)($order['tanggal'] ?? ''), 0, 10);
    if ($orderDate < $rangeStart->format('Y-m-d') || $orderDate > $rangeEnd->format('Y-m-d')) {
        continue;
    }
    $orderTime = substr((string)($order['tanggal'] ?? ''), 11, 5);
    $orderHour = null;
    if ($period === 'hari' && preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $orderTime)) {
        $orderMinutes = ((int)substr($orderTime, 0, 2) * 60) + (int)substr($orderTime, 3, 2);
        $operatingOffset = $orderMinutes - $openingMinutes;
        if ($operatingOffset < 0) {
            $operatingOffset += 24 * 60;
        }
        if ($operatingOffset >= $operatingMinutes) {
            continue;
        }
        $orderHour = intdiv($operatingOffset, 60);
        $hourlyTotals[$orderHour]['orders']++;
    } elseif ($period !== 'hari') {
        $dailyTotals[$orderDate]['orders']++;
    } else {
        continue;
    }
    $ordersInRange[] = $order;
    $orderCount++;
    if (($order['status_pembayaran'] ?? '') === 'Lunas') {
        $paidAmount = (int)($order['total'] ?? 0);
        $revenue += $paidAmount;
        if ($period === 'hari' && $orderHour !== null) {
            $hourlyTotals[$orderHour]['revenue'] += $paidAmount;
        } elseif ($period !== 'hari') {
            $dailyTotals[$orderDate]['revenue'] += $paidAmount;
        }
    }
    if (($order['status'] ?? '') === 'Selesai') {
        foreach (($order['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $quantity = max(0, (int)($item['qty'] ?? 0));
            $portionCount += $quantity;
            $itemName = (string)($item['nama'] ?? 'Menu');
            $bestSellers[$itemName] = ($bestSellers[$itemName] ?? 0) + $quantity;
        }
    }
}

$chartTotals = $period === 'hari' ? $hourlyTotals : $dailyTotals;
arsort($bestSellers);
$bestSellers = array_slice($bestSellers, 0, 5, true);
$maxDailyRevenue = max(array_merge([0], array_column($chartTotals, 'revenue')));
$recentOrders = array_reverse($ordersInRange);
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<style>
.report-controls{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.2rem}
.report-tabs{display:flex;gap:.5rem;flex-wrap:wrap}
.report-tabs a{padding:.55rem .9rem;border:1px solid var(--grey-300);border-radius:999px;background:#fff;color:var(--green-900);font-weight:700}
.report-tabs a.is-active{background:var(--green-900);border-color:var(--green-900);color:#fff}
.report-subtitle{color:var(--grey-700);margin:.25rem 0 1rem}
.report-grid{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(230px,1fr);gap:1.2rem;margin-top:1.2rem}
.chart{display:flex;align-items:flex-end;gap:.4rem;min-height:190px;overflow-x:auto;padding:.75rem .1rem .2rem}
.chart__day{flex:1 0 28px;min-width:28px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:.4rem;height:175px}
.chart__bar{width:min(100%,30px);min-height:3px;border-radius:6px 6px 2px 2px;background:var(--green-500)}
.chart__value{font-size:.65rem;color:var(--grey-700);white-space:nowrap}
.chart__label{font-size:.66rem;color:var(--grey-700);white-space:nowrap}
.best-sellers{list-style:none;margin:0;padding:0}
.best-sellers li{display:flex;justify-content:space-between;gap:.8rem;padding:.7rem 0;border-bottom:1px solid var(--grey-100)}
.best-sellers li:last-child{border-bottom:0}
.best-sellers strong{color:var(--green-900);white-space:nowrap}
.print-only{display:none}
.store-status-card{display:flex;align-items:center;justify-content:space-between;gap:1.2rem;flex-wrap:wrap;margin-bottom:1.2rem}
.store-status-copy{display:grid;gap:.4rem}
.store-status-copy p{margin:0;color:var(--grey-700)}
.store-status-badge{display:inline-flex;align-items:center;gap:.45rem;width:max-content;padding:.35rem .7rem;border-radius:999px;background:#d9eee0;color:#1b6232;font-weight:700;font-size:.82rem}
.store-status-badge.is-closed{background:#f8dedb;color:var(--red)}
.store-status-form{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}
.store-status-form input{width:min(260px,70vw);border:1px solid var(--grey-300);border-radius:9px;background:#fff;padding:.68rem .8rem}
.store-status-message{width:100%;margin:0;color:var(--green-700);font-size:.88rem}
.store-status-message.is-error{color:var(--red)}
.store-confirm-backdrop{position:fixed;inset:0;z-index:200;display:grid;place-items:center;padding:1rem;background:rgba(12,24,16,.56);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px)}
.store-confirm-backdrop[hidden]{display:none}
.store-confirm-dialog{width:min(440px,100%);padding:1.6rem;border-radius:16px;background:#fff;box-shadow:0 24px 70px rgba(0,0,0,.3)}
.store-confirm-dialog h2{margin:0 0 .65rem;color:var(--green-900)}
.store-confirm-dialog p{margin:0;color:var(--grey-700);line-height:1.55}
.store-confirm-actions{display:flex;justify-content:flex-end;gap:.7rem;margin-top:1.5rem}
@media(max-width:760px){.report-grid{grid-template-columns:1fr}}
@media print{
  body{background:#fff!important}
  .sidebar,.report-controls,.page-head>.btn,.print-hide{display:none!important}
  .admin-shell{display:block;min-height:0}
  .main{padding:0!important}
  .card{box-shadow:none;border:1px solid #ddd;break-inside:avoid}
  .stats{grid-template-columns:repeat(3,1fr)}
  .report-grid{grid-template-columns:1.5fr 1fr}
  .print-only{display:block}
  .chart{overflow:visible}
}
</style>
<header class="page-head">
  <div><h1>Dashboard &amp; Laporan</h1><p>Ringkasan operasional Good Life Parepare.</p></div>
  <button class="btn btn--primary print-hide" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
</header>
<section class="card store-status-card" aria-labelledby="storeStatusTitle">
  <div class="store-status-copy">
    <h2 id="storeStatusTitle">Status operasional toko</h2>
    <span class="store-status-badge <?php echo $storeIsOpen ? '' : 'is-closed'; ?>" id="storeStatusBadge">
      <?php echo $storeIsOpen ? 'Buka' : 'Tutup sementara'; ?>
    </span>
    <p id="storeActivityText"><?php echo admin_h($storeActivity); ?></p>
    <p class="store-status-message" id="storeStatusMessage" role="status" hidden></p>
  </div>
  <form class="store-status-form" id="storeStatusForm">
    <input type="hidden" name="csrf_token" value="<?php echo admin_h($csrf); ?>">
    <input type="text" id="storeActivity" maxlength="100" aria-label="Keterangan aktivitas saat toko ditutup" placeholder="Contoh: Sedang bersih-bersih" value="<?php echo $storeIsOpen ? '' : admin_h($storeActivity); ?>" <?php echo $storeIsOpen ? '' : 'required'; ?>>
    <button class="btn <?php echo $storeIsOpen ? 'btn--danger' : 'btn--primary'; ?>" id="storeStatusButton" type="submit" data-next-open="<?php echo $storeIsOpen ? '0' : '1'; ?>">
      <?php echo $storeIsOpen ? 'Tutup toko' : 'Buka toko'; ?>
    </button>
  </form>
</section>
<div class="store-confirm-backdrop" id="storeConfirmModal" hidden>
  <section class="store-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="storeConfirmTitle" aria-describedby="storeConfirmMessage" tabindex="-1">
    <h2 id="storeConfirmTitle">Konfirmasi perubahan toko</h2>
    <p id="storeConfirmMessage"></p>
    <div class="store-confirm-actions">
      <button class="btn btn--secondary" id="storeConfirmNo" type="button">Tidak</button>
      <button class="btn btn--primary" id="storeConfirmYes" type="button">Ya</button>
    </div>
  </section>
</div>
<section class="report-controls print-hide" aria-label="Filter periode laporan">
  <div class="report-tabs">
    <a href="?periode=hari" class="<?php echo $period === 'hari' ? 'is-active' : ''; ?>">Harian</a>
    <a href="?periode=minggu" class="<?php echo $period === 'minggu' ? 'is-active' : ''; ?>">Mingguan</a>
    <a href="?periode=bulan" class="<?php echo $period === 'bulan' ? 'is-active' : ''; ?>">Bulanan</a>
  </div>
  <button class="btn btn--secondary" type="button" onclick="window.print()">Ekspor PDF</button>
</section>
<p class="report-subtitle print-only">Periode laporan: <?php echo admin_h($rangeLabel); ?></p>
<p class="report-subtitle">Periode laporan: <strong><?php echo admin_h($rangeLabel); ?></strong></p>
<section class="grid stats" aria-label="Ringkasan toko untuk periode terpilih">
  <article class="card"><span class="stat-label">Omzet pembayaran lunas</span><strong class="stat-value"><?php echo admin_money($revenue); ?></strong></article>
  <article class="card"><span class="stat-label">Jumlah pesanan</span><strong class="stat-value"><?php echo number_format($orderCount); ?></strong></article>
  <article class="card"><span class="stat-label">Porsi terjual</span><strong class="stat-value"><?php echo number_format($portionCount); ?></strong></article>
</section>
<section class="grid stats" aria-label="Status operasional saat ini">
  <article class="card"><span class="stat-label">Pesanan aktif saat ini</span><strong class="stat-value"><?php echo number_format($activeCount); ?></strong></article>
</section>
<div class="report-grid">
  <section class="card">
    <div class="card-head"><h2><?php echo $period === 'hari' ? 'Omzet per jam pemesanan' : 'Tren omzet harian'; ?></h2></div>
    <?php if ($orderCount === 0): ?><p class="empty">Data transaksi bernilai 0 pada periode ini.</p><?php else: ?>
    <div class="chart" role="img" aria-label="<?php echo $period === 'hari' ? 'Grafik omzet per jam pemesanan' : 'Grafik omzet harian'; ?> periode <?php echo admin_h($rangeLabel); ?>">
      <?php foreach ($chartTotals as $date => $totals): ?>
        <?php $barHeight = $maxDailyRevenue > 0 ? max(3, (int)round($totals['revenue'] / $maxDailyRevenue * 135)) : 3; ?>
        <?php
          if ($period === 'hari') {
              $slot = $totals;
              $slotStart = ($openingMinutes + $slot['start']) % (24 * 60);
              $slotEnd = ($openingMinutes + $slot['end']) % (24 * 60);
              $chartLabel = sprintf('%02d:%02d–%02d:%02d',
                  intdiv($slotStart, 60), $slotStart % 60,
                  intdiv($slotEnd, 60), $slotEnd % 60);
          } else {
              $chartLabel = date('d/m', strtotime($date));
          }
        ?>
        <div class="chart__day" title="<?php echo admin_h($chartLabel . ': ' . admin_money($totals['revenue']) . ', ' . $totals['orders'] . ' pesanan'); ?>">
          <span class="chart__value"><?php echo $totals['revenue'] > 0 ? admin_money($totals['revenue']) : '—'; ?></span>
          <div class="chart__bar" style="height:<?php echo $barHeight; ?>px"></div>
          <span class="chart__label"><?php echo admin_h($chartLabel); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
  <section class="card">
    <div class="card-head"><h2>Menu terlaris</h2></div>
    <?php if (!$bestSellers): ?><p class="empty">Belum ada menu terjual pada periode ini.</p><?php else: ?>
      <ol class="best-sellers">
        <?php foreach ($bestSellers as $name => $quantity): ?>
          <li><span><?php echo admin_h($name); ?></span><strong><?php echo number_format($quantity); ?> porsi</strong></li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>
</div>
<section class="card" style="margin-top:1.2rem">
  <div class="card-head"><h2>Rekap transaksi</h2><a class="btn btn--secondary print-hide" href="pesanan.php">Kelola pesanan</a></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID Pesanan</th><th>Tanggal</th><th>Pelanggan</th><th>Item</th><th>Total</th><th>Pembayaran</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (!$recentOrders): ?><tr><td colspan="7" class="empty">Data transaksi bernilai 0 pada periode ini.</td></tr><?php endif; ?>
      <?php foreach ($recentOrders as $order): ?>
        <tr>
          <td><strong><?php echo admin_h($order['id'] ?? '-'); ?></strong></td>
          <td><?php echo admin_h($order['tanggal'] ?? '-'); ?></td>
          <td><?php echo admin_h($order['nama_pemesan'] ?? 'Data lama'); ?><br><span class="muted"><?php echo admin_h($order['whatsapp'] ?? '-'); ?></span></td>
          <td><?php echo number_format(array_sum(array_map(function ($item) { return (int)($item['qty'] ?? 0); }, is_array($order['items'] ?? null) ? $order['items'] : []))); ?> porsi</td>
          <td><?php echo admin_money($order['total'] ?? 0); ?></td>
          <td><?php echo admin_h($order['status_pembayaran'] ?? 'Belum dibayar'); ?></td>
          <td><?php echo admin_h($order['status'] ?? 'Diterima'); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<script>
var storeStatusForm = document.getElementById('storeStatusForm');
var storeStatusButton = document.getElementById('storeStatusButton');
var storeActivityInput = document.getElementById('storeActivity');
var storeStatusMessage = document.getElementById('storeStatusMessage');
var storeConfirmModal = document.getElementById('storeConfirmModal');
var storeConfirmMessage = document.getElementById('storeConfirmMessage');
var storeConfirmYes = document.getElementById('storeConfirmYes');
var storeConfirmNo = document.getElementById('storeConfirmNo');
var pendingStoreStatusChange = null;
var storeConfirmPreviousFocus = null;
var storeConfirmScrollState = null;

function lockStoreConfirmPage() {
  var body = document.body;
  var scrollY = window.scrollY;
  storeConfirmScrollState = {
    scrollY: scrollY,
    position: body.style.position,
    top: body.style.top,
    left: body.style.left,
    right: body.style.right,
    width: body.style.width
  };
  body.style.position = 'fixed';
  body.style.top = '-' + scrollY + 'px';
  body.style.left = '0';
  body.style.right = '0';
  body.style.width = '100%';
}

function unlockStoreConfirmPage() {
  if (!storeConfirmScrollState) return;
  var body = document.body;
  var previous = storeConfirmScrollState;
  storeConfirmScrollState = null;
  body.style.position = previous.position;
  body.style.top = previous.top;
  body.style.left = previous.left;
  body.style.right = previous.right;
  body.style.width = previous.width;
  window.scrollTo(0, previous.scrollY);
}

function closeStoreConfirm(confirmed) {
  var onConfirm = pendingStoreStatusChange;
  pendingStoreStatusChange = null;
  storeConfirmModal.hidden = true;
  unlockStoreConfirmPage();
  if (storeConfirmPreviousFocus) storeConfirmPreviousFocus.focus();
  if (confirmed && onConfirm) onConfirm();
}

function openStoreConfirm(willOpen, onConfirm) {
  pendingStoreStatusChange = onConfirm;
  storeConfirmPreviousFocus = document.activeElement;
  storeConfirmMessage.textContent = willOpen
    ? 'Toko akan dibuka dan mulai menerima pesanan baru. Apakah kamu yakin?'
    : 'Toko akan ditutup dan pesanan baru dihentikan. Apakah kamu yakin?';
  storeConfirmYes.textContent = 'Ya';
  lockStoreConfirmPage();
  storeConfirmModal.hidden = false;
  storeConfirmNo.focus();
}

storeConfirmYes.addEventListener('click', function () { closeStoreConfirm(true); });
storeConfirmNo.addEventListener('click', function () { closeStoreConfirm(false); });
storeConfirmModal.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    closeStoreConfirm(false);
  } else if (event.key === 'Tab') {
    event.preventDefault();
    (document.activeElement === storeConfirmNo ? storeConfirmYes : storeConfirmNo).focus();
  }
});

function updateStoreStatus(willOpen, activity) {
  storeStatusButton.disabled = true;
  storeStatusMessage.hidden = true;
  fetch('api/update_store_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': <?php echo json_encode($csrf); ?> },
    body: JSON.stringify({ is_open: willOpen, activity: activity })
  })
    .then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok || !data.ok) throw new Error(data.error || 'Status toko gagal diperbarui.');
        return data;
      });
    })
    .then(function (data) {
      var isOpen = data.store_status.is_open;
      var badge = document.getElementById('storeStatusBadge');
      badge.textContent = isOpen ? 'Buka' : 'Tutup sementara';
      badge.classList.toggle('is-closed', !isOpen);
      document.getElementById('storeActivityText').textContent = data.store_status.activity;
      storeStatusButton.textContent = isOpen ? 'Tutup toko' : 'Buka toko';
      storeStatusButton.classList.toggle('btn--danger', isOpen);
      storeStatusButton.classList.toggle('btn--primary', !isOpen);
      storeStatusButton.dataset.nextOpen = isOpen ? '0' : '1';
      storeActivityInput.required = !isOpen;
      storeActivityInput.placeholder = isOpen ? 'Contoh: Sedang bersih-bersih' : 'Keterangan aktivitas saat tutup';
      if (isOpen) storeActivityInput.value = '';
      storeStatusMessage.textContent = isOpen ? 'Toko dibuka dan siap menerima pesanan.' : 'Toko ditutup; pesanan baru sementara dinonaktifkan.';
      storeStatusMessage.classList.remove('is-error');
      storeStatusMessage.hidden = false;
    })
    .catch(function (error) {
      storeStatusMessage.textContent = error.message || 'Terjadi kesalahan jaringan.';
      storeStatusMessage.classList.add('is-error');
      storeStatusMessage.hidden = false;
    })
    .finally(function () { storeStatusButton.disabled = false; });
}

storeStatusForm.addEventListener('submit', function (event) {
  event.preventDefault();
  var willOpen = storeStatusButton.dataset.nextOpen === '1';
  var activity = storeActivityInput.value.trim();
  if (!willOpen && !activity) {
    storeStatusMessage.textContent = 'Isi keterangan aktivitas sebelum menutup toko.';
    storeStatusMessage.classList.add('is-error');
    storeStatusMessage.hidden = false;
    storeActivityInput.focus();
    return;
  }
  openStoreConfirm(willOpen, function () { updateStoreStatus(willOpen, activity); });
});
setTimeout(function () { window.location.reload(); }, 60000);
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
