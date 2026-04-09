import { ref } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance = null;
const echoConnected = ref(false);

function getEcho() {
    if (echoInstance) return echoInstance;

    window.Pusher = Pusher;

    const token = localStorage.getItem('manager_token');

    echoInstance = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
        },
    });

    echoInstance.connector.pusher.connection.bind('connected', () => {
        echoConnected.value = true;
    });
    echoInstance.connector.pusher.connection.bind('disconnected', () => {
        echoConnected.value = false;
    });
    echoInstance.connector.pusher.connection.bind('unavailable', () => {
        echoConnected.value = false;
    });

    return echoInstance;
}

export function useEcho() {
    function subscribeWarehouse(sourceId, { onOrderCreated, onOrderStatusUpdated }) {
        const echo = getEcho();

        echo
            .private(`manager.warehouse.${sourceId}`)
            .listen('.order.created', onOrderCreated)
            .listen('.order.status.updated', onOrderStatusUpdated);
    }

    function disconnect() {
        if (echoInstance) {
            echoInstance.disconnect();
            echoInstance = null;
            echoConnected.value = false;
        }
    }

    return { echoConnected, subscribeWarehouse, disconnect };
}
