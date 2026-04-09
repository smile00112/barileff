import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/api';

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('manager_token') || null);
    const admin = ref(JSON.parse(localStorage.getItem('manager_admin') || 'null'));

    const isAuthenticated = computed(() => token.value !== null);

    async function login(email, password) {
        const { data } = await api.post('/auth/login', { email, password });
        token.value = data.token;
        admin.value = data.admin;
        localStorage.setItem('manager_token', data.token);
        localStorage.setItem('manager_admin', JSON.stringify(data.admin));
    }

    async function fetchMe() {
        const { data } = await api.get('/auth/me');
        admin.value = data;
        localStorage.setItem('manager_admin', JSON.stringify(data));
        return data;
    }

    async function logout() {
        try {
            await api.post('/auth/logout');
        } catch {
            // Ignore errors on logout
        }
        token.value = null;
        admin.value = null;
        localStorage.removeItem('manager_token');
        localStorage.removeItem('manager_admin');
    }

    return { token, admin, isAuthenticated, login, fetchMe, logout };
});
