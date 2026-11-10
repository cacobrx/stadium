import React, { useState, useEffect } from 'react';
import './ProductosForm.css';

const ProductosForm = ({ productoId = null, onSuccess, onCancel }) => {
  const [formData, setFormData] = useState({
    sku: '',
    nombre: '',
    descripcion: '',
    precio: '',
    precio_descuento: '',
    stock: '',
    esta_activo: true,
    destacado: false,
    marca_id: '',
    deporte: '',
    peso: '',
    alto: '',
    ancho: '',
    profundidad: '',
    categorias: [],
    colecciones: [],
    imagenes: [],
    atributos: [],
    variantes: []
  });

  const [marcas, setMarcas] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [colecciones, setColecciones] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [activeTab, setActiveTab] = useState('general');

  // Cargar datos iniciales
  useEffect(() => {
    cargarDatosIniciales();
    if (productoId) {
      cargarProducto(productoId);
    }
  }, [productoId]);

          const cargarDatosIniciales = async () => {
            try {
              // Intentar primero con las APIs autenticadas
              let [marcasRes, categoriasRes, coleccionesRes] = await Promise.all([
                fetch('api/marcas.php'),
                fetch('api/categorias.php'),
                fetch('api/colecciones.php')
              ]);

              let marcasData = await marcasRes.json();
              let categoriasData = await categoriasRes.json();
              let coleccionesData = await coleccionesRes.json();

              // Si falla la autenticación, probar APIs simples y luego las corregidas
              if (!marcasData.success || !categoriasData.success || !coleccionesData.success) {
                console.log('Usando APIs simples por falta de autenticación');
                try {
                  [marcasRes, categoriasRes, coleccionesRes] = await Promise.all([
                    fetch('api-marcas-simple.php'),
                    fetch('api-categorias-simple.php'),
                    fetch('api-colecciones-simple.php')
                  ]);
                } catch (error) {
                  console.log('APIs simples fallaron, probando APIs corregidas');
                  [marcasRes, categoriasRes, coleccionesRes] = await Promise.all([
                    fetch('api-marcas-simple.php'), // Mantener marcas simple
                    fetch('api-categorias-fixed.php'),
                    fetch('api-colecciones-fixed.php')
                  ]);
                }

                marcasData = await marcasRes.json();
                categoriasData = await categoriasRes.json();
                coleccionesData = await coleccionesRes.json();
              }

              if (marcasData.success) setMarcas(marcasData.marcas || []);
              if (categoriasData.success) setCategorias(categoriasData.categorias || []);
              if (coleccionesData.success) setColecciones(coleccionesData.colecciones || []);
              
              console.log('Datos cargados:', {
                marcas: marcasData.marcas?.length || 0,
                categorias: categoriasData.categorias?.length || 0,
                colecciones: coleccionesData.colecciones?.length || 0
              });
            } catch (error) {
              console.error('Error cargando datos iniciales:', error);
            }
          };  const cargarProducto = async (id) => {
    try {
      setIsLoading(true);
  const response = await fetch(`api/productos.php?id=${id}`);
      const data = await response.json();
      
      if (data.success) {
        setFormData({
          ...data.producto,
          categorias: data.producto.categorias?.map(c => c.id) || [],
          colecciones: data.producto.colecciones?.map(c => c.id) || [],
          variantes: data.producto.variantes || []
        });
      }
    } catch (error) {
      console.error('Error cargando producto:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleSelectChange = (name, values) => {
    setFormData(prev => ({
      ...prev,
      [name]: values
    }));
  };

  // FUNCIONES PARA VARIANTES
  const agregarVariante = () => {
    const nuevaVariante = {
      id: Date.now(), // ID temporal
      sku: '',
      nombre: '',
      stock: 0,
      precio: '',
      activa: true,
      atributos: [],
      imagenes: []
    };
    setFormData(prev => ({
      ...prev,
      variantes: [...prev.variantes, nuevaVariante]
    }));
  };

  const eliminarVariante = (index) => {
    setFormData(prev => ({
      ...prev,
      variantes: prev.variantes.filter((_, i) => i !== index)
    }));
  };

  const actualizarVariante = (index, field, value) => {
    setFormData(prev => ({
      ...prev,
      variantes: prev.variantes.map((variante, i) => 
        i === index ? { ...variante, [field]: value } : variante
      )
    }));
  };

  // FUNCIONES PARA IMÁGENES DE VARIANTES
  const agregarImagenVariante = (varianteIndex) => {
    const nuevaImagen = {
      id: Date.now(),
      url: '',
      alt_text: '',
      es_principal: false
    };
    
    setFormData(prev => ({
      ...prev,
      variantes: prev.variantes.map((variante, i) => 
        i === varianteIndex 
          ? { ...variante, imagenes: [...(variante.imagenes || []), nuevaImagen] }
          : variante
      )
    }));
  };

  const eliminarImagenVariante = (varianteIndex, imagenIndex) => {
    setFormData(prev => ({
      ...prev,
      variantes: prev.variantes.map((variante, i) => 
        i === varianteIndex 
          ? { 
              ...variante, 
              imagenes: variante.imagenes.filter((_, imgI) => imgI !== imagenIndex)
            }
          : variante
      )
    }));
  };

  const actualizarImagenVariante = (varianteIndex, imagenIndex, field, value) => {
    setFormData(prev => ({
      ...prev,
      variantes: prev.variantes.map((variante, i) => 
        i === varianteIndex 
          ? {
              ...variante,
              imagenes: variante.imagenes.map((imagen, imgI) => 
                imgI === imagenIndex 
                  ? { ...imagen, [field]: value }
                  : field === 'es_principal' && value ? { ...imagen, es_principal: false } : imagen
              )
            }
          : variante
      )
    }));
  };

  // FUNCIONES PARA ATRIBUTOS DE VARIANTES
  const agregarAtributoVariante = (varianteIndex) => {
    const nuevoAtributo = {
      id: Date.now(),
      nombre: '',
      valor: ''
    };
    
    setFormData(prev => ({
      ...prev,
      variantes: prev.variantes.map((variante, i) => 
        i === varianteIndex 
          ? { ...variante, atributos: [...(variante.atributos || []), nuevoAtributo] }
          : variante
      )
    }));
  };

  const eliminarAtributoVariante = (varianteIndex, atributoIndex) => {
    setFormData(prev => ({
      ...prev,
      variantes: prev.variantes.map((variante, i) => 
        i === varianteIndex 
          ? { 
              ...variante, 
              atributos: variante.atributos.filter((_, attrI) => attrI !== atributoIndex)
            }
          : variante
      )
    }));
  };

  const actualizarAtributoVariante = (varianteIndex, atributoIndex, field, value) => {
    setFormData(prev => ({
      ...prev,
      variantes: prev.variantes.map((variante, i) => 
        i === varianteIndex 
          ? {
              ...variante,
              atributos: variante.atributos.map((atributo, attrI) => 
                attrI === atributoIndex 
                  ? { ...atributo, [field]: value }
                  : atributo
              )
            }
          : variante
      )
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);

    try {
  const url = 'api/productos.php';
      const method = productoId ? 'PUT' : 'POST';
      const payload = productoId ? { ...formData, id: productoId } : formData;

      const response = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (data.success) {
        alert(productoId ? 'Producto actualizado exitosamente' : 'Producto creado exitosamente');
        if (onSuccess) onSuccess(data);
      } else {
        alert('Error: ' + data.error);
      }
    } catch (error) {
      console.error('Error guardando producto:', error);
      alert('Error guardando producto');
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading && productoId) {
    return <div className="loading">Cargando producto...</div>;
  }

  return (
    <div className="productos-form">
      <div className="form-header">
        <h2>{productoId ? 'Editar Producto' : 'Nuevo Producto'}</h2>
        <div className="form-actions">
          <button type="button" onClick={onCancel} className="btn btn-secondary">
            Cancelar
          </button>
          <button 
            type="submit" 
            form="producto-form" 
            className="btn btn-primary"
            disabled={isLoading}
          >
            {isLoading ? 'Guardando...' : 'Guardar'}
          </button>
        </div>
      </div>

      <div className="form-tabs">
        <button 
          type="button"
          className={`tab-button ${activeTab === 'general' ? 'active' : ''}`}
          onClick={() => setActiveTab('general')}
        >
          Información General
        </button>
        <button 
          type="button"
          className={`tab-button ${activeTab === 'variantes' ? 'active' : ''}`}
          onClick={() => setActiveTab('variantes')}
        >
          Variantes
        </button>
        <button 
          type="button"
          className={`tab-button ${activeTab === 'imagenes' ? 'active' : ''}`}
          onClick={() => setActiveTab('imagenes')}
        >
          Imágenes
        </button>
      </div>

      <form id="producto-form" onSubmit={handleSubmit}>
        {activeTab === 'general' && (
          <div className="tab-content">
            <div className="form-grid">
              <div className="form-group">
                <label htmlFor="sku">SKU *</label>
                <input
                  type="text"
                  id="sku"
                  name="sku"
                  value={formData.sku}
                  onChange={handleInputChange}
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="nombre">Nombre *</label>
                <input
                  type="text"
                  id="nombre"
                  name="nombre"
                  value={formData.nombre}
                  onChange={handleInputChange}
                  required
                />
              </div>

              <div className="form-group form-group-full">
                <label htmlFor="descripcion">Descripción</label>
                <textarea
                  id="descripcion"
                  name="descripcion"
                  value={formData.descripcion}
                  onChange={handleInputChange}
                  rows="4"
                />
              </div>

              <div className="form-group">
                <label htmlFor="precio">Precio *</label>
                <input
                  type="number"
                  id="precio"
                  name="precio"
                  step="0.01"
                  value={formData.precio}
                  onChange={handleInputChange}
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="precio_descuento">Precio con Descuento</label>
                <input
                  type="number"
                  id="precio_descuento"
                  name="precio_descuento"
                  step="0.01"
                  value={formData.precio_descuento}
                  onChange={handleInputChange}
                />
              </div>

              <div className="form-group">
                <label htmlFor="stock">Stock</label>
                <input
                  type="number"
                  id="stock"
                  name="stock"
                  value={formData.stock}
                  onChange={handleInputChange}
                />
              </div>

              <div className="form-group">
                <label htmlFor="marca_id">Marca</label>
                <select
                  id="marca_id"
                  name="marca_id"
                  value={formData.marca_id}
                  onChange={handleInputChange}
                >
                  <option value="">Seleccionar marca</option>
                  {marcas.map(marca => (
                    <option key={marca.id} value={marca.id}>
                      {marca.nombre}
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label htmlFor="deporte">Deporte</label>
                <input
                  type="text"
                  id="deporte"
                  name="deporte"
                  value={formData.deporte}
                  onChange={handleInputChange}
                />
              </div>

              <div className="form-group">
                <div className="checkbox-group">
                  <label>
                    <input
                      type="checkbox"
                      name="esta_activo"
                      checked={formData.esta_activo}
                      onChange={handleInputChange}
                    />
                    Producto Activo
                  </label>
                </div>
              </div>

              <div className="form-group">
                <div className="checkbox-group">
                  <label>
                    <input
                      type="checkbox"
                      name="destacado"
                      checked={formData.destacado}
                      onChange={handleInputChange}
                    />
                    Producto Destacado
                  </label>
                </div>
              </div>

              <div className="form-group form-group-full">
                <label>Categorías</label>
                <div className="select-multiple">
                  {categorias.map(categoria => (
                    <div key={categoria.id} className="checkbox-item">
                      <label>
                        <input
                          type="checkbox"
                          checked={formData.categorias.includes(categoria.id)}
                          onChange={(e) => {
                            const isChecked = e.target.checked;
                            const nuevasCategorias = isChecked
                              ? [...formData.categorias, categoria.id]
                              : formData.categorias.filter(id => id !== categoria.id);
                            handleSelectChange('categorias', nuevasCategorias);
                          }}
                        />
                        {categoria.nombre}
                      </label>
                    </div>
                  ))}
                </div>
                {categorias.length === 0 && (
                  <p className="help-text">No hay categorías disponibles</p>
                )}
              </div>

              <div className="form-group form-group-full">
                <label>Colecciones</label>
                <div className="select-multiple">
                  {colecciones.map(coleccion => (
                    <div key={coleccion.id} className="checkbox-item">
                      <label>
                        <input
                          type="checkbox"
                          checked={formData.colecciones.includes(coleccion.id)}
                          onChange={(e) => {
                            const isChecked = e.target.checked;
                            const nuevasColecciones = isChecked
                              ? [...formData.colecciones, coleccion.id]
                              : formData.colecciones.filter(id => id !== coleccion.id);
                            handleSelectChange('colecciones', nuevasColecciones);
                          }}
                        />
                        {coleccion.nombre}
                      </label>
                    </div>
                  ))}
                </div>
                {colecciones.length === 0 && (
                  <p className="help-text">No hay colecciones disponibles</p>
                )}
              </div>
            </div>
          </div>
        )}

        {activeTab === 'variantes' && (
          <div className="tab-content">
            <div className="variantes-section">
              <div className="section-header">
                <h3>Variantes del Producto</h3>
                <button 
                  type="button" 
                  onClick={agregarVariante}
                  className="btn btn-primary btn-sm"
                >
                  + Agregar Variante
                </button>
              </div>

              {formData.variantes.map((variante, varianteIndex) => (
                <div key={variante.id || varianteIndex} className="variante-card">
                  <div className="variante-header">
                    <h4>Variante #{varianteIndex + 1}</h4>
                    <button 
                      type="button"
                      onClick={() => eliminarVariante(varianteIndex)}
                      className="btn btn-danger btn-sm"
                    >
                      Eliminar
                    </button>
                  </div>

                  <div className="variante-content">
                    <div className="form-grid">
                      <div className="form-group">
                        <label>SKU Variante *</label>
                        <input
                          type="text"
                          value={variante.sku}
                          onChange={(e) => actualizarVariante(varianteIndex, 'sku', e.target.value)}
                          required
                        />
                      </div>

                      <div className="form-group">
                        <label>Nombre *</label>
                        <input
                          type="text"
                          value={variante.nombre}
                          onChange={(e) => actualizarVariante(varianteIndex, 'nombre', e.target.value)}
                          required
                        />
                      </div>

                      <div className="form-group">
                        <label>Stock</label>
                        <input
                          type="number"
                          value={variante.stock}
                          onChange={(e) => actualizarVariante(varianteIndex, 'stock', parseInt(e.target.value) || 0)}
                        />
                      </div>

                      <div className="form-group">
                        <label>Precio Específico</label>
                        <input
                          type="number"
                          step="0.01"
                          value={variante.precio || ''}
                          onChange={(e) => actualizarVariante(varianteIndex, 'precio', e.target.value)}
                          placeholder="Usar precio del producto"
                        />
                      </div>

                      <div className="form-group">
                        <div className="checkbox-group">
                          <label>
                            <input
                              type="checkbox"
                              checked={variante.activa}
                              onChange={(e) => actualizarVariante(varianteIndex, 'activa', e.target.checked)}
                            />
                            Variante Activa
                          </label>
                        </div>
                      </div>
                    </div>

                    {/* ATRIBUTOS DE LA VARIANTE */}
                    <div className="atributos-section">
                      <div className="subsection-header">
                        <h5>Atributos</h5>
                        <button 
                          type="button"
                          onClick={() => agregarAtributoVariante(varianteIndex)}
                          className="btn btn-secondary btn-sm"
                        >
                          + Agregar Atributo
                        </button>
                      </div>

                      {(variante.atributos || []).map((atributo, atributoIndex) => (
                        <div key={atributo.id || atributoIndex} className="atributo-row">
                          <input
                            type="text"
                            placeholder="Nombre (ej: Talle, Color)"
                            value={atributo.nombre}
                            onChange={(e) => actualizarAtributoVariante(varianteIndex, atributoIndex, 'nombre', e.target.value)}
                          />
                          <input
                            type="text"
                            placeholder="Valor (ej: 40, Rojo)"
                            value={atributo.valor}
                            onChange={(e) => actualizarAtributoVariante(varianteIndex, atributoIndex, 'valor', e.target.value)}
                          />
                          <button 
                            type="button"
                            onClick={() => eliminarAtributoVariante(varianteIndex, atributoIndex)}
                            className="btn btn-danger btn-sm"
                          >
                            ×
                          </button>
                        </div>
                      ))}
                    </div>

                    {/* IMÁGENES DE LA VARIANTE */}
                    <div className="imagenes-variante-section">
                      <div className="subsection-header">
                        <h5>Imágenes de la Variante</h5>
                        <button 
                          type="button"
                          onClick={() => agregarImagenVariante(varianteIndex)}
                          className="btn btn-secondary btn-sm"
                        >
                          + Agregar Imagen
                        </button>
                      </div>

                      {(variante.imagenes || []).map((imagen, imagenIndex) => (
                        <div key={imagen.id || imagenIndex} className="imagen-variante-row">
                          <div className="imagen-fields">
                            <div className="form-group">
                              <label>URL de la Imagen *</label>
                              <input
                                type="url"
                                placeholder="/uploads/productos/imagen.jpg"
                                value={imagen.url}
                                onChange={(e) => actualizarImagenVariante(varianteIndex, imagenIndex, 'url', e.target.value)}
                                required
                              />
                            </div>
                            
                            <div className="form-group">
                              <label>Texto Alternativo</label>
                              <input
                                type="text"
                                placeholder="Descripción de la imagen"
                                value={imagen.alt_text}
                                onChange={(e) => actualizarImagenVariante(varianteIndex, imagenIndex, 'alt_text', e.target.value)}
                              />
                            </div>

                            <div className="form-group checkbox-group">
                              <label>
                                <input
                                  type="checkbox"
                                  checked={imagen.es_principal}
                                  onChange={(e) => actualizarImagenVariante(varianteIndex, imagenIndex, 'es_principal', e.target.checked)}
                                />
                                Imagen Principal
                              </label>
                            </div>
                          </div>

                          <div className="imagen-preview">
                            {imagen.url && (
                              <img 
                                src={imagen.url} 
                                alt={imagen.alt_text || 'Vista previa'} 
                                className="preview-img"
                                onError={(e) => { e.target.style.display = 'none'; }}
                              />
                            )}
                          </div>

                          <button 
                            type="button"
                            onClick={() => eliminarImagenVariante(varianteIndex, imagenIndex)}
                            className="btn btn-danger btn-sm"
                          >
                            Eliminar Imagen
                          </button>
                        </div>
                      ))}

                      {(!variante.imagenes || variante.imagenes.length === 0) && (
                        <p className="no-images-message">
                          No hay imágenes para esta variante. 
                          <button 
                            type="button"
                            onClick={() => agregarImagenVariante(varianteIndex)}
                            className="btn-link"
                          >
                            Agregar la primera imagen
                          </button>
                        </p>
                      )}
                    </div>
                  </div>
                </div>
              ))}

              {formData.variantes.length === 0 && (
                <div className="empty-state">
                  <p>No hay variantes definidas para este producto.</p>
                  <button 
                    type="button" 
                    onClick={agregarVariante}
                    className="btn btn-primary"
                  >
                    Crear Primera Variante
                  </button>
                </div>
              )}
            </div>
          </div>
        )}

        {activeTab === 'imagenes' && (
          <div className="tab-content">
            <div className="imagenes-producto-section">
              <h3>Imágenes del Producto Principal</h3>
              <p className="help-text">
                Estas son las imágenes principales del producto. Para imágenes específicas de variantes, 
                ve a la pestaña "Variantes".
              </p>
              {/* Aquí puedes agregar la funcionalidad para imágenes del producto principal */}
            </div>
          </div>
        )}
      </form>
    </div>
  );
};

export default ProductosForm;