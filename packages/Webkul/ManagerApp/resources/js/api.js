import axios from 'axios';

const api = axios.create({
    baseURL: '/manager/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('manager_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('manager_token');
            window.location.href = '/manager/';
        }
        return Promise.reject(error);
    }
);

export default api;
