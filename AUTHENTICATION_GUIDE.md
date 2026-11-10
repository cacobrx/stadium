# Sistema de Autenticación - Stadium

## 📋 **Resumen**

El sistema Stadium ahora cuenta con un sistema completo de autenticación basado en sesiones PHP que:

- ✅ **Verifica credenciales** contra la base de datos
- ✅ **Mantiene estado de sesión** durante la navegación
- ✅ **Protege páginas y APIs** automáticamente
- ✅ **Maneja expiración de sesión** (2 horas por defecto)
- ✅ **Permite logout seguro** con destrucción de sesión

## 🔧 **Archivos Modificados/Creados**

### Nuevos Archivos:
- `includes/auth.php` - Sistema de autenticación completo
- `pages/logout.php` - Página de logout
- `debug_session.php` - Herramienta de debugging (remover en producción)

### Archivos Modificados:
- `api/login.php` - Ahora inicia sesiones PHP
- `api/usuarios.php` - Protegido con verificación de sesión
- `pages/dashboard.php` - Protegido + muestra nombre de usuario
- `pages/usuarios.php` - Protegido + muestra nombre de usuario
- `includes/dashboard-functions.js` - Logout redirige a página logout

## 🛡️ **Cómo Funciona**

### 1. **Login (api/login.php)**
```php
// Al validar credenciales exitosamente:
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_nombre'] = $user['nombre'];
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();
```

### 2. **Protección de Páginas**
```php
// Al inicio de cada página protegida:
require_once __DIR__ . '/../includes/auth.php';
requireAuth(); // Redirige al login si no está autenticado
```

### 3. **Protección de APIs**
```php
// Al inicio de cada API protegida:
require_once __DIR__ . '/../includes/auth.php';
requireAuthAPI(); // Retorna JSON error si no está autenticado
```

### 4. **Logout**
```php
// Destruye la sesión y redirige:
logout(); // en pages/logout.php
```

## 🔑 **Funciones Disponibles**

### Verificación de Estado:
- `isLoggedIn()` - ¿Usuario autenticado?
- `isSessionExpired()` - ¿Sesión expirada?
- `getCurrentUser()` - Datos del usuario actual

### Control de Acceso:
- `requireAuth()` - Requiere autenticación (páginas web)
- `requireAuthAPI()` - Requiere autenticación (APIs JSON)
- `logout()` - Cerrar sesión

### Información:
- `getSessionInfo()` - Información completa de sesión (debugging)

## 🚦 **Flujo de Autenticación**

1. **Usuario accede a página protegida sin login**
   → Redirigido automáticamente a `/inicio`

2. **Usuario se loguea correctamente**
   → Sesión iniciada → Redirigido a `/dashboard`

3. **Usuario navega por el sistema**
   → Sesión verificada en cada página/API

4. **Usuario hace logout**
   → Sesión destruida → Redirigido a `/inicio`

5. **Sesión expira (2 horas)**
   → Próximo acceso redirige al login

## 🧪 **Testing del Sistema**

### Verificar Estado de Sesión:
Visita: `http://localhost/stadium/debug_session`

### Probar Flujo Completo:
1. Ir a `http://localhost/stadium/dashboard` (debe redirigir al login)
2. Loguearse en `http://localhost/stadium/inicio`
3. Debería redirigir al dashboard automáticamente
4. Navegar a `http://localhost/stadium/usuarios` (debe funcionar)
5. Hacer logout → debe volver al login

### Probar APIs:
```bash
# Sin sesión - debe retornar error 401
curl http://localhost/stadium/api/usuarios.php

# Con sesión - debe funcionar
# (después de loguearse desde el navegador)
curl -b cookies.txt http://localhost/stadium/api/usuarios.php
```

## ⚙️ **Configuración**

### Timeout de Sesión:
```php
// En includes/auth.php, modificar:
function isSessionExpired($timeout = 7200) // 2 horas
```

### Página de Redirección:
```php
// Cambiar página por defecto del login:
function requireAuth($redirectTo = 'inicio')
```

## 🔒 **Datos de Sesión Almacenados**

```php
$_SESSION = [
    'user_id' => 1,
    'user_email' => 'usuario@email.com',
    'user_nombre' => 'Nombre Usuario',
    'logged_in' => true,
    'login_time' => 1696723200,
    'last_activity' => 1696723500
];
```

## 🚨 **Seguridad**

- ✅ Contraseñas hasheadas con MD5 (considerar upgrade a bcrypt)
- ✅ Sesiones PHP nativas
- ✅ Verificación automática de expiración
- ✅ Limpieza de sesiones en logout
- ✅ Protección tanto de páginas como APIs
- ✅ Validación de entrada en APIs

## 📝 **Próximos Pasos Recomendados**

1. **Upgrade de Hash**: Cambiar MD5 a `password_hash()` y `password_verify()`
2. **CSRF Protection**: Agregar tokens CSRF a formularios
3. **Rate Limiting**: Limitar intentos de login
4. **Remember Me**: Funcionalidad de "recordar usuario"
5. **Roles y Permisos**: Sistema de roles más granular

## 🎯 **Estado Actual**

✅ **Sistema Completamente Funcional**
- Login con persistencia de sesión
- Páginas protegidas automáticamente
- APIs seguras
- Logout completo
- Manejo de expiración

¡El sistema de autenticación está listo para usar! 🚀