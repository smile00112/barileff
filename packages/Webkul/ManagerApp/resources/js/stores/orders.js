import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/api';

export const useOrdersStore = defineStore('orders', () => {
    const orders = ref([]);
    const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
    const loading = ref(false);
    const statuses = ref({});

    async function fetchStatuses() {
        const { data } = await api.get('/orders/statuses');
        statuses.value = data;
    }

    async function fetchOrders(filters = {}) {
        loading.value = true;
        try {
            const { data } = await api.get('/orders', { params: filters });
            orders.value = data.data;
            meta.value = data.meta;
        } finally {
            loading.value = false;
        }
    }

    async function fetchOrder(id) {
        const { data } = await api.get(`/orders/${id}`);
        return data;
    }

    async function updateStatus(id, status) {
        const { data } = await api.patch(`/orders/${id}/status`, { status });
        // Update in-place in the list if present
        const idx = orders.value.findIndex((o) => o.id === id);
        if (idx !== -1) {
            orders.value[idx] = { ...orders.value[idx], status: data.status, status_label: data.status_label };
        }
        return data;
    }

    /**
     * Prepend an order received via WebSocket broadcast.
     */
    function prependOrder(order) {
        const exists = orders.value.findIndex((o) => o.id === order.id);
        if (exists !== -1) {
            orders.value[exists] = order;
        } else {
            orders.value.unshift(order);
            meta.value.total += 1;
        }
    }

    /**
     * Update an order status received via WebSocket broadcast.
     */
    function updateOrderFromBroadcast(order) {
        const idx = orders.value.findIndex((o) => o.id === order.id);
        if (idx !== -1) {
            orders.value[idx] = { ...orders.value[idx], ...order };
        }
    }

    return {
        orders,
        meta,
        loading,
        statuses,
        fetchStatuses,
        fetchOrders,
        fetchOrder,
        updateStatus,
        prependOrder,
        updateOrderFromBroadcast,
    };
});
