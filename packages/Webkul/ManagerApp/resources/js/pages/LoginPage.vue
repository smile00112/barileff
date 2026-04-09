<template>
  <div class="login-wrapper">
    <div class="login-card card">
      <div class="login-header">
        <div class="login-icon">📦</div>
        <h1>Manager App</h1>
        <p>Warehouse order management</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="admin@example.com"
            autocomplete="email"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />
        </div>

        <div v-if="error" class="login-error">
          {{ error }}
        </div>

        <button type="submit" class="btn btn-primary login-btn" :disabled="loading">
          <span v-if="loading">Signing in…</span>
          <span v-else>Sign in</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();

const form = reactive({ email: '', password: '' });
const loading = ref(false);
const error = ref('');

async function handleLogin() {
  error.value = '';
  loading.value = true;
  try {
    await auth.login(form.email, form.password);
    router.replace({ name: 'orders' });
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Login failed. Please try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.login-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: linear-gradient(135deg, var(--color-primary-light) 0%, #fff 100%);
}

.login-card {
  width: 100%;
  max-width: 380px;
  padding: 2rem;
}

.login-header {
  text-align: center;
  margin-bottom: 1.75rem;
}

.login-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

.login-header h1 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-gray-900);
}

.login-header p {
  font-size: 0.875rem;
  color: var(--color-gray-500);
  margin-top: 0.25rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-gray-700);
}

.form-group input {
  padding: 0.625rem 0.75rem;
  border: 1px solid var(--color-gray-300);
  border-radius: var(--radius);
  font-size: 1rem;
  transition: border-color 0.15s, box-shadow 0.15s;
  background: #fff;
}

.form-group input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.login-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: var(--color-danger);
  border-radius: var(--radius);
  padding: 0.625rem 0.75rem;
  font-size: 0.875rem;
}

.login-btn {
  width: 100%;
  padding: 0.75rem;
  font-size: 1rem;
}
</style>
