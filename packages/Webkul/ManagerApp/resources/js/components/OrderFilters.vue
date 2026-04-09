<template>
  <div class="filters">
    <div class="filters-row">
      <div class="search-wrap">
        <input
          v-model="searchInput"
          type="search"
          placeholder="Search order # or customer…"
          class="filter-input"
          @input="onSearch"
        />
      </div>

      <div class="status-tabs">
        <button
          v-for="(label, key) in allStatuses"
          :key="key"
          class="status-tab"
          :class="{ active: modelValue === key }"
          @click="emit('update:modelValue', modelValue === key ? '' : key)"
        >
          {{ label }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useOrdersStore } from '@/stores/orders';
import { useDebounceFn } from '@vueuse/core';

const props = defineProps({
  modelValue: { type: String, default: '' },
  search: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'update:search']);

const store = useOrdersStore();
const searchInput = ref(props.search);

const allStatuses = computed(() => ({
  '': 'All',
  ...store.statuses,
}));

const onSearch = useDebounceFn(() => {
  emit('update:search', searchInput.value);
}, 400);
</script>

<style scoped>
.filters {
  padding: 0.75rem 1rem;
  background: #fff;
  border-bottom: 1px solid var(--color-gray-200);
}

.filters-row {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-gray-300);
  border-radius: var(--radius);
  font-size: 0.875rem;
  background: #fff;
}

.filter-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.status-tabs {
  display: flex;
  gap: 0.375rem;
  flex-wrap: wrap;
}

.status-tab {
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  background: var(--color-gray-100);
  color: var(--color-gray-700);
  border: 1px solid transparent;
  transition: background 0.15s, color 0.15s;
}

.status-tab:hover {
  background: var(--color-gray-200);
}

.status-tab.active {
  background: var(--color-primary);
  color: #fff;
}
</style>
