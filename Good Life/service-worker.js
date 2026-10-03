self.addEventListener('push', function (event) {
  var payload = {};
  if (event.data) {
    try {
      payload = event.data.json();
    } catch (error) {
      payload = { body: event.data.text() };
    }
  }

  var title = typeof payload.title === 'string' ? payload.title : 'Pesanan baru';
  var options = {
    body: typeof payload.body === 'string' ? payload.body : 'Ada pesanan baru.',
    icon: '/assets/logo.jpeg',
    badge: '/assets/gud.png',
    tag: typeof payload.tag === 'string' ? payload.tag : 'new-order',
    data: { url: '/admin/pesanan.php' }
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clients) {
    var target = new URL('/admin/pesanan.php', self.location.origin);
    for (var index = 0; index < clients.length; index += 1) {
      var client = clients[index];
      if ('focus' in client) {
        return client.focus().then(function (focusedClient) {
          if ('navigate' in focusedClient && focusedClient.url !== target.href) {
            return focusedClient.navigate(target.href);
          }
          return focusedClient;
        });
      }
    }
    return self.clients.openWindow(target.href);
  }));
});
