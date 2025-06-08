// public/service-worker.js

self.addEventListener('push', function (event) {
  let data = { title: 'New Notification', body: 'You have a new message.'};
  if (event.data) {
    data = event.data.json();
  }

  const options = {
    body: data.body,
    icon: data.icon,
    badge: data.badge || null,
    data: data.data || {},
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  let url = '/'; // default URL on click
  if (event.notification.data.url) {
    url = event.notification.data.url;
  }
  event.waitUntil(clients.openWindow(url));
});
