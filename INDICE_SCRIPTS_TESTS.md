# 📚 ÍNDICE COMPLETO - Scripts y Documentación

## 🎯 SCRIPTS DE PRUEBA PARA SERVIDOR REMOTO

### 🔧 Scripts PHP de Diagnóstico

| Archivo | Tamaño | Prioridad | Descripción |
|---------|--------|-----------|-------------|
| **debug_simple.php** | 4.6KB | ⭐⭐⭐ | **USAR PRIMERO** - Debug rápido en texto plano |
| **test_inventario_server.php** | 20KB | ⭐⭐⭐ | Test completo con interfaz HTML visual |
| **test_api_inventario.php** | 9.0KB | ⭐⭐ | Test rápido con salida JSON |

### 📄 SQL de Verificación

| Archivo | Tamaño | Descripción |
|---------|--------|-------------|
| **test_inventario_pedidos.sql** | 2.2KB | Queries SQL para verificar datos en BD |

---

## 📖 DOCUMENTACIÓN

### 📘 Guías de Uso (Para el Usuario)

| Archivo | Páginas | Contenido |
|---------|---------|-----------|
| **GUIA_RAPIDA_TESTS.md** | ~15 | Guía rápida de uso de scripts de prueba |
| **INSTRUCCIONES_TEST_SERVIDOR.md** | ~18 | Instrucciones detalladas paso a paso |

### 📗 Documentación Técnica (Para el Desarrollador)

| Archivo | Páginas | Contenido |
|---------|---------|-----------|
| **PEDIDOS_INVENTARIO.md** | ~12 | Documentación técnica completa de integración Pedidos-Inventario |
| **RESUMEN_PEDIDOS_INVENTARIO.md** | ~14 | Resumen ejecutivo con ejemplos y casos de uso |
| **INVENTARIO_ATRIBUTOS_V2.md** | ~8 | Documentación de cambios en manejo de atributos |

---

## 🚀 INICIO RÁPIDO

### Problema: Error al acceder a Inventario en servidor remoto

#### ✅ SOLUCIÓN EN 3 PASOS:

```bash
# 1. SUBIR script al servidor
scp debug_simple.php usuario@servidor:/ruta/www/stadium/

# 2. ACCEDER desde navegador
https://tu-servidor.com/stadium/debug_simple.php

# 3. LEER resultados
# ✅ = OK | ❌ = Error | ⚠️ = Advertencia
```

---

## 📋 MATRIZ DE DECISIÓN

### ¿Qué script usar?

| Situación | Script Recomendado | Por Qué |
|-----------|-------------------|---------|
| 🔴 **Primera vez / Error desconocido** | `debug_simple.php` | Rápido, simple, muestra el problema |
| 🔵 **Reporte detallado** | `test_inventario_server.php` | Visual, profesional, completo |
| 🟢 **Automatización** | `test_api_inventario.php` | JSON parseable |
| 🟡 **Verificar BD manualmente** | `test_inventario_pedidos.sql` | Queries directos en MySQL |

---

## 📊 ESTRUCTURA DE ARCHIVOS EN EL PROYECTO

```
/stadium/
├── 🔧 SCRIPTS DE PRUEBA (Subir al servidor remoto)
│   ├── debug_simple.php              ⭐⭐⭐ PRINCIPAL
│   ├── test_inventario_server.php    ⭐⭐⭐
│   ├── test_api_inventario.php       ⭐⭐
│   └── test_inventario_pedidos.sql   ⭐
│
├── 📖 DOCUMENTACIÓN (Para consulta local)
│   ├── GUIA_RAPIDA_TESTS.md
│   ├── INSTRUCCIONES_TEST_SERVIDOR.md
│   ├── PEDIDOS_INVENTARIO.md
│   ├── RESUMEN_PEDIDOS_INVENTARIO.md
│   └── INVENTARIO_ATRIBUTOS_V2.md
│
├── 🎨 FRONTEND
│   └── pages/
│       ├── inventario.php            (Modificado)
│       └── pedido_form.php           (Modificado)
│
├── 🔌 BACKEND
│   └── api/
│       ├── inventario.php            (Modificado)
│       └── pedidos_det.php           (Modificado)
│
└── 📁 CONFIG
    ├── config/database.php
    └── includes/auth.php
```

---

## 🎯 FLUJO DE DIAGNÓSTICO

```
┌─────────────────────────────────────┐
│  1. SUBIR debug_simple.php          │
└─────────────┬───────────────────────┘
              │
              ▼
┌─────────────────────────────────────┐
│  2. ACCEDER desde navegador         │
│     /stadium/debug_simple.php       │
└─────────────┬───────────────────────┘
              │
              ▼
         ┌────┴────┐
         │ ¿TODO ✅?│
         └────┬────┘
         ┌────┴────┐
         │   Sí    │         No
         ▼         ▼
    ┌────────┐  ┌──────────────────┐
    │ ¡LISTO!│  │ Corregir errores │
    └────────┘  └────────┬─────────┘
                         │
                         ▼
                ┌─────────────────┐
                │ Volver a probar │
                └────────┬────────┘
                         │
                         ▼
                    ┌────────┐
                    │ ¡LISTO!│
                    └────────┘
```

---

## 🔍 ERRORES COMUNES Y SOLUCIONES

### ❌ Error: "config/database.php no encontrado"
```bash
# Verificar ubicación
ls -la /ruta/www/stadium/config/

# Subir si falta
scp config/database.php usuario@servidor:/ruta/www/stadium/config/
```

### ❌ Error: "Conexión a BD fallida"
```php
// Editar config/database.php
define('DB_HOST', 'localhost');      // ← Verificar
define('DB_USER', 'tu_usuario');      // ← Verificar
define('DB_PASS', 'tu_contraseña');   // ← Verificar
define('DB_NAME', 'stadium');         // ← Verificar
```

### ❌ Error: "Tabla no existe"
```bash
# Importar estructura
mysql -u usuario -p stadium < database/stadium_structure.sql
```

### ⚠️ Warning: "No hay variantes"
```bash
# Insertar datos de ejemplo
mysql -u usuario -p stadium < database/insertar_atributos_ejemplo.sql
```

---

## 📞 INFORMACIÓN PARA SOPORTE

Si necesitas ayuda, proporciona:

1. ✅ Output completo de `debug_simple.php`
2. ✅ Screenshot de error en navegador
3. ✅ Últimas 50 líneas de error.log del servidor
4. ✅ Versión de PHP y MySQL

```bash
# Ver últimas líneas del log
tail -n 50 /var/log/apache2/error.log
# o
tail -n 50 /var/log/nginx/error.log
```

---

## 🗑️ LIMPIEZA POST-DIAGNÓSTICO

**⚠️ IMPORTANTE:** Eliminar scripts del servidor después de resolver:

```bash
rm /ruta/www/stadium/debug_simple.php
rm /ruta/www/stadium/test_inventario_server.php
rm /ruta/www/stadium/test_api_inventario.php
```

**Razón:** Exponen información sensible del sistema.

---

## ✨ RESULTADO ESPERADO

Al finalizar el proceso, deberías poder:

✅ Acceder a `/stadium/pages/inventario`
✅ Ver lista de variantes con atributos
✅ Ver stock y precios
✅ Crear/editar variantes
✅ Sin errores en consola

---

## 📚 LEER MÁS

| Tema | Documento |
|------|-----------|
| Uso de scripts de prueba | `GUIA_RAPIDA_TESTS.md` |
| Instrucciones paso a paso | `INSTRUCCIONES_TEST_SERVIDOR.md` |
| Integración Pedidos-Inventario | `PEDIDOS_INVENTARIO.md` |
| Ejemplos y casos de uso | `RESUMEN_PEDIDOS_INVENTARIO.md` |
| Cambios en atributos | `INVENTARIO_ATRIBUTOS_V2.md` |

---

## 🎉 ÉXITO

Si todos los tests pasan:
```
✅ Archivos: OK
✅ Base de datos: OK
✅ Tablas: OK
✅ Query: OK
✅ Sesión: OK
✅ Extensiones: OK
```

**¡Sistema de inventario funcionando correctamente!** 🚀

---

**Fecha de creación:** 14 de octubre de 2025
**Versión:** 1.0
**Autor:** GitHub Copilot
**Proyecto:** Stadium - Sistema de Gestión
