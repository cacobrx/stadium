<?php
// debug_session.php - Para debugging del sistema de autenticación
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Estado de la Sesión</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Información de Sesión</h5>
                    </div>
                    <div class="card-body">
                        <pre><?php print_r(getSessionInfo()); ?></pre>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Estado de Autenticación</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>¿Logueado?:</strong> <?php echo isLoggedIn() ? 'SÍ' : 'NO'; ?></p>
                        <p><strong>¿Sesión expirada?:</strong> <?php echo isSessionExpired() ? 'SÍ' : 'NO'; ?></p>
                        <p><strong>Usuario actual:</strong> <?php echo getCurrentUser() ? getCurrentUser()['nombre'] : 'Ninguno'; ?></p>
                        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="inicio" class="btn btn-primary">Ir al Login</a>
            <a href="dashboard" class="btn btn-success">Ir al Dashboard</a>
            <a href="logout" class="btn btn-danger">Logout</a>
        </div>
    </div>
</body>
</html>