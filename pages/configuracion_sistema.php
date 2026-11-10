<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Stadium</title>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('stadium-theme');
            if (savedTheme) document.documentElement.setAttribute('data-bs-theme', savedTheme);
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
        <?php include __DIR__ . '/../includes/dashboard-styles.css'; ?>
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/menu.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="top-nav d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <button class="btn btn-outline-primary" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
                <h5 class="mb-0 d-inline">Configuración</h5>
            </div>
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="toggleTheme()">
                    <i class="bi bi-sun-fill" id="themeIcon"></i>
                </button>
                <span class="text-muted">Bienvenido, <?php echo htmlspecialchars(getCurrentUser()['nombre']); ?></span>
            </div>
        </div>

        <div class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-gear me-2"></i>Configuración</h2>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-envelope-at me-2"></i>Correo (SMTP)</h5>
                </div>
                <div class="card-body">
                    <form id="mailForm" autocomplete="off" onsubmit="event.preventDefault(); guardar();">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mail_host" class="form-label">Servidor SMTP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mail_host" placeholder="smtp.ejemplo.com" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="mail_encryption" class="form-label">Seguridad</label>
                                <select class="form-select" id="mail_encryption">
                                    <option value="ssl">SSL</option>
                                    <option value="tls">TLS (STARTTLS)</option>
                                    <option value="none">Ninguna</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="mail_port" class="form-label">Puerto <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="mail_port" min="1" max="65535" placeholder="465" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mail_username" class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="mail_username" autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mail_password" class="form-label">Clave</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="mail_password" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()" title="Mostrar/ocultar">
                                        <i class="bi bi-eye" id="passIcon"></i>
                                    </button>
                                </div>
                                <div class="form-text" id="passHelp">Dejar vacío para mantener la clave actual.</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mail_from_email" class="form-label">Email remitente</label>
                                <input type="email" class="form-control" id="mail_from_email" placeholder="no-reply@ejemplo.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mail_from_name" class="form-label">Nombre remitente</label>
                                <input type="text" class="form-control" id="mail_from_name" placeholder="Stadium">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mail_presupuestos_destino" class="form-label">Enviar presupuestos web a</label>
                                <input type="email" class="form-control" id="mail_presupuestos_destino" placeholder="info@stadiumdeportes.com">
                                <div class="form-text">Destinatario del aviso de nuevo presupuesto recibido desde la web.</div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="probar()">
                                <i class="bi bi-send"></i> Enviar correo de prueba
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <script>
        const CAMPOS = ['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption',
                        'mail_from_email', 'mail_from_name', 'mail_presupuestos_destino'];

        document.addEventListener('DOMContentLoaded', cargar);

        async function cargar() {
            try {
                const res = await fetch('./api/configuracion.php', { credentials: 'same-origin' });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Error al cargar');
                const c = data.config || {};
                CAMPOS.forEach(k => {
                    if (k !== 'mail_password' && c[k] !== undefined && c[k] !== null) {
                        document.getElementById(k).value = c[k];
                    }
                });
                document.getElementById('mail_password').placeholder = data.mail_password_cargada ? '•••••••• (guardada)' : '';
                document.getElementById('passHelp').textContent = data.mail_password_cargada
                    ? 'Dejar vacío para mantener la clave actual.'
                    : 'No hay clave guardada.';
            } catch (err) {
                console.error(err);
                CRUDAlerts.connectionError();
            }
        }

        function leerFormulario() {
            const datos = {};
            CAMPOS.forEach(k => datos[k] = document.getElementById(k).value.trim());
            return datos;
        }

        async function guardar() {
            const form = document.getElementById('mailForm');
            if (!form.reportValidity()) return;
            try {
                const res = await fetch('./api/configuracion.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(leerFormulario()),
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('mail_password').value = '';
                    CRUDAlerts.updated('Configuración');
                    cargar();
                } else {
                    CRUDAlerts.updateError(data.error);
                }
            } catch (err) {
                console.error(err);
                CRUDAlerts.connectionError();
            }
        }

        async function probar() {
            const { value: para } = await Swal.fire({
                title: 'Enviar correo de prueba',
                input: 'email',
                inputLabel: 'Destinatario',
                inputValue: document.getElementById('mail_presupuestos_destino').value,
                showCancelButton: true,
                confirmButtonText: 'Enviar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d6efd'
            });
            if (!para) return;

            Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch('./api/configuracion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'test_mail', para, config: leerFormulario() }),
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Correo enviado', text: `Se envió un correo de prueba a ${para}.`, confirmButtonColor: '#0d6efd' });
                } else {
                    Swal.fire({ icon: 'error', title: 'No se pudo enviar', text: data.error || 'Error desconocido', confirmButtonColor: '#0d6efd' });
                }
            } catch (err) {
                console.error(err);
                CRUDAlerts.connectionError();
            }
        }

        function togglePassword() {
            const input = document.getElementById('mail_password');
            const icon = document.getElementById('passIcon');
            const mostrar = input.type === 'password';
            input.type = mostrar ? 'text' : 'password';
            icon.className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
    </script>
</body>
</html>
