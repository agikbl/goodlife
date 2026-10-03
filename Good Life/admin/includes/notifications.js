(function () {
  var button = document.getElementById('adminNotificationToggle');
  var status = document.getElementById('adminNotificationStatus');
  var audio = document.getElementById('adminOrderNotificationSound');
  var toast = document.getElementById('adminOrderToast');
  if (!button || !status || !audio || !toast) return;

  var seenStorageKey = 'goodlife-admin-seen-order-ids';
  var soundStorageKey = 'goodlife-admin-order-sound-enabled';
  var seenOrderIds = {};
  var initialized = false;
  var soundEnabled = false;
  var toastTimer;

  function readSeenOrderIds() {
    try {
      var stored = JSON.parse(window.localStorage.getItem(seenStorageKey) || '[]');
      if (Array.isArray(stored)) {
        stored.forEach(function (id) { seenOrderIds[String(id)] = true; });
      }
    } catch (error) {
      status.textContent = 'Penyimpanan browser tidak tersedia; notifikasi antarhalaman tidak dapat disinkronkan.';
    }
  }

  function saveSeenOrderIds() {
    var ids = Object.keys(seenOrderIds);
    try {
      window.localStorage.setItem(seenStorageKey, JSON.stringify(ids.slice(-1000)));
    } catch (error) {
      status.textContent = 'ID notifikasi tidak dapat disimpan pada browser ini.';
    }
  }

  function playOrderSound() {
    audio.currentTime = 0;
    audio.play().catch(function () {
      status.textContent = 'Pesanan baru terdeteksi, tetapi browser menolak memutar suara. Tekan tombol aktivasi suara lagi.';
    });
  }

  function notifyNewOrders(orders) {
    var newOrders = [];
    orders.forEach(function (order) {
      var id = String(order.id || '');
      if (!id) return;
      if (!seenOrderIds[id]) newOrders.push(order);
      seenOrderIds[id] = true;
    });
    saveSeenOrderIds();

    if (!initialized) {
      initialized = true;
      return;
    }
    if (!newOrders.length) return;

    var noticeText = 'Pesanan Baru Diterima!!' +
      (newOrders.length === 1 ? ' — ' + String(newOrders[0].id) : ' (' + newOrders.length + ' pesanan)');
    status.textContent = noticeText;
    toast.textContent = noticeText;
    toast.hidden = false;
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () { toast.hidden = true; }, 12000);
    if (soundEnabled) playOrderSound();
  }

  function pollOrders() {
    fetch('api/orders.php', { cache: 'no-store' })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) throw new Error(data.error || 'Pesanan gagal diperiksa.');
          return data;
        });
      })
      .then(function (data) { notifyNewOrders(data.orders); })
      .catch(function (error) {
        status.textContent = error.message;
      });
  }

  function decodeApplicationServerKey(value) {
    var padding = '='.repeat((4 - value.length % 4) % 4);
    var base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var output = new Uint8Array(raw.length);
    for (var index = 0; index < raw.length; index += 1) output[index] = raw.charCodeAt(index);
    return output;
  }

  function enablePush() {
    if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
      return Promise.reject(new Error('Browser ini tidak mendukung notifikasi push.'));
    }
    return Notification.requestPermission().then(function (permission) {
      if (permission !== 'granted') throw new Error('Izin notifikasi browser tidak diberikan.');
      return navigator.serviceWorker.register('../service-worker.js');
    }).then(function (registration) {
      return fetch('api/push_config.php', { cache: 'no-store' }).then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) throw new Error(data.error || 'Konfigurasi push gagal dimuat.');
          if (!data.configured || !data.public_key) {
            throw new Error('VAPID belum diset pada server. Suara aktif di semua halaman admin; push saat browser tertutup menunggu konfigurasi VAPID.');
          }
          return registration.pushManager.getSubscription().then(function (existing) {
            return (existing || registration.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: decodeApplicationServerKey(data.public_key)
            })).toJSON();
          });
        });
      });
    }).then(function (subscription) {
      return fetch('api/save_push_subscription.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': button.dataset.csrf },
        body: JSON.stringify({ subscription: subscription })
      }).then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) throw new Error(data.error || 'Pendaftaran push gagal.');
          return data;
        });
      });
    });
  }

  button.addEventListener('click', function () {
    soundEnabled = true;
    try {
      window.localStorage.setItem(soundStorageKey, '1');
    } catch (error) {
      status.textContent = 'Suara aktif pada halaman ini, tetapi browser tidak dapat menyimpan preferensinya.';
    }
    audio.play().then(function () {
      audio.pause();
      audio.currentTime = 0;
      status.textContent = 'Suara aktif di semua halaman admin. Mengaktifkan push...';
      return enablePush();
    }).then(function () {
      status.textContent = 'Suara dan notifikasi push aktif di seluruh halaman admin.';
      button.textContent = 'Notifikasi aktif';
    }).catch(function (error) {
      status.textContent = soundEnabled
        ? 'Suara aktif di semua halaman admin. ' + error.message
        : error.message;
      if (soundEnabled) button.textContent = 'Suara aktif · aktifkan push';
    });
  });

  readSeenOrderIds();
  try {
    soundEnabled = window.localStorage.getItem(soundStorageKey) === '1';
  } catch (error) {
    soundEnabled = false;
  }
  if (soundEnabled) {
    button.textContent = 'Suara aktif · aktifkan push';
    status.textContent = 'Suara aktif di semua halaman admin. Aktifkan push untuk menerima notifikasi saat browser tertutup.';
  }
  pollOrders();
  window.setInterval(pollOrders, 15000);
}());
