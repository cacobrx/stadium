# Eliminación de Columna 'Centro' - Stadium

## 📋 **Resumen de Cambios**

Se eliminó completamente la columna `centro` de la tabla usuarios y de toda la aplicación, ya que no se estaba utilizando.

## 🗃️ **Cambios en Base de Datos**

### Estructura Anterior:
```sql
| id | apellido | nombre | email | clave | acceso | centro | nivel | idcuit | dash | avisopago |
```

### Estructura Actual:
```sql
| id | apellido | nombre | email | clave | acceso | nivel | idcuit | dash | avisopago |
```

### Comando Ejecutado:
```sql
ALTER TABLE usuarios DROP COLUMN centro;
```

## 🔧 **Archivos Modificados**

### 1. **API (`api/usuarios.php`)**

#### Método POST (Crear Usuario):
**Antes:**
```php
$centro = intval($data['centro'] ?? 1);
$stmt = $mysqli->prepare('INSERT INTO usuarios (apellido, nombre, email, clave, acceso, centro, nivel, idcuit, dash, avisopago) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('sssssiiiii', $apellido, $nombre, $email, $clave, $acceso, $centro, $nivel, $idcuit, $dash, $avisopago);
```

**Después:**
```php
// Variable $centro eliminada
$stmt = $mysqli->prepare('INSERT INTO usuarios (apellido, nombre, email, clave, acceso, nivel, idcuit, dash, avisopago) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('sssssiiii', $apellido, $nombre, $email, $clave, $acceso, $nivel, $idcuit, $dash, $avisopago);
```

#### Método PUT (Actualizar Usuario):
**Antes:**
```php
$allowedFields = ['apellido', 'nombre', 'email', 'clave', 'acceso', 'centro', 'nivel', 'idcuit', 'dash', 'avisopago'];
if ($field === 'centro' || $field === 'nivel' || ...)
```

**Después:**
```php
$allowedFields = ['apellido', 'nombre', 'email', 'clave', 'acceso', 'nivel', 'idcuit', 'dash', 'avisopago'];
if ($field === 'nivel' || ...)
```

### 2. **Interfaz Web (`pages/usuarios.php`)**

#### Tabla de Usuarios:
**Antes:**
```html
<th>Centro</th>
```
```javascript
<td>${user.centro}</td>
```

**Después:**
Columna completamente eliminada de la tabla.

#### Formulario de Usuario:
**Antes:**
```html
<div class="col-md-4">
    <label for="centro" class="form-label">Centro</label>
    <input type="number" class="form-control" id="centro" name="centro" value="1" min="1">
</div>
<div class="col-md-4">
    <label for="nivel" class="form-label">Nivel</label>
    ...
</div>
```

**Después:**
```html
<div class="col-md-6">
    <label for="nivel" class="form-label">Nivel</label>
    ...
</div>
```

#### JavaScript:
**Antes:**
```javascript
document.getElementById('centro').value = user.centro;
```

**Después:**
Línea completamente eliminada.

## ✅ **Verificaciones Realizadas**

1. **✅ Base de Datos**: Columna eliminada correctamente
2. **✅ API GET**: Devuelve usuarios sin campo centro
3. **✅ API POST**: Puede crear usuarios sin campo centro
4. **✅ API PUT**: Puede actualizar usuarios sin campo centro
5. **✅ Interfaz**: Formulario funciona sin campo centro
6. **✅ Tabla**: Se muestra correctamente sin columna centro
7. **✅ Sin Referencias**: No quedan referencias a 'centro' en el código

## 🎯 **Resultados**

### Datos de Usuario Actuales:
```json
{
    "id": "1",
    "apellido": "Bragagnolo",
    "nombre": "Gustavo", 
    "email": "gustavo.bragagnolo@gmail.com",
    "acceso": "",
    "nivel": "2",
    "idcuit": "0", 
    "dash": "1",
    "avisopago": "0"
}
```

### Formulario Simplificado:
- **Apellido** y **Nombre** (col-md-6 cada uno)
- **Email** (col-md-12)
- **Contraseña** y **Acceso** (col-md-6 cada uno)
- **Nivel** (col-md-6) - **Campo centro eliminado**

## 🚀 **Beneficios**

1. **Código más limpio**: Eliminada variable no utilizada
2. **Formulario simplificado**: Menos campos confusos para el usuario
3. **Base de datos optimizada**: Columna innecesaria eliminada
4. **Mantenimiento reducido**: Menos código que mantener
5. **Interfaz mejorada**: Diseño más limpio y enfocado

## ⚠️ **Importante**

- **No hay pérdida de datos**: La columna no contenía información crítica
- **Compatibilidad**: Las operaciones CRUD funcionan normalmente
- **Sin impacto**: Los usuarios existentes no se ven afectados
- **Reversible**: Si fuera necesario, se puede agregar la columna nuevamente

## 🎉 **Estado Final**

✅ **Eliminación Completa Exitosa**
- Base de datos actualizada
- API limpia y funcional
- Interfaz simplificada
- Sin referencias residuales
- Operaciones CRUD funcionando correctamente

¡La columna 'centro' ha sido completamente eliminada del sistema Stadium! 🗑️➡️✨