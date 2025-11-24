import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.baseURL = '/gestionmaterial/api';

// Interceptor para token
const token = localStorage.getItem('auth_token');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Interceptor de respuesta para manejar errores globales
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            // Verificar si estamos en una ruta pública antes de redirigir al login
            const currentPath = window.location.pathname;
            const publicRoutes = ['/gestionmaterial/peticion', '/gestionmaterial/albaran'];
            const isPublicRoute = publicRoutes.some(route => currentPath.startsWith(route));
            
            // Solo redirigir al login si no estamos en una ruta pública
            if (!isPublicRoute) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                window.location.href = '/gestionmaterial/login';
            }
        }
        return Promise.reject(error);
    }
);
