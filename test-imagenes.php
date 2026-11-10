<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Imágenes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Test de Guardado de Imágenes</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test con Imágenes</h5>
                    </div>
                    <div class="card-body">
                        <button onclick="testWithImages()" class="btn btn-primary">Test UPDATE con Imágenes</button>
                        <button onclick="testCreateWithImages()" class="btn btn-success">Test CREATE con Imágenes</button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Respuesta</h5>
                    </div>
                    <div class="card-body">
                        <div id="result"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Verificar en BD</h5>
                    </div>
                    <div class="card-body">
                        <button onclick="checkImages()" class="btn btn-info">Ver Imágenes en BD</button>
                        <div id="db-result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function testWithImages() {
            const productData = {
                id: 1,
                sku: "NIK-001-TEST",
                nombre: "Nike Air Max 90 - Con Imágenes",
                descripcion: "Test con imágenes",
                precio: 155.99,
                precio_descuento: 140.00,
                stock: 15,
                marca_id: 1,
                deporte: "Fútbol",
                esta_activo: 1,
                destacado: 0,
                categorias: [1, 2],
                colecciones: [],
                imagenes: [
                    {
                        url: "https://example.com/nike-air-max-90-1.jpg",
                        alt_text: "Nike Air Max 90 - Vista frontal",
                        es_principal: 1,
                        orden: 1
                    },
                    {
                        url: "https://example.com/nike-air-max-90-2.jpg",
                        alt_text: "Nike Air Max 90 - Vista lateral",
                        es_principal: 0,
                        orden: 2
                    }
                ]
            };
            
            await testSave('PUT', productData, 'Actualización con imágenes');
        }
        
        async function testCreateWithImages() {
            const productData = {
                sku: "TEST-IMG-001",
                nombre: "Producto Test con Imágenes",
                descripcion: "Producto creado para test de imágenes",
                precio: 99.99,
                stock: 10,
                marca_id: 2,
                deporte: "Basketball",
                esta_activo: 1,
                destacado: 1,
                categorias: [1],
                colecciones: [],
                imagenes: [
                    {
                        url: "https://example.com/test-product-1.jpg",
                        alt_text: "Producto test - Principal",
                        es_principal: 1,
                        orden: 1
                    }
                ]
            };
            
            await testSave('POST', productData, 'Creación con imágenes');
        }
        
        async function testSave(method, productData, operation) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = `Enviando ${operation}...`;
            
            try {
                const response = await fetch('./api-productos-simple.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(productData)
                });
                
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
                        <strong>Resultado ${operation}:</strong><br>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Error:</strong> ${error.message}
                    </div>
                `;
            }
        }
        
        async function checkImages() {
            const resultDiv = document.getElementById('db-result');
            resultDiv.innerHTML = 'Consultando base de datos...';
            
            try {
                // Crear un API simple para consultar imágenes
                const response = await fetch('./check-images.php');
                const data = await response.json();
                
                if (data.success) {
                    let html = '<h6>Imágenes en la base de datos:</h6>';
                    if (data.imagenes.length > 0) {
                        html += '<table class="table table-sm"><thead><tr><th>ID</th><th>Producto ID</th><th>URL</th><th>Alt Text</th><th>Principal</th></tr></thead><tbody>';
                        data.imagenes.forEach(img => {
                            html += `<tr>
                                <td>${img.id}</td>
                                <td>${img.producto_id}</td>
                                <td style="max-width: 200px; word-break: break-all;">${img.url}</td>
                                <td>${img.alt_text || 'N/A'}</td>
                                <td>${img.es_principal ? 'Sí' : 'No'}</td>
                            </tr>`;
                        });
                        html += '</tbody></table>';
                    } else {
                        html += '<p class="text-muted">No hay imágenes en la base de datos.</p>';
                    }
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Error: ${data.error}</div>`;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>