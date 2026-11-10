# Integración de Pedidos con Inventario

## Cambios Implementados

Se modificó el sistema de pedidos para que al agregar o editar productos en un pedido, la lista desplegable muestre las variantes del **inventario** (tabla `producto_variantes`) en lugar de la tabla `articulos`.

---

## 📋 Archivos Modificados

### 1. `/pages/pedido_form.php` (Frontend)

#### Función `cargarArticulos()`
**Antes**: Cargaba desde `/api/articulos.php`
```javascript
const res = await fetch('./api/articulos.php');
articulos = await res.json();
```

**Ahora**: Carga desde `/api/inventario.php`
```javascript
const res = await fetch('/stadium/api/inventario.php');
const data = await res.json();
articulos = data.inventario;
```

#### Construcción del Nombre en el Dropdown
Ahora el select muestra:
- **Nombre del producto** (de la tabla `productos`)
- **Atributos** de la variante (Talle: 40, Color: Negro, etc.)
- **SKU de la variante** entre corchetes
- **Precio** de la variante (o $0.00 si no tiene)
- **Stock disponible** (o "Sin stock" en rojo)

**Ejemplo de opción:**
```
Nike Air Max 90 - Talle: 40, Color: Negro [NIK-001-40] - $120.00 (Stock: 15)
```

#### Manejo de Precio
- El campo **Precio Unitario** ahora es **editable**
- Al seleccionar un artículo del inventario:
  - Si la variante tiene `precio` definido → se autocompleta
  - Si no tiene precio → queda en $0.00 y puedes ingresar el precio manualmente
  - El precio puede modificarse en cualquier momento

#### Validación de Stock
- Si el stock es **mayor a 0**: limita la cantidad máxima al stock disponible
- Si **no hay stock**: permite ingresar cantidad igual pero muestra advertencia

---

### 2. `/api/pedidos_det.php` (Backend)

#### Función `obtener_detalles_pedido()`
**Query actualizado** con JOINs al inventario:

```sql
SELECT 
    pd.id, pd.idped, pd.cantidad, pd.idart, pd.precio, pd.total, pd.detalle,
    pv.sku_variante,
    pv.stock as variante_stock,
    pv.precio as variante_precio,
    p.nombre as producto_nombre,
    p.sku as producto_sku
FROM pedidos_det pd 
LEFT JOIN producto_variantes pv ON pd.idart = pv.id
LEFT JOIN productos p ON pv.producto_id = p.id
WHERE pd.idped = ?
```

#### Atributos de Variantes
Se agregó consulta adicional para obtener los atributos:
```sql
SELECT nombre, valor 
FROM variante_atributos 
WHERE variante_id = ? 
ORDER BY nombre
```

Los atributos se concatenan en el nombre del producto:
```
Nike Air Max 90 - Talle: 40, Color: Negro [NIK-001-40]
```

#### Retrocompatibilidad
Si el `idart` no encuentra una variante en `producto_variantes`, busca en la tabla `articulos` (para pedidos antiguos).

---

## 🔄 Flujo de Uso

### Crear Nuevo Pedido:

1. **Seleccionar Cliente** y completar datos del pedido
2. En **"Variante de Producto (Inventario)"**, seleccionar una variante del desplegable
   - Verás: Producto + Atributos + SKU + Precio + Stock
3. El **precio se autocompleta** con el precio de la variante
   - Si no tiene precio definido, ingresar manualmente
4. Ingresar **cantidad** (respeta límite de stock si hay disponible)
5. Seleccionar **IVA %**
6. Click en **"Agregar"**
7. El producto se agrega a la tabla con subtotal y total calculados
8. **Guardar** el pedido

### Editar Pedido Existente:

1. Click en botón de editar desde lista de pedidos
2. Se cargan los datos del pedido
3. Se cargan los productos del pedido con sus nombres completos (producto + atributos)
4. Puedes **agregar más productos** del inventario
5. Puedes **eliminar productos** existentes
6. **Guardar** cambios

---

## 📊 Estructura de Datos

### Campo `idart` en `pedidos_det`
**Ahora apunta a**: `producto_variantes.id` (ID de la variante)
**Antes apuntaba a**: `articulos.id`

### Datos que se Envían al Crear/Editar Pedido:
```json
{
  "productos": [
    {
      "idart": 9,              // ID de producto_variantes
      "nombre": "Nike Air Max 90 - Talle: 40 [NIK-001-40]",
      "detalle": "Color negro",
      "cantidad": 2,
      "precio": 120.00,
      "iva": 21,
      "subtotal": 240.00,
      "total": 291.60
    }
  ]
}
```

---

## ⚠️ Consideraciones

### Stock
- El sistema **valida el stock disponible** al agregar productos
- Si no hay stock, muestra advertencia pero permite continuar
- **No descuenta automáticamente** el stock al crear el pedido
  - Esto debe implementarse en el backend si se requiere

### Precios
- **Prioridad 1**: Precio de `producto_variantes.precio`
- **Prioridad 2**: Si es $0 o NULL, permite ingresar manualmente
- El precio puede editarse siempre antes de agregar al pedido

### Retrocompatibilidad
- Pedidos antiguos con `idart` apuntando a `articulos` siguen funcionando
- El sistema busca primero en `producto_variantes`, si no encuentra busca en `articulos`

---

## 🧪 Pruebas Realizadas

✅ Cargar inventario con atributos en el dropdown
✅ Autocompletar precio desde la variante
✅ Validar stock disponible
✅ Agregar productos con atributos al pedido
✅ Mostrar nombres completos en la tabla de productos
✅ Editar pedidos existentes y cargar productos correctamente
✅ Compatibilidad con pedidos antiguos (articulos)

---

## 📝 Próximas Mejoras Sugeridas

- [ ] **Descuento automático de stock** al confirmar pedido
- [ ] **Filtro por categoría/marca** en el dropdown de inventario
- [ ] **Búsqueda/autocompletado** en el select de inventario (Select2 o similar)
- [ ] **Validación de stock** al guardar el pedido completo
- [ ] **Alertas** si se intenta pedir más unidades del stock disponible
- [ ] **Historial de precios** por variante
- [ ] **Sincronización en tiempo real** del stock

---

## 🔧 Mantenimiento

### Para agregar más información al dropdown:
Editar en `pedido_form.php`, función `cargarArticulos()`:
```javascript
// Agregar más datos al option
const marca = item.marca_nombre ? ` (${item.marca_nombre})` : '';
nombreCompleto += marca;
```

### Para cambiar el formato de visualización:
Editar en `pedidos_det.php`, función `obtener_detalles_pedido()`:
```php
// Personalizar construcción del nombre
$nombreCompleto = $row['marca_nombre'] . ' ' . $row['producto_nombre'];
```

---

## 📞 Soporte

Si se encuentran problemas:
1. Verificar que existan registros en `producto_variantes` con `precio` y `stock`
2. Revisar consola del navegador para errores JavaScript
3. Verificar logs del servidor PHP para errores en las APIs
4. Confirmar que la sesión esté activa (requireAuthAPI)
