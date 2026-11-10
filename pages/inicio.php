<?php
// pages/login.php
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Cargar tema inmediatamente para evitar flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('stadium-theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
            }
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="includes/fav.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php include __DIR__ . '/../includes/sweetalert-helpers.js'; ?>
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #171717 0%, #FE3333 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #171717;
            padding: 2.5rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 8px 32px 0 rgba(254, 51, 51, 0.15);
            max-width: 400px;
            width: 100%;
        }
        .form-control {
            background: #222;
            color: #fff;
            border: 1px solid #FE3333;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem #FE333355;
            border-color: #FE3333;
            background: #222;
            color: #fff;
        }
        .login-title {
            font-weight: 700;
            color: #FE3333;
        }
        .btn-primary {
            background-color: #FE3333;
            border-color: #FE3333;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #c82323;
            border-color: #c82323;
        }
        .form-label, .alert {
            color: #fff;
        }
        .alert-danger {
            background-color: #FE3333;
            border-color: #c82323;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center login-title mb-4">Iniciar Sesión</h2>
        <form id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="username" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>

    </div>
    
    <!-- Theme toggle button -->
    <div class="position-fixed top-0 end-0 p-3">
        <button class="btn btn-outline-secondary" onclick="toggleTheme()" id="themeToggleBtn">
            <i class="bi bi-sun-fill" id="themeIcon"></i>
        </button>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/blueimp-md5/2.19.0/js/md5.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
    // Cargar tema al inicializar la página
    document.addEventListener('DOMContentLoaded', function() {
        loadTheme();
    });
    
    // Importar solo la función validarUsuario desde el archivo JS (sin módulo)
    // Copia la función aquí si no puedes usar import/export
    async function validarUsuario(email, passwordMd5) {
        return new Promise((resolve, reject) => {
            console.log('Llamando a la API con XMLHttpRequest...');
            const xhr = new XMLHttpRequest();
            xhr.open('POST', './api/login.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onload = function() {
                console.log('XHR Response status:', xhr.status);
                console.log('XHR Response text:', xhr.responseText);
                
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        console.log('Response data:', data);
                        resolve(data);
                    } catch (e) {
                        console.error('Error parsing JSON:', e);
                        resolve({ success: false, error: 'Error parsing response' });
                    }
                } else {
                    console.error('HTTP error:', xhr.status);
                    resolve({ success: false, error: `HTTP error: ${xhr.status}` });
                }
            };
            
            xhr.onerror = function() {
                console.error('XHR Network error');
                resolve({ success: false, error: 'Network error' });
            };
            
            xhr.send(JSON.stringify({ email, passwordMd5 }));
        });
    }

    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        
        console.log('Email:', email);
        console.log('Password:', password);
        
        // Calcular md5 de la clave antes de validar
        const passwordMd5 = md5(password);
        console.log('Password MD5:', passwordMd5);
        
        const response = await validarUsuario(email, passwordMd5);
        console.log('Response:', response);
        
        if (response.success) {
            console.log('Login exitoso, redirigiendo...');
            showToast('success', 'Bienvenido al sistema');
            setTimeout(() => {
                window.location.href = 'dashboard';
            }, 1000);
        } else {
            console.log('Login fallido:', response.error || 'Sin error específico');
            showErrorAlert('Error de acceso', 'Usuario o contraseña incorrectos');
        }
    });
    </script>
</body>
</html>
