<?php
/*
 * Creado el 07/10/2025 20:07
 * Autor: gus
 * Archivo: index.php
 */
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
    case "pedido_form":
        include 'pages/pedido_form.php';
        break;
    case "inventario":
        include 'pages/inventario.php';
        break;
    // case "informes":
    //     include 'pages/informes.php';
    //     break;
    case "tablas":
        include 'pages/tablas.php';
        break;
    // case "configuracion":
    //     include 'pages/configuracion.php';
    //     break;
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
    case "testmp":
        include 'api/mercadopago/test.html';
        break;
    default:
        include 'pages/404.php';
        break;
}
    