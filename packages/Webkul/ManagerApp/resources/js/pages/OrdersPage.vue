<template>
  <div class="orders-page">
    <!-- Top bar -->
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-logo">📦</span>
        <span class="topbar-title">Manager App</span>
      </div>
      <div class="topbar-right">
        <span v-if="authStore.admin" class="topbar-user">{{ authStore.admin.name }}</span>
        <button class="btn btn-ghost topbar-logout" @click="handleLogout" title="Logout">⎋</button>
      </div>
    </header>

    <!-- Live indicator -->
    <div v-if="echoConnected" class="live-bar live-bar--on">● Live</div>
    <div v-else class="live-bar live-bar--off">○ Reconnecting…</div>

    <!-- New order toast -->
    <Transition name="toast">
      <div v-if="newOrderToast" class="toast" @click="newOrderToast = null">
        🛎 New order #{{ newOrderToast.increment_id }} arrived
      </div>
    </Transition>

    <!-- Filters -->
    <OrderFilters v-model="filterStatus" v-model:search="filterSearch" />

    <!-- Order list -->
    <main class="orders-list">
      <div v-if="store.loading && store.orders.length === 0" class="loading-state">
        Loading orders…
      </div>

      <div v-else-if="store.orders.length === 0" class="empty-state">
        No orders found.
      </div>

      <OrderCard
        v-for="order in store.orders"
        :key="order.id"
        :order="order"
      />

      <!-- Pagination -->
      <div v-if="store.meta.last_page > 1" class="pagination">
        <button
          class="btn btn-ghost"
          :disabled="store.meta.current_page === 1"
          @click="changePage(store.meta.current_page - 1)"
        >← Prev</button>
        <span class="page-info">{{ store.meta.current_page }} / {{ store.meta.last_page }}</span>
        <button
          class="btn btn-ghost"
          :disabled="store.meta.current_page === store.meta.last_page"
          @click="changePage(store.meta.current_page + 1)"
        >Next →</button>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useOrdersStore } from '@/stores/orders';
import OrderFilters from '@/components/OrderFilters.vue';
import OrderCard from '@/components/OrderCard.vue';
import { useEcho } from '@/composables/useEcho';
import { usePush } from '@/composables/usePush';

const router = useRouter();
const authStore = useAuthStore();
const store = useOrdersStore();

const filterStatus = ref('');
const filterSearch = ref('');
const currentPage = ref(1);
const newOrderToast = ref(null);

const { echoConnected, subscribeWarehouse } = useEcho();
const { registerPush } = usePush();

async function load() {
  await store.fetchOrders({
    status: filterStatus.value || undefined,
    search: filterSearch.value || undefined,
    page: currentPage.value,
  });
}

watch([filterStatus, filterSearch], () => {
  currentPage.value = 1;
  load();
});

function changePage(p) {
  currentPage.value = p;
  load();
}

async function handleLogout() {
  await authStore.logout();
  router.replace({ name: 'login' });
}

onMounted(async () => {
  await store.fetchStatuses();
  await load();

  // Refresh admin profile to get inventory_sources list
  const me = await authStore.fetchMe().catch(() => authStore.admin);

  // Register push subscription silently (no-op if VAPID not configured)
  registerPush().catch(() => {});

  // Subscribe to warehouse broadcast channels
  if (me?.inventory_sources) {
    for (const source of me.inventory_sources) {
      subscribeWarehouse(source.id, {
        onOrderCreated(order) {
          store.prependOrder(order);
          newOrderToast.value = order;
          setTimeout(() => { newOrderToast.value = null; }, 5000);
        },
        onOrderStatusUpdated(order) {
          store.updateOrderFromBroadcast(order);
        },
      });
    }
  }
});
</script>

<style scoped>
.orders-page {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Top bar */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: var(--color-primary);
  color: #fff;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.topbar-logo {
  font-size: 1.25rem;
}

.topbar-title {
  font-weight: 700;
  font-size: 1rem;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.topbar-user {
  font-size: 0.875rem;
  opacity: 0.9;
}

.topbar-logout {
  color: #fff;
  font-size: 1.125rem;
}

.topbar-logout:hover {
  background: rgba(255,255,255,0.15);
}

/* Live bar */
.live-bar {
  text-align: center;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem;
}

.live-bar--on {
  background: #dcfce7;
  color: var(--color-success);
}

.live-bar--off {
  background: #fef9c3;
  color: var(--color-warning);
}

/* Toast */
.toast {
  position: fixed;
  top: 4rem;
  left: 50%;
  transform: translateX(-50%);
  background: var(--color-gray-900);
  color: #fff;
  padding: 0.625rem 1.25rem;
  border-radius: var(--radius);
  font-size: 0.875rem;
  font-weight: 500;
  z-index: 100;
  cursor: pointer;
  box-shadow: var(--shadow-md);
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-0.5rem);
}

/* Orders list */
.orders-list {
  flex: 1;
  padding: 0.75rem 0.75rem 2rem;
  max-width: 720px;
  width: 100%;
  margin: 0 auto;
}

.loading-state,
.empty-state {
  text-align: center;
  color: var(--color-gray-500);
  padding: 3rem 1rem;
  font-size: 0.9375rem;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1rem 0;
}

.page-info {
  font-size: 0.875rem;
  color: var(--color-gray-500);
}
</style>
