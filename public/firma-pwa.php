<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Móvil - Gestor Material</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#006633">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Firma Móvil">
    
    <!-- PWA Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="/gestionmaterial/images/icons/firma-icon-192x192.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/gestionmaterial/images/icons/firma-icon-192x192.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/gestionmaterial/manifest-firmamovil.json">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #006633 0%, #004d26 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .app-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            color: white;
        }
        
        .header {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .content {
            flex: 1;
            background: white;
            color: #333;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .signature-pad {
            border: 2px solid #006633;
            border-radius: 8px;
            background: white;
            cursor: crosshair;
            touch-action: none;
            width: 100%;
            max-width: 600px;
            height: 300px;
            margin: 20px auto;
            display: block;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #006633;
            color: white;
        }
        
        .btn-primary:hover {
            background: #004d26;
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info {
            background: #e3f2fd;
            color: #1565c0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 600px) {
            .signature-pad {
                height: 250px;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="header">
            <h1>✍️ Firma Móvil</h1>
            <p>Gestor de Material</p>
        </div>
        
        <div class="content">
            <div class="info">
                ℹ️ <strong>Aplicación PWA instalable</strong><br>
                Si estás en Android/iOS, busca el botón "Instalar" en el menú de tu navegador para usar esta app sin conexión.
            </div>
            
            <div id="firma-container">
                <h2 style="text-align: center; color: #006633;">Firmar Documento</h2>
                
                <canvas id="signaturePad" class="signature-pad"></canvas>
                
                <div class="button-group">
                    <button class="btn btn-secondary" id="clearBtn">🗑️ Limpiar</button>
                    <button class="btn btn-primary" id="saveBtn">💾 Guardar Firma</button>
                    <button class="btn btn-danger" id="downloadBtn" style="display:none;">📥 Descargar</button>
                </div>
                
                <div id="message" style="margin-top: 20px;"></div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                <a href="/gestionmaterial/" class="btn btn-secondary">�� Ir a la Aplicación Principal</a>
            </div>
        </div>
    </div>
    
    <script>
        // Registrar Service Worker para PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/gestionmaterial/service-worker-firmamovil.js')
                    .then(reg => {
                        console.log('✅ Service Worker registrado:', reg.scope);
                    })
                    .catch(err => {
                        console.error('❌ Error al registrar Service Worker:', err);
                    });
            });
        }
        
        // Detectar si puede instalar PWA
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            console.log('💡 PWA puede ser instalada');
        });
        
        // Funcionalidad del canvas de firma
        const canvas = document.getElementById('signaturePad');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        
        // Ajustar tamaño del canvas
        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = canvas.width;
            tempCanvas.height = canvas.height;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(canvas, 0, 0);
            
            canvas.width = rect.width * 2;
            canvas.height = rect.height * 2;
            canvas.style.width = rect.width + 'px';
            canvas.style.height = rect.height + 'px';
            
            ctx.scale(2, 2);
            ctx.drawImage(tempCanvas, 0, 0, rect.width, rect.height);
            
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        
        // Eventos de mouse
        canvas.addEventListener('mousedown', (e) => {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            lastX = e.clientX - rect.left;
            lastY = e.clientY - rect.top;
        });
        
        canvas.addEventListener('mousemove', (e) => {
            if (!isDrawing) return;
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            lastX = x;
            lastY = y;
        });
        
        canvas.addEventListener('mouseup', () => isDrawing = false);
        canvas.addEventListener('mouseout', () => isDrawing = false);
        
        // Eventos táctiles
        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            const touch = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            isDrawing = true;
            lastX = touch.clientX - rect.left;
            lastY = touch.clientY - rect.top;
        });
        
        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            if (!isDrawing) return;
            const touch = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            const x = touch.clientX - rect.left;
            const y = touch.clientY - rect.top;
            
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            lastX = x;
            lastY = y;
        });
        
        canvas.addEventListener('touchend', () => isDrawing = false);
        
        // Botón limpiar
        document.getElementById('clearBtn').addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            document.getElementById('message').innerHTML = '';
            document.getElementById('downloadBtn').style.display = 'none';
        });
        
        // Botón guardar
        document.getElementById('saveBtn').addEventListener('click', () => {
            const dataURL = canvas.toDataURL('image/png');
            
            document.getElementById('message').innerHTML = 
                '<div class="success">✅ Firma lista para descargar</div>';
            
            document.getElementById('downloadBtn').style.display = 'inline-block';
        });
        
        // Botón descargar
        document.getElementById('downloadBtn').addEventListener('click', () => {
            const dataURL = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = 'firma_' + Date.now() + '.png';
            link.href = dataURL;
            link.click();
            
            document.getElementById('message').innerHTML = 
                '<div class="success">✅ Firma descargada correctamente</div>';
        });
    </script>
</body>
</html>

<!-- Script adicional para forzar prompt de instalación -->
<script>
// Botón para instalar PWA manualmente
let installButton = null;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    window.deferredPrompt = e;
    console.log('✅ beforeinstallprompt disparado');
    
    // Crear botón de instalación si no existe
    if (!installButton) {
        installButton = document.createElement('button');
        installButton.className = 'btn btn-primary';
        installButton.innerHTML = '📲 Instalar App en tu Dispositivo';
        installButton.style.cssText = 'position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; animation: pulse 2s infinite;';
        
        installButton.addEventListener('click', async () => {
            if (window.deferredPrompt) {
                window.deferredPrompt.prompt();
                const { outcome } = await window.deferredPrompt.userChoice;
                console.log(`Usuario ${outcome === 'accepted' ? 'aceptó' : 'rechazó'} la instalación`);
                window.deferredPrompt = null;
                installButton.remove();
            }
        });
        
        document.body.appendChild(installButton);
    }
});

window.addEventListener('appinstalled', () => {
    console.log('✅ PWA instalada correctamente');
    if (installButton) installButton.remove();
});

// Agregar animación pulse
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { transform: translateX(-50%) scale(1); }
        50% { transform: translateX(-50%) scale(1.05); box-shadow: 0 0 20px rgba(0,102,51,0.5); }
    }
`;
document.head.appendChild(style);

// Log para debugging
console.log('🔍 Verificando requisitos PWA:');
console.log('- HTTPS:', location.protocol === 'https:' ? '✅' : '❌');
console.log('- Service Worker:', 'serviceWorker' in navigator ? '✅' : '❌');
console.log('- Manifest:', document.querySelector('link[rel="manifest"]') ? '✅' : '❌');
</script>
