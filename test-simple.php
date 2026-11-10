<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API Productos - Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Test API Productos Simplificado</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Estado de Sesión</h5>
                    </div>
                    <div class="card-body">
                        <div id="session-info">Verificando...</div>
                        <button onclick="checkSession()" class="btn btn-primary btn-sm">Verificar Sesión</button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test API</h5>
                    </div>
                    <div class="card-body">
                        <button onclick="testAPI()" class="btn btn-success">Cargar Productos</button>
                        <div id="api-result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Productos</h5>
                    </div>
                    <div class="card-body">
                        <div id="productos-list">No hay productos cargados</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkSession() {
            fetch('check-session.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('session-info').innerHTML = `
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                })
                .catch(error => {
                    document.getElementById('session-info').innerHTML = `Error: ${error}`;
                });
        }
        
        function testAPI() {
            const resultDiv = document.getElementById('api-result');
            const productosDiv = document.getElementById('productos-list');
            
            resultDiv.innerHTML = 'Cargando...';
            
            fetch('api-productos-simple.php')
                .then(response => response.json())
                .then(data => {
                    resultDiv.innerHTML = `
                        <div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
                            <strong>Resultado:</strong><br>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                    
                    if (data.success && data.productos) {
                        let html = '<div class="table-responsive"><table class="table table-striped">';
                        html += '<thead><tr><th>ID</th><th>SKU</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Marca</th></tr></thead><tbody>';
                        
                        data.productos.forEach(producto => {
                            html += `<tr>
                                <td>${producto.id}</td>
                                <td>${producto.sku || ''}</td>
                                <td>${producto.nombre}</td>
                                <td>$${producto.precio}</td>
                                <td>${producto.stock}</td>
                                <td>${producto.marca_nombre || 'Sin marca'}</td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table></div>';
                        productosDiv.innerHTML = html;
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Error de red:</strong> ${error}
                        </div>
                    `;
                });
        }
        
        // Verificar sesión al cargar
        checkSession();
    </script>
</body>
</html>