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
    
    // Propiedades para renderizado de HTML / texto enriquecido
    protected $htmlBold = false;
    protected $htmlItalic = false;
    protected $htmlUnderline = false;
    protected $htmlFontSize = 8;
    protected $baseFontSize = 8;
    protected $htmlHref = '';
    protected $htmlFontFamily = 'Arial';
    
    function setPresupuestoData($data) {
        $this->presupuestoData = $data;
    }
    
    function setClienteData($data) {
        $this->clienteData = $data;
    }
    
    // Convierte UTF-8 a Windows-1252 para las fuentes core de FPDF, soportando caracteres especiales
    private function enc($str) {
        $str = (string)$str;
        $str = str_replace(["\u{00A0}"], [' '], $str);
        $res = @mb_convert_encoding($str, 'Windows-1252', 'UTF-8');
        if ($res === false) {
            $res = utf8_decode($str);
        }
        return $res;
    }
    
    protected function updateHtmlFont() {
        $style = '';
        if ($this->htmlBold) $style .= 'B';
        if ($this->htmlItalic) $style .= 'I';
        if ($this->htmlUnderline) $style .= 'U';
        $this->SetFont($this->htmlFontFamily, $style, $this->htmlFontSize);
    }
    
    public function WriteHTML($html, $contentX, $contentWidth, $baseSize = 8) {
        if (empty($html)) return;
        
        $this->baseFontSize = $baseSize;
        $this->htmlFontSize = $baseSize;
        $this->htmlBold = false;
        $this->htmlItalic = false;
        $this->htmlUnderline = false;
        $this->htmlHref = '';
        $this->htmlFontFamily = 'Arial';
        
        // Ajuste dinámico de tamaño de fuente si el texto es muy extenso
        $plainLen = mb_strlen(strip_tags($html));
        if ($plainLen > 2400) {
            $this->baseFontSize = 7.0;
            $this->htmlFontSize = 7.0;
        } elseif ($plainLen > 1800) {
            $this->baseFontSize = 7.5;
            $this->htmlFontSize = 7.5;
        }
        
        $lineHeight = $this->baseFontSize * 0.44; // ~3.5mm para 8pt
        
        $oldLMargin = $this->lMargin;
        $oldRMargin = $this->rMargin;
        
        // Aislar márgenes a la columna derecha
        $this->SetLeftMargin($contentX);
        $this->SetRightMargin($this->w - ($contentX + $contentWidth));
        $this->SetX($contentX);
        
        // Si no tiene tags HTML, formatear párrafos y saltos
        if (!preg_match('/<[a-z0-9]+/i', $html)) {
            $html = '<p>' . preg_replace('/\n{2,}/', '</p><p>', trim($html)) . '</p>';
            $html = nl2br($html);
        }
        
        // Normalizar saltos de línea
        $html = str_replace(["\r\n", "\r"], "\n", $html);
        
        // Convertir bullets en texto plano seguidos de salto de línea a <br>
        $html = preg_replace('/([^\>])\n\s*([•\-\*])/u', '$1<br>$2', $html);
        
        // Quitar saltos literales escapados
        $html = str_replace(['\n', '\r'], ' ', $html);
        
        // En HTML estándar, saltos de línea restantes son espacios
        $html = str_replace("\n", ' ', $html);
        $html = preg_replace('/[ \t]+/', ' ', $html);
        
        // Limpiar bloques vacíos comunes en editores WYSIWYG
        $html = preg_replace('/<(p|div|h[1-6])[^>]*>\s*(<br\s*\/?>|&nbsp;|\s)*<\/\1>/i', '', $html);
        $html = preg_replace('/(<br\s*\/?>\s*){2,}/i', '<br>', $html);
        
        $tokens = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        $inUl = false;
        $inOl = false;
        $olCount = 1;
        $justBroke = true;
        
        $this->updateHtmlFont();
        
        foreach($tokens as $token) {
            // Protección contra desborde sobre la barra de precios fija (Y=258)
            if ($this->GetY() >= 252) {
                break;
            }
            
            $curLineH = $this->htmlFontSize * 0.44;
            
            if ($token[0] === '<' && substr($token, -1) === '>') {
                $tag = trim($token, '<>');
                $isClose = ($tag[0] === '/');
                if ($isClose) {
                    $tagName = strtolower(substr($tag, 1));
                } else {
                    $parts = explode(' ', $tag);
                    $tagName = strtolower($parts[0]);
                }
                
                switch($tagName) {
                    case 'b':
                    case 'strong':
                        $this->htmlBold = !$isClose;
                        $this->updateHtmlFont();
                        break;
                    case 'i':
                    case 'em':
                        $this->htmlItalic = !$isClose;
                        $this->updateHtmlFont();
                        break;
                    case 'u':
                        $this->htmlUnderline = !$isClose;
                        $this->updateHtmlFont();
                        break;
                    case 'a':
                        if (!$isClose) {
                            $this->htmlHref = '';
                            if (preg_match('/href=[\'"]([^\'"]+)[\'"]/i', $tag, $m)) {
                                $this->htmlHref = $m[1];
                            }
                            $this->SetTextColor(0, 0, 180);
                            $this->htmlUnderline = true;
                        } else {
                            $this->htmlHref = '';
                            $this->SetTextColor(0, 0, 0);
                            $this->htmlUnderline = false;
                        }
                        $this->updateHtmlFont();
                        break;
                    case 'h1':
                        if (!$isClose) {
                            if ($this->x > $this->lMargin + 0.1) {
                                $this->Ln($curLineH + 2.0);
                            } else {
                                $this->SetY($this->GetY() + 1.5);
                            }
                            $this->htmlFontSize = $this->baseFontSize + 2.5;
                            $this->htmlBold = true;
                        } else {
                            $this->Ln($curLineH + 1.5);
                            $this->htmlFontSize = $this->baseFontSize;
                            $this->htmlBold = false;
                            $justBroke = true;
                        }
                        $this->updateHtmlFont();
                        break;
                    case 'h2':
                        if (!$isClose) {
                            if ($this->x > $this->lMargin + 0.1) {
                                $this->Ln($curLineH + 1.8);
                            } else {
                                $this->SetY($this->GetY() + 1.2);
                            }
                            $this->htmlFontSize = $this->baseFontSize + 1.5;
                            $this->htmlBold = true;
                        } else {
                            $this->Ln($curLineH + 1.2);
                            $this->htmlFontSize = $this->baseFontSize;
                            $this->htmlBold = false;
                            $justBroke = true;
                        }
                        $this->updateHtmlFont();
                        break;
                    case 'h3':
                    case 'h4':
                    case 'h5':
                    case 'h6':
                        if (!$isClose) {
                            if ($this->x > $this->lMargin + 0.1) {
                                $this->Ln($curLineH + 1.5);
                            } else {
                                $this->SetY($this->GetY() + 1.0);
                            }
                            $this->htmlFontSize = $this->baseFontSize + 0.8;
                            $this->htmlBold = true;
                        } else {
                            $this->Ln($curLineH + 1.0);
                            $this->htmlFontSize = $this->baseFontSize;
                            $this->htmlBold = false;
                            $justBroke = true;
                        }
                        $this->updateHtmlFont();
                        break;
                    case 'p':
                    case 'div':
                        if (!$isClose) {
                            if ($this->x > $this->lMargin + 0.1) {
                                $this->Ln($curLineH + 1.5);
                                $justBroke = true;
                            }
                        } else {
                            if ($this->x > $this->lMargin + 0.1) {
                                $this->Ln($curLineH + 1.5);
                            }
                            $justBroke = true;
                        }
                        break;
                    case 'br':
                    case 'br/':
                        $this->Ln($curLineH);
                        $justBroke = true;
                        break;
                    case 'ul':
                        $inUl = !$isClose;
                        if (!$isClose) {
                            if ($this->x > $this->lMargin + 0.1) {
                                $this->Ln($curLineH);
                            }
                        } else {
                            if ($this->x > $this->lMargin + 0.1) {
                                $this->Ln($curLineH + 1.0);
                            }
                            $justBroke = true;
                        }
                        break;
                    case 'ol':
                        $inOl = !$isClose;
                        $olCount = 1;
                        if (!$isClose) {
                            if ($this->x > $this->lMargin + 0.1) {
                                $this->Ln($curLineH);
                            }
                        } else {
                            if ($this->x > $this->lMargin + 0.1) {
                                $this->Ln($curLineH + 1.0);
                            }
                            $justBroke = true;
                        }
                        break;
                    case 'li':
                        if (!$isClose) {
                            if ($this->x > $this->lMargin + 0.1) {
                                $this->Ln($curLineH);
                            }
                            $bullet = $inOl ? ($olCount++ . '. ') : (chr(149) . ' ');
                            $this->Write($curLineH, $bullet);
                            $justBroke = false;
                        }
                        break;
                }
            } else {
                $text = html_entity_decode($token, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($justBroke) {
                    $text = ltrim($text);
                }
                if ($text !== '') {
                    $encText = $this->enc($text);
                    $this->Write($curLineH, $encText, $this->htmlHref);
                    $justBroke = false;
                }
            }
        }
        
        // Restaurar estado de márgenes y color
        $this->SetTextColor(0, 0, 0);
        $this->SetLeftMargin($oldLMargin);
        $this->SetRightMargin($oldRMargin);
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
        $this->Cell(0, 5, 'Equipamiento Deportivo', 0, 1, 'L');
        
        // Información del presupuesto a la derecha
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(140, 10);
        $this->Cell(60, 8, 'PRESUPUESTO', 0, 1, 'R');
        
        $this->SetFont('Arial', '', 9);
        $this->SetXY(140, 20);
        $nro = !empty($this->presupuestoData['nro_presupuesto']) ? 
               $this->presupuestoData['nro_presupuesto'] : 
               'N' . chr(176) . ' ' . str_pad($this->presupuestoData['id'], 6, '0', STR_PAD_LEFT);
        $this->Cell(60, 5, $this->enc($nro), 0, 1, 'R');
        
        $this->SetXY(140, 25);
        $this->Cell(60, 5, 'Fecha: ' . date('d/m/Y', strtotime($this->presupuestoData['fecha'])), 0, 1, 'R');
        
        $this->SetXY(140, 30);
        $validez = $this->presupuestoData['validez'] ?? 30;
        $this->Cell(60, 5, $this->enc('Validez: ' . $validez . ' días'), 0, 1, 'R');
        
        // Línea separadora
        $this->Line(10, 45, 200, 45);
    }
    
    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, $this->enc('Página ') . $this->PageNo() . ' de {nb}', 0, 1, 'C');
        
        // Información adicional del pie
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 4, 'Stadium - Equipamiento Deportivo | www.stadium.com | info@stadium.com', 0, 1, 'C');
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
        
        $tieneCliente = !empty($this->clienteData) && (!empty($this->clienteData['apellido']) || !empty($this->clienteData['nombre']));
        
        if ($tieneCliente) {
            $clienteNombre = trim(($this->clienteData['apellido'] ?? '') . ' ' . ($this->clienteData['nombre'] ?? ''));
            $tel = !empty($this->clienteData['telefono']) ? $this->clienteData['telefono'] : (!empty($this->presupuestoData['cli_telefono']) ? $this->presupuestoData['cli_telefono'] : '-');
            $email = !empty($this->clienteData['email']) ? $this->clienteData['email'] : (!empty($this->presupuestoData['cli_email']) ? $this->presupuestoData['cli_email'] : '-');
            $cuit = !empty($this->clienteData['cuit']) ? $this->clienteData['cuit'] : '-';
            $empresa = !empty($this->presupuestoData['cli_empresa']) ? $this->presupuestoData['cli_empresa'] : '';
            
            $this->SetXY(12, $y_start + 2);
            $this->Cell(90, 5, 'Cliente: ' . $this->enc($clienteNombre), 0, 0, 'L');
            
            $this->SetXY(105, $y_start + 2);
            if (!empty($empresa)) {
                $this->Cell(90, 5, $this->enc('Empresa: ' . $empresa), 0, 1, 'L');
            } else {
                $this->Cell(90, 5, $this->enc('Teléfono: ') . $tel, 0, 1, 'L');
            }
            
            $this->SetXY(12, $y_start + 7);
            $this->Cell(90, 5, 'Email: ' . $email, 0, 0, 'L');
            
            $this->SetXY(105, $y_start + 7);
            if (!empty($empresa)) {
                $telCuit = 'Tel: ' . $tel;
                if ($cuit !== '-') {
                    $telCuit .= '  |  CUIT: ' . $cuit;
                }
                $this->Cell(90, 5, $this->enc($telCuit), 0, 1, 'L');
            } else {
                $this->Cell(90, 5, 'CUIT: ' . $cuit, 0, 1, 'L');
            }
        } else {
            // Cliente no especificado: usar datos del presupuesto (cli_nombre, cli_empresa, cli_telefono, cli_email)
            $clienteNombre = !empty($this->presupuestoData['cli_nombre']) 
                ? $this->presupuestoData['cli_nombre'] 
                : (!empty($this->presupuestoData['cliente_nombre']) ? $this->presupuestoData['cliente_nombre'] : 'Cliente no especificado');
            $empresa = !empty($this->presupuestoData['cli_empresa']) ? $this->presupuestoData['cli_empresa'] : '-';
            $tel     = !empty($this->presupuestoData['cli_telefono']) ? $this->presupuestoData['cli_telefono'] : '-';
            $email   = !empty($this->presupuestoData['cli_email']) ? $this->presupuestoData['cli_email'] : '-';
            
            $this->SetXY(12, $y_start + 2);
            $this->Cell(90, 5, 'Cliente: ' . $this->enc($clienteNombre), 0, 0, 'L');
            
            $this->SetXY(105, $y_start + 2);
            $this->Cell(90, 5, $this->enc('Empresa: ' . $empresa), 0, 1, 'L');
            
            $this->SetXY(12, $y_start + 7);
            $this->Cell(90, 5, 'Email: ' . $email, 0, 0, 'L');
            
            $this->SetXY(105, $y_start + 7);
            $this->Cell(90, 5, $this->enc('Teléfono: ') . $tel, 0, 1, 'L');
        }
        
        if(!empty($this->presupuestoData['ubicacion'])) {
            $this->SetXY(12, $y_start + 12);
            $this->MultiCell(185, 5, $this->enc('Dirección: ' . $this->presupuestoData['ubicacion']), 0, 'L');
        }
        
        $this->SetY($y_start + 22);
    }
    
    function PaginaProducto($detalle) {
        $this->AddPage();
        
        $tipo = $detalle['tipo_producto'] ?? 'producto';
        $startY   = 52;
        $leftMargin  = 12;
        $imageWidth  = 85;
        $imageHeight = 95;
        $contentX    = $leftMargin + $imageWidth + 7; // 104
        $contentWidth = 96; // 200 - 104
        
        // Imagen (columna izquierda)
        $imagenTemporal = null;
        $imagen_url = ($tipo == 'equipamiento')
            ? $this->buscarImagenEquipamiento($detalle['idart'])
            : $this->buscarImagenProducto($detalle['idart']);
        
        if($imagen_url && file_exists($imagen_url)) {
            $imagenParaPDF = $this->convertirWebPaJPG($imagen_url);
            if($imagenParaPDF && file_exists($imagenParaPDF)) {
                $this->Image($imagenParaPDF, $leftMargin, $startY, $imageWidth, $imageHeight);
                if($imagenParaPDF !== $imagen_url) $imagenTemporal = $imagenParaPDF;
            } else {
                $this->Rect($leftMargin, $startY, $imageWidth, $imageHeight);
                $this->SetXY($leftMargin, $startY + 43);
                $this->SetFont('Arial', 'I', 9);
                $this->Cell($imageWidth, 5, 'Error imagen', 0, 0, 'C');
            }
        } else {
            $this->Rect($leftMargin, $startY, $imageWidth, $imageHeight);
            $this->SetXY($leftMargin, $startY + 43);
            $this->SetFont('Arial', 'I', 9);
            $this->Cell($imageWidth, 5, 'Sin imagen', 0, 0, 'C');
        }
        
        // Columna derecha: nombre, SKU, tipo
        $this->SetXY($contentX, $startY);
        $this->SetFont('Arial', 'B', 14);
        $this->MultiCell($contentWidth, 7, $this->enc($detalle['producto_nombre'] ?? 'Producto ID: ' . $detalle['idart']), 0, 'L');
        
        $codigo = $detalle['producto_codigo'] ?? '';
        if($codigo) {
            $this->SetX($contentX);
            $this->SetFont('Arial', '', 9);
            $this->Cell($contentWidth, 5, 'SKU: ' . $this->enc($codigo), 0, 1, 'L');
        }
        
        $this->SetX($contentX);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $tipoBadge = ($tipo == 'equipamiento') ? 'Equipamiento Profesional' : 'Producto';
        $this->Cell($contentWidth, 5, $tipoBadge, 0, 1, 'L');
        $this->SetTextColor(0, 0, 0);
        $this->Ln(3);
        
        // Descripción con texto enriquecido junto a la imagen (columna derecha)
        $caracteristicas = $this->obtenerCaracteristicas($detalle['idart'], $tipo);
        if(!empty($caracteristicas)) {
            $this->WriteHTML($caracteristicas, $contentX, $contentWidth, 8);
        }
        
        // Continuar debajo de la imagen si el texto quedó corto
        $currentY = $this->GetY();
        $imageBottomY = $startY + $imageHeight + 5;
        if($currentY < $imageBottomY) {
            $this->SetY($imageBottomY);
        }
        
        // Nota adicional
        if(!empty($detalle['detalle'])) {
            $this->SetX(10);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(0, 0, 150);
            $this->MultiCell(190, 4, $this->enc('Nota: ' . $detalle['detalle']), 0, 'L');
            $this->SetTextColor(0, 0, 0);
        }
        
        // Barra de precios fija al fondo de la página
        $priceY   = 258;
        $cantidad = intval($detalle['cantidad']);
        $precio   = floatval($detalle['precio']);
        $iva      = floatval($detalle['iva'] ?? 21);
        $subtotal = $cantidad * $precio;
        $monto_iva = $subtotal * ($iva / 100);
        $total    = $subtotal + $monto_iva;
        
        $this->SetDrawColor(180, 180, 180);
        $this->Line(10, $priceY - 2, 200, $priceY - 2);
        $this->SetDrawColor(0, 0, 0);
        
        $this->SetFillColor(240, 240, 240);
        $this->Rect(10, $priceY, 190, 14, 'F');
        
        $this->SetFont('Arial', '', 9);
        $this->SetXY(15, $priceY + 2);
        $this->Cell(22, 5, 'Cant: ' . $cantidad, 0, 0, 'L');
        $this->Cell(42, 5, 'P.Unit: $' . number_format($precio, 2), 0, 0, 'L');
        $this->Cell(38, 5, 'Subt: $' . number_format($subtotal, 2), 0, 0, 'L');
        $this->Cell(38, 5, 'IVA (' . number_format($iva, 1) . '%): $' . number_format($monto_iva, 2), 0, 0, 'L');
        
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(0, 100, 0);
        $this->Cell(0, 5, 'TOTAL: $' . number_format($total, 2), 0, 1, 'R');
        $this->SetTextColor(0, 0, 0);
        
        if($imagenTemporal && file_exists($imagenTemporal)) {
            @unlink($imagenTemporal);
        }
    }
    
    function PaginasProductos($detalles) {
        foreach($detalles as $detalle) {
            $this->PaginaProducto($detalle);
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
                    $caracteristicas = '<p><strong>Especificaciones:</strong> ' . implode('. ', $specs) . '.</p>';
                }
                
                // Obtener descripción (conservando texto enriquecido / HTML)
                $stmt = $conn->prepare("SELECT descripcion FROM equipamiento_profesional WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if($row = $result->fetch_assoc()) {
                    if(!empty($row['descripcion'])) {
                        $caracteristicas .= $row['descripcion'];
                    }
                }
                
            } else {
                // Obtener descripción del producto (conservando texto enriquecido / HTML)
                $stmt = $conn->prepare("SELECT descripcion FROM productos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if($row = $result->fetch_assoc()) {
                    if(!empty($row['descripcion'])) {
                        $caracteristicas = $row['descripcion'];
                    }
                }
            }
            
            // No cerrar la conexión - es singleton
            
        } catch(Exception $e) {
            error_log("Error obteniendo características: " . $e->getMessage());
        }
        
        return $caracteristicas;
    }
    
    function PaginaResumen($detalles, $total, $observaciones = '') {
        // Hoja 1: datos del cliente + tabla resumen de precios
        $this->DatosCliente();
        
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 7, 'RESUMEN DEL PRESUPUESTO', 0, 1, 'C');
        $this->Ln(2);
        
        // Encabezado de tabla
        // Cols: Código(22) + Producto(80) + Cant(12) + P.Unit(28) + IVA(15) + Total(33) = 190
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(22, 6, $this->enc('Código'), 1, 0, 'C', true);
        $this->Cell(80, 6, 'Producto', 1, 0, 'C', true);
        $this->Cell(12, 6, 'Cant.', 1, 0, 'C', true);
        $this->Cell(28, 6, 'P.Unit.', 1, 0, 'C', true);
        $this->Cell(15, 6, 'IVA', 1, 0, 'C', true);
        $this->Cell(33, 6, 'Total', 1, 1, 'C', true);
        
        // Filas de productos
        $this->SetFont('Arial', '', 8);
        $totalCalculado = 0;
        foreach($detalles as $detalle) {
            $cantidad = intval($detalle['cantidad']);
            $precio   = floatval($detalle['precio']);
            $iva      = floatval($detalle['iva'] ?? 21);
            $subtotal = $cantidad * $precio;
            $monto_iva = $subtotal * ($iva / 100);
            $totalProducto = $subtotal + $monto_iva;
            $totalCalculado += $totalProducto;
            
            $codigo = $detalle['producto_codigo'] ?? '-';
            $nombre = $detalle['producto_nombre'] ?? 'Producto';
            if(mb_strlen($nombre) > 55) {
                $nombre = mb_substr($nombre, 0, 52) . '...';
            }
            
            $this->Cell(22, 5, $this->enc($codigo), 1, 0, 'C');
            $this->Cell(80, 5, $this->enc($nombre), 1, 0, 'L');
            $this->Cell(12, 5, $cantidad, 1, 0, 'C');
            $this->Cell(28, 5, '$' . number_format($precio, 2), 1, 0, 'R');
            $this->Cell(15, 5, number_format($iva, 0) . '%', 1, 0, 'C');
            $this->Cell(33, 5, '$' . number_format($totalProducto, 2), 1, 1, 'R');
        }
        
        $this->Ln(5);
        
        // Total general (suma de la columna Total)
        $this->SetFont('Arial', 'B', 13);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(157, 10, 'TOTAL PRESUPUESTO:', 0, 0, 'R');
        $this->Cell(33, 10, '$' . number_format($totalCalculado, 2), 1, 1, 'R', true);
        
        $this->Ln(5);
        
        // Observaciones
        if(!empty($observaciones)) {
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 6, 'OBSERVACIONES', 0, 1, 'L');
            $this->SetFont('Arial', '', 9);
            $this->MultiCell(0, 5, $this->enc($observaciones), 1, 'L');
            $this->Ln(3);
        }
        
        // Validez
        if(isset($this->presupuestoData['fecha_vencimiento'])) {
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(150, 0, 0);
            $fecha_venc = date('d/m/Y', strtotime($this->presupuestoData['fecha_vencimiento']));
            $this->Cell(0, 5, $this->enc('* Presupuesto válido hasta el ' . $fecha_venc), 0, 1, 'C');
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
        
        $pdf->PaginaResumen($detalles, floatval($presupuesto['total']), $presupuesto['observaciones'] ?? '');
        $pdf->PaginasProductos($detalles);
        
        $filename = 'Presupuesto_' . str_pad($id, 6, '0', STR_PAD_LEFT) . '.pdf';
        $pdf->Output('D', $filename);
        
    } catch(Exception $e) {
        error_log("Error generando PDF: " . $e->getMessage());
        die('Error generando PDF: ' . $e->getMessage());
    }
} else {
    die('ID de presupuesto no especificado');
}
