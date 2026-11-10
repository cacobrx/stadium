<?php
session_start();
// Simular autenticación para pruebas
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@test.com';
$_SESSION['user_nombre'] = 'Usuario de Prueba';
$_SESSION['login_time'] = time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Productos - Stadium</title>
    
    <!-- React CDN -->
    <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    
    <!-- Estilos del formulario -->
    <link rel="stylesheet" href="frontend/ProductosForm.css">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .main-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-bar {
            background: #e9ecef;
            padding: 10px 15px;
            border-radius: 4px;
            margin-top: 15px;
            font-size: 14px;
            font-family: monospace;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f8f9fa;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #e9ecef;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            border-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="main-header">
            <h1>Sistema de Gestión de Productos</h1>
            <p>Formulario completo con categorías y colecciones</p>
            
            <div>
                <button class="btn btn-primary" onclick="mostrarFormulario()">Nuevo Producto</button>
                <button class="btn" onclick="editarProducto()">Editar Producto #1</button>
                <button class="btn" onclick="verificarAPIs()">Verificar APIs</button>
                <button class="btn" onclick="toggleDebug()">Toggle Debug</button>
            </div>
            
            <div id="status-bar" class="status-bar" style="display: none;">
                Estado: Cargando...
            </div>
        </div>
        
        <div id="app-root"></div>
    </div>

    <!-- Cargar el componente ProductosForm -->
    <script type="text/babel" src="frontend/ProductosForm.jsx"></script>
    
    <script type="text/babel">
        const { useState } = React;
        
        const App = () => {
            const [currentView, setCurrentView] = useState('lista');
            const [editingProductId, setEditingProductId] = useState(null);
            const [debugMode, setDebugMode] = useState(false);

            const handleSuccess = (data) => {
                console.log('Éxito:', data);
                updateStatus('Producto guardado exitosamente');
                alert('Operación exitosa');
                setCurrentView('lista');
            };

            const handleCancel = () => {
                setCurrentView('lista');
                setEditingProductId(null);
            };

            // Función global para verificar APIs
            window.verificarAPIs = async () => {
                updateStatus('Verificando APIs...');
                
                try {
                    const apis = [
                        { name: 'Marcas', url: '/stadium/api/marcas.php' },
                        { name: 'Categorías', url: '/stadium/api/categorias.php' },
                        { name: 'Colecciones', url: '/stadium/api/colecciones.php' },
                        { name: 'Marcas Simple', url: '/stadium/api-marcas-simple.php' },
                        { name: 'Categorías Simple', url: '/stadium/api-categorias-simple.php' },
                        { name: 'Colecciones Simple', url: '/stadium/api-colecciones-simple.php' }
                    ];

                    const results = await Promise.all(
                        apis.map(async api => {
                            try {
                                const response = await fetch(api.url);
                                const data = await response.json();
                                return { ...api, success: data.success, data };
                            } catch (error) {
                                return { ...api, success: false, error: error.message };
                            }
                        })
                    );

                    console.table(results);
                    updateStatus('APIs verificadas - ver consola');
                } catch (error) {
                    updateStatus('Error verificando APIs: ' + error.message);
                }
            };

            // Función global para mostrar formulario
            window.mostrarFormulario = () => {
                setCurrentView('form');
                setEditingProductId(null);
                updateStatus('Mostrando formulario de nuevo producto');
            };

            // Función global para editar producto
            window.editarProducto = () => {
                setCurrentView('form');
                setEditingProductId(1);
                updateStatus('Mostrando formulario de edición');
            };

            // Función global para toggle debug
            window.toggleDebug = () => {
                setDebugMode(!debugMode);
                updateStatus('Debug mode: ' + (!debugMode ? 'activado' : 'desactivado'));
            };

            if (currentView === 'form') {
                return React.createElement(ProductosForm, {
                    productoId: editingProductId,
                    onSuccess: handleSuccess,
                    onCancel: handleCancel
                });
            }

            return React.createElement('div', { 
                style: { 
                    textAlign: 'center', 
                    padding: '50px',
                    background: 'white',
                    borderRadius: '8px',
                    boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                }
            }, [
                React.createElement('h2', { key: 'title' }, 'Gestión de Productos'),
                React.createElement('p', { key: 'desc' }, 'Selecciona una opción de los botones de arriba para comenzar.'),
                React.createElement('div', { key: 'actions', style: { marginTop: '20px' }}, [
                    React.createElement('button', {
                        key: 'new',
                        className: 'btn btn-primary',
                        onClick: () => {
                            setCurrentView('form');
                            setEditingProductId(null);
                        }
                    }, 'Crear Nuevo Producto'),
                    React.createElement('button', {
                        key: 'edit',
                        className: 'btn',
                        onClick: () => {
                            setCurrentView('form');
                            setEditingProductId(1);
                        }
                    }, 'Editar Producto Ejemplo')
                ])
            ]);
        };

        // Función para actualizar status
        function updateStatus(message) {
            const statusBar = document.getElementById('status-bar');
            if (statusBar) {
                statusBar.textContent = 'Estado: ' + message;
                statusBar.style.display = 'block';
                
                // Ocultar después de 3 segundos
                setTimeout(() => {
                    statusBar.style.display = 'none';
                }, 3000);
            }
        }

        // Renderizar la aplicación
        ReactDOM.render(React.createElement(App), document.getElementById('app-root'));

        // Log inicial
        console.log('Sistema de productos cargado');
        updateStatus('Sistema cargado correctamente');
    </script>
</body>
</html>