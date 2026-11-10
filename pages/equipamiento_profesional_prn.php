<?php
/*
 * Creado el 11/11/2025
 * Archivo: equipamiento_profesional_prn.php
 * Stadium2 - Impresión de lista de equipamiento profesional
 */

// Autenticación
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

// Configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// Clase de impresión (cambiar directorio de trabajo para que encuentre pdf_function.php)
chdir(__DIR__ . '/../impresion');
require_once __DIR__ . '/../impresion/pdf_equipamiento_profesional.php';

// Consulta de equipamiento profesional
$ssql = "SELECT id, nombre, categoria, tiempo_entrega, garantia, 
         CASE WHEN disponible = 1 THEN 'SI' ELSE 'NO' END as disponible,
         CASE WHEN instalacion = 1 THEN 'SI' ELSE 'NO' END as instalacion,
         CASE WHEN financiacion = 1 THEN 'SI' ELSE 'NO' END as financiacion,
         CASE WHEN esta_activo = 1 THEN 'Activo' ELSE 'Inactivo' END as estado
         FROM equipamiento_profesional 
         ORDER BY categoria, nombre";
$rows = execute_query($ssql);

if ($rows === false) {
    die('Error al consultar equipamiento profesional');
}

// Preparar arrays globales para el PDF
$a_id = [];
$a_nombre = [];
$a_categoria = [];
$a_tiempo_entrega = [];
$a_garantia = [];
$a_disponible = [];
$a_instalacion = [];
$a_financiacion = [];
$a_estado = [];

foreach ($rows as $row) {
    $a_id[] = $row['id'];
    $a_nombre[] = $row['nombre'];
    $a_categoria[] = $row['categoria'];
    $a_tiempo_entrega[] = $row['tiempo_entrega'];
    $a_garantia[] = $row['garantia'];
    $a_disponible[] = $row['disponible'];
    $a_instalacion[] = $row['instalacion'];
    $a_financiacion[] = $row['financiacion'];
    $a_estado[] = $row['estado'];
}

// Configuración de columnas (posiciones X para cada columna)
$colu = array(5, 12, 55, 95, 120, 145, 165, 185);

// Información de empresa
$nombreemp = "Stadium";
$telefonoemp = "";

// Generar PDF
$pdf = new pdf_EQP("L", "mm", "A4"); // Landscape para más espacio
$pdf->AddPage();
$pdf->addDetalle();
$pdf->Output();
?>
