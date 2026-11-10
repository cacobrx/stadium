<?php
/*
 * Archivo: pdf_presupuesto.php
 * Stadium - Impresión de Presupuestos
 */

require_once __DIR__ . '/../config/database.php';
require('tfpdf.php');

class PDF_Presupuesto extends tFPDF {
    private $presupuestoData;
    private $clienteData;
    
    function setPresupuestoData($data) {
        $this->presupuestoData = $data;
    }
    
    function setClienteData($data) {
        $this->clienteData = $data;
    }
    
    function Header() {
        // Logo
        $logo_path = __DIR__ . '/../includes/logo.jpg';
        if(file_exists($logo_path)) {
            $this->Image($logo_path, 10, 10, 35);
        }
        
        // Información de la empresa
        $this->SetFont('Arial', 'B', 16);
        $this->SetXY(50, 10);
        $this->Cell(0, 8, 'STADIUM', 0, 1, 'L');
        $this->SetFont('Arial', '', 9);
        $this->SetX(50);
        $this->Cell(0, 5, utf8_decode('Equipamiento Deportivo'), 0, 1, 'L');
        
        // Información del presupuesto a la derecha
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(140, 10);
        $this->Cell(60, 8, 'PRESUPUESTO', 0, 1, 'R');
        
        $this->SetFont('Arial', '', 9);
        $this->SetXY(140, 20);
        $nro = !empty($this->presupuestoData['nro_presupuesto']) ? 
               $this->presupuestoData['nro_presupuesto'] : 
               'N° ' . str_pad($this->presupuestoData['id'], 6, '0', STR_PAD_LEFT);
        $this->Cell(60, 5, utf8_decode($nro), 0, 1, 'R');
        
        $this->SetXY(140, 25);
        $this->Cell(60, 5, 'Fecha: ' . date('d/m/Y', strtotime($this->presupuestoData['fecha'])), 0, 1, 'R');
        
        $this->SetXY(140, 30);
        $validez = $this->presupuestoData['validez'] ?? 30;
        $this->Cell(60, 5, utf8_decode('Validez: ' . $validez . ' días'), 0, 1, 'R');
        
        // Línea separadora
        $this->Line(10, 45, 200, 45);
    }
    
    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, utf8_decode('Página ') . $this->PageNo() . ' de {nb}', 0, 1, 'C');
        
        // Información adicional del pie
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 4, utf8_decode('Stadium - Equipamiento Deportivo | www.stadium.com | info@stadium.com'), 0, 1, 'C');
    }
    
    function DatosCliente() {
        $this->SetY(50);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, 'DATOS DEL CLIENTE', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 9);
        $this->SetFillColor(240, 240, 240);
        
        // Crear un recuadro para los datos del cliente
        $y_start = $this->GetY();
        $this->Rect(10, $y_start, 190, 20);
        
        $this->SetXY(12, $y_start + 2);
        $clienteNombre = isset($this->clienteData['apellido']) && isset($this->clienteData['nombre']) ?
                        utf8_decode($this->clienteData['apellido'] . ' ' . $this->clienteData['nombre']) :
                        utf8_decode($this->presupuestoData['cliente_nombre'] ?? 'Cliente no especificado');
        $this->Cell(90, 5, 'Cliente: ' . $clienteNombre, 0, 0, 'L');
        
        $this->SetXY(105, $y_start + 2);
        $tel = isset($this->clienteData['telefono']) ? $this->clienteData['telefono'] : '-';
        $this->Cell(90, 5, utf8_decode('Teléfono: ') . $tel, 0, 1, 'L');
        
        $this->SetXY(12, $y_start + 7);
        $email = isset($this->clienteData['email']) ? $this->clienteData['email'] : '-';
        $this->Cell(90, 5, 'Email: ' . $email, 0, 0, 'L');
        
        $this->SetXY(105, $y_start + 7);
        $doc = isset($this->clienteData['dni']) ? $this->clienteData['dni'] : '-';
        $this->Cell(90, 5, 'Documento: ' . $doc, 0, 1, 'L');
        
        if(!empty($this->presupuestoData['ubicacion'])) {
            $this->SetXY(12, $y_start + 12);
            $this->MultiCell(185, 5, utf8_decode('Dirección: ' . $this->presupuestoData['ubicacion']), 0, 'L');
        }
        
        $this->SetY($y_start + 22);
    }
    
    function TablaDetalles($detalles) {
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, utf8_decode('DETALLE DEL PRESUPUESTO'), 0, 1, 'L');
        
        // Procesar cada producto
        foreach($detalles as $detalle) {
            $this->agregarProducto($detalle);
        }
    }
    
    function agregarProducto($detalle) {
        $startY = $this->GetY();
        
        // Verificar si hay espacio en la página
        if($startY > 240) {
            $this->AddPage();
            $startY = $this->GetY();
        }
        
        // Buscar imagen del producto
        $imagen_url = null;
        $tipo = $detalle['tipo_producto'] ?? 'producto';
        
        if($tipo == 'equipamiento') {
            $imagen_url = $this->buscarImagenEquipamiento($detalle['idart']);
        } else {
            $imagen_url = $this->buscarImagenProducto($detalle['idart']);
        }
        
        // Dibujar borde del producto
        $this->SetDrawColor(200, 200, 200);
        $this->Rect(10, $startY, 190, 0); // Línea superior
        
        $leftMargin = 10;
        $imageWidth = 40;
        $imageHeight = 40;
        $contentX = $leftMargin + $imageWidth + 5;
        
        // Mostrar imagen si existe
        $imagenTemporal = null;
        if($imagen_url && file_exists($imagen_url)) {
            // Convertir WebP a JPG si es necesario
            $imagenParaPDF = $this->convertirWebPaJPG($imagen_url);
            
            if($imagenParaPDF && file_exists($imagenParaPDF)) {
                $this->Image($imagenParaPDF, $leftMargin + 2, $startY + 2, $imageWidth, $imageHeight);
                
                // Marcar para eliminación si es archivo temporal
                if($imagenParaPDF !== $imagen_url) {
                    $imagenTemporal = $imagenParaPDF;
                }
            } else {
                // Placeholder si falla la conversión
                $this->Rect($leftMargin + 2, $startY + 2, $imageWidth, $imageHeight);
                $this->SetXY($leftMargin + 2, $startY + 20);
                $this->SetFont('Arial', 'I', 8);
                $this->Cell($imageWidth, 5, 'Error imagen', 0, 0, 'C');
            }
        } else {
            // Placeholder si no hay imagen
            $this->Rect($leftMargin + 2, $startY + 2, $imageWidth, $imageHeight);
            $this->SetXY($leftMargin + 2, $startY + 20);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell($imageWidth, 5, 'Sin imagen', 0, 0, 'C');
        }
        
        // Información del producto (a la derecha de la imagen)
        $this->SetXY($contentX, $startY + 2);
        $this->SetFont('Arial', 'B', 10);
        
        $producto_nombre = utf8_decode($detalle['producto_nombre'] ?? 'Producto ID: ' . $detalle['idart']);
        $this->MultiCell(145, 5, $producto_nombre, 0, 'L');
        
        // Código/SKU
        $this->SetX($contentX);
        $this->SetFont('Arial', '', 8);
        $codigo = $detalle['producto_codigo'] ?? '';
        if($codigo) {
            $this->Cell(50, 4, 'SKU: ' . $codigo, 0, 1, 'L');
        }
        
        // Tipo de producto
        $this->SetX($contentX);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $tipoBadge = $tipo == 'equipamiento' ? 'Equipamiento Profesional' : 'Producto';
        $this->Cell(50, 4, $tipoBadge, 0, 1, 'L');
        $this->SetTextColor(0, 0, 0);
        
        // Obtener características/especificaciones
        $this->SetFont('Arial', '', 9);
        $this->SetX($contentX);
        $caracteristicas = $this->obtenerCaracteristicas($detalle['idart'], $tipo);
        
        if($caracteristicas) {
            $this->MultiCell(145, 4, utf8_decode($caracteristicas), 0, 'L');
        }
        
        // Detalle adicional si existe
        if(!empty($detalle['detalle'])) {
            $this->SetX($contentX);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(0, 0, 150);
            $this->MultiCell(145, 4, utf8_decode('Nota: ' . $detalle['detalle']), 0, 'L');
            $this->SetTextColor(0, 0, 0);
        }
        
        // Cálculos de precio
        $cantidad = intval($detalle['cantidad']);
        $precio = floatval($detalle['precio']);
        $iva = floatval($detalle['iva'] ?? 21);
        $subtotal = $cantidad * $precio;
        $monto_iva = $subtotal * ($iva / 100);
        $total = $subtotal + $monto_iva;
        
        // Tabla de precios en la parte inferior del producto
        $priceY = max($this->GetY(), $startY + $imageHeight - 10);
        $this->SetY($priceY);
        $this->SetFont('Arial', '', 9);
        
        // Fondo gris claro para precios
        $this->SetFillColor(245, 245, 245);
        $priceStartY = $this->GetY();
        $this->Rect(10, $priceStartY, 190, 18, 'F');
        
        // Primera línea: Cantidad, Precio Unit., Subtotal
        $this->SetXY($contentX, $priceStartY + 2);
        $this->Cell(30, 5, 'Cantidad: ' . $cantidad, 0, 0, 'L');
        $this->Cell(50, 5, 'Precio Unit.: $' . number_format($precio, 2), 0, 0, 'L');
        $this->Cell(50, 5, 'Subtotal: $' . number_format($subtotal, 2), 0, 1, 'L');
        
        // Segunda línea: IVA y Total (más destacado)
        $this->SetX($contentX);
        $this->Cell(50, 5, 'IVA (' . number_format($iva, 1) . '%): $' . number_format($monto_iva, 2), 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(0, 100, 0);
        $this->Cell(0, 5, 'TOTAL: $' . number_format($total, 2), 0, 1, 'R');
        $this->SetTextColor(0, 0, 0);
        
        $this->SetY($priceStartY + 18);
        $this->Ln(2);
        
        // Eliminar archivo temporal si existe
        if(isset($imagenTemporal) && $imagenTemporal && file_exists($imagenTemporal)) {
            @unlink($imagenTemporal);
        }
    }
    
    // Función auxiliar para convertir WebP a JPG
    function convertirWebPaJPG($rutaImagen) {
        $extension = strtolower(pathinfo($rutaImagen, PATHINFO_EXTENSION));
        
        // Si no es WebP, devolver la ruta original
        if ($extension !== 'webp') {
            return $rutaImagen;
        }
        
        // Verificar si GD soporta WebP
        if (!function_exists('imagecreatefromwebp')) {
            error_log("GD no soporta WebP");
            return null;
        }
        
        // Cargar imagen WebP
        $imagen = @imagecreatefromwebp($rutaImagen);
        if (!$imagen) {
            error_log("Error al cargar WebP: " . $rutaImagen);
            return null;
        }
        
        // Crear archivo temporal JPG
        $rutaTempJPG = sys_get_temp_dir() . '/' . uniqid('pdf_img_') . '.jpg';
        
        // Convertir y guardar como JPG con calidad 90
        $exito = imagejpeg($imagen, $rutaTempJPG, 90);
        imagedestroy($imagen);
        
        if ($exito) {
            return $rutaTempJPG;
        }
        
        return null;
    }
    
    function buscarImagenProducto($id) {
        try {
            $conn = get_database_connection();
            $stmt = $conn->prepare("SELECT url FROM producto_imagenes WHERE producto_id = ? AND es_principal = 1 LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($row = $result->fetch_assoc()) {
                $url = $row['url'];
                // Convertir URL relativa a path absoluto
                $path = __DIR__ . '/../' . ltrim($url, './');
                if(file_exists($path)) {
                    return $path;
                }
            }
            // No cerrar la conexión - es singleton
        } catch(Exception $e) {
            error_log("Error buscando imagen producto: " . $e->getMessage());
        }
        return null;
    }
    
    function buscarImagenEquipamiento($id) {
        try {
            $conn = get_database_connection();
            $stmt = $conn->prepare("SELECT url FROM equipamiento_imagenes WHERE equipamiento_id = ? AND es_principal = 1 LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($row = $result->fetch_assoc()) {
                $url = $row['url'];
                if($url) {
                    $path = __DIR__ . '/../' . ltrim($url, './');
                    if(file_exists($path)) {
                        return $path;
                    }
                }
            }
            // No cerrar la conexión - es singleton
        } catch(Exception $e) {
            error_log("Error buscando imagen equipamiento: " . $e->getMessage());
        }
        return null;
    }
    
    function obtenerCaracteristicas($id, $tipo) {
        $caracteristicas = '';
        
        try {
            $conn = get_database_connection();
            
            if($tipo == 'equipamiento') {
                // Obtener especificaciones del equipamiento
                $stmt = $conn->prepare("SELECT etiqueta, valor FROM equipamiento_especificaciones WHERE equipamiento_id = ? ORDER BY orden ASC");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $specs = [];
                while($row = $result->fetch_assoc()) {
                    $specs[] = $row['etiqueta'] . ': ' . $row['valor'];
                }
                
                if(count($specs) > 0) {
                    $caracteristicas = 'Especificaciones: ' . implode('. ', $specs) . '.';
                }
                
                // Obtener descripción
                $stmt = $conn->prepare("SELECT descripcion FROM equipamiento_profesional WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if($row = $result->fetch_assoc()) {
                    if(!empty($row['descripcion'])) {
                        if($caracteristicas) $caracteristicas .= ' ';
                        $caracteristicas .= strip_tags($row['descripcion']);
                    }
                }
                
            } else {
                // Obtener descripción del producto
                $stmt = $conn->prepare("SELECT descripcion FROM productos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if($row = $result->fetch_assoc()) {
                    if(!empty($row['descripcion'])) {
                        $caracteristicas = strip_tags($row['descripcion']);
                    }
                }
            }
            
            // No cerrar la conexión - es singleton
            
            // Limitar longitud
            if(strlen($caracteristicas) > 350) {
                $caracteristicas = substr($caracteristicas, 0, 347) . '...';
            }
            
        } catch(Exception $e) {
            error_log("Error obteniendo características: " . $e->getMessage());
        }
        
        return $caracteristicas;
    }
    
    function ResumenTotal($total, $observaciones = '') {
        $this->Ln(5);
        
        // Observaciones
        if(!empty($observaciones)) {
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 6, 'OBSERVACIONES', 0, 1, 'L');
            $this->SetFont('Arial', '', 9);
            $this->MultiCell(0, 5, utf8_decode($observaciones), 1, 'L');
            $this->Ln(3);
        }
        
        // Total general
        $this->SetFont('Arial', 'B', 14);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(130, 10, '', 0, 0);
        $this->Cell(60, 10, 'TOTAL: $' . number_format($total, 2), 1, 1, 'R', true);
        
        $this->Ln(5);
        
        // Mensaje de validez
        if(isset($this->presupuestoData['fecha_vencimiento'])) {
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(150, 0, 0);
            $fecha_venc = date('d/m/Y', strtotime($this->presupuestoData['fecha_vencimiento']));
            $this->Cell(0, 5, utf8_decode('* Presupuesto válido hasta el ' . $fecha_venc), 0, 1, 'C');
            $this->SetTextColor(0, 0, 0);
        }
    }
}

// Procesar la solicitud
if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        $conn = get_database_connection();
        
        // Obtener presupuesto
        $stmt = $conn->prepare("
            SELECT p.*, CONCAT(c.apellido, ' ', c.nombre) as cliente_nombre
            FROM presupuestos p
            LEFT JOIN clientes c ON p.idcli = c.id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $presupuesto = $result->fetch_assoc();
        
        if(!$presupuesto) {
            die('Presupuesto no encontrado');
        }
        
        // Obtener detalles
        $stmt = $conn->prepare("
            SELECT pd.*,
                CASE 
                    WHEN pd.tipo_producto = 'equipamiento' THEN ep.nombre
                    ELSE p.nombre
                END as producto_nombre,
                CASE 
                    WHEN pd.tipo_producto = 'equipamiento' THEN CONCAT('EQ-', LPAD(ep.id, 5, '0'))
                    ELSE p.sku
                END as producto_codigo
            FROM presupuestos_det pd
            LEFT JOIN productos p ON pd.idart = p.id AND pd.tipo_producto = 'producto'
            LEFT JOIN equipamiento_profesional ep ON pd.idart = ep.id AND pd.tipo_producto = 'equipamiento'
            WHERE pd.idpres = ?
            ORDER BY pd.id
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $detalles = [];
        while($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }
        
        // Obtener datos del cliente
        $cliente = null;
        if($presupuesto['idcli']) {
            $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
            $stmt->bind_param("i", $presupuesto['idcli']);
            $stmt->execute();
            $result = $stmt->get_result();
            $cliente = $result->fetch_assoc();
        }
        
        // No cerrar la conexión aquí - se usa en los métodos del PDF
        
        // Generar PDF
        $pdf = new PDF_Presupuesto();
        $pdf->setPresupuestoData($presupuesto);
        if($cliente) {
            $pdf->setClienteData($cliente);
        }
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $pdf->DatosCliente();
        $pdf->TablaDetalles($detalles);
        $pdf->ResumenTotal(floatval($presupuesto['total']), $presupuesto['observaciones']);
        
        $filename = 'Presupuesto_' . str_pad($id, 6, '0', STR_PAD_LEFT) . '.pdf';
        $pdf->Output('D', $filename);
        
    } catch(Exception $e) {
        error_log("Error generando PDF: " . $e->getMessage());
        die('Error generando PDF: ' . $e->getMessage());
    }
} else {
    die('ID de presupuesto no especificado');
}
