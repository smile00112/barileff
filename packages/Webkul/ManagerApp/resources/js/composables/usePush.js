import api from '@/api';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

export function usePush() {
    async function registerPush() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            return;
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return;

        const { data } = await api.get('/push/vapid-public-key');
        const publicKey = data.public_key;

        const registration = await navigator.serviceWorker.ready;

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(publicKey),
        });

        const { endpoint, keys } = subscription.toJSON();

        await api.post('/push/subscribe', {
            endpoint,
            public_key: keys.p256dh,
            auth_token: keys.auth,
        });
    }

    async function unregisterPush() {
        if (!('serviceWorker' in navigator)) return;

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            await api.delete('/push/subscribe', {
                data: { endpoint: subscription.endpoint },
            });
            await subscription.unsubscribe();
        }
    }

    return { registerPush, unregisterPush };
}
