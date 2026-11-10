<?php
/*
 * Creado el 21/10/2025
 * Archivo: proveedores_prn.php
 * Stadium2 - Impresión de lista de proveedores
 */

// Autenticación
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

// Configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// Clase de impresión (cambiar directorio de trabajo para que encuentre pdf_function.php)
chdir(__DIR__ . '/../impresion');
require_once __DIR__ . '/../impresion/pdf_proveedores.php';

// Consulta de proveedores
$ssql = "SELECT id, apellido, nombre, direccion, telefono, ciudad, cuit FROM proveedores ORDER BY apellido, nombre";
$rows = execute_query($ssql);

if ($rows === false) {
    die('Error al consultar proveedores');
}

// Preparar arrays globales para el PDF
$a_id = [];
$a_apellido = [];
$a_nombre = [];
$a_direccion = [];
$a_telefono = [];
$a_cuit = [];

foreach ($rows as $row) {
    $a_id[] = $row['id'];
    $a_apellido[] = $row['apellido'];
    $a_nombre[] = $row['nombre'];
    $a_direccion[] = $row['direccion'];
    $a_telefono[] = $row['telefono'];
    $a_cuit[] = $row['cuit'];
}

// Configuración de columnas (posiciones X para cada columna)
$colu = array(5, 25, 90, 150, 175);

// Información de empresa
$nombreemp = "Stadium";
$telefonoemp = "";

// Generar PDF
$pdf = new pdf_PRV("P", "mm", "A4");
$pdf->AddPage();
$pdf->addDetalle();
$pdf->Output();
?>
