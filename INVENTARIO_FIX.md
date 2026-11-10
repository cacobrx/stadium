# Correcciones aplicadas al CRUD de Inventario

## Problema identificado
La página de inventario no mostraba datos debido a dos problemas:

### 1. Falta de inicialización de conexión a BD
**Archivo:** `api/inventario.php`
**Problema:** No se inicializaba la variable `$mysqli`
**Solución:** Agregado `$mysqli = get_database_connection();` después de los requires

### 2. Uso incorrecto de función de autenticación
**Archivo:** `api/inventario.php`
**Problema:** Se usaba `requireAuth()` que hace redirect HTML en lugar de devolver JSON
**Solución:** Cambiado a `requireAuthAPI()` que devuelve respuesta JSON apropiada para APIs

## Cambios realizados

### api/inventario.php (líneas 1-13)
```php
<?php
// API para gestión de inventario de variantes de productos
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticación para API (devuelve JSON)  <- CAMBIADO
requireAuthAPI();  <- CAMBIADO de requireAuth()

// Obtener conexión a la base de datos  <- AGREGADO
$mysqli = get_database_connection();  <- AGREGADO

$method = $_SERVER['REQUEST_METHOD'];
```

### pages/inventario.php (función loadInventario)
**Mejora:** Agregado logging detallado en consola para debugging:
- Log de URL de petición
- Log de status de respuesta
- Log de texto de respuesta
- Manejo de errores de parsing JSON más detallado

## Archivos de testing creados
1. `api/test_inventario.php` - Muestra estructura de tabla producto_variantes
2. `api/test_inventario_simple.php` - Test de consulta sin autenticación
3. `test_inventario_debug.html` - Interfaz web para testing de APIs

## Verificación
La consulta SQL funciona correctamente y devuelve 8 registros:
- 4 variantes del producto Nike Air Max 90 (talles 40-43)
- 4 variantes del producto Adidas Ultraboost 22 (talles 39-42)

## Estado
✅ API corregida
✅ Conexión a BD inicializada correctamente
✅ Autenticación configurada para APIs
✅ Debugging mejorado en frontend
