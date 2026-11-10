<?php
// Obtener la página actual
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'inicio';
// Si estamos en inicio, no mostrar ninguna opción como activa
// Si estamos en dashboard, mostrar dashboard como activo
?>

<!-- Sidebar Menu -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4 class="mb-0"><i class="bi bi-trophy"></i> <span class="sidebar-text">Stadium</span></h4>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
            <a href="dashboard">
                <i class="bi bi-speedometer2"></i> <span class="sidebar-text">Dashboard</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'articulos') ? 'active' : ''; ?>">
            <a href="articulos">
                <i class="bi bi-box"></i> <span class="sidebar-text">Artículos</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'clientes') ? 'active' : ''; ?>">
            <a href="clientes">
                <i class="bi bi-people"></i> <span class="sidebar-text">Clientes</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'pedidos') ? 'active' : ''; ?>">
            <a href="pedidos">
                <i class="bi bi-cart"></i> <span class="sidebar-text">Pedidos</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'facturas') ? 'active' : ''; ?>">
            <a href="facturas">
                <i class="bi bi-receipt"></i> <span class="sidebar-text">Comprobantes Fiscales</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'presupuestos') ? 'active' : ''; ?>">
            <a href="presupuestos">
                <i class="bi bi-file-earmark-text"></i> <span class="sidebar-text">Presupuestos</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'inventario') ? 'active' : ''; ?>">
            <a href="inventario">
                <i class="bi bi-boxes"></i> <span class="sidebar-text">Inventario</span>
            </a>
        </li>
        <li class="<?php echo (in_array($currentPage, ['equipamiento_profesional', 'equipamiento_profesional_form'])) ? 'active' : ''; ?>">
            <a href="equipamiento_profesional">
                <i class="bi bi-trophy-fill"></i> <span class="sidebar-text">Equipamiento Profesional</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'tableros_marcadores') ? 'active' : ''; ?>">
            <a href="tableros_marcadores">
                <i class="bi bi-display"></i> <span class="sidebar-text">Tableros y Marcadores</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'pisos_deportivos') ? 'active' : ''; ?>">
            <a href="pisos_deportivos">
                <i class="bi bi-grid-3x3-gap"></i> <span class="sidebar-text">Pisos Deportivos</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'proyectos') ? 'active' : ''; ?>">
            <a href="proyectos">
                <i class="bi bi-building"></i> <span class="sidebar-text">Proyectos</span>
            </a>
        </li>
        <li class="<?php echo ($currentPage == 'novedades') ? 'active' : ''; ?>">
            <a href="novedades">
                <i class="bi bi-megaphone"></i> <span class="sidebar-text">Novedades</span>
            </a>
        </li>
                <li class="<?php echo (in_array($currentPage, ['proveedores', 'entradas', 'entradas_form', 'informe_stock'])) ? 'active' : ''; ?>">
            <a href="#" onclick="toggleSubmenu('entrada-submenu')">
                <i class="bi bi-box-arrow-in-down"></i> <span class="sidebar-text">Entrada</span>
                <i class="bi bi-chevron-down float-end sidebar-text"></i>
            </a>
            <ul class="submenu" id="entrada-submenu" style="display: <?php echo (in_array($currentPage, ['proveedores', 'entradas', 'entradas_form', 'informe_stock'])) ? 'block' : 'none'; ?>;">
                <li class="<?php echo ($currentPage == 'proveedores') ? 'active' : ''; ?>"><a href="proveedores"><i class="bi bi-building"></i> <span class="sidebar-text">Proveedores</span></a></li>
                <li class="<?php echo (in_array($currentPage, ['entradas', 'entradas_form'])) ? 'active' : ''; ?>"><a href="entradas"><i class="bi bi-box-arrow-in-down"></i> <span class="sidebar-text">Entrada de Artículos</span></a></li>
                <li class="<?php echo ($currentPage == 'informe_stock') ? 'active' : ''; ?>"><a href="informe_stock"><i class="bi bi-bar-chart-line"></i> <span class="sidebar-text">Informe de Stock</span></a></li>
            </ul>
        </li>
        <li class="<?php echo ($currentPage == 'informes') ? 'active' : ''; ?>">
            <a href="informes">
                <i class="bi bi-graph-up"></i> <span class="sidebar-text">Informes</span>
            </a>
        </li>
        <li class="<?php echo (in_array($currentPage, ['categorias', 'colecciones', 'deportes', 'marcas', 'cupones'])) ? 'active' : ''; ?>">
            <a href="#" onclick="toggleSubmenu('tablas-submenu')">
                <i class="bi bi-table"></i> <span class="sidebar-text">Tablas</span>
                <i class="bi bi-chevron-down float-end sidebar-text"></i>
            </a>
            <ul class="submenu" id="tablas-submenu" style="display: <?php echo (in_array($currentPage, ['categorias', 'colecciones', 'deportes', 'marcas', 'cupones'])) ? 'block' : 'none'; ?>;">
                <li class="<?php echo ($currentPage == 'categorias') ? 'active' : ''; ?>"><a href="categorias"><i class="bi bi-tags"></i> <span class="sidebar-text">Categorías</span></a></li>
                <li class="<?php echo ($currentPage == 'colecciones') ? 'active' : ''; ?>"><a href="colecciones"><i class="bi bi-collection"></i> <span class="sidebar-text">Colecciones</span></a></li>
                <li class="<?php echo ($currentPage == 'deportes') ? 'active' : ''; ?>"><a href="deportes"><i class="bi bi-trophy"></i> <span class="sidebar-text">Deportes</span></a></li>
                <li class="<?php echo ($currentPage == 'marcas') ? 'active' : ''; ?>"><a href="marcas"><i class="bi bi-award"></i> <span class="sidebar-text">Marcas</span></a></li>
                <li class="<?php echo ($currentPage == 'cupones') ? 'active' : ''; ?>"><a href="cupones"><i class="bi bi-ticket-perforated"></i> <span class="sidebar-text">Cupones</span></a></li>
            </ul>
        </li>
        <li class="<?php echo (in_array($currentPage, ['usuarios', 'slider', 'tablas', 'actividad_mp', 'auditoria', 'cuit', 'configuracion'])) ? 'active' : ''; ?>">
            <a href="#" onclick="toggleSubmenu('utilidades-submenu')">
                <i class="bi bi-tools"></i> <span class="sidebar-text">Utilidades</span>
                <i class="bi bi-chevron-down float-end sidebar-text"></i>
            </a>
            <ul class="submenu" id="utilidades-submenu" style="display: <?php echo (in_array($currentPage, ['usuarios', 'slider', 'tablas', 'actividad_mp', 'auditoria', 'cuit', 'configuracion'])) ? 'block' : 'none'; ?>;">
                <li class="<?php echo ($currentPage == 'usuarios') ? 'active' : ''; ?>"><a href="usuarios"><i class="bi bi-person-gear"></i> <span class="sidebar-text">Usuarios</span></a></li>
                <li class="<?php echo ($currentPage == 'cuit') ? 'active' : ''; ?>"><a href="cuit"><i class="bi bi-building-check"></i> <span class="sidebar-text">Datos Fiscales</span></a></li>
                <li class="<?php echo ($currentPage == 'tablas') ? 'active' : ''; ?>"><a href="tablas"><i class="bi bi-table"></i> <span class="sidebar-text">Tablas</span></a></li>
                <li class="<?php echo ($currentPage == 'configuracion') ? 'active' : ''; ?>"><a href="configuracion"><i class="bi bi-gear"></i> <span class="sidebar-text">Configuración</span></a></li>
                <li class="<?php echo ($currentPage == 'slider') ? 'active' : ''; ?>"><a href="slider"><i class="bi bi-images"></i> <span class="sidebar-text">Slider</span></a></li>
                <li class="<?php echo ($currentPage == 'actividad_mp') ? 'active' : ''; ?>"><a href="actividad_mp"><i class="bi bi-wallet2"></i> <span class="sidebar-text">Actividad MP</span></a></li>
                <li class="<?php echo ($currentPage == 'auditoria') ? 'active' : ''; ?>"><a href="auditoria"><i class="bi bi-shield-check"></i> <span class="sidebar-text">Auditoría</span></a></li>
                <li><a href="#" onclick="logout()"><i class="bi bi-box-arrow-right"></i> <span class="sidebar-text">Logout</span></a></li>
            </ul>
        </li>
    </ul>
</div>