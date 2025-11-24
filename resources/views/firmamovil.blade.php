<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <title>Firma Móvil - Gestor Material</title>
    <meta name="description" content="Aplicación para firmar movimientos de material desde dispositivos móviles">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#006633">
    
    <!-- Manifest - DESHABILITADO -->
    <!-- <link rel="manifest" href="/gestionmaterial/manifest-firmamovil.json"> -->
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/gestionmaterial/images/icons/firma-icon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/gestionmaterial/images/icons/firma-icon-72x72.png">
    
    <!-- MS Tile -->
    <meta name="msapplication-TileColor" content="#006633">
    <meta name="msapplication-TileImage" content="/gestionmaterial/images/icons/firma-icon-144x144.png">
    <meta name="msapplication-config" content="/gestionmaterial/browserconfig.xml">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://100.102.224.49/gestionmaterial/firmamovil">
    <meta property="og:title" content="Firma Móvil - Gestor Material">
    <meta property="og:description" content="Aplicación para firmar movimientos de material desde dispositivos móviles">
    <meta property="og:image" content="https://100.102.224.49/gestionmaterial/images/icons/firma-icon-512x512.png">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary">
    <meta property="twitter:url" content="http://100.102.224.49/gestionmaterial/firmamovil">
    <meta property="twitter:title" content="Firma Móvil - Gestor Material">
    <meta property="twitter:description" content="Aplicación para firmar movimientos de material desde dispositivos móviles">
    <meta property="twitter:image" content="/gestionmaterial/images/icons/firma-icon-512x512.png">
    
    <!-- Prevent caching for dynamic content -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- NO CARGAR app.js - Firmamovil es standalone -->
    <!-- Solo cargar estilos básicos -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        /* Asegurar que el body ocupe toda la pantalla */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Ocultar scrollbar en iOS */
        ::-webkit-scrollbar {
            display: none;
        }
        
        /* Prevenir zoom en iOS */
        input, textarea, select {
            font-size: 16px !important;
        }
        
        /* PWA splash screen style */
        .pwa-splash {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #006633 0%, #004d26 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }
        
        .pwa-splash.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .pwa-splash-logo {
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
        }
        
        .pwa-splash-text {
            color: white;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .pwa-splash-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
        }
        
        /* Loading spinner */
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-top: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div id="firma-app">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #006633 0%, #004d26 100%); color: white; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h1 style="font-size: 24px; font-weight: 600; margin-bottom: 8px;">📱 Firma Móvil</h1>
            <p style="font-size: 14px; opacity: 0.9;">Gestor de Material - ADA Córdoba</p>
        </div>
        
        <!-- Content -->
        <div id="main-content" style="padding: 20px; max-width: 500px; margin: 0 auto;">
            
            <!-- Pantalla: Código de sesión -->
            <div id="session-screen" style="background: white; padding: 32px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 48px; margin-bottom: 16px;">🔐</div>
                <h2 style="font-size: 20px; color: #333; margin-bottom: 8px;">Tu código de sesión</h2>
                <p style="font-size: 14px; color: #666; margin-bottom: 24px;">Introduce este código en el PC cuando solicites una firma remota</p>
                
                <div id="session-code" style="font-size: 72px; font-weight: 700; color: #006633; letter-spacing: 12px; margin: 24px 0; font-family: 'Courier New', monospace;">
                    ----
                </div>
                
                <div id="connection-status" style="padding: 12px; background: #f0f0f0; border-radius: 8px; margin-top: 24px;">
                    <div style="font-size: 14px; color: #666;">
                        <span id="status-icon">⏳</span>
                        <span id="status-text">Conectando...</span>
                    </div>
                </div>
                
                <button 
                    id="refresh-session" 
                    onclick="generarNuevaSesion()"
                    style="margin-top: 16px; padding: 12px 24px; background: #f0f0f0; color: #666; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; display: none;"
                >
                    🔄 Generar nuevo código
                </button>
            </div>
            
            <!-- Pantalla: Solicitud de firma (oculta inicialmente) -->
            <div id="firma-request-screen" style="display: none;"></div>
            
            <!-- Pantalla: Esperando (oculta inicialmente) -->
            <div id="waiting-screen" style="display: none; background: white; padding: 32px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 64px; margin-bottom: 16px;">⏰</div>
                <h2 style="font-size: 20px; color: #333; margin-bottom: 8px;">Esperando solicitud...</h2>
                <p style="font-size: 14px; color: #666;">Cuando se solicite una firma desde el PC, aparecerá aquí</p>
                
                <div style="margin-top: 24px; padding: 16px; background: #f9f9f9; border-radius: 8px;">
                    <div style="font-size: 48px; font-weight: 700; color: #006633; letter-spacing: 8px; font-family: 'Courier New', monospace;" id="waiting-code">
                        ----
                    </div>
                    <div style="font-size: 12px; color: #999; margin-top: 8px;">Tu código de sesión</div>
                </div>
                
                <button 
                    onclick="volverAInicio()"
                    style="margin-top: 24px; padding: 12px 24px; background: #f0f0f0; color: #666; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;"
                >
                    🔙 Volver
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let sessionId = null;
        let eventSource = null;
        let isConnected = false;
        
        // Elementos del DOM
        const sessionScreen = document.getElementById('session-screen');
        const sessionCodeDisplay = document.getElementById('session-code');
        const statusIcon = document.getElementById('status-icon');
        const statusText = document.getElementById('status-text');
        const refreshBtn = document.getElementById('refresh-session');
        const firmaRequestScreen = document.getElementById('firma-request-screen');
        const waitingScreen = document.getElementById('waiting-screen');
        const waitingCode = document.getElementById('waiting-code');
        
        // Generar código de sesión aleatorio de 4 dígitos
        function generarCodigoSesion() {
            return Math.floor(1000 + Math.random() * 9000).toString();
        }
        
        // Iniciar sesión
        function iniciarSesion() {
            sessionId = generarCodigoSesion();
            sessionCodeDisplay.textContent = sessionId;
            waitingCode.textContent = sessionId;
            
            conectarSSE();
        }
        
        // Conectar al stream SSE
        function conectarSSE() {
            if (eventSource) {
                eventSource.close();
            }
            
            updateStatus('⏳', 'Conectando...', '#ffc107');
            
            eventSource = new EventSource(`/gestionmaterial/api/firma-movil/stream?session=${sessionId}`);
            
            eventSource.onopen = function() {
                console.log('Conexión SSE establecida');
            };
            
            eventSource.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    console.log('Mensaje SSE:', data);
                    
                    switch(data.tipo) {
                        case 'connected':
                            isConnected = true;
                            updateStatus('✅', 'Conectado - En espera', '#4caf50');
                            refreshBtn.style.display = 'inline-block';
                            // Mostrar pantalla de espera después de 2 segundos
                            setTimeout(mostrarPantallaEspera, 2000);
                            break;
                            
                        case 'ping':
                            // Mantener vivo
                            console.log('Ping recibido');
                            break;
                            
                        case 'solicitud_firma':
                            mostrarSolicitudFirma(data);
                            break;
                    }
                } catch (error) {
                    console.error('Error procesando mensaje SSE:', error);
                }
            };
            
            eventSource.onerror = function(error) {
                console.error('Error SSE:', error);
                isConnected = false;
                updateStatus('❌', 'Error de conexión', '#f44336');
                
                // Intentar reconectar después de 5 segundos
                setTimeout(() => {
                    if (sessionId) {
                        updateStatus('🔄', 'Reconectando...', '#ff9800');
                        conectarSSE();
                    }
                }, 5000);
            };
        }
        
        function updateStatus(icon, text, color) {
            statusIcon.textContent = icon;
            statusText.textContent = text;
            statusText.style.color = color || '#666';
        }
        
        function mostrarPantallaEspera() {
            sessionScreen.style.display = 'none';
            waitingScreen.style.display = 'block';
        }
        
        let currentMovimientoId = null;
        let currentTipoFirma = null;
        let canvas = null;
        let ctx = null;
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        
        function mostrarSolicitudFirma(data) {
            waitingScreen.style.display = 'none';
            
            const mov = data.movimiento;
            const tipoFirma = data.tipo_firma || 'receptor';
            currentMovimientoId = mov.id;
            currentTipoFirma = tipoFirma;
            
            // Lista de materiales
            let materialesHTML = '';
            if (mov.materiales && mov.materiales.length > 0) {
                materialesHTML = '<div style="margin-top: 12px;"><strong style="color: #666; font-size: 12px; text-transform: uppercase;">Materiales</strong><ul style="margin: 8px 0; padding-left: 20px; font-size: 14px; color: #666;">';
                mov.materiales.forEach(mat => {
                    materialesHTML += `<li>${mat.nombre || mat.codigo} (x${mat.cantidad})</li>`;
                });
                materialesHTML += '</ul></div>';
            }
            
            firmaRequestScreen.innerHTML = `
                <div style="background: white; padding: 32px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div style="text-align: center; margin-bottom: 24px;">
                        <div style="font-size: 64px; margin-bottom: 16px;">✍️</div>
                        <h2 style="font-size: 20px; color: #333; margin-bottom: 8px;">Solicitud de Firma</h2>
                        <p style="font-size: 14px; color: #666;">Dibuja tu firma en el recuadro</p>
                    </div>
                    
                    <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 24px;">
                        <div style="margin-bottom: 12px;">
                            <strong style="color: #666; font-size: 12px; text-transform: uppercase;">Movimiento</strong>
                            <div style="font-size: 18px; color: #333; font-weight: 600;">#${mov.id}</div>
                        </div>
                        
                        <div style="margin-bottom: 12px;">
                            <strong style="color: #666; font-size: 12px; text-transform: uppercase;">Tipo</strong>
                            <div style="font-size: 16px; color: #333;">${mov.tipo || 'Movimiento'}</div>
                        </div>
                        
                        ${mov.numero_albaran ? `
                        <div style="margin-bottom: 12px;">
                            <strong style="color: #666; font-size: 12px; text-transform: uppercase;">Documento</strong>
                            <div style="font-size: 16px; color: #333;">${mov.numero_albaran}</div>
                        </div>
                        ` : ''}
                        
                        <div style="margin-bottom: 12px;">
                            <strong style="color: #666; font-size: 12px; text-transform: uppercase;">Origen/Destino</strong>
                            <div style="font-size: 14px; color: #666;">${mov.origen || 'N/A'} → ${mov.destino || 'N/A'}</div>
                        </div>
                        
                        <div style="margin-bottom: 12px;">
                            <strong style="color: #666; font-size: 12px; text-transform: uppercase;">Tipo de Firma</strong>
                            <div style="font-size: 16px; color: #333;">
                                ${tipoFirma === 'receptor' ? '📦 Receptor' : '📤 Emisor'}
                            </div>
                        </div>
                        
                        ${materialesHTML}
                        
                        ${mov.observaciones ? `
                        <div style="margin-top: 12px;">
                            <strong style="color: #666; font-size: 12px; text-transform: uppercase;">Observaciones</strong>
                            <div style="font-size: 14px; color: #666; margin-top: 4px;">${mov.observaciones}</div>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Canvas para firma -->
                    <div style="margin-bottom: 24px;">
                        <div style="margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: #666; font-size: 12px; text-transform: uppercase;">Tu Firma</strong>
                            <button 
                                onclick="limpiarCanvas()"
                                style="padding: 6px 12px; background: #f0f0f0; color: #666; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"
                            >
                                🗑️ Limpiar
                            </button>
                        </div>
                        <canvas 
                            id="firma-canvas"
                            width="400" 
                            height="200"
                            style="border: 2px solid #ddd; border-radius: 8px; width: 100%; height: auto; touch-action: none; background: white;"
                        ></canvas>
                    </div>
                    
                    <div style="display: flex; gap: 12px;">
                        <button 
                            onclick="rechazarFirma()"
                            style="flex: 1; padding: 16px; background: #f44336; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;"
                        >
                            ❌ Rechazar
                        </button>
                        <button 
                            onclick="enviarFirma()"
                            style="flex: 1; padding: 16px; background: #4caf50; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;"
                        >
                            ✅ Firmar
                        </button>
                    </div>
                </div>
            `;
            
            firmaRequestScreen.style.display = 'block';
            
            // Inicializar canvas
            setTimeout(inicializarCanvas, 100);
        }
        
        function inicializarCanvas() {
            canvas = document.getElementById('firma-canvas');
            if (!canvas) return;
            
            ctx = canvas.getContext('2d');
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            
            // Touch events
            canvas.addEventListener('touchstart', startDrawing, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            canvas.addEventListener('touchend', stopDrawing);
            
            // Mouse events (para testing en PC)
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseleave', stopDrawing);
        }
        
        function getCoordinates(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            
            let clientX, clientY;
            if (e.touches && e.touches[0]) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else {
                clientX = e.clientX;
                clientY = e.clientY;
            }
            
            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        }
        
        function startDrawing(e) {
            e.preventDefault();
            isDrawing = true;
            const coords = getCoordinates(e);
            lastX = coords.x;
            lastY = coords.y;
        }
        
        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            
            const coords = getCoordinates(e);
            
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(coords.x, coords.y);
            ctx.stroke();
            
            lastX = coords.x;
            lastY = coords.y;
        }
        
        function stopDrawing(e) {
            if (isDrawing) {
                e.preventDefault();
            }
            isDrawing = false;
        }
        
        function limpiarCanvas() {
            if (ctx && canvas) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        }
        
        async function enviarFirma() {
            if (!canvas) {
                alert('Error: Canvas no inicializado');
                return;
            }
            
            // Verificar que se haya dibujado algo
            const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            let isEmpty = true;
            for (let i = 0; i < imgData.data.length; i += 4) {
                if (imgData.data[i + 3] !== 0) {
                    isEmpty = false;
                    break;
                }
            }
            
            if (isEmpty) {
                alert('Por favor, dibuja tu firma antes de continuar');
                return;
            }
            
            const firmaBase64 = canvas.toDataURL('image/png');
            
            try {
                const response = await fetch(`/gestionmaterial/api/material-movimientos/${currentMovimientoId}/firmar-remoto`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        tipo_firma: currentTipoFirma,
                        firma: firmaBase64
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    mostrarResultado('✅ Firma enviada correctamente', '#4caf50');
                } else {
                    mostrarResultado('❌ Error: ' + (data.message || 'No se pudo firmar'), '#f44336');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarResultado('❌ Error de conexión', '#f44336');
            }
        }
        
        
        async function confirmarFirma(movimientoId, tipoFirma) {
            try {
                const response = await fetch(`/gestionmaterial/api/movimientos/${movimientoId}/firmar-movil`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        tipo_firma: tipoFirma,
                        firmado: true
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    mostrarResultado('✅ Firma confirmada correctamente', '#4caf50');
                } else {
                    mostrarResultado('❌ Error: ' + (data.message || 'No se pudo firmar'), '#f44336');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarResultado('❌ Error de conexión', '#f44336');
            }
        }
        
        
        function rechazarFirma() {
            mostrarResultado('❌ Firma rechazada', '#ff9800');
        }
        
        function mostrarResultado(mensaje, color) {
            firmaRequestScreen.innerHTML = `
                <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                    <div style="font-size: 64px; margin-bottom: 24px;">${mensaje.includes('✅') ? '✅' : '❌'}</div>
                    <h2 style="font-size: 20px; color: ${color}; margin-bottom: 24px;">${mensaje}</h2>
                    <button 
                        onclick="volverAEspera()"
                        style="padding: 14px 32px; background: #006633; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;"
                    >
                        Continuar esperando
                    </button>
                </div>
            `;
        }
        
        function volverAEspera() {
            firmaRequestScreen.style.display = 'none';
            waitingScreen.style.display = 'block';
        }
        
        function generarNuevaSesion() {
            if (eventSource) {
                eventSource.close();
            }
            sessionScreen.style.display = 'block';
            waitingScreen.style.display = 'none';
            firmaRequestScreen.style.display = 'none';
            iniciarSesion();
        }
        
        function volverAInicio() {
            if (eventSource) {
                eventSource.close();
            }
            sessionScreen.style.display = 'block';
            waitingScreen.style.display = 'none';
            firmaRequestScreen.style.display = 'none';
            sessionId = null;
            sessionCodeDisplay.textContent = '----';
            updateStatus('⏳', 'Desconectado', '#999');
            refreshBtn.style.display = 'none';
        }
        
        // Desregistrar service workers
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    console.log('Desregistrando SW:', registration.scope);
                    registration.unregister();
                }
            });
        }
        
        // Iniciar al cargar
        console.log('Firma Móvil cargada correctamente');
        iniciarSesion();
        
        // Limpiar al cerrar
        window.addEventListener('beforeunload', function() {
            if (eventSource) {
                eventSource.close();
            }
        });
    </script>
</body>
</html>
