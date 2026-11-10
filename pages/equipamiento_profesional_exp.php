<?php
/*
 * Creado el 11/11/2025
 * Archivo: equipamiento_profesional_exp.php
 * Stadium2 - Exportación de lista de equipamiento profesional a Excel
 */

// Autenticación
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

// Configuración de base de datos
require_once __DIR__ . '/../config/database.php';

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

// Preparar arrays para exportar
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
    // Formatear categoría para mejor presentación
    $categoria_formateada = '';
    if ($row['categoria'] == 'jirafas') {
        $categoria_formateada = 'Jirafas';
    } elseif ($row['categoria'] == 'tableros-marcadores') {
        $categoria_formateada = 'Tableros y Marcadores';
    } else {
        $categoria_formateada = $row['categoria'];
    }
    $a_categoria[] = $categoria_formateada;
    $a_tiempo_entrega[] = $row['tiempo_entrega'];
    $a_garantia[] = $row['garantia'];
    $a_disponible[] = $row['disponible'];
    $a_instalacion[] = $row['instalacion'];
    $a_financiacion[] = $row['financiacion'];
    $a_estado[] = $row['estado'];
}

// Configurar headers para descarga de Excel
header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=equipamiento_profesional.xls");
header("Pragma: no-cache");
header("Expires: 0"); 

echo "<table border=1> ";
echo "<tr> ";
echo "<th>ID</th> ";
echo "<th>Nombre</th> ";
echo "<th>Categoría</th> ";
echo "<th>Tiempo Entrega</th> ";
echo "<th>Garantía</th> ";
echo "<th>Disponible</th> ";
echo "<th>Instalación</th> ";
echo "<th>Financiación</th> ";
echo "<th>Estado</th> ";
echo "</tr> ";  

for($i=0; $i<count($a_id); $i++) {
    echo "<tr>";
    echo "<td>".$a_id[$i]."</td>";
    echo "<td>".utf8_decode($a_nombre[$i])."</td>";
    echo "<td>".utf8_decode($a_categoria[$i])."</td>";
    echo "<td>".utf8_decode($a_tiempo_entrega[$i])."</td>";
    echo "<td>".utf8_decode($a_garantia[$i])."</td>";
    echo "<td>".$a_disponible[$i]."</td>";
    echo "<td>".$a_instalacion[$i]."</td>";
    echo "<td>".$a_financiacion[$i]."</td>";
    echo "<td>".utf8_decode($a_estado[$i])."</td>";
    echo "</tr>";
}

echo "</table>";
?>
