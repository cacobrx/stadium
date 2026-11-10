# Componente ProductosForm - Documentación

## Descripción

El componente `ProductosForm` es una interfaz completa para crear y editar productos con soporte para **variantes con imágenes**. Incluye tres pestañas principales:

1. **Información General**: Datos básicos del producto
2. **Variantes**: Gestión de variantes con atributos e imágenes
3. **Imágenes**: Imágenes principales del producto

## Características Principales

### ✅ Gestión de Variantes
- Crear, editar y eliminar variantes
- Atributos personalizados por variante (talle, color, etc.)
- **Múltiples imágenes por variante** 
- Precio específico por variante (opcional)
- Control de stock individual

### ✅ Imágenes de Variantes
- **Campo para URL de imagen** (lo que pediste)
- Texto alternativo personalizable
- Opción de imagen principal por variante
- Vista previa de imágenes
- Gestión completa CRUD de imágenes

### ✅ Interfaz Intuitiva
- Pestañas organizadas
- Responsive design
- Validaciones en tiempo real
- Estados de carga
- Confirmaciones de eliminación

## Uso Básico

```jsx
import ProductosForm from './ProductosForm';

// Para crear un producto nuevo
<ProductosForm 
  onSuccess={(data) => console.log('Producto creado:', data)}
  onCancel={() => console.log('Cancelado')}
/>

// Para editar un producto existente
<ProductosForm 
  productoId={123}
  onSuccess={(data) => console.log('Producto actualizado:', data)}
  onCancel={() => console.log('Cancelado')}
/>
```

## Integración con ProductosManager

```jsx
import React, { useState } from 'react';
import ProductosManager from './ProductosManager';

const App = () => {
  return (
    <div className="app">
      <ProductosManager />
    </div>
  );
};

export default App;
```

## Flujo de Trabajo: Agregar Imágenes a Variantes

### 1. Crear/Editar Producto
1. Abrir el formulario de productos
2. Ir a la pestaña **"Variantes"**
3. Hacer clic en **"+ Agregar Variante"**

### 2. Configurar Variante
1. Llenar datos básicos (SKU, nombre, stock)
2. Agregar atributos (ej: Talle: 40, Color: Rojo)

### 3. **Agregar Imágenes** (LO QUE PEDISTE)
1. En la sección **"Imágenes de la Variante"**
2. Hacer clic en **"+ Agregar Imagen"**
3. **Llenar el campo URL** (ej: `/uploads/productos/zapatilla-roja-40.jpg`)
4. Agregar texto alternativo descriptivo
5. Marcar como "Imagen Principal" si corresponde
6. **Ver vista previa automática**

### 4. Múltiples Imágenes
- Puedes agregar múltiples imágenes por variante
- Solo una puede ser principal
- Se ordenan automáticamente (principal primero)

## Estructura de Datos

### Producto con Variantes e Imágenes
```json
{
  "sku": "NIKE-001",
  "nombre": "Nike Air Max 90",
  "precio": 150.00,
  "variantes": [
    {
      "sku": "NIKE-001-40",
      "nombre": "Nike Air Max 90 - Talle 40",
      "stock": 5,
      "atributos": [
        { "nombre": "Talle", "valor": "40" },
        { "nombre": "Color", "valor": "Negro" }
      ],
      "imagenes": [
        {
          "url": "/uploads/productos/nike-001-40-main.jpg",
          "alt_text": "Nike Air Max 90 Talle 40 - Vista Principal",
          "es_principal": true
        },
        {
          "url": "/uploads/productos/nike-001-40-side.jpg", 
          "alt_text": "Nike Air Max 90 Talle 40 - Vista Lateral",
          "es_principal": false
        }
      ]
    }
  ]
}
```

## API Endpoints Requeridos

El componente espera estos endpoints:

- `GET /stadium/api/productos.php?id={id}` - Obtener producto
- `POST /stadium/api/productos.php` - Crear producto
- `PUT /stadium/api/productos.php` - Actualizar producto
- `DELETE /stadium/api/productos.php` - Eliminar producto
- `GET /stadium/api/marcas.php` - Obtener marcas
- `GET /stadium/api/categorias.php` - Obtener categorías
- `GET /stadium/api/colecciones.php` - Obtener colecciones

## Instalación y Configuración

### 1. Copiar Archivos
```bash
# Copiar componentes React
cp ProductosForm.jsx tu-proyecto/src/components/
cp ProductosForm.css tu-proyecto/src/components/
cp ProductosManager.jsx tu-proyecto/src/components/
cp ProductosManager.css tu-proyecto/src/components/
```

### 2. Importar en tu Aplicación
```jsx
// En tu router principal
import ProductosManager from './components/ProductosManager';

// Agregar ruta
<Route path="/productos" element={<ProductosManager />} />
```

### 3. Configurar API Base URL (Opcional)
Si tu API está en una URL diferente, modifica las llamadas fetch en el componente.

## Personalización

### Modificar Campos
Puedes agregar/quitar campos editando el estado inicial de `formData` en `ProductosForm.jsx`.

### Cambiar Estilos
Modifica `ProductosForm.css` y `ProductosManager.css` según tu tema.

### Validaciones Personalizadas
Agrega validaciones en la función `handleSubmit`.

## Solución a tu Problema Original

**TU CONSULTA**: "no veo en la modificación, en la solapa 'variantes' la posibilidad de cargar la imagen en un link"

**SOLUCIÓN IMPLEMENTADA**:
1. ✅ **Pestaña "Variantes"** - Creada y funcional
2. ✅ **Campo para URL de imagen** - En cada variante
3. ✅ **Múltiples imágenes por variante** - Soportado
4. ✅ **Vista previa automática** - Incluida
5. ✅ **Gestión completa** - Agregar, editar, eliminar imágenes

### Ubicación del Campo de Imagen
```
Formulario de Producto
└── Pestaña "Variantes"
    └── Variante #1
        └── Sección "Imágenes de la Variante"
            └── [+ Agregar Imagen]
                ├── 📝 Campo "URL de la Imagen" ← AQUÍ
                ├── 📝 Campo "Texto Alternativo"
                ├── ☑️ Checkbox "Imagen Principal"
                └── 🖼️ Vista Previa
```

## Ejemplo de Uso Completo

```jsx
import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import ProductosManager from './components/ProductosManager';

function App() {
  return (
    <BrowserRouter>
      <div className="App">
        <nav>
          <a href="/productos">Gestionar Productos</a>
        </nav>
        
        <Routes>
          <Route path="/productos" element={<ProductosManager />} />
        </Routes>
      </div>
    </BrowserRouter>
  );
}

export default App;
```

## Resultado Final

Ahora tienes:
- ✅ **Interfaz completa** para gestión de productos
- ✅ **Pestaña de variantes** con campos de imagen
- ✅ **Backend funcional** (ya implementado antes)
- ✅ **Base de datos** con tabla `variante_imagenes`
- ✅ **API completo** con soporte para imágenes de variantes

**¡El campo para cargar imágenes de variantes está listo y funcional!**