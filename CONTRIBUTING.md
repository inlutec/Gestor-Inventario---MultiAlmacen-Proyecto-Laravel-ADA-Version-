# Guía de Contribución

Gracias por tu interés en contribuir al proyecto. Esta guía te ayudará a entender cómo contribuir de manera efectiva.

## Cómo Contribuir

### Reportar Bugs

Si encuentras un bug, por favor:

1. Verifica que no haya sido reportado ya en los issues
2. Crea un nuevo issue con:
   - Descripción clara del problema
   - Pasos para reproducir
   - Comportamiento esperado vs actual
   - Versión del sistema y entorno
   - Logs relevantes (si aplica)

### Sugerir Funcionalidades

Para sugerir nuevas funcionalidades:

1. Verifica que no haya sido sugerida ya
2. Crea un nuevo issue con:
   - Descripción detallada de la funcionalidad
   - Casos de uso
   - Beneficios esperados

### Contribuir con Código

#### Proceso

1. **Fork** el repositorio
2. **Crea una rama** para tu feature/fix:
   ```bash
   git checkout -b feature/nueva-funcionalidad
   # o
   git checkout -b fix/correccion-bug
   ```
3. **Haz tus cambios** siguiendo las convenciones del proyecto
4. **Prueba** tus cambios exhaustivamente
5. **Commit** tus cambios con mensajes descriptivos:
   ```bash
   git commit -m "feat: añadir nueva funcionalidad X"
   # o
   git commit -m "fix: corregir bug en Y"
   ```
6. **Push** a tu fork:
   ```bash
   git push origin feature/nueva-funcionalidad
   ```
7. **Abre un Pull Request** con descripción detallada

#### Convenciones de Código

##### PHP (Laravel)
- Seguir PSR-12
- Usar nombres descriptivos en español para el dominio de negocio
- Documentar funciones complejas
- Usar type hints donde sea posible
- Seguir convenciones de Laravel

##### JavaScript/Vue.js
- Seguir estándares de Vue.js 3
- Usar Composition API
- Nombres descriptivos
- Comentar código complejo

##### Commits
Usar formato Conventional Commits:
- `feat:` Nueva funcionalidad
- `fix:` Corrección de bug
- `docs:` Cambios en documentación
- `style:` Cambios de formato (no afectan código)
- `refactor:` Refactorización de código
- `test:` Añadir o modificar tests
- `chore:` Cambios en build, dependencias, etc.

Ejemplos:
```
feat: añadir sistema de notificaciones push
fix: corregir error en cálculo de stock
docs: actualizar guía de instalación
```

#### Testing

Antes de hacer commit:
- Probar funcionalidad manualmente
- Verificar que no haya errores en consola
- Verificar que los assets se compilen correctamente
- Verificar permisos y configuración

#### Estructura de Pull Request

Un buen PR incluye:
- Descripción clara de los cambios
- Referencia a issues relacionados (si aplica)
- Capturas de pantalla (si es cambio de UI)
- Lista de verificación de pruebas realizadas

## Estándares de Código

### PHP
- PSR-12 coding standard
- Type hints en métodos
- Docblocks en métodos públicos
- Nombres en español para dominio de negocio

### JavaScript/Vue.js
- ESLint configurado
- Composition API preferido
- Nombres descriptivos
- Comentarios donde sea necesario

## Preguntas

Si tienes preguntas sobre cómo contribuir:
1. Revisa la documentación en `docs/`
2. Busca en issues existentes
3. Abre un nuevo issue con tu pregunta

## Agradecimientos

¡Gracias por contribuir al proyecto!
