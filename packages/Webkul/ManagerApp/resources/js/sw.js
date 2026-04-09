import { precacheAndRoute } from 'workbox-precaching';
import { NavigationRoute, registerRoute } from 'workbox-routing';
import { NetworkFirst } from 'workbox-strategies';

// Injected by vite-plugin-pwa
precacheAndRoute(self.__WB_MANIFEST);

// SPA fallback: serve index from cache for all /manager/* navigation requests
registerRoute(
    new NavigationRoute(
        new NetworkFirst({ cacheName: 'manager-html' }),
        { denylist: [/^\/manager\/api\//] }
    )
);

// Push notification handler
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'Manager App', body: event.data.text() };
    }

    const title = payload.title ?? 'Manager App';
    const options = {
        body: payload.body ?? '',
        icon: '/manager/icons/icon-192.png',
        badge: '/manager/icons/icon-192.png',
        data: { url: payload.url ?? '/manager/' },
        requireInteraction: false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Open / focus the app when the notification is clicked
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url ?? '/manager/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.startsWith(self.location.origin + '/manager') && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            return clients.openWindow(targetUrl);
        })
    );
});
