# Cambios en Inventario - Manejo de Atributos

## Resumen
Se modificó el sistema de inventario para que la columna "Variante" muestre únicamente los atributos de la tabla `variante_atributos` (nombre: valor) en lugar del campo `nombre` de `producto_variantes`. También se habilitó la edición de estos atributos en el modal de crear/editar.

## Cambios en la Base de Datos (API)

### 1. api/inventario.php

#### Consultas GET modificadas:
- **Eliminado** el campo `pv.nombre as variante_nombre` de las consultas SELECT
- El ordenamiento cambió de `ORDER BY p.nombre, pv.nombre` a `ORDER BY p.nombre, pv.sku_variante`

#### POST (crear variante):
```php
// Antes: insertaba campo 'nombre'
INSERT INTO producto_variantes 
(producto_id, sku_variante, nombre, stock, precio, activa)

// Ahora: NO inserta 'nombre', inserta atributos en tabla separada
INSERT INTO producto_variantes 
(producto_id, sku_variante, stock, precio, activa)

// Luego inserta atributos:
INSERT INTO variante_atributos (variante_id, nombre, valor)
```

#### PUT (actualizar variante):
```php
// Eliminado el update del campo 'nombre'
// Agregado manejo de atributos:
1. Elimina atributos existentes: DELETE FROM variante_atributos WHERE variante_id = ?
2. Inserta nuevos atributos: INSERT INTO variante_atributos...
```

## Cambios en el Frontend

### 1. Modal de Crear/Editar (pages/inventario.php)

#### Antes:
```html
<input id="nombre" placeholder="Ej: Talle 40">
```

#### Ahora:
```html
<div id="atributosContainer">
  <!-- Atributos dinámicos -->
</div>
<button onclick="agregarAtributo()">Agregar Atributo</button>
```

Cada atributo tiene:
- Campo "Nombre" (ej: Talle, Color, Sabor)
- Campo "Valor" (ej: 40, Negro, Naranja)
- Botón eliminar

### 2. Visualización en Tabla

#### Antes:
```
Nike Air Max 90 - Talle 40
Talle: 40
```

#### Ahora:
```
[Badge: Talle: 40] [Badge: Color: Negro]
```

Los atributos se muestran como badges grises, uno al lado del otro.

## Funciones JavaScript Nuevas

### agregarAtributo(nombre, valor)
Agrega un par de campos (nombre/valor) al contenedor de atributos.

### eliminarAtributo(index)
Elimina un par de campos de atributo.

### getAtributosFromForm()
Recopila todos los atributos del formulario y los devuelve como array:
```javascript
[
  {nombre: "Talle", valor: "40"},
  {nombre: "Color", valor: "Negro"}
]
```

### limpiarAtributos()
Limpia el contenedor de atributos al abrir el modal.

## Flujo de Uso

### Crear nueva variante:
1. Click en "Nueva Variante"
2. Seleccionar producto
3. Ingresar SKU
4. Click en "Agregar Atributo"
5. Completar nombre y valor (ej: Talle, 40)
6. Repetir para más atributos
7. Guardar

### Editar variante:
1. Click en botón editar (lápiz)
2. Se cargan los atributos existentes
3. Puedes modificar, eliminar o agregar más
4. Guardar

## Estructura de Datos

### Solicitud POST/PUT:
```json
{
  "producto_id": 1,
  "sku_variante": "NIK-001-40",
  "stock": 10,
  "precio": 100.00,
  "activa": 1,
  "atributos": [
    {"nombre": "Talle", "valor": "40"},
    {"nombre": "Color", "valor": "Negro"}
  ]
}
```

### Respuesta GET:
```json
{
  "success": true,
  "inventario": [
    {
      "id": "9",
      "sku_variante": "NIK-001-40",
      "stock": "5",
      "atributos": [
        {"nombre": "Talle", "valor": "40"}
      ]
    }
  ]
}
```

## Validaciones

- Los atributos con nombre o valor vacío se ignoran
- Se pueden tener múltiples atributos por variante
- Al actualizar, se eliminan todos los atributos anteriores y se insertan los nuevos
- Los atributos se ordenan alfabéticamente por nombre

## Migración de Datos

Si ya tenías variantes con el campo `nombre` poblado, esos datos siguen en la BD pero ya no se muestran ni editan. Los atributos están en la tabla `variante_atributos`.

Para migrar datos existentes, ejecutar:
```sql
-- Ver qué variantes tienen 'nombre' pero no tienen atributos
SELECT pv.id, pv.nombre, pv.sku_variante
FROM producto_variantes pv
LEFT JOIN variante_atributos va ON pv.id = va.variante_id
WHERE pv.nombre IS NOT NULL AND pv.nombre != ''
  AND va.id IS NULL;
```

## Archivos Modificados

1. **api/inventario.php**
   - Eliminado campo `nombre` de consultas
   - Agregado manejo de atributos en POST/PUT
   
2. **pages/inventario.php**
   - Reemplazado campo nombre por contenedor de atributos dinámicos
   - Agregadas funciones para manejar atributos
   - Modificada visualización en tabla (badges)
