<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Debug Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Debug del Error</h2>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <button onclick="debugStep1()" class="btn btn-primary">Step 1: Test API Simple</button>
                        <button onclick="debugStep2()" class="btn btn-warning">Step 2: Test API Original</button>
                        <div id="debug-result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const testData = {
            id: 1,
            sku: "NIK-001-MOD",
            nombre: "Nike Air Max 90 - Modificado",
            precio: 155.99
        };
        
        async function debugStep1() {
            const resultDiv = document.getElementById('debug-result');
            resultDiv.innerHTML = 'Testing API debug simple...';
            
            try {
                const response = await fetch('./api-debug-simple.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(testData)
                });
                
                const responseText = await response.text();
                
                resultDiv.innerHTML = `
                    <div class="alert ${response.ok ? 'alert-success' : 'alert-danger'}">
                        <strong>API Debug Simple:</strong><br>
                        <strong>Status:</strong> ${response.status}<br>
                        <strong>Response:</strong><br>
                        <pre>${responseText}</pre>
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
        
        async function debugStep2() {
            const resultDiv = document.getElementById('debug-result');
            resultDiv.innerHTML = 'Testing API original...';
            
            try {
                const response = await fetch('./api-productos-simple.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(testData)
                });
                
                const responseText = await response.text();
                
                resultDiv.innerHTML = `
                    <div class="alert ${response.ok ? 'alert-success' : 'alert-danger'}">
                        <strong>API Original:</strong><br>
                        <strong>Status:</strong> ${response.status}<br>
                        <strong>Response:</strong><br>
                        <pre>${responseText}</pre>
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
    </script>
</body>
</html>