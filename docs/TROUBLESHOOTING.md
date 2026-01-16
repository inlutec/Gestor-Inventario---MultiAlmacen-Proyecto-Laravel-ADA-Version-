# Guía de Troubleshooting

## Problemas Comunes y Soluciones

### La aplicación no carga

**Síntomas**: Error 404 o página en blanco

**Soluciones**:
1. Verificar configuración de Nginx:
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   ```

2. Verificar permisos:
   ```bash
   sudo chown -R www-data:www-data /var/www/gestor-inventario-material/storage
   sudo chmod -R 775 /var/www/gestor-inventario-material/storage
   ```

3. Limpiar caché de Laravel:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   ```

4. Verificar que los assets estén compilados:
   ```bash
   npm run build
   ```

---

### Los assets no se cargan (CSS/JS)

**Síntomas**: Página sin estilos o JavaScript no funciona

**Soluciones**:
1. Verificar que `ASSET_URL` en `.env` sea correcto:
   ```env
   ASSET_URL=/gestionmaterial
   ```

2. Recompilar assets:
   ```bash
   npm run build
   ```

3. Verificar que `app.blade.php` tenga los nombres correctos de archivos compilados (ver `public/build/manifest.json`)

4. Limpiar caché del navegador (Ctrl+F5)

5. Verificar permisos de `public/build/`:
   ```bash
   sudo chmod -R 755 public/build
   ```

---

### Error 500 en producción

**Síntomas**: Error 500 Internal Server Error

**Soluciones**:
1. Verificar logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Verificar permisos de storage:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

3. Verificar configuración de PHP:
   ```bash
   php -v
   php -m  # Verificar extensiones
   ```

4. Verificar variables de entorno:
   ```bash
   php artisan config:cache
   ```

5. Verificar logs de PHP-FPM:
   ```bash
   sudo tail -f /var/log/php8.3-fpm.log
   ```

---

### No se pueden crear movimientos

**Síntomas**: Error al guardar movimiento

**Soluciones**:
1. Verificar que existan justificantes activos:
   ```sql
   SELECT * FROM justificantes WHERE activo = 1;
   ```

2. Verificar que los materiales tengan stock suficiente (para salidas):
   - Revisar existencias en la interfaz
   - Verificar que `material_existencias` tenga datos correctos

3. Verificar que el destino tenga sede y departamento (para entradas):
   - El destino debe tener `destino_sede_id` y `destino_departamento_id`

4. Revisar logs de Laravel:
   ```bash
   tail -f storage/logs/laravel.log
   ```

5. Verificar validaciones en el controlador:
   - Revisar `MaterialMovimientoController@store`

---

### Las peticiones públicas no funcionan

**Síntomas**: No se ven categorías o materiales en `/peticion`

**Soluciones**:
1. Verificar que las categorías estén activas:
   ```sql
   SELECT * FROM categorias WHERE activo = 1;
   ```

2. Verificar que los materiales tengan `foto_visible_publico = true` si quieren mostrar foto

3. Verificar que los materiales tengan stock > 0:
   ```sql
   SELECT * FROM material_existencias WHERE cantidad > 0;
   ```

4. Verificar que las rutas públicas estén en `routes/web.php` (no en `api.php`)

5. Verificar CORS si hay problemas de acceso:
   - Revisar `config/cors.php`

---

### Los emails no se envían

**Síntomas**: Notificaciones no llegan

**Soluciones**:
1. Verificar configuración SMTP en Configuración → SMTP

2. Probar conexión SMTP desde la interfaz

3. Verificar logs de Laravel para errores de email:
   ```bash
   tail -f storage/logs/laravel.log | grep -i mail
   ```

4. Verificar que `MAIL_*` esté configurado en `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.example.com
   MAIL_PORT=587
   MAIL_USERNAME=usuario@example.com
   MAIL_PASSWORD=contraseña
   MAIL_ENCRYPTION=tls
   ```

5. Verificar que el servidor SMTP esté accesible:
   ```bash
   telnet smtp.example.com 587
   ```

6. Probar con comando de Laravel:
   ```bash
   php artisan tinker
   >>> Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });
   ```

---

### La firma móvil no funciona

**Síntomas**: El dispositivo móvil no recibe solicitudes de firma o se desconecta

**Soluciones**:
1. **Verificar conexión SSE**:
   - Verificar que el endpoint `/api/firma-movil/stream` esté accesible
   - Verificar que Nginx no esté haciendo buffering (debe tener `X-Accel-Buffering: no`)
   - Verificar logs del navegador para errores de EventSource

2. **Verificar ID de sesión**:
   - El ID de sesión debe ser de 4 dígitos
   - Verificar que el ID introducido en la web coincida con el del móvil
   - La sesión expira después de 24 horas de inactividad

3. **Verificar configuración de Nginx para SSE**:
   ```nginx
   # En la configuración de /gestionmaterial/
   proxy_buffering off;
   proxy_cache off;
   proxy_read_timeout 3600s;
   add_header X-Accel-Buffering no;
   ```

4. **Verificar caché de Laravel**:
   - Las sesiones SSE se almacenan en caché de Laravel
   - Verificar que el driver de caché esté funcionando correctamente
   - Limpiar caché si es necesario: `php artisan cache:clear`

5. **Reconexión automática**:
   - El cliente SSE tiene reconexión automática cada 3 segundos
   - Si la conexión se pierde, se reconecta automáticamente
   - Verificar que no haya firewalls bloqueando conexiones persistentes

6. **Verificar logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i "firma-movil"
   ```

Ver documentación completa en `docs/SSE_IMPLEMENTATION.md`

---

### El mapa de almacenes no se muestra

**Síntomas**: Mapa en blanco en petición pública

**Soluciones**:
1. **Verificar que Leaflet.js esté cargado**:
   - El mapa utiliza la librería **Leaflet.js** (versión 1.9.4, incluida en `package.json`)
   - Verificar que se importe correctamente en `MaterialPeticionPublica.vue`
   - Verificar que los CSS de Leaflet se carguen (normalmente desde CDN o node_modules)
   - Verificar consola del navegador para errores de carga de Leaflet
   - El mapa se inicializa con `L.map()` y utiliza tiles de OpenStreetMap

2. **Verificar que los almacenes tengan coordenadas**:
   - Los almacenes deben tener `lat` y `lng` en sus datos
   - Verificar en la base de datos que los departamentos marcados como almacenes (`es_almacen = true`) tengan coordenadas
   - El endpoint `/api/almacenes-publicos` debe devolver estos campos

3. **Verificar consola del navegador**:
   - Buscar errores JavaScript relacionados con Leaflet
   - Verificar que no haya conflictos con otras librerías
   - Verificar que el contenedor `#mapa-almacenes` exista en el DOM

4. **Verificar que la API devuelva datos**:
   - Probar endpoint: `GET /api/almacenes-publicos`
   - Verificar que devuelva `{ almacenes: [...], provincias: [...] }`
   - Cada almacén debe tener: `id`, `nombre`, `lat`, `lng`, `provincia`, `direccion`

5. **Verificar CSS de Leaflet**:
   - Asegurar que los estilos de Leaflet se carguen correctamente
   - Verificar que el contenedor del mapa tenga altura definida (ej: `height: 400px`)
   - Verificar que no haya conflictos con estilos de Tailwind

---

### Error de conexión a base de datos

**Síntomas**: Error "SQLSTATE[HY000] [2002] Connection refused" o similar

**Soluciones**:
1. Verificar que MySQL esté corriendo:
   ```bash
   sudo systemctl status mysql
   sudo systemctl start mysql
   ```

2. Verificar credenciales en `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nombre_base_datos
   DB_USERNAME=usuario_db
   DB_PASSWORD=contraseña_db
   ```

3. Probar conexión:
   ```bash
   mysql -u usuario_db -p nombre_base_datos
   ```

4. Verificar permisos del usuario:
   ```sql
   SHOW GRANTS FOR 'usuario_db'@'localhost';
   ```

5. Limpiar caché de configuración:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

---

### Problemas con migraciones

**Síntomas**: Error al ejecutar migraciones

**Soluciones**:
1. Verificar estado de migraciones:
   ```bash
   php artisan migrate:status
   ```

2. Verificar logs de errores:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Si hay conflicto, hacer rollback:
   ```bash
   php artisan migrate:rollback --step=1
   ```

4. Verificar que la base de datos exista:
   ```sql
   SHOW DATABASES;
   ```

5. Verificar permisos del usuario de BD:
   ```sql
   GRANT ALL PRIVILEGES ON nombre_base_datos.* TO 'usuario_db'@'localhost';
   FLUSH PRIVILEGES;
   ```

---

### Problemas con permisos

**Síntomas**: Error "Permission denied" al escribir archivos

**Soluciones**:
1. Verificar propietario de archivos:
   ```bash
   ls -la storage/
   ```

2. Corregir propietario:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

3. Verificar permisos de directorios:
   ```bash
   find storage -type d -exec chmod 775 {} \;
   find storage -type f -exec chmod 664 {} \;
   ```

---

### Problemas con compilación de assets

**Síntomas**: Error al ejecutar `npm run build`

**Soluciones**:
1. Limpiar node_modules y reinstalar:
   ```bash
   rm -rf node_modules package-lock.json
   npm install
   ```

2. Verificar versión de Node.js:
   ```bash
   node -v  # Debe ser 18.x o superior
   ```

3. Verificar espacio en disco:
   ```bash
   df -h
   ```

4. Limpiar caché de npm:
   ```bash
   npm cache clean --force
   ```

---

### Problemas con autenticación

**Síntomas**: No se puede iniciar sesión o se cierra automáticamente

**Soluciones**:
1. Verificar configuración de Sanctum:
   - Revisar `config/sanctum.php`
   - Verificar que `SANCTUM_STATEFUL_DOMAINS` esté configurado

2. Verificar cookies:
   - Verificar que las cookies se estén enviando
   - Verificar configuración de sesión en `config/session.php`

3. Verificar tokens:
   - Verificar que el token se guarde en `localStorage`
   - Verificar que se envíe en headers: `Authorization: Bearer {token}`

4. Limpiar sesiones:
   ```bash
   php artisan session:gc
   ```

---

## Comandos Útiles de Diagnóstico

```bash
# Ver estado de la aplicación
php artisan about

# Ver rutas registradas
php artisan route:list

# Ver configuración actual
php artisan config:show

# Verificar migraciones
php artisan migrate:status

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Verificar conexión a BD
php artisan tinker
>>> DB::connection()->getPdo();

# Limpiar todas las cachés
php artisan optimize:clear

# Verificar permisos
ls -la storage/
ls -la bootstrap/cache/
```

## Obtener Ayuda

Si el problema persiste:

1. Revisar logs completos: `storage/logs/laravel.log`
2. Revisar logs del servidor web: `/var/log/nginx/error.log`
3. Revisar esta documentación completa
4. Revisar documentación oficial de Laravel y Vue.js
5. Buscar en issues del repositorio (si existe)
