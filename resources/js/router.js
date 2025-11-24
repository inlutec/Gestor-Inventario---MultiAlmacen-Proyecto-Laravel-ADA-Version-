import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const routes = [
    // Rutas públicas
    {
        path: '/',
        name: 'BienvenidaPublica',
        component: () => import('./views/BienvenidaPublica.vue'),
        meta: { public: true },
    },
    {
        path: '/albaran/:token',
        name: 'AlbaranPublico',
        component: () => import('./views/MaterialAlbaranPublico.vue'),
        meta: { public: true },
    },
    {
        path: '/peticion',
        name: 'PeticionPublica',
        component: () => import('./views/MaterialPeticionPublica.vue'),
        meta: { public: true },
    },
    // NOTA: /firmamovil ya NO está en Vue Router - es una PWA independiente servida por Laravel
    // {
    //     path: '/firmamovil',
    //     name: 'FirmaMovil',
    //     component: () => import('./views/FirmaMovil.vue'),
    //     meta: { public: true },
    // },
    {
        path: '/login',
        name: 'Login',
        component: () => import('./views/Login.vue'),
        meta: { guest: true },
    },
    // Rutas protegidas
    {
        path: '/',
        component: () => import('./layouts/MainLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                redirect: { name: 'Referencias' },
            },
            {
                path: 'dashboard',
                name: 'Dashboard',
                component: () => import('./views/Dashboard.vue'),
            },
            {
                path: 'referencias',
                name: 'Referencias',
                component: () => import('./views/MaterialReferencias.vue'),
            },
            {
                path: 'existencias',
                name: 'Existencias',
                component: () => import('./views/MaterialExistencias.vue'),
            },
            {
                path: 'movimientos',
                name: 'Movimientos',
                component: () => import('./views/MaterialMovimientos.vue'),
            },
            {
                path: 'historico',
                name: 'Historico',
                component: () => import('./views/MaterialHistorico.vue'),
            },
            {
                path: 'peticiones',
                name: 'Peticiones',
                component: () => import('./views/MaterialPeticiones.vue'),
            },
            {
                path: 'solicitudes-reposicion',
                name: 'SolicitudesReposicion',
                component: () => import('./views/SolicitudesReposicion.vue'),
            },
            {
                path: 'configuracion',
                name: 'Configuracion',
                component: () => import('./views/Configuracion.vue'),
            },
            {
                path: 'perfil',
                name: 'Perfil',
                component: () => import('./views/Perfil.vue'),
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory('/gestionmaterial/'),
    routes,
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    const isAuthenticated = authStore.isAuthenticated;

    // IMPORTANTE: Primero verificar si es una ruta pública ESPECÍFICA
    if (to.meta?.public === true) {
        return next();
    }
    
    // Si es guest route (login) y está autenticado, redirigir
    if (to.meta?.guest && isAuthenticated) {
        return next({ name: 'Referencias' });
    }
    
    // Si requiere autenticación y NO está autenticado, redirigir a login
    if (to.meta?.requiresAuth && !isAuthenticated) {
        return next({ name: 'Login' });
    }
    
    next();
});

export default router;
