# Corrección de errores 404 y funciones no definidas

## Problemas corregidos

### 1. Error 404 en rutas de API
**Problema:** Las URLs usaban `../api/` causando rutas incorrectas
**Solución:** Cambiadas todas las referencias a `./api/`

#### Cambios en inventario.php:
```javascript
// ANTES
fetch('../api/inventario.php')
fetch('../api/productos.php')

// DESPUÉS
fetch('./api/inventario.php')
fetch('./api/productos.php')
```

**Total de cambios:** 5 URLs corregidas

### 2. Función showError no definida
**Problema:** El código llamaba a `showError()` y `showSuccess()` pero solo existían `showErrorAlert()` y `showSuccessAlert()`

**Solución:** Agregadas funciones alias en `sweetalert-helpers.js`

#### Funciones añadidas:
```javascript
function showError(message)
function showSuccess(message)
function showWarning(message)
function showInfo(message)
```

### 3. Archivo dashboard-scripts.js inexistente
**Problema:** Se incluía `dashboard-scripts.js` pero el archivo correcto es `dashboard-functions.js`

**Solución:** Corregida la línea 556 de inventario.php
```php
// ANTES
<?php include __DIR__ . '/../includes/dashboard-scripts.js'; ?>

// DESPUÉS
<?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
```

## Archivos modificados

1. **pages/inventario.php**
   - ✅ Corregidas 5 URLs de API (de `../api/` a `./api/`)
   - ✅ Corregido include de dashboard-functions.js

2. **includes/sweetalert-helpers.js**
   - ✅ Agregadas funciones alias: showError, showSuccess, showWarning, showInfo

## Estructura de rutas corregida

Cuando estás en: `/stadium/pages/inventario.php`

### ❌ ANTES (incorrecto):
```
../api/inventario.php → /stadium/api/inventario.php (404 porque se sale de la carpeta actual)
```

### ✅ AHORA (correcto):
```
./api/inventario.php → /stadium/pages/api/inventario.php
```

Pero espera... esto todavía está mal. Las páginas están en `/stadium/pages/` y los APIs en `/stadium/api/`. 

Necesitamos usar la ruta absoluta desde la raíz del servidor.

## Corrección adicional necesaria

Las rutas correctas deberían ser:
```javascript
fetch('/stadium/api/inventario.php')  // Ruta absoluta desde raíz
// O mejor aún, usar una variable base
const API_BASE = '/stadium/api';
fetch(`${API_BASE}/inventario.php`)
```

Déjame corregir esto ahora...
