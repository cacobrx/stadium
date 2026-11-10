# API Productos con Imágenes de Variantes

## Resumen de Cambios Implementados

Se ha agregado soporte completo para imágenes de variantes en el API de productos. Los cambios incluyen:

### 1. Estructura de Base de Datos

- **Nueva tabla `variante_imagenes`**: 
  - `id` (INT, PRIMARY KEY)
  - `variante_id` (INT, FOREIGN KEY hacia `producto_variantes`)
  - `url` (VARCHAR(500)) - Ruta de la imagen
  - `alt_text` (VARCHAR(255)) - Texto alternativo
  - `es_principal` (TINYINT(1)) - Indica si es la imagen principal
  - `orden` (INT) - Orden de visualización
  - `fecha_creacion`, `fecha_actualizacion` (TIMESTAMP)

### 2. Funcionalidades Implementadas

#### GET - Obtener productos con imágenes de variantes
```http
GET /api/productos.php?id=1
```

**Respuesta extendida**: Ahora incluye arrays de `imagenes` para cada variante:

```json
{
  "success": true,
  "producto": {
    "id": 1,
    "nombre": "Nike Air Max 90",
    "variantes": [
      {
        "id": 9,
        "sku_variante": "NIK-001-40",
        "nombre": "Nike Air Max 90 - Talle 40",
        "stock": 5,
        "imagenes": [
          {
            "id": 1,
            "url": "/uploads/productos/nike-air-max-90-talle-40-main.jpg",
            "alt_text": "Nike Air Max 90 Talle 40 - Vista Principal",
            "es_principal": 1,
            "orden": 0
          },
          {
            "id": 2,
            "url": "/uploads/productos/nike-air-max-90-talle-40-side.jpg",
            "alt_text": "Nike Air Max 90 Talle 40 - Vista Lateral",
            "es_principal": 0,
            "orden": 1
          }
        ]
      }
    ]
  }
}
```

#### POST - Crear productos con variantes e imágenes
```http
POST /api/productos.php
Content-Type: application/json
```

**Ejemplo de payload**:
```json
{
  "sku": "TEST-001",
  "nombre": "Producto de Prueba",
  "descripcion": "Descripción del producto",
  "precio": 150.00,
  "stock": 0,
  "esta_activo": 1,
  "marca_id": 1,
  "deporte": "Running",
  "variantes": [
    {
      "sku": "TEST-001-38",
      "nombre": "Talle 38",
      "stock": 5,
      "precio": null,
      "activa": 1,
      "atributos": [
        { "nombre": "Talle", "valor": "38" }
      ],
      "imagenes": [
        {
          "url": "/uploads/productos/test-001-38-main.jpg",
          "alt_text": "Producto Test Talle 38 - Principal"
        },
        {
          "url": "/uploads/productos/test-001-38-side.jpg",
          "alt_text": "Producto Test Talle 38 - Lateral"
        }
      ]
    }
  ]
}
```

#### PUT - Actualizar productos con variantes e imágenes
```http
PUT /api/productos.php
Content-Type: application/json
```

El mismo formato que POST pero incluyendo el `id` del producto a actualizar.

### 3. Características Técnicas

- **Integridad referencial**: Las imágenes se eliminan automáticamente cuando se elimina una variante (CASCADE)
- **Imagen principal**: Solo una imagen por variante puede ser marcada como principal
- **Orden de visualización**: Las imágenes se ordenan por `es_principal` DESC, `orden` ASC
- **Transacciones**: Todas las operaciones usan transacciones para mantener consistencia

### 4. Archivos Creados/Modificados

- **Creados**:
  - `/database/agregar_variante_imagenes.sql` - Script para crear tabla de imágenes
  - `/database/insertar_imagenes_variantes_simple.sql` - Datos de ejemplo
  - `/test_variantes_imagenes.html` - Interfaz de prueba web
  - `/test_api_variantes.sh` - Script de prueba por línea de comandos

- **Modificados**:
  - `/api/productos.php` - Funcionalidad extendida para manejar imágenes de variantes

### 5. Uso del Frontend

Para usar esta funcionalidad en el frontend (React), el código puede ser así:

```javascript
// Crear producto con variantes e imágenes
const crearProductoConVariantes = async (productoData) => {
  const response = await fetch('/api/productos.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      ...productoData,
      variantes: [
        {
          sku: 'PROD-001-40',
          nombre: 'Talle 40',
          stock: 5,
          imagenes: [
            {
              url: '/uploads/productos/imagen-variante-1.jpg',
              alt_text: 'Descripción de la imagen'
            }
          ]
        }
      ]
    })
  });
  
  return response.json();
};

// Mostrar imágenes de variantes
const mostrarVariantes = (variantes) => {
  return variantes.map(variante => (
    <div key={variante.id}>
      <h3>{variante.nombre}</h3>
      <div className="imagenes-variante">
        {variante.imagenes.map(imagen => (
          <img 
            key={imagen.id}
            src={imagen.url} 
            alt={imagen.alt_text}
            className={imagen.es_principal ? 'principal' : 'secundaria'}
          />
        ))}
      </div>
    </div>
  ));
};
```

### 6. Notas de Implementación

- Las imágenes deben subirse previamente al servidor usando un endpoint de upload separado
- Se recomienda validar que las URLs de imágenes existan antes de insertarlas
- Para mejor rendimiento, considerar lazy loading para imágenes de variantes
- Las imágenes se ordenan automáticamente: principal primero, luego por orden ascendente

### 7. Testing

Se incluyen herramientas de testing:
- **Web**: Abrir `/test_variantes_imagenes.html` en el navegador
- **CLI**: Ejecutar `./test_api_variantes.sh` (requiere jq para formato JSON)

---

La implementación está completa y funcional. Las variantes ahora pueden tener múltiples imágenes con soporte completo para CRUD operations.