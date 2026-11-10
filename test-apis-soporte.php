<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test APIs de Soporte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Test de APIs de Soporte</h2>
        
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Marcas</h5>
                    </div>
                    <div class="card-body">
                        <button onclick="testAPI('marcas')" class="btn btn-primary btn-sm">Test Marcas</button>
                        <div id="marcas-result" class="mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Deportes</h5>
                    </div>
                    <div class="card-body">
                        <button onclick="testAPI('deportes')" class="btn btn-primary btn-sm">Test Deportes</button>
                        <div id="deportes-result" class="mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Categorías</h5>
                    </div>
                    <div class="card-body">
                        <button onclick="testAPI('categorias')" class="btn btn-primary btn-sm">Test Categorías</button>
                        <div id="categorias-result" class="mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Colecciones</h5>
                    </div>
                    <div class="card-body">
                        <button onclick="testAPI('colecciones')" class="btn btn-primary btn-sm">Test Colecciones</button>
                        <div id="colecciones-result" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <button onclick="testAllAPIs()" class="btn btn-success">Test Todos los APIs</button>
            </div>
        </div>
    </div>

    <script>
        async function testAPI(tipo) {
            const resultDiv = document.getElementById(`${tipo}-result`);
            resultDiv.innerHTML = 'Cargando...';
            
            try {
                const response = await fetch(`./api-${tipo}-simple.php`);
                const data = await response.json();
                
                if (data.success) {
                    const items = data[tipo];
                    let html = `<small class="text-success">✓ ${items.length} items</small><br>`;
                    items.forEach(item => {
                        html += `<small>${item.id}: ${item.nombre}</small><br>`;
                    });
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<small class="text-danger">Error: ${data.error}</small>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<small class="text-danger">Error: ${error.message}</small>`;
            }
        }
        
        function testAllAPIs() {
            testAPI('marcas');
            testAPI('deportes');
            testAPI('categorias');
            testAPI('colecciones');
        }
        
        // Test automático al cargar
        window.onload = function() {
            testAllAPIs();
        };
    </script>
</body>
</html>