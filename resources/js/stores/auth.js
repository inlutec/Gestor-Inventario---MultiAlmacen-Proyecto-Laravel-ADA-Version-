import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('auth_token') || null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => state.user?.rol === 'admin',
    },

    actions: {
        async login(credentials) {
            try {
                const response = await axios.post('/login', credentials);
                
                if (response.data.success) {
                    this.token = response.data.data.token;
                    this.user = response.data.data.usuario;
                    
                    localStorage.setItem('auth_token', this.token);
                    localStorage.setItem('user', JSON.stringify(this.user));
                    
                    axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                    
                    return { success: true };
                } else {
                    return {
                        success: false,
                        message: response.data.message || 'Error al iniciar sesión',
                    };
                }
            } catch (error) {
                console.error('Error en login:', error);
                return {
                    success: false,
                    message: error.response?.data?.message || 'Error al conectar con el servidor',
                };
            }
        },

        async logout() {
            try {
                await axios.post('/logout');
            } catch (error) {
                console.error('Error al cerrar sesión:', error);
            } finally {
                this.user = null;
                this.token = null;
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                delete axios.defaults.headers.common['Authorization'];
            }
        },

        async checkAuth() {
            if (!this.token) return false;
            
            try {
                const response = await axios.get('/me');
                if (response.data.success) {
                    this.user = response.data.data;
                    localStorage.setItem('user', JSON.stringify(this.user));
                    return true;
                }
            } catch (error) {
                this.logout();
                return false;
            }
        },
    },
});
