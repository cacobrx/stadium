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

header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=proveedores.xls");
header("Pragma: no-cache");
header("Expires: 0"); 

echo "<table border=1> ";
echo "<tr> ";
echo "<th>ID</th> ";
echo "<th>Proveedor</th> ";
echo "<th>Dirección</th> ";
echo "<th>Teléfono</th> ";
echo "<th>CUIT</th> ";
echo "</tr> ";  
for($i=0;$i<count($a_id);$i++) {

  echo "<tr>";
  echo "<td>".$a_id[$i]."</td>";
echo "<td>".utf8_decode($a_apellido[$i]." ".$a_nombre[$i])."</td>";
echo "<td>".utf8_decode($a_direccion[$i])."</td>";
echo "<td>".utf8_decode($a_telefono[$i])."</td>";
echo "<td>".$a_cuit[$i]."</td>";
  echo "</tr>";
}