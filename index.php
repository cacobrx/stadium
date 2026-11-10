<?php
/*
 * Creado el 07/10/2025 20:07
 * Autor: gus
 * Archivo: index.php
 */

// Iniciar sesión antes que cualquier página
require_once __DIR__ . '/includes/bootstrap.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'inicio';
// Detectar si la URL es /shop y redirigir a index.php?page=shop

switch ($page) {
    case '404':
        include 'pages/404.php';
        break;
    case "inicio":
        include 'pages/inicio.php';
        break;
    case "dashboard":
        include 'pages/dashboard.php';
        break;
    case "usuarios":
        include 'pages/usuarios.php';
        break;
    case "cuit":
        include 'pages/cuit.php';
        break;
    case "articulos":
        include 'pages/articulos.php';
        break;
    case "articulos_form":
        include 'pages/articulos_form.php';
        break;
    case "clientes":
        include 'pages/clientes.php';
        break;
    case "clientes_add_save":
        include 'pages/clientes_add_save.php';
        break;
    case "clientes_mod_save":
        include 'pages/clientes_mod_save.php';
        break;
    case "clientes_del":
        include 'pages/clientes_del.php';
        break;
    case "pedidos":
        include 'pages/pedidos.php';
        break;
    case "facturas":
        include 'pages/facturas.php';
        break;
    case "pedido_form":
        include 'pages/pedido_form.php';
        break;
    case "test_api_pedido":
        include 'pages/test_api_pedido.php';
        break;
    case "presupuestos":
        include 'pages/presupuestos.php';
        break;
    case "presupuesto_form":
        include 'pages/presupuesto_form.php';
        break;
    case "pdf_presupuesto":
        include 'impresion/pdf_presupuesto.php';
        break;
    case "inventario":
        include 'pages/inventario.php';
        break;
            case "proveedores":
        include 'pages/proveedores.php';
        break;
    case "proveedores_prn":
        include 'pages/proveedores_prn.php';
        break;
    case "proveedores_exp":
        include 'pages/proveedores_exp.php';
        break;
    case "compras":
        include 'pages/compras.php';
        break;
    case "compras_form":
        include 'pages/compras_form.php';
        break;
    case "entradas":
        include 'pages/entradas.php';
        break;
    case "entradas_form":
        include 'pages/entradas_form.php';
        break;
    case "informe_stock":
        include 'pages/informe_stock.php';
        break;
    case "proyectos":
        include 'pages/proyectos.php';
        break;
    case "novedades":
        include 'pages/novedades.php';
        break;
    // case "informes":
    //     include 'pages/informes.php';
    //     break;
    case "tablas":
        include 'pages/tablas.php';
        break;
    case "slider":
        include 'pages/slider.php';
        break;
    case "configuracion":
        include 'pages/configuracion_sistema.php';
        break;
    case "actividad_mp":
        include 'pages/actividad_mp.php';
        break;
    // case "auditoria":
    //     include 'pages/auditoria.php';
    //     break;
    case "logout":
        include 'pages/logout.php';
        break;
    case "categorias":
        include 'pages/categorias.php';
        break;
    case "colecciones":
        include 'pages/colecciones.php';
        break;
    case "deportes":
        include 'pages/deportes.php';
        break;
    case "marcas":
        include 'pages/marcas.php';
        break;
    case "cupones":
        include 'pages/cupones.php';
        break;
    case "equipamiento_profesional":
        include 'pages/equipamiento_profesional.php';
        break;
    case "equipamiento_profesional_form":
        include 'pages/equipamiento_profesional_form.php';
        break;
    case "equipamiento_profesional_prn":
        include 'pages/equipamiento_profesional_prn.php';
        break;
    case "equipamiento_profesional_exp":
        include 'pages/equipamiento_profesional_exp.php';
        break;
    case "tableros_marcadores":
        include 'pages/tableros_marcadores.php';
        break;
    case "pisos_deportivos":
        include 'pages/pisos_deportivos.php';
        break;
    case "test_api_pedido":
        include 'pages/test_api_pedido.php';
        break;
    case "comprobante_fis":
        include 'pages/comprobante_fis.php';
        break;
    default:
        include 'pages/404.php';
        break;
}
    