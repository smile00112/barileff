/* Push Notification Service Worker */

self.addEventListener('push', function (event) {
    if (!event.data) {
        return;
    }

    let data = {};

    try {
        data = event.data.json();
    } catch (e) {
        data = { title: 'Notification', body: event.data.text() };
    }

    const title   = data.title || 'Notification';
    const options = {
        body:             data.body    || '',
        icon:             data.icon    || '/favicon.ico',
        badge:            data.badge   || '/favicon.ico',
        data:             { url: data.url || '/' },
        requireInteraction: false,
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (const client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
