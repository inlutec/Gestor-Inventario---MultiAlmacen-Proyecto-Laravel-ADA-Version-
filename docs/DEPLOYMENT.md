# Guía de Despliegue en Producción

## Requisitos Previos

### Servidor
- **Sistema Operativo**: Linux (Ubuntu 20.04+ recomendado)
- **PHP**: 8.3 o superior
- **MySQL/MariaDB**: 8.0+ o 10.3+
- **Nginx**: 1.18+ o Apache 2.4+
- **Node.js**: 18.x o superior
- **NPM**: 9.x o superior
- **Composer**: 2.x

### Extensiones PHP Requeridas
```bash
php -m | grep -E "(bcmath|ctype|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml|gd|zip)"
```

## Checklist Pre-Despliegue

- [ ] Variables de entorno configuradas (`.env`)
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Base de datos creada y migrada
- [ ] Assets compilados (`npm run build`)
- [ ] Permisos de storage correctos
- [ ] Nginx configurado
- [ ] PHP-FPM configurado
- [ ] SMTP configurado
- [ ] Backup inicial creado
- [ ] Usuario admin creado
- [ ] SSL/HTTPS configurado (recomendado)

## Pasos de Despliegue

### 1. Preparar el Servidor

#### Instalar Dependencias del Sistema
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-xml php8.3-mbstring \
    php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath nginx mysql-server nodejs npm composer git
```

#### Configurar PHP-FPM
```bash
# Editar /etc/php/8.3/fpm/pool.d/www.conf
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

Configuración recomendada:
```ini
user = www-data
group = www-data
listen = /var/run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

### 2. Clonar y Configurar el Proyecto

```bash
# Clonar repositorio
cd /var/www
sudo git clone <url-del-repositorio> gestor-inventario-material
cd gestor-inventario-material

# Instalar dependencias PHP
sudo composer install --no-dev --optimize-autoloader

# Instalar dependencias Node.js
sudo npm ci

# Configurar permisos
sudo chown -R www-data:www-data /var/www/gestor-inventario-material
sudo chmod -R 755 /var/www/gestor-inventario-material
sudo chmod -R 775 storage bootstrap/cache
```

### 3. Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Editar .env
nano .env
```

Configuración mínima en `.env`:
```env
APP_NAME="Gestión de Material"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://tu-dominio.com/gestionmaterial

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_base_datos
DB_USERNAME=usuario_db
DB_PASSWORD=contraseña_segura

ASSET_URL=/gestionmaterial

# Configuración SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=usuario@example.com
MAIL_PASSWORD=contraseña
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 4. Configurar Base de Datos

```bash
# Conectarse a MySQL
sudo mysql -u root -p

# Crear base de datos
CREATE DATABASE nombre_base_datos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Crear usuario
CREATE USER 'usuario_db'@'localhost' IDENTIFIED BY 'contraseña_segura';
GRANT ALL PRIVILEGES ON nombre_base_datos.* TO 'usuario_db'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Ejecutar migraciones
php artisan migrate --force

# Ejecutar seeders (opcional)
php artisan db:seed --class=ProvinciaSeeder
```

### 5. Compilar Assets

```bash
# Compilar assets para producción
npm run build
```

### 6. Configurar Nginx

Crear archivo de configuración:
```bash
sudo nano /etc/nginx/sites-available/gestionmaterial
```

Contenido:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tu-dominio.com;
    
    # Redirigir a HTTPS (si tienes SSL)
    # return 301 https://$server_name$request_uri;
    
    root /var/www/html;
    index index.php index.html index.htm;

    # Redirigir /gestionmaterial a /gestionmaterial/
    location = /gestionmaterial {
        return 301 /gestionmaterial/;
    }

    # Configuración para /gestionmaterial/ - Laravel
    location /gestionmaterial/ {
        alias /var/www/gestor-inventario-material/public/;
        try_files $uri $uri/ @gestionmaterial_fallback;
        
        # Configuración para archivos estáticos
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
            add_header X-Content-Type-Options "nosniff";
            access_log off;
        }
        
        # Configuración para PHP
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            fastcgi_param PATH_INFO $fastcgi_path_info;
            include fastcgi_params;
        }
    }
    
    # Fallback para Laravel routing
    location @gestionmaterial_fallback {
        rewrite ^/gestionmaterial/(.*)$ /gestionmaterial/index.php?$query_string last;
    }
    
    # Configuración específica para SSE (Server-Sent Events) - Firma móvil
    location /gestionmaterial/api/firma-movil/stream {
        alias /var/www/gestor-inventario-material/public/;
        try_files $uri @gestionmaterial_fallback;
        
        # Configuración para SSE
        proxy_buffering off;
        proxy_cache off;
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
        
        # Headers SSE
        add_header Cache-Control no-cache;
        add_header X-Accel-Buffering no;
        
        # Configuración para PHP
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            fastcgi_param PATH_INFO $fastcgi_path_info;
            include fastcgi_params;
        }
    }
    
    # Headers de seguridad
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    
    # Ocultar versión de Nginx
    server_tokens off;
    
    # Limitar tamaño de uploads
    client_max_body_size 10M;
}
```

Habilitar sitio:
```bash
sudo ln -s /etc/nginx/sites-available/gestionmaterial /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. Optimizar Laravel

```bash
# Cachear configuración
php artisan config:cache

# Cachear rutas
php artisan route:cache

# Cachear vistas
php artisan view:cache

# Optimizar autoloader
composer dump-autoload --optimize
```

### 8. Crear Usuario Admin

```bash
php artisan tinker
```

```php
$usuario = new \App\Models\Usuario();
$usuario->nombre = 'Admin';
$usuario->apellido = 'Sistema';
$usuario->email = 'admin@example.com';
$usuario->password = bcrypt('contraseña_segura');
$usuario->rol = 'admin';
$usuario->activo = true;
$usuario->save();
exit
```

### 9. Configurar SSL/HTTPS (Recomendado)

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d tu-dominio.com

# Renovación automática
sudo certbot renew --dry-run
```

## Actualización en Producción

### Proceso de Actualización

```bash
# 1. Hacer backup
php artisan backup:crear

# 2. Actualizar código
cd /var/www/gestor-inventario-material
git pull origin main

# 3. Actualizar dependencias
composer install --no-dev --optimize-autoloader
npm ci

# 4. Ejecutar migraciones
php artisan migrate --force

# 5. Recompilar assets
npm run build

# 6. Limpiar y recachear
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Recargar servicios
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx
```

## Rollback

Si algo sale mal durante el despliegue:

```bash
# 1. Restaurar backup
php artisan backup:restaurar nombre_backup.sql

# 2. Revertir código
git checkout <commit-anterior>

# 3. Revertir migraciones (si es necesario)
php artisan migrate:rollback

# 4. Recompilar assets
npm run build

# 5. Recargar servicios
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx
```

## Monitoreo

### Logs

```bash
# Logs de Laravel
tail -f storage/logs/laravel.log

# Logs de Nginx
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Logs de PHP-FPM
sudo tail -f /var/log/php8.3-fpm.log
```

### Verificar Estado

```bash
# Estado de servicios
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql

# Verificar conexión a BD
php artisan tinker
>>> DB::connection()->getPdo();
```

## Optimizaciones de Producción

### PHP

```ini
; /etc/php/8.3/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  # En producción
```

### MySQL

```sql
-- Verificar índices
SHOW INDEX FROM material_movimientos;

-- Optimizar tablas periódicamente
OPTIMIZE TABLE material_movimientos;
```

### Nginx

```nginx
# Compresión gzip
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/json;
```

## Tareas Programadas (Cron)

```bash
# Editar crontab
sudo crontab -e -u www-data
```

Agregar:
```cron
# Limpiar sesiones expiradas (diario a las 2 AM)
0 2 * * * cd /var/www/gestor-inventario-material && php artisan session:gc

# Backup automático (diario a las 3 AM)
0 3 * * * cd /var/www/gestor-inventario-material && php artisan backup:crear

# Limpiar logs antiguos (semanal)
0 4 * * 0 find /var/www/gestor-inventario-material/storage/logs -name "*.log" -mtime +30 -delete
```

## Seguridad

### Firewall

```bash
# Configurar UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Permisos

```bash
# Verificar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chmod -R 755 public
```

### Variables de Entorno

- Nunca commitear `.env` al repositorio
- Usar contraseñas seguras
- Rotar credenciales periódicamente

## Troubleshooting

Ver `docs/TROUBLESHOOTING.md` para problemas comunes y soluciones.
