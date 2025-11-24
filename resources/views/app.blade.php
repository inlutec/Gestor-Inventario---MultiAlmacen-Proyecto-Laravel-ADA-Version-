<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="Gestión Material">
    <meta name="apple-mobile-web-app-title" content="Gestión Material">
    <meta name="description" content="Sistema de gestión de pequeño material - Junta de Andalucía">
    <meta name="theme-color" content="#006633">
    <meta name="msapplication-TileColor" content="#006633">
    <meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('icon-192x192.png') }}">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/icon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('images/icons/icon-152x152.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('images/icons/icon-144x144.png') }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('images/icons/icon-128x128.png') }}">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    
    <!-- Prevent zoom on input focus (iOS) -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
    
    <!-- Safe Area Support -->
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    
    <title>Gestión de Pequeño Material - Junta de Andalucía</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="{{ asset('images/junta-logo.png') }}" as="image">
    <link rel="preload" href="{{ asset('images/ada-logo.png') }}" as="image">
    
    <script>
        // Theme initialization with PWA support
        (function() {
            try {
                var mode = localStorage.getItem("theme:mode") || "system";
                var isDark = mode === "dark" || (mode === "system" && window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches);
                if (isDark) document.documentElement.classList.add("dark");
                
                // PWA detection
                if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
                    document.documentElement.classList.add('pwa-standalone');
                }
                
                // Prevent bounce scroll on iOS
                if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
                    document.body.addEventListener('touchmove', function(e) {
                        if (e.scale !== 1) {
                            e.preventDefault();
                        }
                    }, { passive: false });
                }
            } catch (e) {}
        })();
        
        // Service Worker registration with better error handling
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('{{ asset('service-worker.js') }}', {
                    scope: '{{ url('/') }}/'
                }).then(function(registration) {
                    console.log('[PWA] Service Worker registered:', registration.scope);
                    
                    // Check for updates periodically
                    setInterval(function() {
                        registration.update();
                    }, 60 * 60 * 1000); // Every hour
                    
                }).catch(function(error) {
                    console.error('[PWA] Service Worker registration failed:', error);
                });
            });
        }
    </script>
    
    <!-- Leaflet CSS and JS for map functionality -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <!-- CSS Assets with preload - UPDATED FILE NAMES FROM MANIFEST -->
    <link rel="preload" href="{{ asset('build/assets/app-DiwkvIH4.css') }}" as="style">
    <link rel="stylesheet" href="{{ asset('build/assets/app-DiwkvIH4.css') }}">
    
    <!-- JS Assets with defer - UPDATED FILE NAME FROM MANIFEST -->
    <link rel="modulepreload" href="{{ asset('build/assets/app-vg5esXCD.js') }}">
    <script type="module" src="{{ asset('build/assets/app-vg5esXCD.js') }}" defer></script>
    
    <!-- Fallback for older browsers -->
    <script>
        if (!window.customElements) {
            document.write('<script src="https://unpkg.com/@webcomponents/webcomponentsjs/webcomponents-bundle.js"><\/script>');
        }
    </script>
</head>
<body class="antialiased">
    <!-- Loading indicator for PWA -->
    <div id="initial-loader" class="fixed inset-0 bg-white z-50 flex items-center justify-center" style="display: none;">
        <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-junta-green-600"></div>
            <p class="mt-2 text-sm text-gray-600">Cargando...</p>
        </div>
    </div>
    
    <div id="app"></div>
    
    <!-- Show loader if app takes too long to load -->
    <script>
        setTimeout(function() {
            if (!document.getElementById('app').innerHTML.trim()) {
                document.getElementById('initial-loader').style.display = 'flex';
            }
        }, 1000);
    </script>
</body>
</html>