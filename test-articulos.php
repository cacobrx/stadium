<?php
// test-articulos.php - Prueba directa de la página de artículos sin auth

// Simular usuario logueado
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@test.com';
$_SESSION['user_nombre'] = 'Usuario Test';
$_SESSION['login_time'] = time();

// Incluir la página de artículos
include 'pages/articulos.php';
?>