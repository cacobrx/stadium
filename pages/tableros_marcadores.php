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
    <title>Tableros y Marcadores - Stadium</title>
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
        .est-badge, .item-badge { 
            display: inline-flex; gap: 6px; background: var(--bs-light); 
            padding: 6px 10px; border-radius: 4px; margin: 3px; font-size: 0.9rem; 
            align-items: center;
        }
        [data-bs-theme="dark"] .est-badge, [data-bs-theme="dark"] .item-badge { background: var(--bs-gray-700); }
        .est-badge input, .item-badge input { 
            font-size: 0.9rem; padding: 5px 8px; height: 32px; width: auto; 
            border: 1px solid var(--bs-border-color); border-radius: 3px;
        }
        .item-badge input { width: 150px; }
        .est-badge input.ev { width: 80px; }
        .est-badge input.ee { width: 120px; }
        .nav-tabs .nav-link { font-size: 0.95rem; }
        .nav-tabs .nav-link.active { font-weight: 500; }
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
                <h5 class="mb-0 d-inline">Tableros y Marcadores</h5>
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
                <h2>Tableros y Marcadores</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" onclick="loadConfig()">
                        <i class="bi bi-arrow-clockwise"></i> Recargar
                    </button>
                    <button class="btn btn-primary" onclick="saveConfig()">
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
                    <button class="nav-link" id="slides-tab" data-bs-toggle="tab" data-bs-target="#slides" type="button" role="tab">
                        <i class="bi bi-images me-1"></i>Slides <span class="badge bg-secondary ms-1" id="slides-count">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="beneficios-tab" data-bs-toggle="tab" data-bs-target="#beneficios" type="button" role="tab">
                        <i class="bi bi-star me-1"></i>Beneficios <span class="badge bg-secondary ms-1" id="beneficios-count">0</span>
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
                                    <input type="text" class="form-control" id="titulo_pagina" placeholder="Ej: Tableros y Marcadores Deportivos">
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
                                    <input type="text" class="form-control" id="hero_etiqueta" placeholder="Ej: Líderes en Tableros Deportivos">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Título</label>
                                    <input type="text" class="form-control" id="hero_titulo" placeholder="Ej: Tableros Deportivos de">
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
                                        <input type="text" class="form-control" id="hero_boton_principal" placeholder="Ej: Explorar Tableros">
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
                                <div class="col-12">
                                    <label class="form-label d-block">Badges (Etiquetas destacadas)</label>
                                    <div id="hero_badges_container" class="item-list">
                                        <!-- Se cargará mediante JS -->
                                        <button type="button" id="btn-add-hero-badge" class="btn btn-outline-primary btn-sm" onclick="addHeroBadge()"><i class="bi bi-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Slides Hero -->
                <div class="tab-pane fade" id="slides" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-images me-2"></i>Slides Hero</h5>
                            <button class="btn btn-success btn-sm" onclick="addSlide()">
                                <i class="bi bi-plus"></i> Agregar Slide
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="50">#</th>
                                            <th width="20%">Etiqueta</th>
                                            <th width="25%">Título</th>
                                            <th>Destacado</th>
                                            <th width="60">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="slides-body"></tbody>
                                </table>
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
        const B = window.location.pathname.includes('/stadium/') ? '/stadium/' : '/';

        // Small debounce helper to avoid too many synchronous model updates while typing
        function debounce(fn, wait = 150) {
            let timer = null;
            return function(...args) {
                if (timer) clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), wait);
            };
        }

        document.addEventListener('DOMContentLoaded', loadConfig);

        async function loadConfig() {
            try {
                // Obtener datos principales
                const r = await fetch(B + 'api/tableros_marcadores.php');
                const d = await r.json();
                if (!d.success) throw new Error(d.error || 'Error al obtener datos');

                // Obtener hero separado
                let heroRemote = null;
                try {
                    const rh = await fetch(B + 'api/tableros_marcadores_hero.php');
                    const dh = await rh.json();
                    if (dh && dh.success) heroRemote = dh.data;
                } catch (e) {
                    console.warn('No se pudo obtener hero separado:', e.message || e);
                }

                D = d.data || {};
                if (heroRemote) D.hero = heroRemote;
                renderAll();
            } catch (e) {
                Swal.fire({icon: 'error', title: 'Error', text: e.message});
            }
        }

        function renderAll() {
            if (!D) return;
            document.getElementById('titulo_pagina').value = D.config?.titulo_pagina || '';
            document.getElementById('descripcion_pagina').value = D.config?.descripcion_pagina || '';
            document.getElementById('esta_activo').checked = D.config?.esta_activo == 1;
            
            renderHero();
            renderSlides();
            renderBeneficios();
            renderProceso();
            renderGaleria();
        }

        // ========== HERO ==========
        function renderHero() {
            if (!D || !D.hero) return;
            const h = D.hero;
            document.getElementById('hero_etiqueta').value = h.etiqueta || '';
            document.getElementById('hero_titulo').value = h.titulo || '';
            document.getElementById('hero_titulo_destacado').value = h.titulo_destacado || '';
            document.getElementById('hero_descripcion').value = h.descripcion || '';
            
            const heroPreview = document.getElementById('hero-preview');
            document.getElementById('hero_imagen_fondo').value = h.imagen_fondo || '';
            if (h.imagen_fondo) {
                heroPreview.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <img src="${rUrl(h.imagen_fondo)}" alt="Imagen actual" class="img-thumbnail" style="max-width: 200px; max-height: 120px;">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeHeroImg()">
                            <i class="bi bi-trash"></i> Quitar
                        </button>
                    </div>
                `;
            } else {
                heroPreview.innerHTML = '';
            }
            
            document.getElementById('hero_boton_principal').value = h.boton_principal || '';
            document.getElementById('hero_boton_principal_link').value = h.boton_principal_link || '';
            document.getElementById('hero_boton_secundario').value = h.boton_secundario || '';
            document.getElementById('hero_boton_secundario_link').value = h.boton_secundario_link || '';
            
            // Badges
            const badgesContainer = document.getElementById('hero_badges_container');
            const addButton = document.getElementById('btn-add-hero-badge');
            // Limpiar badges anteriores
            badgesContainer.querySelectorAll('.item-badge').forEach(el => el.remove());
            
            const badges = h.badges ? h.badges.split(',') : [];
            badges.forEach(badge => {
                if (badge.trim()) {
                    const div = document.createElement('div');
                    div.className = 'item-badge';
                    div.innerHTML = `
                        <input type="text" class="form-control form-control-sm" value="${esc(badge.trim())}" placeholder="Badge">
                        <button class="btn btn-link btn-sm p-0 text-danger" onclick="rmHeroBadge(this)" title="Quitar"><i class="bi bi-x-circle"></i></button>
                    `;
                    badgesContainer.insertBefore(div, addButton);
                }
            });
        }

        function addHeroBadge() {
            const container = document.getElementById('hero_badges_container');
            const addButton = document.getElementById('btn-add-hero-badge');
            const div = document.createElement('div');
            div.className = 'item-badge';
            div.innerHTML = `
                <input type="text" class="form-control form-control-sm" value="" placeholder="Badge">
                <button class="btn btn-link btn-sm p-0 text-danger" onclick="rmHeroBadge(this)" title="Quitar"><i class="bi bi-x-circle"></i></button>
            `;
            container.insertBefore(div, addButton);
            div.querySelector('input').focus();
        }

        function rmHeroBadge(btn) {
            btn.closest('.item-badge').remove();
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
                if (preview) preview.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 120px;">
                        <span class="badge bg-info">Procesando...</span>
                    </div>
                `;
            };
            reader.readAsDataURL(file);

            try {
                // Usar nombre fijo 'tablero_1'
                const uploadedUrl = await uploadImage(inp, 'tablero_1', true);
                if (uploadedUrl) {
                    urlField.value = uploadedUrl;
                    // Agregar timestamp para evitar cache
                    const previewUrl = rUrl(uploadedUrl) + '?t=' + new Date().getTime();
                    if (preview) preview.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <img src="${previewUrl}" alt="Imagen subida" class="img-thumbnail" style="max-width: 200px; max-height: 120px;">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeHeroImg()">
                                <i class="bi bi-trash"></i> Quitar
                            </button>
                        </div>
                    `;
                    if (D && D.hero) D.hero.imagen_fondo = uploadedUrl;
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
        }

        // ========== SLIDES ==========
        function renderSlides() {
            const b = document.getElementById('slides-body');
            const s = D.slidesHero || [];
            document.getElementById('slides-count').textContent = s.length;
            if (!s.length) {
                b.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay slides configurados</td></tr>';
                return;
            }
            b.innerHTML = s.map((x, i) => `
                <tr data-i="${i}">
                    <td class="align-middle" rowspan="2">${i + 1}</td>
                    <td><input class="form-control form-control-sm s-etiq" oninput="debouncedSyncSlide(this)" value="${x.etiqueta || ''}" placeholder="Etiqueta"></td>
                    <td><input class="form-control form-control-sm s-tit" oninput="debouncedSyncSlide(this)" value="${x.titulo || ''}" placeholder="Título"></td>
                    <td><input class="form-control form-control-sm s-titd" oninput="debouncedSyncSlide(this)" value="${x.titulo_destacado || ''}" placeholder="Destacado"></td>
                    <td class="align-middle" rowspan="2"><button class="btn btn-outline-danger btn-sm" onclick="rmSlide(${i})" title="Eliminar"><i class="bi bi-trash"></i></button></td>
                </tr>
                <tr data-i="${i}" class="est-row">
                    <td colspan="3" class="pt-0 pb-2">
                        <div class="d-flex flex-wrap align-items-center gap-1">
                            <small class="text-muted me-2">Estadísticas:</small>
                            ${(x.estadisticas || []).map((e, ei) => `
                                <span class="est-badge">
                                    <input class="ev" oninput="debouncedSyncEstadistica(this)" value="${e.valor || ''}" placeholder="Valor">
                                    <input class="ee" oninput="debouncedSyncEstadistica(this)" value="${e.etiqueta || ''}" placeholder="Etiqueta">
                                    <button class="btn btn-link btn-sm p-0 text-danger" onclick="rmEst(this)" title="Quitar"><i class="bi bi-x-circle"></i></button>
                                </span>
                            `).join('')}
                            <button class="btn btn-outline-primary btn-sm" onclick="addEst(this)" title="Agregar estadística"><i class="bi bi-plus"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function addSlide() {
            if (!D.slidesHero) D.slidesHero = [];
            D.slidesHero.push({etiqueta: '', titulo: '', titulo_destacado: '', descripcion: '', imagen: '', estadisticas: []});
            renderSlides();
        }

        function rmSlide(i) {
            Swal.fire({
                title: '¿Eliminar slide?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    D.slidesHero.splice(i, 1);
                    renderSlides();
                }
            });
        }

        function addEst(btn) {
            const row = btn.closest('tr[data-i]');
            if (!row) return;
            const slideIndex = parseInt(row.getAttribute('data-i'));
            if (isNaN(slideIndex)) return;
            
            // Actualizar modelo de datos
            if (D.slidesHero && D.slidesHero[slideIndex]) {
                if (!D.slidesHero[slideIndex].estadisticas) {
                    D.slidesHero[slideIndex].estadisticas = [];
                }
                D.slidesHero[slideIndex].estadisticas.push({valor: '', etiqueta: ''});
            }
            
            // Agregar visualmente al final (antes del botón)
            const sp = document.createElement('span');
            sp.className = 'est-badge';
            sp.innerHTML = `
                <input class="ev" oninput="debouncedSyncEstadistica(this)" placeholder="Valor">
                <input class="ee" oninput="debouncedSyncEstadistica(this)" placeholder="Etiqueta">
                <button class="btn btn-link btn-sm p-0 text-danger" onclick="rmEst(this)" title="Quitar"><i class="bi bi-x-circle"></i></button>
            `;
            btn.before(sp);
        }

        function rmEst(btn) {
            const badge = btn.closest('.est-badge');
            const row = btn.closest('tr[data-i]');
            if (!row || !badge) return;
            
            const slideIndex = parseInt(row.getAttribute('data-i'));
            if (!isNaN(slideIndex) && D.slidesHero && D.slidesHero[slideIndex]) {
                // Encontrar el índice de la estadística
                const container = btn.closest('.d-flex');
                const badges = Array.from(container.querySelectorAll('.est-badge'));
                const estIndex = badges.indexOf(badge);
                
                if (estIndex >= 0 && D.slidesHero[slideIndex].estadisticas) {
                    D.slidesHero[slideIndex].estadisticas.splice(estIndex, 1);
                }
            }
            
            badge.remove();
        }
        
        // Sincronizar inputs de estadísticas
        function syncEstadistica(inp) {
            const row = inp.closest('tr[data-i]');
            if (!row) return;
            const slideIndex = parseInt(row.getAttribute('data-i'));
            if (isNaN(slideIndex) || !D.slidesHero || !D.slidesHero[slideIndex]) return;
            
            const badge = inp.closest('.est-badge');
            const container = inp.closest('.d-flex');
            const badges = Array.from(container.querySelectorAll('.est-badge'));
            const estIndex = badges.indexOf(badge);
            
            if (estIndex >= 0) {
                if (!D.slidesHero[slideIndex].estadisticas) {
                    D.slidesHero[slideIndex].estadisticas = [];
                }
                if (!D.slidesHero[slideIndex].estadisticas[estIndex]) {
                    D.slidesHero[slideIndex].estadisticas[estIndex] = {valor: '', etiqueta: ''};
                }
                
                const evField = badge.querySelector('.ev');
                const eeField = badge.querySelector('.ee');
                D.slidesHero[slideIndex].estadisticas[estIndex].valor = evField ? evField.value.trim() : '';
                D.slidesHero[slideIndex].estadisticas[estIndex].etiqueta = eeField ? eeField.value.trim() : '';
            }
        }
        
        const debouncedSyncEstadistica = debounce(syncEstadistica, 150);

        // Sincronizar inputs de slides
        function syncSlide(inp) {
            const row = inp.closest('tr[data-i]');
            if (!row) return;
            const idx = parseInt(row.getAttribute('data-i'));
            if (isNaN(idx) || !D) return;
            D.slidesHero = D.slidesHero || [];
            if (!D.slidesHero[idx]) D.slidesHero[idx] = {etiqueta: '', titulo: '', titulo_destacado: '', descripcion: '', imagen: '', estadisticas: []};
            const etiq = row.querySelector('.s-etiq');
            const tit = row.querySelector('.s-tit');
            const titd = row.querySelector('.s-titd');
            D.slidesHero[idx].etiqueta = etiq ? etiq.value.trim() : '';
            D.slidesHero[idx].titulo = tit ? tit.value.trim() : '';
            D.slidesHero[idx].titulo_destacado = titd ? titd.value.trim() : '';
        }
        
        const debouncedSyncSlide = debounce(syncSlide, 150);

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
                    <td><input class="form-control form-control-sm b-tit" oninput="debouncedSyncBeneficio(this)" value="${x.titulo || ''}" placeholder="Título del beneficio"></td>
                    <td><input class="form-control form-control-sm b-desc" oninput="debouncedSyncBeneficio(this)" value="${x.descripcion || ''}" placeholder="Descripción"></td>
                    <td class="align-middle"><button class="btn btn-outline-danger btn-sm" onclick="rmBeneficio(${i})" title="Eliminar"><i class="bi bi-trash"></i></button></td>
                </tr>
            `).join('');
        }

        function addBeneficio() {
            if (!D.beneficios) D.beneficios = [];
            D.beneficios.push({titulo: '', descripcion: ''});
            renderBeneficios();
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
                    renderBeneficios();
                }
            });
        }

        // Sincronizar inputs de beneficios
        function syncBeneficio(inp) {
            const row = inp.closest('tr[data-i]');
            if (!row) return;
            const idx = parseInt(row.getAttribute('data-i'));
            if (isNaN(idx) || !D) return;
            D.beneficios = D.beneficios || [];
            if (!D.beneficios[idx]) D.beneficios[idx] = {titulo: '', descripcion: ''};
            const tit = row.querySelector('.b-tit');
            const desc = row.querySelector('.b-desc');
            D.beneficios[idx].titulo = tit ? tit.value.trim() : '';
            D.beneficios[idx].descripcion = desc ? desc.value.trim() : '';
        }
        
        const debouncedSyncBeneficio = debounce(syncBeneficio, 150);

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
                    <td><input class="form-control form-control-sm p-tit" oninput="debouncedSyncProceso(this)" value="${x.titulo || ''}" placeholder="Título del paso"></td>
                    <td><input class="form-control form-control-sm p-desc" oninput="debouncedSyncProceso(this)" value="${x.descripcion || ''}" placeholder="Descripción"></td>
                    <td><input class="form-control form-control-sm p-dur" oninput="debouncedSyncProceso(this)" value="${x.duracion || ''}" placeholder="Ej: 2 días"></td>
                    <td class="align-middle"><button class="btn btn-outline-danger btn-sm" onclick="rmPaso(${i})" title="Eliminar"><i class="bi bi-trash"></i></button></td>
                </tr>
            `).join('');
        }

        function addPaso() {
            if (!D.procesoInstalacion) D.procesoInstalacion = [];
            const nextNum = D.procesoInstalacion.length + 1;
            D.procesoInstalacion.push({numero: nextNum, titulo: '', descripcion: '', duracion: ''});
            renderProceso();
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
                    renderProceso();
                }
            });
        }

        // Sincronizar inputs de proceso
        function syncProceso(inp) {
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
        }
        
        const debouncedSyncProceso = debounce(syncProceso, 150);

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
                    <td><input class="form-control form-control-sm g-tit" value="${x.titulo || ''}" placeholder="Título de imagen"></td>
                    <td><input class="form-control form-control-sm g-cat" value="${x.categoria || ''}" list="catlist" placeholder="Categoría"></td>
                    <td>
                        <div class="mb-2">
                            <input type="file" class="form-control form-control-sm g-file" accept="image/*" onchange="previewImg(this,${i})">
                            <input type="hidden" class="g-url" value="${x.url || ''}">
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
                    renderGaleria();
                }
            });
        }

        // Preview de imagen al seleccionar archivo (como en deportes)
        async function previewImg(inp, i) {
                        const file = inp.files[0];
                        const preview = document.getElementById('preview-' + i);
                        const urlField = inp.closest('tr').querySelector('.g-url');

                        if (!file) {
                            preview.innerHTML = '';
                            return;
                        }

                        // Mostrar preview inmediato
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

                        // Subir inmediatamente (con compresión ya implementada en uploadImage)
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
                                // Marcar en memoria D.galeria si existe
                                if (D && D.galeria && D.galeria[i]) D.galeria[i].url = uploadedUrl;
                            }
                        } catch (err) {
                            console.error('Error upload preview:', err);
                            preview.innerHTML = `<span class="text-danger">Error al subir: ${err.message}</span>`;
                        }
                    }

        // Quitar imagen (como en deportes)
        function removeImg(i) {
            if (D.galeria && D.galeria[i]) D.galeria[i].url = '';
            const row = document.querySelector(`#galeria-body tr[data-i="${i}"]`);
            if (!row) return;
            const urlField = row.querySelector('.g-url');
            const fileField = row.querySelector('.g-file');
            if (urlField) urlField.value = '';
            if (fileField) fileField.value = '';
            const preview = document.getElementById('preview-' + i);
            if (preview) preview.innerHTML = '<span class="text-muted">Imagen eliminada. Guarde para aplicar.</span>';
        }

        function rUrl(u) {
            return !u ? '' : u.startsWith('http') || u.startsWith('/') ? u : B + u;
        }

        // Limpiar URL: quitar prefijo absoluto para guardar solo ruta relativa
        function cleanUrl(u) {
            if (!u) return '';
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
            return u;
        }

        function esc(s) {
            if (!s) return '';
            return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        // ========== GUARDAR ==========
        function collect() {
            // Badges a coma separated string
            const badgesItems = [];
            document.querySelectorAll('#hero_badges_container .item-badge input').forEach(inp => {
                const val = inp.value.trim();
                if (val) badgesItems.push(val);
            });

            const data = {
                config: {
                    titulo_pagina: document.getElementById('titulo_pagina').value,
                    descripcion_pagina: document.getElementById('descripcion_pagina').value,
                    esta_activo: document.getElementById('esta_activo').checked ? 1 : 0
                },
                hero: {
                    etiqueta: document.getElementById('hero_etiqueta').value.trim(),
                    titulo: document.getElementById('hero_titulo').value.trim(),
                    titulo_destacado: document.getElementById('hero_titulo_destacado').value.trim(),
                    descripcion: document.getElementById('hero_descripcion').value.trim(),
                    imagen_fondo: cleanUrl(document.getElementById('hero_imagen_fondo').value.trim()),
                    boton_principal: document.getElementById('hero_boton_principal').value.trim(),
                    boton_principal_link: document.getElementById('hero_boton_principal_link').value.trim(),
                    boton_secundario: document.getElementById('hero_boton_secundario').value.trim(),
                    boton_secundario_link: document.getElementById('hero_boton_secundario_link').value.trim(),
                    badges: badgesItems.join(',')
                },
                slidesHero: [],
                beneficios: [],
                procesoInstalacion: [],
                galeria: []
            };

            // Procesar slides: cada slide tiene 2 filas (la primera con campos, la segunda con estadísticas)
            // Solo procesamos las filas que NO son .est-row para evitar duplicados
            document.querySelectorAll('#slides-body tr[data-i]:not(.est-row)').forEach((r, i) => {
                const dataI = r.getAttribute('data-i');
                // Buscar la fila de estadísticas correspondiente
                const estRow = document.querySelector(`#slides-body tr.est-row[data-i="${dataI}"]`);
                
                const est = [];
                if (estRow) {
                    estRow.querySelectorAll('.est-badge').forEach(g => {
                        const evField = g.querySelector('.ev');
                        const eeField = g.querySelector('.ee');
                        const v = evField ? evField.value.trim() : '';
                        const e = eeField ? eeField.value.trim() : '';
                        if (v || e) est.push({valor: v, etiqueta: e});
                    });
                }
                
                const etiqField = r.querySelector('.s-etiq');
                const titField = r.querySelector('.s-tit');
                const titdField = r.querySelector('.s-titd');
                data.slidesHero.push({
                    etiqueta: etiqField ? etiqField.value.trim() : '',
                    titulo: titField ? titField.value.trim() : '',
                    titulo_destacado: titdField ? titdField.value.trim() : '',
                    descripcion: '',
                    imagen: '',
                    estadisticas: est,
                    orden: i + 1
                });
            });

            document.querySelectorAll('#beneficios-body tr[data-i]').forEach((r, i) => {
                const titField = r.querySelector('.b-tit');
                const descField = r.querySelector('.b-desc');
                data.beneficios.push({
                    titulo: titField ? titField.value.trim() : '',
                    descripcion: descField ? descField.value.trim() : '',
                    orden: i + 1
                });
            });

            document.querySelectorAll('#proceso-body tr[data-i]').forEach((r, i) => {
                const titField = r.querySelector('.p-tit');
                const descField = r.querySelector('.p-desc');
                const durField = r.querySelector('.p-dur');
                data.procesoInstalacion.push({
                    numero: i + 1,
                    titulo: titField ? titField.value.trim() : '',
                    descripcion: descField ? descField.value.trim() : '',
                    duracion: durField ? durField.value.trim() : '',
                    orden: i + 1
                });
            });

            document.querySelectorAll('#galeria-body tr[data-i]').forEach((r, i) => {
                const titField = r.querySelector('.g-tit');
                const catField = r.querySelector('.g-cat');
                const urlField = r.querySelector('.g-url');
                data.galeria.push({
                    titulo: titField ? titField.value.trim() : '',
                    categoria: catField ? catField.value.trim() : '',
                    url: cleanUrl(urlField ? urlField.value.trim() : ''),
                    alt: '',
                    orden: i + 1,
                    _fileInput: r.querySelector('.g-file') // referencia al input file
                });
            });

            return data;
        }

        // Subir imagen con compresión cliente-side (preferir WebP) y devolver URL
        async function uploadImage(fileInput, customName = null, useExactName = false) {
            if (!fileInput || !fileInput.files || !fileInput.files[0]) return null;

            const file = fileInput.files[0];

            // Solo procesar imágenes
            if (!file.type.startsWith('image/')) {
                // Enviar tal cual
                const fdRaw = new FormData();
                fdRaw.append('imagen', file, file.name);
                if (customName) fdRaw.append('custom_name', customName);
                if (useExactName) fdRaw.append('use_exact_name', 'true');
                const rRaw = await fetch(B + 'api/upload_imagen.php', {method: 'POST', body: fdRaw, credentials: 'same-origin'});
                const dRaw = await rRaw.json();
                if (dRaw.success) return dRaw.url;
                throw new Error(dRaw.error || 'Error al subir archivo');
            }

            // Comprensión/resizing: max dimension
            const MAX_DIM = 1600; // px
            const QUALITY = 0.8; // 0..1

            // Crear un Image y canvas para redimensionar
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

            // Intentar generar WebP si el navegador lo soporta
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
                    // fallback a jpeg
                    canvas.toBlob((bj) => res(bj), 'image/jpeg', QUALITY);
                });
            });

            if (!blob) throw new Error('No se pudo procesar la imagen en el navegador');

            // Nombre de archivo: usar extensión adecuada
            const ext = blob.type === 'image/webp' ? 'webp' : 'jpg';
            const fileName = `${file.name.replace(/\.[^/.]+$/, '')}.${ext}`;

            const fd = new FormData();
            fd.append('imagen', blob, fileName);
            if (customName) fd.append('custom_name', customName);
            if (useExactName) fd.append('use_exact_name', 'true');

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
                const data = collect();

                // Contar cuántas imágenes hay para subir
                const totalUploads = data.galeria.filter(it => it._fileInput && it._fileInput.files && it._fileInput.files[0]).length;

                // Mostrar modal con progreso
                Swal.fire({
                    title: 'Guardando...',
                    html: `Subidas 0 / ${totalUploads}`,
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                if (totalUploads > 0) {
                    let uploadedCount = 0;

                    // Crear tareas (funciones) de subida
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

                    // Ejecutar con concurrencia limitada
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

                // Limpiar referencias DOM antes de enviar
                data.galeria.forEach(it => delete it._fileInput);

                // Guardar hero por separado
                if (data.hero) {
                    try {
                        const rh = await fetch(B + 'api/tableros_marcadores_hero.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({hero: data.hero})
                        });
                        const dh = await rh.json();
                        if (!dh.success) console.warn('No se pudo guardar hero:', dh);
                    } catch (errH) {
                        console.warn('Error guardando hero:', errH);
                    }
                }

                // Enviar configuración completa
                const r = await fetch(B + 'api/tableros_marcadores.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data),
                    credentials: 'same-origin'
                });
                const d = await r.json();
                if (d.success) {
                    Swal.fire({icon: 'success', title: '¡Guardado!', text: 'Configuración actualizada correctamente', timer: 2000, showConfirmButton: false});
                    await loadConfig();
                } else {
                    throw new Error(d.error || 'Respuesta inválida');
                }
            } catch (e) {
                Swal.fire({icon: 'error', title: 'Error al guardar', text: e.message});
            }
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
</body>
</html>
