# Sistema CRUD de Artículos - Stadium

## Descripción

Se ha implementado un sistema completo de gestión de artículos (productos) que incluye:

- **CRUD completo**: Crear, leer, actualizar y eliminar productos
- **Relaciones**: Marcas, categorías, colecciones y deportes
- **Imágenes**: Soporte para múltiples imágenes por producto
- **Atributos**: Sistema flexible de atributos (color, talla, material, etc.)
- **Variantes**: Soporte para variantes de productos
- **Dimensiones**: Peso, alto, ancho y profundidad

## Estructura de Base de Datos

### Tablas Principales
- `productos` - Tabla principal de productos
- `marcas` - Marcas de productos
- `categorias` - Categorías de productos (con soporte para subcategorías)
- `colecciones` - Colecciones de productos
- `deportes` - Deportes asociados a productos

### Tablas de Relación
- `producto_categorias` - Relación muchos a muchos entre productos y categorías
- `producto_colecciones` - Relación muchos a muchos entre productos y colecciones
- `producto_imagenes` - Imágenes de productos
- `producto_atributos` - Atributos flexibles de productos
- `producto_variantes` - Variantes de productos (tallas, colores específicos)
- `variante_atributos` - Atributos de variantes

## Archivos Creados

### Base de Datos
- `/database/productos_schema.sql` - Esquema completo de la base de datos
- `/database/productos_ejemplo.sql` - Datos de ejemplo

### APIs
- `/api/productos.php` - API completa para gestión de productos (GET, POST, PUT, DELETE)
- `/api/marcas.php` - API para obtener marcas
- `/api/categorias.php` - API para obtener categorías
- `/api/colecciones.php` - API para obtener colecciones
- `/api/deportes.php` - API para obtener deportes

### Páginas
- `/pages/articulos.php` - Página principal de gestión de artículos

## Interfaz de Usuario

La página de artículos (`/pages/articulos.php`) incluye:

### Tabla de Productos
- Lista todos los productos con información básica
- Muestra imagen principal, SKU, nombre, marca, precio, stock, estado
- Indica si el producto está destacado
- Botones para editar y eliminar

### Modal de Edición
El modal de edición está organizado en pestañas:

#### Pestaña General
- SKU (requerido)
- Nombre (requerido)
- Descripción
- Precio (requerido)
- Precio de descuento
- Stock
- Marca (selección desde base de datos)
- Deporte
- Estado (activo/inactivo)
- Destacado (sí/no)

#### Pestaña Categorías
- Lista de categorías disponibles (checkboxes múltiples)
- Lista de colecciones disponibles (checkboxes múltiples)

#### Pestaña Imágenes
- Agregar URLs de imágenes
- Establecer imagen principal
- Texto alternativo para cada imagen
- Vista previa de imágenes

#### Pestaña Dimensiones
- Peso (kg)
- Alto (cm)
- Ancho (cm)
- Profundidad (cm)

## Funcionalidades

### Crear Producto
1. Hacer clic en "Nuevo Artículo"
2. Completar los campos requeridos (SKU, nombre, precio)
3. Seleccionar categorías y colecciones
4. Agregar imágenes
5. Especificar dimensiones
6. Guardar

### Editar Producto
1. Hacer clic en el botón editar (lápiz) en la tabla
2. Modificar los campos necesarios
3. Guardar cambios

### Eliminar Producto
1. Hacer clic en el botón eliminar (basura) en la tabla
2. Confirmar la eliminación

## Datos de Ejemplo

El sistema incluye datos de ejemplo:

### Marcas
- Nike
- Adidas  
- Puma
- Under Armour

### Deportes
- Fútbol
- Basketball
- Tennis
- Running
- Training

### Categorías
- Calzado
- Indumentaria
- Accesorios
- Equipamiento

### Colecciones
- Temporada 2024
- Clásicos
- Performance

### Productos de Ejemplo
- Nike Air Max 90 (Zapatillas)
- Adidas Ultraboost 22 (Zapatillas)
- Puma Future Z (Botines)
- Under Armour HeatGear (Camiseta)

## Navegación

Los artículos son accesibles desde el menú lateral principal del sistema, con el ícono de caja (bi-box).

## Características Técnicas

- **Validación**: Campos requeridos validados tanto en frontend como backend
- **Transacciones**: Operaciones de base de datos con soporte de transacciones
- **Relaciones**: Manejo automático de relaciones muchos a muchos
- **Imágenes**: Soporte para múltiples imágenes con imagen principal
- **Responsive**: Interfaz adaptable a diferentes tamaños de pantalla
- **Alertas**: Sistema de notificaciones con SweetAlert2
- **Temas**: Soporte para tema claro/oscuro

## Uso del Sistema

1. **Acceder**: Navegar a `/pages/articulos.php` o hacer clic en "Artículos" en el menú
2. **Ver productos**: La tabla muestra todos los productos existentes
3. **Crear**: Usar el botón "Nuevo Artículo"
4. **Editar**: Hacer clic en el ícono de lápiz
5. **Eliminar**: Hacer clic en el ícono de basura y confirmar

El sistema está completamente integrado con el template existente y sigue los mismos patrones de diseño y funcionamiento que el resto de la aplicación.