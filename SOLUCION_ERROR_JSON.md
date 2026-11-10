# Solución al error "Unexpected token '<'" en Inventario

## Problema identificado
El error "Unexpected token '<'" ocurre cuando JavaScript intenta parsear HTML como JSON. Esto sucede cuando:
1. No hay sesión activa (usuario no logueado)
2. La sesión expiró
3. El API redirige a la página de login devolviendo HTML

## Causa raíz
Cuando accedes a `/stadium/pages/inventario.php` sin estar logueado:
- La página te redirige a `inicio` (página de login)
- El JavaScript intenta cargar datos del API
- El API también detecta falta de autenticación y devuelve JSON con error 401
- Pero si hay algún redirect o error PHP, puede devolver HTML

## Soluciones implementadas

### 1. Mejora en el manejo de errores del JavaScript (inventario.php)

#### loadInventario()
```javascript
// Verificar Content-Type de la respuesta
const contentType = response.headers.get('content-type');
if (!contentType || !contentType.includes('application/json')) {
    // Detectar si recibimos HTML (redirect)
    if (text.includes('<!DOCTYPE') || text.includes('<html')) {
        showError('Sesión expirada. Por favor, recarga la página.');
        setTimeout(() => {
            window.location.href = 'inicio';
        }, 2000);
        return;
    }
}

// Verificar código de autenticación
if (!data.success && (data.code === 'NOT_AUTHENTICATED' || response.status === 401)) {
    showError('Sesión expirada. Redirigiendo al login...');
    setTimeout(() => {
        window.location.href = 'inicio';
    }, 2000);
    return;
}
```

#### loadProductos()
- Agregado verificación de Content-Type
- Manejo silencioso de errores de autenticación
- No bloquea la página si falla

### 2. Logs detallados en consola
- URL de la petición
- Status de respuesta
- Content-Type
- Primeros 200-500 caracteres de la respuesta
- Errores de parsing JSON

## Archivos de diagnóstico creados

### check_session.php
Verifica el estado de la sesión actual:
```bash
curl http://localhost/stadium/check_session.php
```

Devuelve:
- Estado de la sesión
- Si el usuario está logueado
- Datos del usuario actual
- Si la sesión expiró

## Cómo usar

### 1. Verificar que estás logueado
```bash
# Desde terminal
curl -b cookies.txt http://localhost/stadium/check_session.php

# O abre en navegador:
http://localhost/stadium/check_session.php
```

### 2. Si no estás logueado
1. Abre http://localhost/stadium/pages/inicio
2. Ingresa tus credenciales
3. Luego accede a inventario

### 3. Ver logs en consola del navegador
1. Abre DevTools (F12)
2. Ve a la pestaña Console
3. Verás logs detallados de:
   - URLs llamadas
   - Status codes
   - Errores de parsing
   - Mensajes de autenticación

## Errores comunes y sus soluciones

| Error | Causa | Solución |
|-------|-------|----------|
| `Unexpected token '<'` | Respuesta HTML en lugar de JSON | Verificar que estás logueado |
| `NOT_AUTHENTICATED` | Sesión no iniciada | Hacer login |
| `Session expired` | Sesión expirada (2 horas) | Volver a hacer login |
| `Content-Type no es JSON` | Error PHP o redirect | Ver logs PHP y verificar auth |

## Verificación

Después de loguearte correctamente, deberías ver en consola:
```
Cargando inventario desde: ../api/inventario.php
Response status: 200
Response headers: application/json
Response text: {"success":true,"inventario":[...]}
```

Y la tabla mostrará los 8 registros de inventario.
