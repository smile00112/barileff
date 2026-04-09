<template>
  <div class="order-card card" :class="{ expanded: isExpanded }">
    <!-- Header row -->
    <div class="order-header" @click="toggle">
      <div class="order-header-left">
        <span class="order-id">#{{ order.increment_id }}</span>
        <span :class="['badge', 'badge-' + order.status]">{{ order.status_label }}</span>
      </div>
      <div class="order-header-right">
        <span class="order-total">{{ order.base_currency_code }} {{ order.grand_total }}</span>
        <span class="order-date">{{ formatDate(order.created_at) }}</span>
        <span class="expand-icon">{{ isExpanded ? '▲' : '▼' }}</span>
      </div>
    </div>

    <!-- Customer line -->
    <div class="order-customer">
      <span>{{ order.customer_name }}</span>
      <span class="muted">{{ order.customer_email }}</span>
    </div>

    <!-- Expanded detail -->
    <Transition name="slide">
      <div v-if="isExpanded" class="order-detail">
        <div v-if="detail === null" class="loading-detail">Loading…</div>
        <template v-else>
          <!-- Addresses -->
          <div class="detail-section" v-if="detail.shipping_address">
            <h4>Delivery</h4>
            <p>{{ detail.shipping_address.name }}</p>
            <p>{{ detail.shipping_address.address }}, {{ detail.shipping_address.city }}</p>
            <p>{{ detail.shipping_address.phone }}</p>
          </div>

          <!-- Items -->
          <div class="detail-section">
            <h4>Items</h4>
            <div v-for="item in detail.items" :key="item.id" class="order-item">
              <span class="item-name">{{ item.name }}</span>
              <span class="item-qty">× {{ item.qty_ordered }}</span>
              <span class="item-price">{{ detail.base_currency_code }} {{ item.total }}</span>
            </div>
          </div>

          <!-- Status change -->
          <div class="detail-section status-change">
            <h4>Change status</h4>
            <div class="status-change-row">
              <StatusSelect v-model="selectedStatus" :loading="statusLoading" />
              <button
                class="btn btn-primary"
                :disabled="selectedStatus === order.status || statusLoading"
                @click.stop="saveStatus"
              >
                Save
              </button>
            </div>
            <p v-if="statusError" class="status-error">{{ statusError }}</p>
          </div>
        </template>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import StatusSelect from './StatusSelect.vue';
import { useOrdersStore } from '@/stores/orders';

const props = defineProps({
  order: { type: Object, required: true },
});

const store = useOrdersStore();

const isExpanded = ref(false);
const detail = ref(null);
const selectedStatus = ref(props.order.status);
const statusLoading = ref(false);
const statusError = ref('');

watch(() => props.order.status, (v) => {
  selectedStatus.value = v;
});

async function toggle() {
  isExpanded.value = !isExpanded.value;
  if (isExpanded.value && detail.value === null) {
    detail.value = await store.fetchOrder(props.order.id);
    selectedStatus.value = detail.value.status;
  }
}

async function saveStatus() {
  statusError.value = '';
  statusLoading.value = true;
  try {
    const updated = await store.updateStatus(props.order.id, selectedStatus.value);
    detail.value = { ...detail.value, ...updated };
  } catch (err) {
    statusError.value = err.response?.data?.message ?? 'Failed to update status.';
  } finally {
    statusLoading.value = false;
  }
}

function formatDate(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>

<style scoped>
.order-card {
  margin-bottom: 0.5rem;
  transition: box-shadow 0.15s;
}

.order-card.expanded {
  box-shadow: var(--shadow-md);
}

.order-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  cursor: pointer;
  user-select: none;
  gap: 0.5rem;
}

.order-header:hover {
  background: var(--color-gray-50);
}

.order-header-left,
.order-header-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.order-id {
  font-weight: 700;
  font-size: 0.9375rem;
  color: var(--color-primary);
}

.order-total {
  font-weight: 600;
  font-size: 0.9375rem;
}

.order-date {
  font-size: 0.75rem;
  color: var(--color-gray-500);
}

.expand-icon {
  font-size: 0.75rem;
  color: var(--color-gray-500);
}

.order-customer {
  display: flex;
  gap: 0.5rem;
  padding: 0 1rem 0.625rem;
  font-size: 0.875rem;
  flex-wrap: wrap;
}

.muted {
  color: var(--color-gray-500);
}

/* Detail section */
.order-detail {
  border-top: 1px solid var(--color-gray-100);
  padding: 0.75rem 1rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.loading-detail {
  color: var(--color-gray-500);
  font-size: 0.875rem;
  text-align: center;
  padding: 0.5rem 0;
}

.detail-section h4 {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-gray-500);
  margin-bottom: 0.375rem;
}

.detail-section p {
  font-size: 0.875rem;
  color: var(--color-gray-700);
  line-height: 1.4;
}

.order-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  padding: 0.25rem 0;
  border-bottom: 1px solid var(--color-gray-100);
}

.order-item:last-child {
  border-bottom: none;
}

.item-name {
  flex: 1;
}

.item-qty {
  color: var(--color-gray-500);
  min-width: 3rem;
  text-align: right;
}

.item-price {
  font-weight: 600;
  min-width: 4.5rem;
  text-align: right;
}

.status-change-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-error {
  color: var(--color-danger);
  font-size: 0.8125rem;
  margin-top: 0.25rem;
}

/* Expand/collapse animation */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  max-height: 0;
}

.slide-enter-to,
.slide-leave-from {
  opacity: 1;
  max-height: 600px;
}
</style>
