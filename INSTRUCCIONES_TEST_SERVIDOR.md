# 🔍 Scripts de Prueba e Integridad para Servidor Remoto

## Scripts Creados

Se han creado 2 scripts de diagnóstico para identificar problemas en el servidor remoto:

---

## 1️⃣ Test Completo con Interfaz Web

### Archivo: `test_inventario_server.php`

**Descripción:** Script completo con interfaz HTML que muestra todos los tests de forma visual.

### 📍 Cómo usar:

1. **Subir el archivo** al servidor remoto en la carpeta `/stadium/`
2. **Acceder desde el navegador:**
   ```
   https://tu-dominio.com/stadium/test_inventario_server.php
   ```
3. **Revisar los resultados** en la interfaz visual

### ✅ Qué verifica:

- ✔️ **Archivos del sistema** (database.php, auth.php, API, páginas, includes)
- ✔️ **Conexión a base de datos** (host, charset, base de datos activa)
- ✔️ **Estructura de BD** (tablas: productos, producto_variantes, variante_atributos, marcas)
- ✔️ **Datos en inventario** (productos con variantes, atributos, stock, precios)
- ✔️ **Permisos de archivos** (legibilidad de archivos críticos)
- ✔️ **Extensiones PHP** (mysqli, json, session)
- ✔️ **Sistema de sesión** (sesión activa, usuario autenticado)
- ✔️ **Variables de entorno** (document root, paths, server software)

### 📊 Salida:

Interfaz web con:
- ✅ Tests exitosos en **verde**
- ⚠️ Advertencias en **amarillo**
- ❌ Errores críticos en **rojo**
- 📋 Tablas con datos de ejemplo
- 💡 Recomendaciones para solucionar problemas

---

## 2️⃣ Test de API en JSON

### Archivo: `test_api_inventario.php`

**Descripción:** Script que devuelve resultados en formato JSON, útil para debugging rápido o integración con otras herramientas.

### 📍 Cómo usar:

1. **Subir el archivo** al servidor remoto en la carpeta `/stadium/`
2. **Acceder desde el navegador o curl:**
   ```bash
   # Navegador
   https://tu-dominio.com/stadium/test_api_inventario.php
   
   # cURL
   curl https://tu-dominio.com/stadium/test_api_inventario.php
   ```
3. **Analizar el JSON** devuelto

### ✅ Qué verifica:

- ✔️ Archivo `config/database.php` existe y se carga
- ✔️ Conexión a base de datos funcional
- ✔️ Tablas requeridas existen (productos, producto_variantes, variante_atributos, marcas)
- ✔️ Conteo de registros en cada tabla
- ✔️ **Consulta real de inventario** (ejecuta el SELECT completo)
- ✔️ Muestra **muestra de 5 variantes** con atributos
- ✔️ Archivos API y páginas existen y son legibles
- ✔️ Extensiones PHP necesarias cargadas
- ✔️ Estado de sesión

### 📊 Salida JSON:

```json
{
  "success": true,
  "message": "✅ Todos los tests pasaron correctamente",
  "tests": {
    "database_connection": true,
    "inventory_query": true,
    "tables": {
      "productos": true,
      "productos_count": 10,
      "producto_variantes": true,
      "producto_variantes_count": 25
    }
  },
  "inventory_sample": [...],
  "errors": [],
  "warnings": [],
  "recommendations": [...]
}
```

---

## 🚀 Procedimiento Recomendado

### Paso 1: Subir Scripts
```bash
# Subir ambos scripts al servidor en /stadium/
scp test_inventario_server.php usuario@servidor:/ruta/stadium/
scp test_api_inventario.php usuario@servidor:/ruta/stadium/
```

O usar **FileZilla/FTP** para subir los archivos.

---

### Paso 2: Ejecutar Test Visual

1. Abre el navegador
2. Accede a: `https://tu-dominio.com/stadium/test_inventario_server.php`
3. Revisa cada sección:
   - 📁 **Archivos del Sistema** → Verifica que no falten archivos
   - 🔌 **Conexión a BD** → Verifica credenciales y acceso
   - 🗄️ **Estructura de BD** → Verifica que existan todas las tablas
   - 📦 **Datos en Inventario** → Verifica que haya productos y variantes
   - 🔒 **Permisos** → Verifica que los archivos sean legibles

---

### Paso 3: Ejecutar Test JSON (Opcional)

```bash
curl https://tu-dominio.com/stadium/test_api_inventario.php | jq .
```

O simplemente abre en el navegador y verás el JSON formateado.

---

## 🔍 Interpretación de Resultados

### ✅ Caso 1: Todo Verde (Sin Errores)

```
✅ Todos los tests pasaron correctamente
```

**Acción:** El sistema debería funcionar. Si aún da error:
1. Hacer login en el sistema
2. Acceder a `/stadium/pages/inventario`
3. Si sigue fallando, revisar **logs del servidor** (Apache/Nginx)

---

### ⚠️ Caso 2: Advertencias (Amarillo)

```
⚠️ Sistema funcional pero con advertencias
```

**Causas comunes:**
- No hay datos en las tablas (inventario vacío)
- Usuario no autenticado
- Faltan archivos opcionales (includes)

**Acción:** Revisar la sección "Advertencias" y seguir recomendaciones.

---

### ❌ Caso 3: Errores Críticos (Rojo)

```
❌ Se encontraron errores críticos
```

**Causas comunes:**
- `config/database.php` no existe o tiene errores
- Credenciales de BD incorrectas
- Tablas no existen en la base de datos
- Permisos de archivos incorrectos (no legibles)
- Extensiones PHP no cargadas

**Acción:** 
1. Revisar la sección "Errores Críticos"
2. Corregir cada error listado
3. Volver a ejecutar el test

---

## 🐛 Errores Comunes y Soluciones

### Error: "Archivo config/database.php no encontrado"

**Causa:** Archivo no existe o está en otra ubicación

**Solución:**
```bash
# Verificar que exista
ls -la /ruta/stadium/config/database.php

# Si no existe, subirlo desde local
scp config/database.php usuario@servidor:/ruta/stadium/config/
```

---

### Error: "Error de conexión MySQL"

**Causa:** Credenciales incorrectas o servidor MySQL inaccesible

**Solución:**
1. Editar `config/database.php`
2. Verificar: host, usuario, contraseña, nombre de BD
3. Probar conexión manual:
   ```bash
   mysql -h HOST -u USUARIO -p BASE_DE_DATOS
   ```

---

### Error: "Tabla 'producto_variantes' no existe"

**Causa:** La base de datos no tiene las tablas necesarias

**Solución:**
```bash
# Importar estructura de BD
mysql -u usuario -p stadium < database/stadium_structure.sql

# O ejecutar manualmente los CREATE TABLE
```

---

### Error: "Archivo api/inventario.php no es legible"

**Causa:** Permisos incorrectos

**Solución:**
```bash
# Cambiar permisos
chmod 644 /ruta/stadium/api/inventario.php

# O recursivo para toda la carpeta
chmod -R 755 /ruta/stadium
```

---

### Warning: "No hay variantes activas en el inventario"

**Causa:** Las tablas existen pero están vacías

**Solución:**
```bash
# Insertar datos de ejemplo
mysql -u usuario -p stadium < database/insertar_atributos_ejemplo.sql
```

---

### Warning: "Usuario NO autenticado"

**Causa:** No has hecho login en el sistema

**Solución:**
1. Accede a `/stadium/pages/login` (o la página de login que uses)
2. Inicia sesión
3. Vuelve a acceder a inventario

---

## 📋 Checklist de Verificación

Usa esta lista para verificar cada aspecto:

- [ ] ✅ Script `test_inventario_server.php` subido al servidor
- [ ] ✅ Script `test_api_inventario.php` subido al servidor
- [ ] ✅ Test visual ejecutado y revisado
- [ ] ✅ Archivo `config/database.php` existe
- [ ] ✅ Conexión a BD exitosa
- [ ] ✅ Todas las tablas existen (productos, producto_variantes, etc.)
- [ ] ✅ Hay datos en las tablas
- [ ] ✅ Archivos tienen permisos correctos
- [ ] ✅ Extensiones PHP cargadas (mysqli, json, session)
- [ ] ✅ Usuario puede hacer login
- [ ] ✅ Inventario accesible en `/stadium/pages/inventario`

---

## 📞 Qué Hacer con los Resultados

### 1. Captura de pantalla
Toma screenshot de los resultados del test visual

### 2. Copia el JSON
Si usas el test JSON, copia el output completo

### 3. Revisa logs del servidor
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP
tail -f /var/log/php/error.log
```

### 4. Comparte la información
Si necesitas ayuda, comparte:
- Screenshot del test visual
- JSON completo del test API
- Últimas 20 líneas de error.log del servidor
- Mensaje de error específico que aparece al acceder a inventario

---

## 🗑️ Eliminar Scripts Después

**Una vez solucionado el problema**, puedes eliminar estos scripts del servidor:

```bash
rm /ruta/stadium/test_inventario_server.php
rm /ruta/stadium/test_api_inventario.php
```

**Razón:** Exponen información sensible sobre la estructura de tu sistema.

---

## 📝 Registro de Ejecución

Guarda un registro de cada ejecución:

| Fecha | Hora | Errores | Advertencias | Estado | Notas |
|-------|------|---------|--------------|--------|-------|
| 2025-10-14 | 10:30 | 2 | 1 | ❌ | Tabla producto_variantes no existe |
| 2025-10-14 | 11:00 | 0 | 1 | ⚠️ | Inventario vacío |
| 2025-10-14 | 11:30 | 0 | 0 | ✅ | Sistema funcionando |

---

## 🎯 Objetivo Final

**Resultado esperado:** 
```
✅ Todos los tests pasaron correctamente
✅ Sistema de inventario accesible
✅ Variantes se muestran con atributos
✅ Stock y precios visibles
```

**Confirmar funcionamiento:**
1. Login exitoso
2. Acceder a `/stadium/pages/inventario`
3. Ver lista de variantes con atributos
4. Poder crear/editar variantes
5. Sin errores en consola del navegador

---

## 📚 Archivos Relacionados

- `test_inventario_server.php` - Test visual completo
- `test_api_inventario.php` - Test JSON rápido
- `test_inventario_pedidos.sql` - Queries SQL de verificación
- `PEDIDOS_INVENTARIO.md` - Documentación de integración
- `RESUMEN_PEDIDOS_INVENTARIO.md` - Resumen ejecutivo

---

**¡Buena suerte con el debugging!** 🚀
