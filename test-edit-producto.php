<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Edit Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Test de Edición de Producto</h2>
        
        <div class="mb-3">
            <label>ID del Producto:</label>
            <input type="number" id="productId" value="1" class="form-control" style="width: 100px; display: inline;">
            <button onclick="testLoadProduct()" class="btn btn-primary">Cargar Producto</button>
        </div>
        
        <div id="result" class="mt-3"></div>
        
        <div id="productForm" style="display: none;" class="mt-4">
            <h4>Datos del Producto:</h4>
            <form>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>SKU:</label>
                            <input type="text" id="sku" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Nombre:</label>
                            <input type="text" id="nombre" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Precio:</label>
                            <input type="number" id="precio" step="0.01" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Stock:</label>
                            <input type="number" id="stock" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Marca ID:</label>
                            <input type="number" id="marca_id" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Descripción:</label>
                            <textarea id="descripcion" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="testSaveProduct()" class="btn btn-success">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <script>
        async function testLoadProduct() {
            const id = document.getElementById('productId').value;
            const resultDiv = document.getElementById('result');
            const formDiv = document.getElementById('productForm');
            
            resultDiv.innerHTML = 'Cargando...';
            formDiv.style.display = 'none';
            
            try {
                const response = await fetch(`./api-productos-simple.php?id=${id}`);
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
                        <strong>Respuesta del API:</strong><br>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
                
                if (data.success && data.producto) {
                    const producto = data.producto;
                    
                    // Llenar formulario
                    document.getElementById('sku').value = producto.sku || '';
                    document.getElementById('nombre').value = producto.nombre || '';
                    document.getElementById('precio').value = producto.precio || '';
                    document.getElementById('stock').value = producto.stock || '';
                    document.getElementById('marca_id').value = producto.marca_id || '';
                    document.getElementById('descripcion').value = producto.descripcion || '';
                    
                    formDiv.style.display = 'block';
                }
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Error:</strong> ${error.message}
                    </div>
                `;
            }
        }
        
        async function testSaveProduct() {
            const id = document.getElementById('productId').value;
            const resultDiv = document.getElementById('result');
            
            const productData = {
                id: parseInt(id),
                sku: document.getElementById('sku').value,
                nombre: document.getElementById('nombre').value,
                precio: parseFloat(document.getElementById('precio').value),
                stock: parseInt(document.getElementById('stock').value),
                marca_id: parseInt(document.getElementById('marca_id').value) || null,
                descripcion: document.getElementById('descripcion').value
            };
            
            resultDiv.innerHTML = 'Guardando...';
            
            try {
                const response = await fetch('./api-productos-simple.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(productData)
                });
                
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
                        <strong>Resultado del guardado:</strong><br>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Error al guardar:</strong> ${error.message}
                    </div>
                `;
            }
        }
        
        // Cargar producto 1 automáticamente
        window.onload = function() {
            testLoadProduct();
        };
    </script>
</body>
</html>