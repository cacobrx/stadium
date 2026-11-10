<?php
// Verificar autenticación
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pisos Deportivos - Stadium</title>
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
    <script>
        <?php include __DIR__ . '/../includes/dashboard-functions.js'; ?>
    </script>
    <style>
        <?php include __DIR__ . '/../includes/dashboard-styles.css'; ?>
        .img-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }

        /* Badges estilo estadísticas (como en tableros_marcadores) */
        .est-badge, .spec-badge, .item-badge {
            display: inline-flex; gap: 6px; background: var(--bs-light);
            padding: 6px 10px; border-radius: 4px; margin: 3px; font-size: 0.9rem;
            align-items: center;
        }
        [data-bs-theme="dark"] .est-badge, [data-bs-theme="dark"] .spec-badge, [data-bs-theme="dark"] .item-badge {
            background: var(--bs-gray-700);
        }
        .est-badge input, .spec-badge input, .item-badge input {
            font-size: 0.9rem; padding: 5px 8px; height: 32px; width: auto;
            border: 1px solid var(--bs-border-color); border-radius: 3px;
        }
        /* tamaños similares a tableros_marcadores */
        .est-badge input.ev { width: 80px; }
        .est-badge input.ee { width: 120px; }
        .spec-badge input.spe { width: 120px; }
        .spec-badge input.spv { width: 150px; }
        /* inputs compactos para listas dentro de tipos */
        .item-badge input { width: 120px; }

        .nav-tabs .nav-link { font-size: 0.95rem; }
        .nav-tabs .nav-link.active { font-weight: 500; }
        .tipo-detail-row {
            background: var(--bs-light);
        }
        [data-bs-theme="dark"] .tipo-detail-row {
            background: var(--bs-gray-800);
        }
        .tipo-detail-content {
            padding: 15px;
        }
        .tipo-section {
            margin-bottom: 15px;
        }
        .tipo-section label {
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }

        /* Mejorar visibilidad de botones "outline" y enlaces en modo oscuro (más específico y forzado) */
        [data-bs-theme="dark"] .btn-outline-primary,
        [data-bs-theme="dark"] .btn-outline-primary.btn-sm {
            color: #fff !important;
            background-color: var(--bs-primary) !important;
            border-color: var(--bs-primary) !important;
        }
        [data-bs-theme="dark"] .btn-outline-secondary,
        [data-bs-theme="dark"] .btn-outline-secondary.btn-sm {
            color: #fff !important;
            background-color: var(--bs-secondary) !important;
            border-color: var(--bs-secondary) !important;
        }
        [data-bs-theme="dark"] .btn-outline-danger,
        [data-bs-theme="dark"] .btn-outline-danger.btn-sm {
            color: #fff !important;
            background-color: var(--bs-danger) !important;
            border-color: var(--bs-danger) !important;
        }
        /* Asegurar que los iconos hereden el color y sean legibles */
        [data-bs-theme="dark"] .btn-outline-primary i,
        [data-bs-theme="dark"] .btn-outline-secondary i,
        [data-bs-theme="dark"] .btn-outline-danger i,
        [data-bs-theme="dark"] .btn-outline-primary .bi,
        [data-bs-theme="dark"] .btn-outline-secondary .bi,
        [data-bs-theme="dark"] .btn-outline-danger .bi {
            color: inherit !important;
            opacity: 1 !important;
        }
        /* Mantener botones de enlace legibles y con ligero fondo para contraste */
        [data-bs-theme="dark"] .btn-link {
            color: #fff !important;
            background-color: rgba(255,255,255,0.02) !important;
            text-decoration: none !important;
            border-radius: 4px;
            padding: 0.05rem 0.35rem;
        }
        [data-bs-theme="dark"] .btn-link.text-danger {
            color: var(--bs-danger) !important;
            background-color: rgba(220,53,69,0.07) !important;
        }
        /* Hover/focus visual claro en dark */
        [data-bs-theme="dark"] .btn-outline-primary:hover,
        [data-bs-theme="dark"] .btn-outline-secondary:hover,
        [data-bs-theme="dark"] .btn-outline-danger:hover,
        [data-bs-theme="dark"] .btn-link:hover {
            filter: brightness(0.9) !important;
            text-decoration: none !important;
        }

        /* Ajustes para la caja de descripción en la fila detalle */
        .tipo-detail-content .p-2.border {
            background: var(--bs-light);
            color: var(--bs-dark);
        }
        [data-bs-theme="dark"] .tipo-detail-content .p-2.border {
            background: var(--bs-gray-800) !important;
            color: var(--bs-light) !important;
            border-color: rgba(255,255,255,0.06) !important;
        }
        [data-bs-theme="dark"] .tipo-detail-content em.text-muted,
        [data-bs-theme="dark"] .tipo-detail-content .text-muted {
            color: var(--bs-gray-400) !important;
        }
        [data-bs-theme="dark"] .tipo-detail-content strong {
            color: var(--bs-light) !important;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../includes/menu.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation -->
        <div class="top-nav d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <button class="btn btn-outline-primary" onclick="abrirEnNuevaPestana()" title="Abrir en nueva pestaña">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>
                <h5 class="mb-0 d-inline">Pisos Deportivos</h5>
            </div>
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="toggleTheme()">
                    <i class="bi bi-sun-fill" id="themeIcon"></i>
                </button>
                <span class="text-muted">Bienvenido, <?php echo htmlspecialchars(getCurrentUser()['nombre']); ?></span>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Pisos Deportivos</h2>
                <div class="d-flex gap-2 align-items-center">
                    <span id="changeIndicator" class="badge bg-warning text-dark me-2" style="display: none;">
                        <i class="bi bi-exclamation-triangle"></i> Hay cambios sin guardar
                    </span>
                    <button type="button" class="btn btn-outline-secondary" id="btnRecargar" onclick="loadConfig();">
                        <i class="bi bi-arrow-clockwise"></i> Recargar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardar" onclick="saveConfig();">
                        <i class="bi bi-floppy"></i> Guardar
                    </button>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-3" id="configTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                        <i class="bi bi-gear me-1"></i>General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="hero-tab" data-bs-toggle="tab" data-bs-target="#hero" type="button" role="tab">
                        <i class="bi bi-badge-tm me-1"></i>Hero
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="beneficios-tab" data-bs-toggle="tab" data-bs-target="#beneficios" type="button" role="tab">
                        <i class="bi bi-star me-1"></i>Beneficios <span class="badge bg-secondary ms-1" id="beneficios-count">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tipos-tab" data-bs-toggle="tab" data-bs-target="#tipos" type="button" role="tab">
                        <i class="bi bi-grid-3x2-gap me-1"></i>Tipos de Pisos <span class="badge bg-secondary ms-1" id="tipos-count">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="proceso-tab" data-bs-toggle="tab" data-bs-target="#proceso" type="button" role="tab">
                        <i class="bi bi-list-ol me-1"></i>Proceso <span class="badge bg-secondary ms-1" id="proceso-count">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="galeria-tab" data-bs-toggle="tab" data-bs-target="#galeria" type="button" role="tab">
                        <i class="bi bi-grid-3x3-gap me-1"></i>Galería <span class="badge bg-secondary ms-1" id="galeria-count">0</span>
                    </button>
                </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content" id="configTabsContent">
                <!-- Tab General -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Configuración General</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Título de la Página</label>
                                    <input type="text" class="form-control" id="titulo_pagina" placeholder="Ej: Pisos Deportivos Profesionales">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Descripción</label>
                                    <input type="text" class="form-control" id="descripcion_pagina" placeholder="Descripción breve de la página">
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="esta_activo" checked>
                                        <label class="form-check-label" for="esta_activo">Página Activa</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Hero -->
                <div class="tab-pane fade" id="hero" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-badge-tm me-2"></i>Sección Hero</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Etiqueta</label>
                                    <input type="text" class="form-control" id="hero_etiqueta" placeholder="Ej: Líderes en Pisos Deportivos">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Título</label>
                                    <input type="text" class="form-control" id="hero_titulo" placeholder="Ej: Pisos Deportivos de">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Título Destacado</label>
                                    <input type="text" class="form-control" id="hero_titulo_destacado" placeholder="Ej: Clase Mundial">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" id="hero_descripcion" rows="3" placeholder="Descripción del hero"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Imagen de Fondo</label>
                                    <div class="mb-2">
                                        <input type="file" id="hero_imagen_fondo_file" class="form-control form-control-sm" accept="image/*" onchange="previewHeroImg(this)">
                                        <input type="hidden" id="hero_imagen_fondo" value="">
                                        <small class="text-muted">Formatos: JPG, PNG, GIF, WEBP</small>
                                    </div>
                                    <div id="hero-preview"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Botón Principal</label>
                                        <input type="text" class="form-control" id="hero_boton_principal" placeholder="Ej: Explorar Soluciones">
                                    </div>
                                    <div>
                                        <label class="form-label">Botón Secundario</label>
                                        <input type="text" class="form-control" id="hero_boton_secundario" placeholder="Ej: Solicitar Cotización">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Link Botón Principal</label>
                                        <input type="text" class="form-control" id="hero_boton_principal_link" placeholder="Ej: #productos">
                                    </div>
                                    <div>
                                        <label class="form-label">Link Botón Secundario</label>
                                        <input type="text" class="form-control" id="hero_boton_secundario_link" placeholder="Ej: #contacto">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Beneficios -->
                <div class="tab-pane fade" id="beneficios" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-star me-2"></i>Beneficios</h5>
                            <button class="btn btn-success btn-sm" onclick="addBeneficio()">
                                <i class="bi bi-plus"></i> Agregar Beneficio
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="50">#</th>
                                            <th width="30%">Título</th>
                                            <th>Descripción</th>
                                            <th width="60">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="beneficios-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Tipos de Pisos -->
                <div class="tab-pane fade" id="tipos" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-grid-3x2-gap me-2"></i>Tipos de Pisos</h5>
                            <button class="btn btn-success btn-sm" onclick="addTipo()">
                                <i class="bi bi-plus"></i> Agregar Tipo
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="50">#</th>
                                            <th width="20%">Nombre</th>
                                            <th width="15%">Slug</th>
                                            <th width="20%">Etiqueta</th>
                                            <th>Imagen</th>
                                            <th width="60">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tipos-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Proceso Instalación -->
                <div class="tab-pane fade" id="proceso" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-list-ol me-2"></i>Proceso de Instalación</h5>
                            <button class="btn btn-success btn-sm" onclick="addPaso()">
                                <i class="bi bi-plus"></i> Agregar Paso
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="50">#</th>
                                            <th width="25%">Título</th>
                                            <th>Descripción</th>
                                            <th width="120">Duración</th>
                                            <th width="60">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="proceso-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Galería -->
                <div class="tab-pane fade" id="galeria" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Galería de Imágenes</h5>
                            <button class="btn btn-success btn-sm" onclick="addGaleria()">
                                <i class="bi bi-plus"></i> Agregar Imagen
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="50">#</th>
                                            <th width="25%">Título</th>
                                            <th width="20%">Categoría</th>
                                            <th>Imagen</th>
                                            <th width="60">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="galeria-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let D = null;
        let hasChanges = false; // Indica si hay cambios pendientes
        const B = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';

        // Small debounce helper to avoid too many synchronous model updates while typing
        function debounce(fn, wait = 150) {
            let timer = null;
            return function(...args) {
                if (timer) clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), wait);
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadConfig();
            
            // Agregar evento para marcar cambios en inputs del Hero y General
            const heroInputs = [
                'titulo_pagina', 'descripcion_pagina', 'esta_activo',
                'hero_etiqueta', 'hero_titulo', 'hero_titulo_destacado', 
                'hero_descripcion', 'hero_boton_principal', 'hero_boton_principal_link',
                'hero_boton_secundario', 'hero_boton_secundario_link'
            ];
            heroInputs.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', markChanged);
                    el.addEventListener('change', markChanged);
                }
            });
        });

        // Marcar que hay cambios pendientes
        function markChanged() {
            if (!hasChanges) {
                hasChanges = true;
                updateChangeIndicator();
            }
        }

        // Actualizar indicador visual de cambios
        function updateChangeIndicator() {
            const btn = document.getElementById('btnGuardar');
            const indicator = document.getElementById('changeIndicator');
            if (hasChanges) {
                btn.classList.add('btn-warning');
                btn.classList.remove('btn-primary');
                if (indicator) indicator.style.display = 'inline';
            } else {
                btn.classList.remove('btn-warning');
                btn.classList.add('btn-primary');
                if (indicator) indicator.style.display = 'none';
            }
        }

        async function loadConfig() {
            try {
                const ts = Date.now();
                // Obtener datos principales (beneficios, tipos, hero, galería, etc.)
                const r = await fetch(B + 'api/pisos_deportivos.php?t=' + ts);
                const d = await r.json();
                if (!d.success) throw new Error(d.error || 'Error al obtener datos');

                // Obtener config separada (tabla pisos_deportivos_config)
                const rc = await fetch(B + 'api/pisos_deportivos_config.php?t=' + ts);
                const dc = await rc.json();
                const cfg = (dc && dc.success) ? dc.data : null;

                // Obtener hero separado (para persistencia consistente)
                let heroRemote = null;
                try {
                    const rh = await fetch(B + 'api/pisos_deportivos_hero.php?t=' + ts);
                    const dh = await rh.json();
                    if (dh && dh.success) heroRemote = dh.data;
                } catch (e) {
                    console.warn('No se pudo obtener hero separado:', e.message || e);
                }

                D = d.data || {};
                if (cfg) D.config = cfg;
                if (heroRemote) D.hero = heroRemote;
                renderAll();
            } catch (e) {
                Swal.fire({icon: 'error', title: 'Error', text: e.message});
            }
        }

        function renderAll() {
            if (!D) return;
            // General
            document.getElementById('titulo_pagina').value = D.config?.titulo_pagina || '';
            document.getElementById('descripcion_pagina').value = D.config?.descripcion_pagina || '';
            document.getElementById('esta_activo').checked = D.config?.esta_activo == 1;
            
            // Hero
            const h = D.hero || {};
            document.getElementById('hero_etiqueta').value = h.etiqueta || '';
            document.getElementById('hero_titulo').value = h.titulo || '';
            document.getElementById('hero_titulo_destacado').value = h.titulo_destacado || '';
            document.getElementById('hero_descripcion').value = h.descripcion || '';
            const heroPreview = document.getElementById('hero-preview');
            document.getElementById('hero_imagen_fondo').value = h.imagen_fondo || '';
            if (h.imagen_fondo) {
                if (heroPreview) heroPreview.innerHTML = '<div class="d-flex align-items-center gap-2"><img src="' + rUrl(h.imagen_fondo) + '" alt="Imagen actual" class="img-thumbnail" style="max-width: 200px; max-height: 120px;"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeHeroImg()"><i class="bi bi-trash"></i> Quitar</button></div>';
            } else {
                if (heroPreview) heroPreview.innerHTML = '';
            }
            document.getElementById('hero_boton_principal').value = h.boton_principal || '';
            document.getElementById('hero_boton_principal_link').value = h.boton_principal_link || '';
            document.getElementById('hero_boton_secundario').value = h.boton_secundario || '';
            document.getElementById('hero_boton_secundario_link').value = h.boton_secundario_link || '';
            
            renderBeneficios();
            renderTipos();
            renderProceso();
            renderGaleria();
        }

        // ========== BENEFICIOS ==========
        function renderBeneficios() {
            const b = document.getElementById('beneficios-body');
            const s = D.beneficios || [];
            document.getElementById('beneficios-count').textContent = s.length;
            if (!s.length) {
                b.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No hay beneficios configurados</td></tr>';
                return;
            }
            b.innerHTML = s.map((x, i) => `
                <tr data-i="${i}">
                    <td class="align-middle">${i + 1}</td>
                            <td><input class="form-control form-control-sm b-tit" oninput="debouncedSyncBeneficioInput(this)" value="${esc(x.titulo)}" placeholder="Título del beneficio"></td>
                            <td><input class="form-control form-control-sm b-desc" oninput="debouncedSyncBeneficioInput(this)" value="${esc(x.descripcion)}" placeholder="Descripción"></td>
                    <td class="align-middle"><button class="btn btn-outline-danger btn-sm" onclick="rmBeneficio(${i})" title="Eliminar"><i class="bi bi-trash"></i></button></td>
                </tr>
            `).join('');
        }

        function addBeneficio() {
            if (!D.beneficios) D.beneficios = [];
            const newIndex = D.beneficios.length;
            D.beneficios.push({titulo: '', descripcion: ''});
            markChanged();
            renderBeneficios();
            // Dar foco al nuevo input para facilitar escritura inmediata
            setTimeout(() => {
                const last = document.querySelector(`#beneficios-body tr[data-i="${newIndex}"] .b-tit`);
                if (last) last.focus();
            }, 20);
        }

        function rmBeneficio(i) {
            Swal.fire({
                title: '¿Eliminar beneficio?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    D.beneficios.splice(i, 1);
                    markChanged();
                    renderBeneficios();
                }
            });
        }

        // ========== TIPOS DE PISOS ==========
        function renderTipos() {
            const b = document.getElementById('tipos-body');
            const tipos = D.tiposPisos || [];
            document.getElementById('tipos-count').textContent = tipos.length;

            if (!tipos.length) {
                b.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay tipos de pisos configurados</td></tr>';
                return;
            }

            b.innerHTML = tipos.map((t, i) => `
                <tr data-i="${i}" class="tipo-main-row">
                    <td class="align-middle">${i + 1}</td>
                    <td>${esc(t.nombre)}</td>
                    <td>${esc(t.slug)}</td>
                    <td>${esc(t.etiqueta)}</td>
                    <td>
                        <div class="t-preview">
                            ${t.imagen ? `<img src="${rUrl(t.imagen)}" alt="Img" class="img-thumb">` : '<span class="text-muted small">Sin img</span>'}
                        </div>
                    </td>
                    <td class="align-middle">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-outline-primary btn-sm" onclick="openEditTipo(${i})" title="Editar completo">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="toggleTipoDetail(${i})" title="Ver detalles">
                                <i class="bi bi-chevron-down" id="chevron-${i}"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="rmTipo(${i})" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr data-i="${i}" class="tipo-detail-row" id="detail-${i}" style="display: none;">
                    <td colspan="6" class="pt-0 pb-3">
                        <div class="tipo-detail-content">
                            <small class="text-muted d-block mb-2">Vista previa (Solo Lectura)</small>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="p-2 border rounded bg-white text-dark">
                                        <strong>Descripción:</strong><br>
                                        ${esc(t.descripcion) || '<em class="text-muted">Sin descripción</em>'}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Características:</strong>
                                    <ul class="mb-0 ps-3">
                                        ${(t.caracteristicas || []).length ? (t.caracteristicas || []).map(x => `<li>${esc(x)}</li>`).join('') : '<li class="text-muted">Ninguna</li>'}
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <strong>Aplicaciones:</strong>
                                    <ul class="mb-0 ps-3">
                                        ${(t.aplicaciones || []).length ? (t.aplicaciones || []).map(x => `<li>${esc(x)}</li>`).join('') : '<li class="text-muted">Ninguna</li>'}
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <strong>Certificaciones:</strong>
                                    <ul class="mb-0 ps-3">
                                        ${(t.certificaciones || []).length ? (t.certificaciones || []).map(x => `<li>${esc(x)}</li>`).join('') : '<li class="text-muted">Ninguna</li>'}
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <strong>Especificaciones:</strong>
                                    <ul class="mb-0 ps-3">
                                        ${(t.especificaciones || []).length ? (t.especificaciones || []).map(s => `<li><b>${esc(s.etiqueta)}:</b> ${esc(s.valor)}</li>`).join('') : '<li class="text-muted">Ninguna</li>'}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function toggleTipoDetail(i) {
            const detail = document.getElementById('detail-' + i);
            const chevron = document.getElementById('chevron-' + i);
            if (detail.style.display === 'none') {
                detail.style.display = '';
                chevron.className = 'bi bi-chevron-up';
            } else {
                detail.style.display = 'none';
                chevron.className = 'bi bi-chevron-down';
            }
        }

        function addTipo() {
            if (!D.tiposPisos) D.tiposPisos = [];
            const newIndex = D.tiposPisos.length;
            D.tiposPisos.push({
                nombre: '',
                slug: '',
                etiqueta: '',
                descripcion: '',
                imagen: '',
                caracteristicas: [],
                aplicaciones: [],
                certificaciones: [],
                especificaciones: []
            });
            markChanged();
            renderTipos();
            setTimeout(() => {
                const last = document.querySelector(`#tipos-body tr[data-i="${newIndex}"] .t-nombre`);
                if (last) last.focus();
            }, 20);
        }

        function rmTipo(i) {
            Swal.fire({
                title: '¿Eliminar tipo de piso?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    D.tiposPisos.splice(i, 1);
                    markChanged();
                    renderTipos();
                }
            });
        }

        function addArrayItem(btn) {
            // Insertar nuevo input al inicio de la lista visual y en el modelo D
            const sp = document.createElement('div');
            sp.className = 'item-badge';
            sp.innerHTML = `
                <input oninput="debouncedSyncArrayInput(this)" placeholder="Item">
                <button class="btn btn-link btn-sm p-0 text-danger" onclick="rmArrayItem(this)" title="Quitar"><i class="bi bi-x-circle"></i></button>
            `;

            // localizar el contenedor .item-list y el índice del tipo
            const itemList = btn.closest('.item-list');
            if (itemList) {
                // insertar al inicio (arriba)
                itemList.insertBefore(sp, itemList.firstChild);

                // actualizar D.tiposPisos correspondiente si existe
                const detailRow = btn.closest('tr[data-i]') || btn.closest('.tipo-detail-row');
                let tipoIndex = null;
                if (detailRow) tipoIndex = parseInt(detailRow.getAttribute('data-i'));
                if (!isNaN(tipoIndex) && D && D.tiposPisos && D.tiposPisos[tipoIndex]) {
                    // determinar qué lista es (caracteristicas=0, aplicaciones=1, certificaciones=2)
                    const lists = Array.from(detailRow.querySelectorAll('.item-list'));
                    const listPos = lists.indexOf(itemList);
                    if (listPos === 0) {
                        D.tiposPisos[tipoIndex].caracteristicas = D.tiposPisos[tipoIndex].caracteristicas || [];
                        D.tiposPisos[tipoIndex].caracteristicas.push('');
                    } else if (listPos === 1) {
                        D.tiposPisos[tipoIndex].aplicaciones = D.tiposPisos[tipoIndex].aplicaciones || [];
                        D.tiposPisos[tipoIndex].aplicaciones.push('');
                    } else if (listPos === 2) {
                        D.tiposPisos[tipoIndex].certificaciones = D.tiposPisos[tipoIndex].certificaciones || [];
                        D.tiposPisos[tipoIndex].certificaciones.push('');
                    }
                }
            } else {
                // fallback: insertar antes del botón
                btn.before(sp);
            }
        }

        function addSpec(btn) {
            // Insertar nueva especificación al inicio y actualizar modelo D
            const sp = document.createElement('span');
            sp.className = 'spec-badge';
            sp.innerHTML = `
                <input class="spe" oninput="debouncedSyncSpecInput(this)" placeholder="Etiqueta">
                <input class="spv" oninput="debouncedSyncSpecInput(this)" placeholder="Valor">
                <button class="btn btn-link btn-sm p-0 text-danger" onclick="rmArrayItem(this)" title="Quitar"><i class="bi bi-x-circle"></i></button>
            `;

            const specList = btn.closest('.spec-list');
            if (specList) {
                specList.insertBefore(sp, specList.firstChild);

                const detailRow = btn.closest('tr[data-i]') || btn.closest('.tipo-detail-row');
                let tipoIndex = null;
                if (detailRow) tipoIndex = parseInt(detailRow.getAttribute('data-i'));
                if (!isNaN(tipoIndex) && D && D.tiposPisos && D.tiposPisos[tipoIndex]) {
                    D.tiposPisos[tipoIndex].especificaciones = D.tiposPisos[tipoIndex].especificaciones || [];
                    D.tiposPisos[tipoIndex].especificaciones.push({etiqueta: '', valor: ''});
                }
            } else {
                btn.before(sp);
            }
        }

        function rmArrayItem(btn) {
            const badge = btn.closest('.item-badge, .spec-badge');
            if (!badge) return;

            // actualizar modelo D: localizar contenedor y índice
            const listContainer = badge.closest('.item-list, .spec-list');
            const detailRow = badge.closest('tr[data-i]') || badge.closest('.tipo-detail-row');
            if (detailRow && listContainer) {
                const tipoIndex = parseInt(detailRow.getAttribute('data-i'));
                if (!isNaN(tipoIndex) && D && D.tiposPisos && D.tiposPisos[tipoIndex]) {
                    // si es spec-list
                    if (listContainer.classList.contains('spec-list')) {
                        const badges = Array.from(listContainer.querySelectorAll('.spec-badge'));
                        const idx = badges.indexOf(badge);
                        if (idx >= 0) D.tiposPisos[tipoIndex].especificaciones.splice(idx, 1);
                    } else {
                        // item-list (caracteristicas/aplicaciones/certificaciones)
                        const lists = Array.from(detailRow.querySelectorAll('.item-list'));
                        const listPos = lists.indexOf(listContainer);
                        const badges = Array.from(listContainer.querySelectorAll('.item-badge'));
                        const idx = badges.indexOf(badge);
                        if (idx >= 0) {
                            if (listPos === 0) D.tiposPisos[tipoIndex].caracteristicas.splice(idx, 1);
                            else if (listPos === 1) D.tiposPisos[tipoIndex].aplicaciones.splice(idx, 1);
                            else if (listPos === 2) D.tiposPisos[tipoIndex].certificaciones.splice(idx, 1);
                        }
                    }
                }
            }

            badge.remove();
        }

        // ========== PROCESO ==========
        function renderProceso() {
            const b = document.getElementById('proceso-body');
            const s = D.procesoInstalacion || [];
            document.getElementById('proceso-count').textContent = s.length;
            if (!s.length) {
                b.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay pasos configurados</td></tr>';
                return;
            }
            b.innerHTML = s.map((x, i) => `
                <tr data-i="${i}">
                    <td class="align-middle">${x.numero || i + 1}</td>
                            <td><input class="form-control form-control-sm p-tit" oninput="debouncedSyncProcesoInput(this)" value="${esc(x.titulo)}" placeholder="Título del paso"></td>
                            <td><input class="form-control form-control-sm p-desc" oninput="debouncedSyncProcesoInput(this)" value="${esc(x.descripcion)}" placeholder="Descripción"></td>
                            <td><input class="form-control form-control-sm p-dur" oninput="debouncedSyncProcesoInput(this)" value="${esc(x.duracion)}" placeholder="Ej: 2 días"></td>
                    <td class="align-middle"><button class="btn btn-outline-danger btn-sm" onclick="rmPaso(${i})" title="Eliminar"><i class="bi bi-trash"></i></button></td>
                </tr>
            `).join('');
        }

        function addPaso() {
            if (!D.procesoInstalacion) D.procesoInstalacion = [];
            const nextNum = D.procesoInstalacion.length + 1;
            const newIndex = D.procesoInstalacion.length;
            D.procesoInstalacion.push({numero: nextNum, titulo: '', descripcion: '', duracion: ''});
            markChanged();
            renderProceso();
            setTimeout(() => {
                const last = document.querySelector(`#proceso-body tr[data-i="${newIndex}"] .p-tit`);
                if (last) last.focus();
            }, 20);
        }

        function rmPaso(i) {
            Swal.fire({
                title: '¿Eliminar paso?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    D.procesoInstalacion.splice(i, 1);
                    D.procesoInstalacion.forEach((p, i) => p.numero = i + 1);
                    markChanged();
                    renderProceso();
                }
            });
        }

        // ========== GALERÍA ==========
        function renderGaleria() {
            const b = document.getElementById('galeria-body');
            const s = D.galeria || [];
            document.getElementById('galeria-count').textContent = s.length;
            const cats = [...new Set(s.map(g => g.categoria).filter(x => x))];
            if (!s.length) {
                b.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay imágenes en la galería</td></tr>';
                return;
            }
            b.innerHTML = s.map((x, i) => `
                <tr data-i="${i}">
                    <td class="align-middle">${i + 1}</td>
                    <td><input class="form-control form-control-sm g-tit" oninput="debouncedSyncGaleriaInput(this)" value="${esc(x.titulo)}" placeholder="Título de imagen"></td>
                    <td><input class="form-control form-control-sm g-cat" oninput="debouncedSyncGaleriaInput(this)" value="${esc(x.categoria)}" list="catlist" placeholder="Categoría"></td>
                    <td>
                        <div class="mb-2">
                            <input type="file" class="form-control form-control-sm g-file" accept="image/*" onchange="previewImg(this,${i})">
                            <input type="hidden" class="g-url" value="${esc(x.url)}">
                            <small class="text-muted">Formatos: JPG, PNG, GIF, WEBP</small>
                        </div>
                        <div class="g-preview" id="preview-${i}">
                            ${x.url ? `
                                <div class="d-flex align-items-center gap-2">
                                    <img src="${rUrl(x.url)}" alt="Imagen actual" class="img-thumbnail" style="max-width: 100px; max-height: 100px;" onerror="this.style.display='none'">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImg(${i})">
                                        <i class="bi bi-trash"></i> Quitar
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                    </td>
                    <td class="align-middle"><button class="btn btn-outline-danger btn-sm" onclick="rmGaleria(${i})" title="Eliminar fila"><i class="bi bi-x-lg"></i></button></td>
                </tr>
            `).join('') + `<datalist id="catlist">${cats.map(c => `<option value="${c}">`).join('')}</datalist>`;
        }

        function addGaleria() {
            if (!D.galeria) D.galeria = [];
            D.galeria.push({titulo: '', categoria: '', url: '', alt: ''});
            markChanged();
            renderGaleria();
        }

        function rmGaleria(i) {
            Swal.fire({
                title: '¿Eliminar imagen?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    D.galeria.splice(i, 1);
                    markChanged();
                    renderGaleria();
                }
            });
        }

        async function previewImg(inp, i) {
            const file = inp.files[0];
            const preview = document.getElementById('preview-' + i);
            const urlField = inp.closest('tr').querySelector('.g-url');

            if (!file) {
                preview.innerHTML = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                        <span class="badge bg-info">Procesando...</span>
                    </div>
                `;
            };
            reader.readAsDataURL(file);

            try {
                const uploadedUrl = await uploadImage(inp);
                if (uploadedUrl) {
                    urlField.value = uploadedUrl;
                    preview.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <img src="${rUrl(uploadedUrl)}" alt="Imagen subida" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImg(${i})">
                                <i class="bi bi-trash"></i> Quitar
                            </button>
                        </div>
                    `;
                    if (D && D.galeria && D.galeria[i]) D.galeria[i].url = uploadedUrl;
                }
            } catch (err) {
                console.error('Error upload preview:', err);
                preview.innerHTML = `<span class="text-danger">Error al subir: ${err.message}</span>`;
            }
        }

        function removeImg(i) {
            D.galeria[i].url = '';
            const row = document.querySelector(`#galeria-body tr[data-i="${i}"]`);
            row.querySelector('.g-url').value = '';
            row.querySelector('.g-file').value = '';
            document.getElementById('preview-' + i).innerHTML = '<span class="text-muted">Imagen eliminada. Guarde para aplicar.</span>';
        }

        // ======= Tipos image preview / upload helpers =======
        async function previewTipoImg(inp, i) {
            const file = inp.files[0];
            const preview = document.getElementById('tpreview-' + i);
            const urlField = inp.closest('tr').querySelector('.t-url');

            if (!file) {
                preview.innerHTML = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width:100px; max-height:100px;">
                        <span class="badge bg-info">Procesando...</span>
                    </div>
                `;
            };
            reader.readAsDataURL(file);

            try {
                const uploadedUrl = await uploadImage(inp);
                if (uploadedUrl) {
                    urlField.value = uploadedUrl;
                    preview.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <img src="${rUrl(uploadedUrl)}" alt="Imagen subida" class="img-thumbnail" style="max-width:100px; max-height:100px;">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTipoImg(${i})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                    if (D && D.tiposPisos && D.tiposPisos[i]) D.tiposPisos[i].imagen = uploadedUrl;
                }
            } catch (err) {
                console.error('Error upload preview (tipo):', err);
                preview.innerHTML = `<span class="text-danger">Error al subir: ${err.message}</span>`;
            }
        }

        function removeTipoImg(i) {
            const row = document.querySelector(`#tipos-body tr[data-i="${i}"]`);
            if (!row) return;
            const urlField = row.querySelector('.t-url');
            const fileInput = row.querySelector('.t-file');
            if (urlField) urlField.value = '';
            if (fileInput) fileInput.value = '';
            const preview = document.getElementById('tpreview-' + i);
            if (preview) preview.innerHTML = '<span class="text-muted">Imagen eliminada. Guarde para aplicar.</span>';
            if (D && D.tiposPisos && D.tiposPisos[i]) D.tiposPisos[i].imagen = '';
        }

        // ======= Hero image preview / upload helpers =======
        async function previewHeroImg(inp) {
            const file = inp.files[0];
            const preview = document.getElementById('hero-preview');
            const urlField = document.getElementById('hero_imagen_fondo');

            if (!file) {
                if (preview) preview.innerHTML = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                if (preview) preview.innerHTML = '<div class="d-flex align-items-center gap-2"><img src="' + e.target.result + '" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 120px;"><span class="badge bg-info">Procesando...</span></div>';
            };
            reader.readAsDataURL(file);

            try {
                const uploadedUrl = await uploadImage(inp);
                if (uploadedUrl) {
                    urlField.value = uploadedUrl;
                    if (preview) preview.innerHTML = '<div class="d-flex align-items-center gap-2"><img src="' + rUrl(uploadedUrl) + '" alt="Imagen subida" class="img-thumbnail" style="max-width: 200px; max-height: 120px;"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeHeroImg()"><i class="bi bi-trash"></i> Quitar</button></div>';
                    if (D && D.hero) D.hero.imagen_fondo = uploadedUrl;
                    markChanged();
                }
            } catch (err) {
                console.error('Error upload preview (hero):', err);
                if (preview) preview.innerHTML = `<span class="text-danger">Error al subir: ${err.message}</span>`;
            }
        }

        function removeHeroImg() {
            const urlField = document.getElementById('hero_imagen_fondo');
            const fileInput = document.getElementById('hero_imagen_fondo_file');
            const preview = document.getElementById('hero-preview');
            if (urlField) urlField.value = '';
            if (fileInput) fileInput.value = '';
            if (preview) preview.innerHTML = '<span class="text-muted">Imagen eliminada. Guarde para aplicar.</span>';
            if (D && D.hero) D.hero.imagen_fondo = '';
            markChanged();
        }

        function rUrl(u) {
            return !u ? '' : u.startsWith('http') || u.startsWith('/') ? u : B + u;
        }

        // Limpiar URL: quitar prefijo absoluto para guardar solo ruta relativa
        function cleanUrl(u) {
            if (!u) return '';
            // Quitar prefijos conocidos
            const prefixes = [
                'https://stadium.planbsistemas.com.ar/',
                'http://stadium.planbsistemas.com.ar/',
                'https://localhost/',
                'http://localhost/',
                window.location.origin + '/'
            ];
            for (const p of prefixes) {
                if (u.startsWith(p)) {
                    return u.substring(p.length);
                }
            }
            // Si ya es relativa, devolverla tal cual
            return u;
        }

        function esc(s) {
            if (!s) return '';
            return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        // Sincronizar inputs de listas (caracteristicas/aplicaciones/certificaciones)
        function syncArrayInput(inp) {
            const badge = inp.closest('.item-badge');
            if (!badge) return;
            const listContainer = badge.closest('.item-list');
            const detailRow = badge.closest('tr[data-i]') || badge.closest('.tipo-detail-row');
            if (!detailRow || !listContainer) return;
            const tipoIndex = parseInt(detailRow.getAttribute('data-i'));
            if (isNaN(tipoIndex) || !D || !D.tiposPisos || !D.tiposPisos[tipoIndex]) return;
            const lists = Array.from(detailRow.querySelectorAll('.item-list'));
            const listPos = lists.indexOf(listContainer);
            const badges = Array.from(listContainer.querySelectorAll('.item-badge'));
            const idx = badges.indexOf(badge);
            if (idx < 0) return;
            const v = inp.value.trim();
            if (listPos === 0) {
                D.tiposPisos[tipoIndex].caracteristicas = D.tiposPisos[tipoIndex].caracteristicas || [];
                D.tiposPisos[tipoIndex].caracteristicas[idx] = v;
            } else if (listPos === 1) {
                D.tiposPisos[tipoIndex].aplicaciones = D.tiposPisos[tipoIndex].aplicaciones || [];
                D.tiposPisos[tipoIndex].aplicaciones[idx] = v;
            } else if (listPos === 2) {
                D.tiposPisos[tipoIndex].certificaciones = D.tiposPisos[tipoIndex].certificaciones || [];
                D.tiposPisos[tipoIndex].certificaciones[idx] = v;
            }
        }

        // Sincronizar inputs de especificaciones (spe/spv)
        function syncSpecInput(inp) {
            const badge = inp.closest('.spec-badge');
            if (!badge) return;
            const listContainer = badge.closest('.spec-list');
            const detailRow = badge.closest('tr[data-i]') || badge.closest('.tipo-detail-row');
            if (!detailRow || !listContainer) return;
            const tipoIndex = parseInt(detailRow.getAttribute('data-i'));
            if (isNaN(tipoIndex) || !D || !D.tiposPisos || !D.tiposPisos[tipoIndex]) return;
            const badges = Array.from(listContainer.querySelectorAll('.spec-badge'));
            const idx = badges.indexOf(badge);
            if (idx < 0) return;
            const etiqueta = badge.querySelector('.spe').value.trim();
            const valor = badge.querySelector('.spv').value.trim();
            D.tiposPisos[tipoIndex].especificaciones = D.tiposPisos[tipoIndex].especificaciones || [];
            D.tiposPisos[tipoIndex].especificaciones[idx] = {etiqueta, valor};
        }

    // Sincronizar inputs de beneficios (título / descripción)
    function syncBeneficioInput(inp) {
        const row = inp.closest('tr[data-i]');
        if (!row) return;
        const idx = parseInt(row.getAttribute('data-i'));
        if (isNaN(idx) || !D) return;
        D.beneficios = D.beneficios || [];
        // asegurar que exista la posición
        if (!D.beneficios[idx]) D.beneficios[idx] = {titulo: '', descripcion: ''};
        const tit = row.querySelector('.b-tit');
        const desc = row.querySelector('.b-desc');
        D.beneficios[idx].titulo = tit ? tit.value.trim() : (D.beneficios[idx].titulo || '');
        D.beneficios[idx].descripcion = desc ? desc.value.trim() : (D.beneficios[idx].descripcion || '');
        markChanged();
    }

    // Sincronizar campos principales de un tipo (nombre, slug, etiqueta, descripción)
    function syncTipoInput(inp) {
        // localizar fila principal
        const mainRow = inp.closest('tr.tipo-main-row') || inp.closest('tr[data-i]');
        if (!mainRow) return;
        const idx = parseInt(mainRow.getAttribute('data-i'));
        if (isNaN(idx) || !D) return;
        D.tiposPisos = D.tiposPisos || [];
        if (!D.tiposPisos[idx]) D.tiposPisos[idx] = {};
        const row = mainRow;
        const nombre = row.querySelector('.t-nombre') ? row.querySelector('.t-nombre').value.trim() : (D.tiposPisos[idx].nombre || '');
        const slug = row.querySelector('.t-slug') ? row.querySelector('.t-slug').value.trim() : (D.tiposPisos[idx].slug || '');
        const etiqueta = row.querySelector('.t-etiqueta') ? row.querySelector('.t-etiqueta').value.trim() : (D.tiposPisos[idx].etiqueta || '');
        // descripción está en la fila detalle siguiente
        let descripcion = '';
        const detailRow = document.querySelector(`#detail-${idx}`) || row.nextElementSibling;
        if (detailRow) {
            const ta = detailRow.querySelector('.t-descripcion');
            if (ta) descripcion = ta.value.trim();
        }
        D.tiposPisos[idx].nombre = nombre;
        D.tiposPisos[idx].slug = slug;
        D.tiposPisos[idx].etiqueta = etiqueta;
        D.tiposPisos[idx].descripcion = descripcion;
        markChanged();
    }

    // Sincronizar inputs del proceso (pasos)
    function syncProcesoInput(inp) {
        const row = inp.closest('tr[data-i]');
        if (!row) return;
        const idx = parseInt(row.getAttribute('data-i'));
        if (isNaN(idx) || !D) return;
        D.procesoInstalacion = D.procesoInstalacion || [];
        if (!D.procesoInstalacion[idx]) D.procesoInstalacion[idx] = {numero: idx + 1, titulo: '', descripcion: '', duracion: ''};
        const tit = row.querySelector('.p-tit');
        const desc = row.querySelector('.p-desc');
        const dur = row.querySelector('.p-dur');
        D.procesoInstalacion[idx].titulo = tit ? tit.value.trim() : '';
        D.procesoInstalacion[idx].descripcion = desc ? desc.value.trim() : '';
        D.procesoInstalacion[idx].duracion = dur ? dur.value.trim() : '';
        markChanged();
    }

    // Sincronizar inputs de galería (titulo / categoria)
    function syncGaleriaInput(inp) {
        const row = inp.closest('tr[data-i]');
        if (!row) return;
        const idx = parseInt(row.getAttribute('data-i'));
        if (isNaN(idx) || !D) return;
        D.galeria = D.galeria || [];
        if (!D.galeria[idx]) D.galeria[idx] = {titulo: '', categoria: '', url: ''};
        const tit = row.querySelector('.g-tit');
        const cat = row.querySelector('.g-cat');
        D.galeria[idx].titulo = tit ? tit.value.trim() : '';
        D.galeria[idx].categoria = cat ? cat.value.trim() : '';
        markChanged();
    }

    // Crear versiones debounced de los sync para evitar demasiadas actualizaciones durante tipeo
    const debouncedSyncBeneficioInput = debounce(syncBeneficioInput, 150);
    const debouncedSyncArrayInput = debounce(syncArrayInput, 150);
    const debouncedSyncSpecInput = debounce(syncSpecInput, 150);
    const debouncedSyncTipoInput = debounce(syncTipoInput, 150);
    const debouncedSyncProcesoInput = debounce(syncProcesoInput, 150);
    const debouncedSyncGaleriaInput = debounce(syncGaleriaInput, 150);

        // ========== GUARDAR ==========
        function collect() {
            const data = {
                hero: {
                    etiqueta: document.getElementById('hero_etiqueta').value.trim(),
                    titulo: document.getElementById('hero_titulo').value.trim(),
                    titulo_destacado: document.getElementById('hero_titulo_destacado').value.trim(),
                    descripcion: document.getElementById('hero_descripcion').value.trim(),
                    imagen_fondo: cleanUrl(document.getElementById('hero_imagen_fondo').value.trim()),
                    boton_principal: document.getElementById('hero_boton_principal').value.trim(),
                    boton_principal_link: document.getElementById('hero_boton_principal_link').value.trim(),
                    boton_secundario: document.getElementById('hero_boton_secundario').value.trim(),
                    boton_secundario_link: document.getElementById('hero_boton_secundario_link').value.trim()
                },
                beneficios: [],
                tiposPisos: [],
                procesoInstalacion: [],
                galeria: []
            };

            document.querySelectorAll('#beneficios-body tr[data-i]').forEach((r, i) => {
                data.beneficios.push({
                    titulo: r.querySelector('.b-tit').value.trim(),
                    descripcion: r.querySelector('.b-desc').value.trim(),
                    orden: i + 1
                });
            });

            // Recolectar tipos de pisos desde la memoria (ya actualizados por Modal)
            if (D.tiposPisos && D.tiposPisos.length > 0) {
                 data.tiposPisos = D.tiposPisos.map((t, i) => {
                     return {
                         ...t,
                         orden: i + 1,
                         imagen: cleanUrl(t.imagen || '')
                     };
                 });
            } else {
            }

            document.querySelectorAll('#proceso-body tr[data-i]').forEach((r, i) => {
                data.procesoInstalacion.push({
                    numero: i + 1,
                    titulo: r.querySelector('.p-tit').value.trim(),
                    descripcion: r.querySelector('.p-desc').value.trim(),
                    duracion: r.querySelector('.p-dur').value.trim(),
                    orden: i + 1
                });
            });

            document.querySelectorAll('#galeria-body tr[data-i]').forEach((r, i) => {
                data.galeria.push({
                    titulo: r.querySelector('.g-tit').value.trim(),
                    categoria: r.querySelector('.g-cat').value.trim(),
                    url: cleanUrl(r.querySelector('.g-url').value.trim()),
                    alt: '',
                    orden: i + 1,
                    _fileInput: r.querySelector('.g-file')
                });
            });

            return data;
        }

        async function uploadImage(fileInput) {
            if (!fileInput || !fileInput.files || !fileInput.files[0]) return null;

            const file = fileInput.files[0];

            if (!file.type.startsWith('image/')) {
                const fdRaw = new FormData();
                fdRaw.append('imagen', file, file.name);
                const rRaw = await fetch(B + 'api/upload_imagen.php', {method: 'POST', body: fdRaw, credentials: 'same-origin'});
                const dRaw = await rRaw.json();
                if (dRaw.success) return dRaw.url;
                throw new Error(dRaw.error || 'Error al subir archivo');
            }

            const MAX_DIM = 1600;
            const QUALITY = 0.8;

            const img = await new Promise((res, rej) => {
                const reader = new FileReader();
                reader.onload = () => {
                    const i = new Image();
                    i.onload = () => res(i);
                    i.onerror = rej;
                    i.src = reader.result;
                };
                reader.onerror = rej;
                reader.readAsDataURL(file);
            });

            let width = img.width;
            let height = img.height;
            if (width > MAX_DIM || height > MAX_DIM) {
                if (width > height) {
                    height = Math.round((MAX_DIM / width) * height);
                    width = MAX_DIM;
                } else {
                    width = Math.round((MAX_DIM / height) * width);
                    height = MAX_DIM;
                }
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            const tryWebP = (callback) => {
                if (canvas.toBlob) {
                    try {
                        canvas.toBlob((blob) => callback(blob), 'image/webp', QUALITY);
                    } catch (e) {
                        callback(null);
                    }
                } else {
                    callback(null);
                }
            };

            const blob = await new Promise((res) => {
                tryWebP((b) => {
                    if (b) return res(b);
                    canvas.toBlob((bj) => res(bj), 'image/jpeg', QUALITY);
                });
            });

            if (!blob) throw new Error('No se pudo procesar la imagen en el navegador');

            const ext = blob.type === 'image/webp' ? 'webp' : 'jpg';
            const fileName = `${file.name.replace(/\.[^/.]+$/, '')}.${ext}`;

            const fd = new FormData();
            fd.append('imagen', blob, fileName);

            const r = await fetch(B + 'api/upload_imagen.php', {method: 'POST', body: fd, credentials: 'same-origin'});
            const d = await r.json();

            if (d.success) {
                return d.url;
            } else {
                throw new Error(d.error || 'Error al subir imagen');
            }
        }

        async function saveConfig() {
            try {
                // Guardar config primero (para que los campos generales persistan)
                const configPayload = {
                    config: {
                        titulo_pagina: document.getElementById('titulo_pagina').value.trim(),
                        descripcion_pagina: document.getElementById('descripcion_pagina').value.trim(),
                        esta_activo: document.getElementById('esta_activo').checked ? 1 : 0
                    }
                };

                try {
                    const rcfg = await fetch(B + 'api/pisos_deportivos_config.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(configPayload)
                    });
                    const dcfg = await rcfg.json();
                } catch (errCfg) {
                    // Config error silencioso
                }

                // Guardar hero por separado (mismo enfoque que config)
                const heroPayload = {
                    hero: {
                        etiqueta: document.getElementById('hero_etiqueta').value.trim(),
                        titulo: document.getElementById('hero_titulo').value.trim(),
                        titulo_destacado: document.getElementById('hero_titulo_destacado').value.trim(),
                        descripcion: document.getElementById('hero_descripcion').value.trim(),
                        imagen_fondo: cleanUrl(document.getElementById('hero_imagen_fondo').value.trim()),
                        boton_principal: document.getElementById('hero_boton_principal').value.trim(),
                        boton_principal_link: document.getElementById('hero_boton_principal_link').value.trim(),
                        boton_secundario: document.getElementById('hero_boton_secundario').value.trim(),
                        boton_secundario_link: document.getElementById('hero_boton_secundario_link').value.trim()
                    }
                };

                try {
                    const rh = await fetch(B + 'api/pisos_deportivos_hero.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(heroPayload)
                    });
                    const dh = await rh.json();
                } catch (errH) {
                    // Hero error silencioso
                }

                const data = collect();

                const totalUploads = data.galeria.filter(it => it._fileInput && it._fileInput.files && it._fileInput.files[0]).length;

                Swal.fire({
                    title: 'Guardando...',
                    html: `Subidas 0 / ${totalUploads}`,
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                if (totalUploads > 0) {
                    let uploadedCount = 0;

                    const tasks = data.galeria.map((item, idx) => {
                        if (item._fileInput && item._fileInput.files && item._fileInput.files[0]) {
                            return async () => {
                                try {
                                    const uploadedUrl = await uploadImage(item._fileInput);
                                    item.url = uploadedUrl || item.url || '';
                                    uploadedCount++;
                                    const sw = Swal.getHtmlContainer();
                                    if (sw) sw.textContent = `Subidas ${uploadedCount} / ${totalUploads}`;
                                } catch (err) {
                                    throw new Error(`Imagen ${idx+1}: ${err.message}`);
                                }
                            };
                        }
                        return null;
                    }).filter(Boolean);

                    async function runWithConcurrency(taskFns, limit = 3) {
                        const results = [];
                        let i = 0;
                        const workers = new Array(limit).fill(null).map(async () => {
                            while (i < taskFns.length) {
                                const cur = taskFns[i++];
                                try {
                                    const r = await cur();
                                    results.push(r);
                                } catch (e) {
                                    throw e;
                                }
                            }
                        });
                        await Promise.all(workers);
                        return results;
                    }

                    try {
                        await runWithConcurrency(tasks, 3);
                    } catch (err) {
                        Swal.fire({icon: 'error', title: 'Error al subir imágenes', text: err.message});
                        return;
                    }
                }

                data.galeria.forEach(it => delete it._fileInput);

                // Enviar cada sección a su endpoint correspondiente
                try {
                    const send = async (url, payload) => {
                        const rr = await fetch(B + url, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(payload),
                            credentials: 'same-origin'
                        });
                        return await rr.json();
                    };

                    // Beneficios
                    const rBen = await send('api/pisos_deportivos_beneficios.php', {beneficios: data.beneficios});

                    // Tipos
                    const rTipos = await send('api/pisos_deportivos_tipos.php', {tiposPisos: data.tiposPisos});
                    if (!rTipos.success) {
                        throw new Error('No se pudo guardar Tipos: ' + (rTipos.error || 'Error desconocido'));
                    }

                    // Proceso
                    const rProc = await send('api/pisos_deportivos_proceso.php', {procesoInstalacion: data.procesoInstalacion});
                    if (!rProc.success) {
                        throw new Error('No se pudo guardar Proceso: ' + (rProc.error || 'Error desconocido'));
                    }

                    // Galería
                    const rGal = await send('api/pisos_deportivos_galeria.php', {galeria: data.galeria});
                    if (!rGal.success) {
                        throw new Error('No se pudo guardar Galería: ' + (rGal.error || 'Error desconocido'));
                    }

                    hasChanges = false;
                    updateChangeIndicator();
                    Swal.fire({icon: 'success', title: '¡Guardado!', text: 'Configuración actualizada correctamente', timer: 2000, showConfirmButton: false});
                    setTimeout(() => loadConfig(), 500);
                } catch (errSend) {
                    throw errSend;
                }
            } catch (e) {
                Swal.fire({icon: 'error', title: 'Error al guardar', text: e.message});
            }
        }


        // ========== MODAL TIPOS LOGIC ==========
        let currentTipoIdx = -1;

        function openEditTipo(i) {
            currentTipoIdx = i;
            const t = D.tiposPisos[i];
            if(!t) return;

            document.getElementById('m_tipo_index').value = i;
            document.getElementById('m_nombre').value = t.nombre || '';
            document.getElementById('m_slug').value = t.slug || '';
            document.getElementById('m_etiqueta').value = t.etiqueta || '';
            document.getElementById('m_descripcion').value = t.descripcion || '';
            
            // Imagen
            document.getElementById('m_url').value = t.imagen || '';
            document.getElementById('m_file').value = ''; 
            const pv = document.getElementById('m_img_preview');
            if(t.imagen) {
                pv.innerHTML = `<div class="d-flex align-items-center gap-2"><img src="${rUrl(t.imagen)}" class="img-thumbnail" style="max-height:100px"> <button class="btn btn-sm btn-outline-danger" onclick="clearModalImg()">Quitar</button></div>`;
            } else {
                pv.innerHTML = '';
            }

            // Listas
            renderModalList('m_caracteristicas_list', t.caracteristicas || []);
            renderModalList('m_aplicaciones_list', t.aplicaciones || []);
            renderModalList('m_certificaciones_list', t.certificaciones || []);
            renderModalSpecs(t.especificaciones || []);

            const modal = new bootstrap.Modal(document.getElementById('tipoModal'));
            modal.show();
        }

        function renderModalList(id, items) {
            const container = document.getElementById(id);
            container.innerHTML = '';
            items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'd-flex gap-2 mb-2';
                div.innerHTML = `
                    <input class="form-control form-control-sm m-list-item" value="${esc(item)}">
                    <button class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
                `;
                container.appendChild(div);
            });
        }

        function addModalItem(id) {
             const container = document.getElementById(id);
             const div = document.createElement('div');
             div.className = 'd-flex gap-2 mb-2';
             div.innerHTML = `
                <input class="form-control form-control-sm m-list-item" placeholder="Nuevo item">
                <button class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
             `;
            container.appendChild(div);
        }

        function renderModalSpecs(specs) {
            const container = document.getElementById('m_especificaciones_list');
            container.innerHTML = '';
            specs.forEach(s => {
                const div = document.createElement('div');
                div.className = 'd-flex gap-2 mb-2';
                div.innerHTML = `
                    <input class="form-control form-control-sm m-spec-k" value="${esc(s.etiqueta)}" placeholder="Etiqueta">
                    <input class="form-control form-control-sm m-spec-v" value="${esc(s.valor)}" placeholder="Valor">
                    <button class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
                `;
                container.appendChild(div);
            });
        }

        function addModalSpec() {
            const container = document.getElementById('m_especificaciones_list');
            const div = document.createElement('div');
            div.className = 'd-flex gap-2 mb-2';
            div.innerHTML = `
                <input class="form-control form-control-sm m-spec-k" placeholder="Etiqueta">
                <input class="form-control form-control-sm m-spec-v" placeholder="Valor">
                <button class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
            `;
            container.appendChild(div);
        }

        async function previewModalImg(inp) {
             try {
                const url = await uploadImage(inp);
                if(url) {
                    document.getElementById('m_url').value = url;
                    const pv = document.getElementById('m_img_preview');
                     pv.innerHTML = `<div class="d-flex align-items-center gap-2"><img src="${rUrl(url)}" class="img-thumbnail" style="max-height:100px"> <button class="btn btn-sm btn-outline-danger" onclick="clearModalImg()">Quitar</button></div>`;
                }
             } catch(e) {
                 Swal.fire('Error', e.message, 'error');
             }
        }

        function clearModalImg() {
            document.getElementById('m_url').value = '';
            document.getElementById('m_file').value = '';
            document.getElementById('m_img_preview').innerHTML = '';
        }

        function saveTipoFromModal() {
            if(currentTipoIdx < 0 || !D.tiposPisos[currentTipoIdx]) return;
            const t = D.tiposPisos[currentTipoIdx];
            
            t.nombre = document.getElementById('m_nombre').value.trim();
            t.slug = document.getElementById('m_slug').value.trim();
            t.etiqueta = document.getElementById('m_etiqueta').value.trim();
            t.descripcion = document.getElementById('m_descripcion').value.trim();
            t.imagen = cleanUrl(document.getElementById('m_url').value.trim());

            // Recolectar listas
            t.caracteristicas = Array.from(document.querySelectorAll('#m_caracteristicas_list .m-list-item')).map(i => i.value.trim()).filter(v => v);
            t.aplicaciones = Array.from(document.querySelectorAll('#m_aplicaciones_list .m-list-item')).map(i => i.value.trim()).filter(v => v);
            t.certificaciones = Array.from(document.querySelectorAll('#m_certificaciones_list .m-list-item')).map(i => i.value.trim()).filter(v => v);
            
            // Recolectar specs
            const specs = [];
            document.querySelectorAll('#m_especificaciones_list > div').forEach(div => {
                const k = div.querySelector('.m-spec-k').value.trim();
                const v = div.querySelector('.m-spec-v').value.trim();
                if(k || v) specs.push({etiqueta: k, valor: v});
            });
            t.especificaciones = specs;

            markChanged();
            renderTipos();
        }

        // ========== SIDEBAR & THEME ==========
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('stadium-theme', newTheme);
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const icon = document.getElementById('themeIcon');
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            icon.className = currentTheme === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
        }

        updateThemeIcon();
    </script>

    <!-- Modal Editar Tipo -->
    <div class="modal fade" id="tipoModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Tipo de Piso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="m_tipo_index">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" id="m_nombre">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input class="form-control" id="m_slug">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Etiqueta</label>
                            <input class="form-control" id="m_etiqueta">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" id="m_descripcion" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Imagen</label>
                            <input type="file" class="form-control mb-2" id="m_file" onchange="previewModalImg(this)">
                            <input type="hidden" id="m_url">
                            <div id="m_img_preview"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Características</label>
                            <div id="m_caracteristicas_list"></div>
                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="addModalItem('m_caracteristicas_list')">+ Agregar</button>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Aplicaciones</label>
                            <div id="m_aplicaciones_list"></div>
                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="addModalItem('m_aplicaciones_list')">+ Agregar</button>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Certificaciones</label>
                            <div id="m_certificaciones_list"></div>
                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="addModalItem('m_certificaciones_list')">+ Agregar</button>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Especificaciones (Etiqueta : Valor)</label>
                            <div id="m_especificaciones_list"></div>
                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="addModalSpec()">+ Agregar</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="saveTipoFromModal()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
