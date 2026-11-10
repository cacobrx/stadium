import React, { useState } from 'react';
import ProductosForm from './ProductosForm';
import './ProductosManager.css';

const ProductosManager = () => {
  const [vistaActual, setVistaActual] = useState('lista'); // 'lista', 'crear', 'editar'
  const [productoEditando, setProductoEditando] = useState(null);
  const [productos, setProductos] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  // Cargar lista de productos
  const cargarProductos = async () => {
    try {
      setIsLoading(true);
      const response = await fetch('/stadium/api/productos.php');
      const data = await response.json();
      
      if (data.success) {
        setProductos(data.productos || []);
      }
    } catch (error) {
      console.error('Error cargando productos:', error);
    } finally {
      setIsLoading(false);
    }
  };

  // Efectos
  React.useEffect(() => {
    if (vistaActual === 'lista') {
      cargarProductos();
    }
  }, [vistaActual]);

  // Manejadores de eventos
  const handleNuevoProducto = () => {
    setProductoEditando(null);
    setVistaActual('crear');
  };

  const handleEditarProducto = (producto) => {
    setProductoEditando(producto);
    setVistaActual('editar');
  };

  const handleEliminarProducto = async (id) => {
    if (!confirm('¿Estás seguro de que quieres eliminar este producto?')) {
      return;
    }

    try {
      const response = await fetch('/stadium/api/productos.php', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id })
      });

      const data = await response.json();

      if (data.success) {
        alert('Producto eliminado exitosamente');
        cargarProductos();
      } else {
        alert('Error eliminando producto: ' + data.error);
      }
    } catch (error) {
      console.error('Error eliminando producto:', error);
      alert('Error eliminando producto');
    }
  };

  const handleFormSuccess = (data) => {
    setVistaActual('lista');
    cargarProductos();
  };

  const handleFormCancel = () => {
    setVistaActual('lista');
    setProductoEditando(null);
  };

  // Renderizado condicional
  if (vistaActual === 'crear' || vistaActual === 'editar') {
    return (
      <ProductosForm
        productoId={productoEditando?.id}
        onSuccess={handleFormSuccess}
        onCancel={handleFormCancel}
      />
    );
  }

  return (
    <div className="productos-manager">
      <div className="manager-header">
        <h1>Gestión de Productos</h1>
        <button 
          className="btn btn-primary"
          onClick={handleNuevoProducto}
        >
          + Nuevo Producto
        </button>
      </div>

      {isLoading ? (
        <div className="loading-state">
          <p>Cargando productos...</p>
        </div>
      ) : (
        <div className="productos-lista">
          {productos.length === 0 ? (
            <div className="empty-state">
              <h3>No hay productos</h3>
              <p>Crea tu primer producto para comenzar</p>
              <button 
                className="btn btn-primary"
                onClick={handleNuevoProducto}
              >
                Crear Primer Producto
              </button>
            </div>
          ) : (
            <div className="productos-grid">
              {productos.map(producto => (
                <div key={producto.id} className="producto-card">
                  <div className="producto-imagen">
                    {producto.imagen_principal ? (
                      <img 
                        src={producto.imagen_principal} 
                        alt={producto.nombre}
                        onError={(e) => { e.target.style.display = 'none'; }}
                      />
                    ) : (
                      <div className="sin-imagen">Sin imagen</div>
                    )}
                  </div>
                  
                  <div className="producto-info">
                    <h3>{producto.nombre}</h3>
                    <p className="producto-sku">SKU: {producto.sku}</p>
                    <p className="producto-precio">${producto.precio}</p>
                    <p className="producto-stock">Stock: {producto.stock}</p>
                    
                    {producto.marca_nombre && (
                      <p className="producto-marca">{producto.marca_nombre}</p>
                    )}
                    
                    <div className="producto-estado">
                      <span className={`estado ${producto.esta_activo ? 'activo' : 'inactivo'}`}>
                        {producto.esta_activo ? 'Activo' : 'Inactivo'}
                      </span>
                      {producto.destacado && (
                        <span className="destacado">Destacado</span>
                      )}
                    </div>
                  </div>
                  
                  <div className="producto-acciones">
                    <button 
                      className="btn btn-secondary btn-sm"
                      onClick={() => handleEditarProducto(producto)}
                    >
                      Editar
                    </button>
                    <button 
                      className="btn btn-danger btn-sm"
                      onClick={() => handleEliminarProducto(producto.id)}
                    >
                      Eliminar
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default ProductosManager;