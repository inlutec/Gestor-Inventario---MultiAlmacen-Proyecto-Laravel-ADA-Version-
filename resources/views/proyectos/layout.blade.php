<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <meta name="application-name" content="Gestión de Proyectos">
    <meta name="description" content="Sistema de gestión de proyectos colaborativo - Junta de Andalucía">
    <meta name="theme-color" content="#006633">
    
    <link rel="apple-touch-icon" sizes="180x180" href="/gestionmaterial/images/icons/icon-192x192.png">
    
    <title>@yield('title', 'Gestión de Proyectos') - Junta de Andalucía</title>
    
    <script>
        (function() {
            try {
                var mode = localStorage.getItem("theme:mode") || "system";
                var isDark = mode === "dark" || (mode === "system" && window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches);
                if (isDark) document.documentElement.classList.add("dark");
            } catch (e) {}
        })();
    </script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="antialiased bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-10 w-auto" src="/gestionmaterial/images/junta-logo.png" alt="Junta de Andalucía">
                        <span class="ml-3 text-lg font-semibold text-gray-900">Gestión de Proyectos</span>
                    </div>
                    
                    <div class="hidden sm:ml-8 sm:flex sm:space-x-4">
                        <a href="{{ route('proyectos.dashboard') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium {{ request()->routeIs('proyectos.dashboard') ? 'text-junta-green-600 border-b-2 border-junta-green-600' : 'text-gray-600 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Dashboard
                        </a>
                        <a href="{{ route('proyectos.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium {{ request()->routeIs('proyectos.index') ? 'text-junta-green-600 border-b-2 border-junta-green-600' : 'text-gray-600 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Proyectos
                        </a>
                        <a href="{{ route('proyectos.tareas.mis-tareas') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium {{ request()->routeIs('proyectos.tareas.*') ? 'text-junta-green-600 border-b-2 border-junta-green-600' : 'text-gray-600 hover:text-gray-900' }}">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            Mis Tareas
                        </a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Notificaciones -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if(auth()->user()->notificacionesProyectos()->noLeidas()->count() > 0)
                            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500"></span>
                            @endif
                        </button>
                    </div>
                    
                    <!-- Usuario -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-700">{{ auth()->user()->nombre }}</span>
                        <a href="/" class="text-sm text-gray-600 hover:text-gray-900">
                            Volver al sistema principal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <main class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img class="h-8" src="/gestionmaterial/images/ada-logo.png" alt="Agencia Digital">
                    <span class="text-sm text-gray-500">© 2024 Junta de Andalucía</span>
                </div>
                <div class="text-sm text-gray-500">
                    Sistema de Gestión de Proyectos v1.0.0
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
    
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
