# Documentación de API

## Base URL

```
https://tu-dominio.com/gestionmaterial/api
```

## Autenticación

La mayoría de los endpoints requieren autenticación mediante **Laravel Sanctum**. El token se envía en el header:

```
Authorization: Bearer {token}
```

El token se obtiene mediante el endpoint `/api/login` y se almacena en `localStorage` del navegador.

## Formato de Respuestas

### Respuesta Exitosa
```json
{
  "success": true,
  "data": { ... },
  "message": "Operación exitosa"
}
```

### Respuesta de Error
```json
{
  "success": false,
  "message": "Mensaje de error",
  "errors": {
    "campo": ["Error de validación"]
  }
}
```

## Endpoints Públicos (Sin Autenticación)

### Autenticación

#### POST /api/login
Iniciar sesión y obtener token de autenticación.

**Request:**
```json
{
  "email": "usuario@example.com",
  "password": "contraseña"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "nombre": "Juan",
      "apellido": "Pérez",
      "email": "usuario@example.com",
      "rol": "admin"
    },
    "token": "1|abcdef123456..."
  }
}
```

### Web Pública

#### GET /api/categorias-publicas
Lista de categorías activas para el formulario público.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Material de Oficina",
      "descripcion": "...",
      "imagen": "/storage/categorias/1.jpg"
    }
  ]
}
```

#### GET /api/provincias
Lista de provincias de Andalucía.

#### GET /api/sedes-publicas
Lista de sedes públicas.

#### GET /api/sedes-publicas/{sedeId}/departamentos
Departamentos de una sede específica.

#### GET /api/custom-fields-publicos
Campos personalizados públicos para formularios.

#### GET /api/almacenes-publicos
Lista de almacenes con coordenadas geográficas para el mapa.

**Response:**
```json
{
  "success": true,
  "data": {
    "almacenes": [
      {
        "id": 1,
        "nombre": "Almacén Central",
        "lat": 37.3891,
        "lng": -5.9845,
        "provincia": "Sevilla",
        "direccion": "Calle Ejemplo, 1"
      }
    ],
    "provincias": [...]
  }
}
```

#### GET /api/almacenes-por-provincia
Almacenes filtrados por provincia.

**Query Parameters:**
- `provincia_id` (opcional): ID de la provincia

#### GET /api/materiales-disponibles
Materiales con stock disponible para peticiones públicas.

**Query Parameters:**
- `categoria_id` (opcional): Filtrar por categoría
- `search` (opcional): Búsqueda por nombre/referencia

#### POST /api/peticiones
Crear petición pública de material.

**Request:**
```json
{
  "materiales": [
    {
      "entidad_id": 1,
      "cantidad": 10
    }
  ],
  "departamento_id": 5,
  "usuario_solicitante": "Juan Pérez",
  "email_solicitante": "juan@example.com",
  "telefono_solicitante": "600123456",
  "observaciones": "Material urgente",
  "datos_personalizados": {}
}
```

### Albaranes Públicos

#### GET /api/albaran/{token}
Ver albarán por token público.

#### POST /api/albaran/{token}/firmar
Firmar albarán desde enlace público.

**Request:**
```json
{
  "nombre": "Juan",
  "apellidos": "Pérez",
  "dni": "12345678A",
  "firma": "data:image/png;base64,..."
}
```

#### GET /api/albaran/{token}/pdf
Descargar PDF del albarán firmado.

#### GET /api/albaran/{token}/pdf-sin-firmar
Descargar PDF sin firmar.

#### POST /api/albaran/{token}/subir-pdf-firmado
Subir PDF firmado externamente.

### Firma Móvil (SSE)

#### GET /api/firma-movil/stream
Stream SSE para recibir solicitudes de firma en tiempo real.

**Query Parameters:**
- `session` (requerido): ID de sesión de 4 dígitos

**Response:** `text/event-stream`

**Eventos:**
- `connected`: Conexión establecida
- `ping`: Mantener conexión viva (cada 15 segundos)
- `solicitud_firma`: Solicitud de firma recibida

**Ejemplo de evento:**
```
data: {"tipo":"solicitud_firma","movimiento":{"id":1,"numero_albaran":"ALB-001"},"tipo_firma":"receptor"}

```

#### POST /api/material-movimientos/{id}/firmar-remoto
Solicitar firma remota desde la aplicación web.

**Request:**
```json
{
  "session_id": "1234",
  "tipo_firma": "receptor"
}
```

#### POST /api/firma-movil/firmar
Enviar firma desde dispositivo móvil.

**Request:**
```json
{
  "movimiento_id": 1,
  "tipo_firma": "receptor",
  "firma": "data:image/png;base64,..."
}
```

## Endpoints Autenticados

### Autenticación

#### POST /api/logout
Cerrar sesión e invalidar token.

#### GET /api/me
Obtener información del usuario actual.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Juan",
    "apellido": "Pérez",
    "email": "usuario@example.com",
    "rol": "admin",
    "almacenes": [...]
  }
}
```

#### GET /api/check-session
Verificar si la sesión es válida.

### Dashboard

#### GET /api/dashboard/stats
Estadísticas del dashboard.

**Query Parameters:**
- `almacen_ids[]` (opcional): Filtrar por almacenes

**Response:**
```json
{
  "success": true,
  "data": {
    "kpis": {
      "total_materiales": 150,
      "pendientes_firma": 5,
      "alertas_stock": 12,
      "peticiones_pendientes": 3
    },
    "graficos": {
      "movimientos_mensuales": [...],
      "stock_por_categoria": [...]
    },
    "alertas": [...],
    "movimientos_recientes": [...]
  }
}
```

### Materiales/Entidades

#### GET /api/entidades
Listar entidades (materiales).

**Query Parameters:**
- `tipo_entidad_id` (opcional)
- `almacen_ids[]` (opcional)
- `categoria_id` (opcional)
- `search` (opcional)

#### POST /api/entidades
Crear nueva entidad.

**Request:**
```json
{
  "tipo_entidad_id": 1,
  "categoria_id": 2,
  "departamento_id": 5,
  "datos": {
    "nombre": "Material ejemplo",
    "referencia": "REF-001",
    "stock_minimo": 10,
    "unidad": "ud"
  }
}
```

#### GET /api/entidades/{id}
Obtener entidad específica.

#### PUT /api/entidades/{id}
Actualizar entidad.

#### DELETE /api/entidades/{id}
Eliminar entidad (solo admin).

#### POST /api/entidades/{id}/upload-photo
Subir foto de entidad.

**Request:** `multipart/form-data`
- `photo`: Archivo de imagen

#### POST /api/entidades/{id}/upload-foto-material
Subir foto de material (alternativa).

#### GET /api/entidades/{id}/historial-stock
Historial de stock de una entidad.

**Query Parameters:**
- `almacen_ids[]` (opcional)

#### POST /api/entidades/{id}/regularizar-stock
Regularizar stock de una entidad.

**Request:**
```json
{
  "nuevo_stock": 50,
  "almacen_id": 1,
  "observaciones": "Inventario físico"
}
```

#### PATCH /api/entidades/{id}/ubicacion
Actualizar ubicación de entidad.

### Movimientos

#### GET /api/material-movimientos
Listar movimientos.

**Query Parameters:**
- `tipo` (opcional): `entrada` o `salida`
- `estado` (opcional)
- `almacen_ids[]` (opcional)
- `fecha_desde` (opcional)
- `fecha_hasta` (opcional)

#### GET /api/material-movimientos/inventario
Obtener inventario actual.

#### POST /api/material-movimientos
Crear nuevo movimiento.

**Request:**
```json
{
  "tipo": "salida",
  "fecha_movimiento": "2025-01-15",
  "justificante_id": 1,
  "origen_sede_id": 1,
  "origen_departamento_id": 5,
  "destino_sede_id": 2,
  "destino_departamento_id": 8,
  "observaciones": "Salida de material",
  "detalles": [
    {
      "entidad_id": 1,
      "cantidad": 10,
      "unidad": "ud"
    }
  ]
}
```

#### GET /api/material-movimientos/{id}
Obtener movimiento específico.

#### PUT /api/material-movimientos/{id}
Actualizar movimiento.

#### DELETE /api/material-movimientos/{id}
Eliminar movimiento (solo admin).

#### POST /api/material-movimientos/{id}/generar-enlace
Generar enlace público para firma.

#### POST /api/material-movimientos/{id}/firmar-emisor
Firmar como emisor.

**Request:**
```json
{
  "nombre": "Juan",
  "apellidos": "Pérez",
  "dni": "12345678A",
  "firma": "data:image/png;base64,..."
}
```

#### GET /api/material-movimientos/{id}/pdf
Descargar PDF del albarán.

#### POST /api/material-movimientos/{id}/marcar-entregado
Marcar movimiento como entregado.

#### GET /api/material-movimientos/{id}/historial-auditoria
Obtener historial de auditoría del movimiento.

#### DELETE /api/material-movimientos/{movimiento}/firmas/{firma}
Anular una firma específica.

### Peticiones

#### GET /api/peticiones
Listar peticiones (filtrado por almacén).

**Query Parameters:**
- `estado` (opcional)
- `almacen_ids[]` (opcional)

#### POST /api/peticiones/{id}/aprobar
Aprobar petición.

**Request:**
```json
{
  "cantidades_aprobadas": {
    "1": 10,
    "2": 5
  },
  "observaciones": "Aprobado parcialmente"
}
```

#### POST /api/peticiones/{id}/denegar
Denegar petición.

**Request:**
```json
{
  "motivo": "Stock insuficiente"
}
```

#### GET /api/peticiones/{id}/historial
Obtener historial de petición.

#### GET /api/peticiones/{id}/historial-auditoria
Obtener historial de auditoría.

#### DELETE /api/peticiones/{id}
Eliminar petición (solo admin).

### Notificaciones

#### POST /api/notifications/subscribe
Suscribirse a notificaciones push.

**Request:**
```json
{
  "endpoint": "https://...",
  "keys": {
    "p256dh": "...",
    "auth": "..."
  }
}
```

#### POST /api/notifications/unsubscribe
Desuscribirse de notificaciones push.

#### GET /api/notifications/vapid-public-key
Obtener clave pública VAPID.

#### GET /api/notificaciones
Listar notificaciones del usuario.

#### POST /api/notificaciones/{id}/marcar-leida
Marcar notificación como leída.

#### POST /api/notificaciones/marcar-todas-leidas
Marcar todas las notificaciones como leídas.

#### GET /api/notificaciones/conteo
Obtener conteo de notificaciones no leídas.

### Configuración (Solo Admin)

#### Usuarios

- `GET /api/usuarios` - Listar usuarios
- `GET /api/usuarios/{id}` - Obtener usuario
- `POST /api/usuarios` - Crear usuario
- `PUT /api/usuarios/{id}` - Actualizar usuario
- `DELETE /api/usuarios/{id}` - Eliminar usuario

#### Almacenes por Usuario

- `GET /api/usuarios/{userId}/almacenes` - Almacenes de usuario
- `POST /api/usuarios/{userId}/almacenes` - Asignar almacén

#### Categorías

- `GET /api/config/categorias` - Listar (todos)
- `POST /api/config/categorias` - Crear (admin)
- `PUT /api/config/categorias/{id}` - Actualizar (admin)
- `DELETE /api/config/categorias/{id}` - Eliminar (admin)
- `POST /api/config/categorias/{id}/upload-imagen` - Subir imagen

#### Provincias

- `GET /api/config/provincias` - Listar
- `POST /api/config/provincias` - Crear
- `PUT /api/config/provincias/{id}` - Actualizar
- `DELETE /api/config/provincias/{id}` - Eliminar

#### Sedes y Departamentos

- `GET /api/config/sedes` - Listar sedes
- `POST /api/config/sedes` - Crear sede
- `PUT /api/config/sedes/{id}` - Actualizar sede
- `DELETE /api/config/sedes/{id}` - Eliminar sede
- `POST /api/config/sedes/{sedeId}/departamentos` - Crear departamento
- `PUT /api/config/departamentos/{id}` - Actualizar departamento
- `PATCH /api/config/departamentos/{id}/almacen` - Marcar como almacén
- `DELETE /api/config/departamentos/{id}` - Eliminar departamento

#### Justificantes

- `GET /api/config/justificantes` - Listar (todos)
- `POST /api/config/justificantes` - Crear (admin)
- `PUT /api/config/justificantes/{id}` - Actualizar (admin)
- `DELETE /api/config/justificantes/{id}` - Eliminar (admin)

#### SMTP

- `GET /api/config/smtp` - Obtener configuración SMTP
- `POST /api/config/smtp` - Guardar configuración SMTP
- `POST /api/config/smtp/test` - Probar conexión SMTP
- `DELETE /api/config/smtp/{id}` - Eliminar configuración

#### Backups

- `GET /api/config/backup/crear` - Crear backup
- `POST /api/config/backup/restaurar` - Restaurar backup
- `POST /api/config/backup/reset-sistema` - Resetear sistema (¡CUIDADO!)

### Solicitudes de Reposición

#### GET /api/solicitudes-reposicion
Listar solicitudes (filtrado por almacén).

#### GET /api/solicitudes-reposicion/{id}
Obtener solicitud específica.

#### POST /api/solicitudes-reposicion
Crear solicitud (admin).

#### PUT /api/solicitudes-reposicion/{id}
Actualizar solicitud (admin).

#### DELETE /api/solicitudes-reposicion/{id}
Eliminar solicitud (admin).

## Códigos de Estado HTTP

- `200` - OK
- `201` - Creado
- `400` - Bad Request (validación fallida)
- `401` - Unauthorized (token inválido o ausente)
- `403` - Forbidden (sin permisos)
- `404` - Not Found
- `422` - Unprocessable Entity (error de validación)
- `500` - Internal Server Error

## Rate Limiting

Los endpoints de autenticación tienen rate limiting configurado:
- `/api/login`: 5 intentos por minuto por IP

## Ejemplos con cURL

### Login
```bash
curl -X POST https://dominio.com/gestionmaterial/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@example.com",
    "password": "contraseña"
  }'
```

### Obtener Entidades
```bash
curl -X GET https://dominio.com/gestionmaterial/api/entidades \
  -H "Authorization: Bearer {token}"
```

### Crear Movimiento
```bash
curl -X POST https://dominio.com/gestionmaterial/api/material-movimientos \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "tipo": "salida",
    "fecha_movimiento": "2025-01-15",
    "justificante_id": 1,
    "detalles": [...]
  }'
```
