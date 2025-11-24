-- Script SQL para crear usuario administrador inicial
-- Ejecutar después de las migraciones

USE impresoras;

-- Crear usuario admin por defecto
-- Contraseña: Admin2025! (cambiar después del primer login)
INSERT INTO usuarios (nombre, apellido, email, password, rol, activo, created_at, updated_at)
VALUES (
    'Administrador',
    'Sistema',
    'admin@juntadeandalucia.es',
    '$2y$12$LQv3c1yYqBWh0nrPsWL.mOC7xHOW5wqwvQJWXZ5KnP0Ky5K5gKqWS', -- Admin2025!
    'admin',
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE id=id;

-- Crear tipos de entidad básicos
INSERT INTO tipos_entidad (id, nombre, clave, icono, color, orden) VALUES
(1, 'Impresoras', 'impresora', 'printer', '#006A4E', 1),
(2, 'Consumibles', 'consumible', 'box', '#F3B20A', 2)
ON DUPLICATE KEY UPDATE id=id;

-- Crear campos para impresoras
INSERT INTO campos (tipo_entidad_id, nombre, clave, tipo_dato, opciones, obligatorio, mostrar_en_tabla, orden) VALUES
(1, 'Referencia', 'referencia', 'text', NULL, 1, 1, 1),
(1, 'IP', 'ip', 'text', NULL, 0, 1, 2),
(1, 'Marca', 'marca', 'text', NULL, 1, 1, 3),
(1, 'Modelo', 'modelo', 'text', NULL, 1, 1, 4),
(1, 'Sede', 'sede', 'select', '["Constitucion", "Cultura", "Deportes", "Igualdad", "Biblioteca"]', 1, 1, 5),
(1, 'Número de serie', 'numero_serie', 'text', NULL, 1, 1, 6),
(1, 'Ubicación', 'ubicacion', 'text', NULL, 0, 1, 7),
(1, 'Departamento', 'departamento', 'select', '["Informática", "Administración", "Dirección", "Recursos Humanos", "Mantenimiento", "Otro"]', 0, 1, 8),
(1, 'Notas', 'notas', 'textarea', NULL, 0, 0, 9)
ON DUPLICATE KEY UPDATE id=id;

-- Crear campos para consumibles
INSERT INTO campos (tipo_entidad_id, nombre, clave, tipo_dato, opciones, obligatorio, mostrar_en_tabla, orden) VALUES
(2, 'Referencia', 'referencia', 'text', NULL, 1, 1, 1),
(2, 'Tipo', 'tipo', 'select', '["Tóner", "Tinta", "Papel", "Otro"]', 1, 1, 2),
(2, 'Marca', 'marca', 'text', NULL, 1, 1, 3),
(2, 'Modelo', 'modelo', 'text', NULL, 1, 1, 4),
(2, 'Color', 'color', 'select', '["Negro", "Amarillo", "Cian", "Magenta"]', 0, 1, 5),
(2, 'Stock actual', 'stock_actual', 'number', NULL, 1, 1, 6),
(2, 'Stock mínimo', 'stock_minimo', 'number', NULL, 0, 1, 7),
(2, 'Ubicación', 'ubicacion', 'text', NULL, 0, 1, 8),
(2, 'Notas', 'notas', 'textarea', NULL, 0, 0, 9)
ON DUPLICATE KEY UPDATE id=id;

-- Mostrar información
SELECT 'Base de datos inicializada correctamente' as Resultado;
SELECT '===========================================' as '';
SELECT 'Usuario administrador creado:' as '';
SELECT 'Email: admin@juntadeandalucia.es' as '';
SELECT 'Contraseña: Admin2025!' as '';
SELECT '¡IMPORTANTE: Cambiar la contraseña después del primer login!' as '';
