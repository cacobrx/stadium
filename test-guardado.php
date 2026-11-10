<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Guardado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Test de Guardado de Producto</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Datos a Enviar</h5>
                    </div>
                    <div class="card-body">
                        <button onclick="testUpdate()" class="btn btn-warning">Test UPDATE (Producto 1)</button>
                        <button onclick="testCreate()" class="btn btn-success">Test CREATE</button>
                        <div id="request-data" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Respuesta del Servidor</h5>
                    </div>
                    <div class="card-body">
                        <div id="response-data"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function testUpdate() {
            const productData = {
                id: 1,
                sku: 'NIK-001-MOD',
                nombre: 'Nike Air Max 90 - Modificado',
                descripcion: 'Descripción modificada para test',
                precio: 155.99,
                precio_descuento: 140.00,
                stock: 15,
                marca_id: 1,
                deporte: 'Fútbol',
                esta_activo: 1,
                destacado: 0,
                peso: '500',
                alto: '15',
                ancho: '30',
                profundidad: '12',
                categorias: [1, 2],
                colecciones: [],
                imagenes: []
            };
            
            await testSave('PUT', productData, 'Actualización');
        }
        
        async function testCreate() {
            const productData = {
                sku: 'TEST-001',
                nombre: 'Producto de Test',
                descripcion: 'Este es un producto creado para test',
                precio: 99.99,
                precio_descuento: null,
                stock: 10,
                marca_id: 2,
                deporte: 'Basketball',
                esta_activo: 1,
                destacado: 1,
                peso: '300',
                alto: '10',
                ancho: '25',
                profundidad: '8',
                categorias: [1],
                colecciones: [],
                imagenes: []
            };
            
            await testSave('POST', productData, 'Creación');
        }
        
        async function testSave(method, productData, operation) {
            const requestDiv = document.getElementById('request-data');
            const responseDiv = document.getElementById('response-data');
            
            requestDiv.innerHTML = `
                <h6>${operation} - Método ${method}</h6>
                <pre style="font-size: 11px;">${JSON.stringify(productData, null, 2)}</pre>
            `;
            
            responseDiv.innerHTML = 'Enviando...';
            
            try {
                const response = await fetch('./api-productos-simple.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(productData)
                });
                
                const responseText = await response.text();
                
                responseDiv.innerHTML = `
                    <div class="alert ${response.ok ? 'alert-info' : 'alert-danger'}">
                        <strong>Status:</strong> ${response.status} ${response.statusText}<br>
                        <strong>Headers:</strong> ${response.headers.get('content-type')}<br>
                        <strong>Respuesta:</strong><br>
                        <pre style="font-size: 11px;">${responseText}</pre>
                    </div>
                `;
                
                // Intentar parsear como JSON
                try {
                    const data = JSON.parse(responseText);
                    responseDiv.innerHTML += `
                        <div class="alert ${data.success ? 'alert-success' : 'alert-danger'}">
                            <strong>JSON Parseado:</strong><br>
                            <pre style="font-size: 11px;">${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } catch (e) {
                    responseDiv.innerHTML += `
                        <div class="alert alert-warning">
                            <strong>No se pudo parsear como JSON:</strong> ${e.message}
                        </div>
                    `;
                }
                
            } catch (error) {
                responseDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Error de red:</strong> ${error.message}
                    </div>
                `;
            }
        }
    </script>
</body>
</html>