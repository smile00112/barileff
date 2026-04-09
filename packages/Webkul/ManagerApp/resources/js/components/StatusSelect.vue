<template>
  <div class="status-select" @click.stop>
    <select
      :value="modelValue"
      :disabled="loading"
      class="select"
      @change="emit('update:modelValue', $event.target.value)"
    >
      <option v-for="(label, key) in statuses" :key="key" :value="key">
        {{ label }}
      </option>
    </select>
    <span v-if="loading" class="select-spinner">⟳</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useOrdersStore } from '@/stores/orders';

defineProps({
  modelValue: { type: String, required: true },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const store = useOrdersStore();
const statuses = computed(() => store.statuses);
</script>

<style scoped>
.status-select {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.select {
  padding: 0.25rem 2rem 0.25rem 0.5rem;
  border: 1px solid var(--color-gray-300);
  border-radius: var(--radius);
  font-size: 0.8125rem;
  background: #fff;
  appearance: auto;
  cursor: pointer;
}

.select:focus {
  outline: none;
  border-color: var(--color-primary);
}

.select:disabled {
  opacity: 0.6;
  cursor: wait;
}

.select-spinner {
  position: absolute;
  right: 0.25rem;
  font-size: 0.875rem;
  animation: spin 0.8s linear infinite;
  pointer-events: none;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
