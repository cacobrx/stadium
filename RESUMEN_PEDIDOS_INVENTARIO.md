# Resumen de Cambios: Integración Pedidos con Inventario

## ✅ COMPLETADO

### Cambios en el Formulario de Pedidos (`pedido_form.php`)

#### 🔄 Cambio Principal
**ANTES**: La lista desplegable mostraba artículos de la tabla `articulos`
**AHORA**: La lista desplegable muestra variantes del **inventario** (`producto_variantes`)

---

### 📊 Vista del Dropdown

#### Formato de Visualización:
```
[Nombre Producto] - [Atributos] [SKU Variante] - $[Precio] (Stock: [Cantidad])
```

#### Ejemplos Reales:
```
Nike Air Max 90 - Talle: 40, Color: Negro [NIK-001-40] - $120.00 (Stock: 15)
Adidas Superstar - Talle: 42 [ADI-002-42] - $95.00 (Stock: 8)
Puma Suede - Talle: 39, Color: Azul [PUM-003-39] - $85.50 (Sin stock)
```

#### Características:
✅ Muestra **atributos** de cada variante (Talle, Color, etc.)
✅ Indica **stock disponible**
✅ Muestra **precio** de la variante
✅ Si no hay stock, aparece en **rojo** con "(Sin stock)"
✅ SKU único de la variante entre corchetes

---

### 💰 Manejo de Precios

#### Prioridad de Precios:
1. **Si la variante tiene precio** → Se autocompleta en el campo
2. **Si NO tiene precio** (NULL o 0) → Campo queda vacío para ingresar manualmente
3. El precio **siempre es editable** antes de agregar al pedido

#### Campo de Precio:
- **Antes**: `readonly` (solo lectura)
- **Ahora**: `editable` con `min="0"`

---

### 📦 Validación de Stock

```javascript
if (stock > 0) {
    // Limita cantidad máxima al stock disponible
    cantidadInput.max = stock;
} else {
    // Sin stock pero permite continuar con advertencia
    showWarning('Este artículo no tiene stock disponible');
}
```

---

### 🔙 Retrocompatibilidad

El sistema mantiene compatibilidad con pedidos antiguos:

```php
// Si no encuentra en producto_variantes, busca en articulos
if (!$row['producto_nombre']) {
    $stmt_art = $conn->prepare("SELECT nombre FROM articulos WHERE id = ?");
    // ... busca en tabla articulos
}
```

---

## 🗂️ Archivos Modificados

### 1. `/pages/pedido_form.php`
- ✏️ Función `cargarArticulos()`: Cambiado endpoint a `/stadium/api/inventario.php`
- ✏️ Construcción del nombre con atributos y stock
- ✏️ Función `actualizarPrecio()`: Validación de stock y advertencias
- ✏️ Función `cargarProductosPedido()`: Construcción de nombres con inventario
- ✏️ Campo precio: Cambiado de `readonly` a editable
- ✏️ Label: "Artículo" → "Variante de Producto (Inventario)"

### 2. `/api/pedidos_det.php`
- ✏️ Función `obtener_detalles_pedido()`: JOIN a `producto_variantes` y `productos`
- ➕ Agregada consulta de atributos de variantes
- ➕ Construcción de nombre completo con atributos
- ✅ Mantiene compatibilidad con tabla `articulos`

---

## 🎯 Casos de Uso

### Caso 1: Crear Pedido Nuevo
```
1. Seleccionar cliente
2. Elegir variante del inventario
   → Se autocompleta precio desde la variante
   → Se muestra stock disponible
3. Ingresar cantidad (respeta límite de stock)
4. Agregar al pedido
5. Guardar
```

### Caso 2: Variante Sin Precio
```
1. Seleccionar variante que tiene precio = 0
   → Campo precio queda vacío
2. Ingresar precio manualmente
3. Continuar normalmente
```

### Caso 3: Variante Sin Stock
```
1. Seleccionar variante con stock = 0
   → Aparece "(Sin stock)" en rojo
   → Muestra advertencia
2. Permite ingresar cantidad igual
3. Pedido se crea normalmente
```

### Caso 4: Editar Pedido Existente
```
1. Click en editar pedido
2. Se cargan productos con nombres completos
   Ejemplo: "Nike Air Max 90 - Talle: 40 [NIK-001-40]"
3. Puedes agregar más variantes del inventario
4. Guardar cambios
```

---

## 📋 Estructura de Datos

### Tabla `pedidos_det`
```sql
idart → Ahora apunta a producto_variantes.id (antes: articulos.id)
```

### JSON que se envía al guardar:
```json
{
  "productos": [
    {
      "idart": 9,                    // ID de producto_variantes
      "nombre": "Nike Air Max 90 - Talle: 40, Color: Negro [NIK-001-40]",
      "detalle": "Urgente",
      "cantidad": 2,
      "precio": 120.00,              // Precio de la variante (o ingresado)
      "iva": 21,
      "subtotal": 240.00,
      "total": 291.60
    }
  ]
}
```

### Respuesta de API `pedidos_det.php`:
```json
[
  {
    "id": 123,
    "idart": 9,
    "cantidad": 2,
    "precio": 120.00,
    "producto_nombre": "Nike Air Max 90 - Talle: 40, Color: Negro [NIK-001-40]",
    "sku_variante": "NIK-001-40",
    "variante_stock": 15,
    "variante_precio": 120.00,
    "atributos": "Talle: 40, Color: Negro"
  }
]
```

---

## 🧪 Testing

### Probar en el Sistema:

1. **Login** en Stadium
2. Ir a **Pedidos** → **Nuevo Pedido**
3. Verificar dropdown con variantes del inventario
4. Seleccionar una variante con stock y precio
   - ✅ Precio se autocompleta
   - ✅ Cantidad limitada al stock
5. Seleccionar variante SIN stock
   - ⚠️ Advertencia aparece
   - ✅ Permite continuar
6. Agregar productos y guardar
7. Editar el pedido creado
   - ✅ Productos se muestran con atributos

---

## 🚀 Ventajas de la Integración

| Antes (Artículos) | Ahora (Inventario) |
|-------------------|-------------------|
| Sin variantes | ✅ Variantes con atributos |
| Sin control de stock en tiempo real | ✅ Stock visible al seleccionar |
| Precio fijo del artículo | ✅ Precio por variante específica |
| Sin SKU de variante | ✅ SKU único por combinación |
| Descripción genérica | ✅ Descripción + atributos (Talle, Color) |

---

## ⚙️ Configuración Requerida

Para que funcione correctamente, asegúrate de:

1. ✅ Tener productos en `productos`
2. ✅ Tener variantes en `producto_variantes` asociadas a productos
3. ✅ Tener atributos en `variante_atributos` asociados a variantes
4. ✅ Definir `precio` en `producto_variantes` (si quieres autocompletado)
5. ✅ Definir `stock` en `producto_variantes` (para validación)

### Ejemplo de Datos Necesarios:

```sql
-- Producto
INSERT INTO productos (id, nombre, sku) VALUES (1, 'Nike Air Max 90', 'NIK-001');

-- Variante
INSERT INTO producto_variantes (id, producto_id, sku_variante, precio, stock) 
VALUES (9, 1, 'NIK-001-40', 120.00, 15);

-- Atributos
INSERT INTO variante_atributos (variante_id, nombre, valor) VALUES 
(9, 'Talle', '40'),
(9, 'Color', 'Negro');
```

---

## 📞 Troubleshooting

### Problema: No aparecen variantes en el dropdown
**Solución**: Verificar que existan registros en `producto_variantes` con `activa = 1`

### Problema: No se muestra el precio
**Solución**: Verificar que `producto_variantes.precio` tenga un valor (no NULL ni 0)

### Problema: No se muestran atributos
**Solución**: Verificar que existan registros en `variante_atributos` para esa variante

### Problema: Error al cargar inventario
**Solución**: 
1. Verificar sesión activa
2. Verificar endpoint `/stadium/api/inventario.php` accesible
3. Revisar consola del navegador

---

## 📝 Notas Finales

- El campo `idart` en `pedidos_det` ahora almacena el ID de `producto_variantes`
- Los pedidos antiguos siguen funcionando (busca en `articulos` si no encuentra en variantes)
- El precio es editable por si necesitas sobrescribir el precio del inventario
- El sistema NO descuenta stock automáticamente (debe implementarse si se requiere)

**Documentación completa**: Ver archivo `PEDIDOS_INVENTARIO.md`
