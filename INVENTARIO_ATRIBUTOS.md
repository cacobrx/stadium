# Implementación de Atributos en Inventario

## Cambios realizados

### 1. API de Inventario (api/inventario.php)

#### Consulta GET (lista completa)
Se agregó consulta adicional para obtener atributos de cada variante:

```php
// Después de obtener el inventario básico
foreach ($inventario as &$item) {
    $stmt = $mysqli->prepare("
        SELECT nombre, valor 
        FROM variante_atributos 
        WHERE variante_id = ?
        ORDER BY nombre
    ");
    $stmt->bind_param('i', $item['id']);
    $stmt->execute();
    $atributos_result = $stmt->get_result();
    $item['atributos'] = $atributos_result->fetch_all(MYSQLI_ASSOC);
}
```

#### Consulta GET (registro individual)
También se agregó para cuando se edita un registro:

```php
// Obtener atributos de la variante
$stmt_attr = $mysqli->prepare("
    SELECT nombre, valor 
    FROM variante_atributos 
    WHERE variante_id = ?
    ORDER BY nombre
");
$stmt_attr->bind_param('i', $id);
$stmt_attr->execute();
$atributos_result = $stmt_attr->get_result();
$row['atributos'] = $atributos_result->fetch_all(MYSQLI_ASSOC);
```

### 2. Frontend (pages/inventario.php)

Se modificó la columna de Variante para mostrar el nombre y los atributos:

```javascript
<td>
    ${item.variante_nombre || ''}
    ${item.atributos && item.atributos.length > 0 ? 
        '<br><small class="text-muted">' + 
        item.atributos.map(attr => `${attr.nombre}: ${attr.valor}`).join(', ') + 
        '</small>' : 
        ''}
</td>
```

### 3. Datos de ejemplo

Se creó script SQL para agregar atributos de ejemplo:
`database/insertar_atributos_ejemplo.sql`

Atributos agregados:
- **Nike Air Max 90**: Talle (40, 41, 42, 43)
- **Adidas Ultraboost**: Talle + Color (Negro/Azul)
- **Pastillas DRF**: Sabor (Naranja)

## Formato de respuesta del API

```json
{
  "success": true,
  "inventario": [
    {
      "id": "29",
      "producto_id": "2",
      "sku_variante": "ADS-002-39",
      "variante_nombre": "Adidas Ultraboost 22 - Variante 1",
      "stock": "4",
      "precio": "185.00",
      "activa": "1",
      "atributos": [
        {"nombre": "Color", "valor": "Negro"},
        {"nombre": "Talle", "valor": "39"}
      ]
    }
  ]
}
```

## Visualización en la tabla

La columna "Variante" ahora muestra:

```
Adidas Ultraboost 22 - Variante 1
Color: Negro, Talle: 39
```

Donde:
- Primera línea: Nombre de la variante
- Segunda línea: Atributos separados por coma (en texto pequeño y gris)

## Estructura de la base de datos

### Tabla variante_atributos
```sql
CREATE TABLE variante_atributos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variante_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,      -- Ej: "Talle", "Color", "Sabor"
    valor VARCHAR(255) NOT NULL,        -- Ej: "40", "Negro", "Naranja"
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (variante_id) REFERENCES producto_variantes(id) ON DELETE CASCADE
);
```

## Relaciones

```
productos
    └── producto_variantes (1:N)
            └── variante_atributos (1:N)
```

## Ejemplo de uso

1. Una variante puede tener múltiples atributos
2. Los atributos se ordenan alfabéticamente por nombre
3. Si no hay atributos, solo se muestra el nombre de la variante
4. Los atributos se muestran como "Nombre: Valor"

## Consulta para agregar atributos manualmente

```sql
-- Obtener ID de una variante
SELECT id FROM producto_variantes WHERE sku_variante = 'ADS-002-39';

-- Agregar atributos
INSERT INTO variante_atributos (variante_id, nombre, valor) VALUES
(29, 'Talle', '39'),
(29, 'Color', 'Negro');
```
