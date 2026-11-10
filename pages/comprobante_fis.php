<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("ID de comprobante inválido");
}

$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
if ($cliente_id <= 0) {
    die("No autorizado");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../impresion/pdf_factura.php';

try {
    $conn = get_database_connection();
} catch (Exception $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// 1. Validar que la factura existe y pertenece al cliente
$stmt = $conn->prepare("SELECT idcli FROM facturas WHERE id = ? AND idcli = ?");
$stmt->bind_param("ii", $id, $cliente_id);
$stmt->execute();
$rs = $stmt->get_result();
$vta = $rs->fetch_assoc();
$stmt->close();

if (!$vta) {
    die("Comprobante no encontrado o no pertenece al cliente");
}

// 2. Generar y mostrar el PDF utilizando la función del sistema
ob_clean(); // Limpiar el buffer para evitar que salgan caracteres extraños antes del PDF
$pdfString = generar_pdf_factura($conn, $id);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="comprobante_' . $id . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdfString;
