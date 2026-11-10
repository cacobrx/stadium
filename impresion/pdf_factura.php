<?php
/**
 * impresion/pdf_factura.php
 * Genera el PDF de una factura (Factura B / A / C) conforme AFIP.
 *
 * Uso:
 *   require_once __DIR__ . '/../impresion/pdf_factura.php';
 *   $pdfString = generar_pdf_factura($conn, $idFactura); // string con el PDF
 */

require_once __DIR__ . '/tfpdf.php';
$qr_path = __DIR__ . '/../qrcode/qrcode.class.php';
if (file_exists($qr_path)) {
    require_once $qr_path;
}

class PDF_Factura extends tFPDF
{
    private array $fac  = [];
    private array $emit = [];
    private array $cli  = [];
    private float $totalFac = 0.0;

    public function setData(array $fac, array $emit, array $cli): void
    {
        $this->fac  = $fac;
        $this->emit = $emit;
        $this->cli  = $cli;
    }

    // ── Encabezado ─────────────────────────────────────────────────────────
    public function Header(): void
    {
        $fac  = $this->fac;
        $emit = $this->emit;

        // Logo (izquierda)
        $logo = __DIR__ . '/../includes/logo.jpg';
        if (file_exists($logo)) {
            $this->Image($logo, 10, 8, 30);
        }

        // Datos del emisor (centro-izquierda)
        $this->SetFont('Arial', 'B', 13);
        $this->SetXY(44, 8);
        $this->Cell(46, 6, utf8_decode($emit['fantasia'] ?: $emit['razonsocial']), 0, 1, 'L');

        $this->SetFont('Arial', '', 8);
        $this->SetX(44);
        $this->Cell(46, 4, utf8_decode('Razón Social: ' . $emit['razonsocial']), 0, 1, 'L');
        $this->SetX(44);
        $this->Cell(46, 4, 'CUIT: ' . $emit['cuit'], 0, 1, 'L');
        $this->SetX(44);
        $this->Cell(46, 4, utf8_decode('Cond. IVA: ' . $emit['condicioniva']), 0, 1, 'L');
        $this->SetX(44);
        $this->Cell(46, 4, utf8_decode($emit['direccion'] . ' — ' . $emit['ciudad']), 0, 1, 'L');

        // Cuadrito de letra (AFIP: centrado en la hoja)
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.8);
        $this->Rect(93, 7, 24, 24);
        $this->SetLineWidth(0.2);
        $this->SetFont('Arial', 'B', 22);
        $this->SetXY(93, 10);
        $this->Cell(24, 13, $fac['letra'], 0, 0, 'C');
        $this->SetFont('Arial', '', 7);
        $this->SetXY(93, 24);
        $this->Cell(24, 5, 'COD. ' . $this->cbteTipoCod($fac['tipocom'], $fac['letra']), 0, 0, 'C');

        // Datos del comprobante (derecha)
        $nroFmt = str_pad($fac['ptovta'] ?? 1, 4, '0', STR_PAD_LEFT)
                . '-'
                . str_pad($fac['numero'] ?? 0, 8, '0', STR_PAD_LEFT);
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(120, 8);
        $this->Cell(80, 6, utf8_decode(strtoupper($fac['tipocom'] ?? 'FACTURA')), 0, 1, 'R');
        $this->SetFont('Arial', '', 9);
        $this->SetXY(120, 15);
        $this->Cell(80, 5, utf8_decode('Nº ') . $nroFmt, 0, 1, 'R');
        $this->SetXY(120, 21);
        $fechaFmt = !empty($fac['fecha']) ? date('d/m/Y', strtotime($fac['fecha'])) : date('d/m/Y');
        $this->Cell(80, 5, 'Fecha: ' . $fechaFmt, 0, 1, 'R');

        // Línea separadora
        $this->Line(10, 33, 200, 33);
    }

    // ── Pie ─────────────────────────────────────────────────────────────────
    public function Footer(): void
    {
        $fac = $this->fac;

        if (!empty($fac['numerocae'])) {
            if (class_exists('QRcode')) {
                $url_qr = $this->getAfipQrUrl();
                $qrcode = new QRcode($url_qr);
                $this->SetFont('Arial', '', 7);
                $this->SetXY(75, 270);
                $this->Cell(30, 5, utf8_decode("Esta administración Federal no se responsabiliza  por los datos ingresados en el detalle de la operación"), 0, 0);
                $qrcode->displayFPDF($this, 5, 255, 30);
            }

            $vto = !empty($fac['fechacae'])
                ? date('d/m/Y', strtotime($fac['fechacae']))
                : '—';
            $this->SetXY(10, 277);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(0, 5, 'CAE: ' . $fac['numerocae'] . '     Vencimiento CAE: ' . $vto, 0, 1, 'C');
        }

        $this->SetXY(10, 282);
        $this->SetFont('Arial', 'I', 7);
        $this->Cell(0, 4, utf8_decode('Página ') . $this->PageNo() . ' de {nb}', 0, 1, 'C');
    }

    private function getAfipQrUrl(): string
    {
        $fac = $this->fac;
        $emit = $this->emit;
        $cli = $this->cli;
        
        $fecha = !empty($fac['fecha']) ? $fac['fecha'] : date('Y-m-d');
        $cuit = (int)str_replace('-', '', $emit['cuit'] ?? '0');
        $ptovta = (int)($fac['ptovta'] ?? 1);
        $numero = (int)($fac['numero'] ?? 0);
        $tipocmp = (int)$this->cbteTipoCod($fac['tipocom'] ?? 'FACTURA', $fac['letra'] ?? 'B');
        $importe = (float)$this->totalFac;
        
        $docRec = (int)str_replace(['-', '.'], '', $cli['cuit'] ?? ($cli['nrodir'] ?? '0'));
        $tipoDocRec = 99;
        if (!empty($cli['cuit']) && strlen(trim($cli['cuit'])) == 11) {
            $tipoDocRec = 80;
        } elseif (!empty($cli['cuit']) || !empty($cli['nrodir'])) {
            $tipoDocRec = 96;
        }

        $a = [
            'ver' => 1,
            'fecha' => $fecha,
            'cuit'  => $cuit,
            'ptoVta' => $ptovta,
            'tipoCmp' => $tipocmp,
            'nroCmp' => $numero,
            'importe' => $importe,
            'moneda' => 'PES',
            'ctz' => 1.0,
            'tipoDocRec' => $tipoDocRec,
            'nroDocRec' => $docRec,
            'tipoCodAut' => 'E',
            'codAut' => (int)($fac['numerocae'] ?? 0)
        ];
        $respuesta = json_encode($a);
        $base64 = base64_encode($respuesta);
        return "https://www.afip.gob.ar/fe/qr/?p=" . $base64;
    }

    // ── Bloque de datos del cliente ─────────────────────────────────────────
    public function bloqueCliente(): void
    {
        $cli = $this->cli;
        $fac = $this->fac;

        $this->SetY(36);

        $nombre    = utf8_decode(trim(($cli['apellido'] ?? '') . ' ' . ($cli['nombre'] ?? '')));
        $domicilio = utf8_decode(trim(($cli['direccion'] ?? '') . ' ' . ($cli['ciudad'] ?? '')));
        $cuitCli   = !empty($cli['cuit']) ? $cli['cuit'] : ($cli['nrodir'] ?? '—');

        $condMap = [
            '0' => 'Consumidor Final',
            '1' => 'Responsable Inscripto',
            '2' => 'Monotributista',
        ];
        $condCli = $condMap[$cli['condicioniva'] ?? '0'] ?? 'Consumidor Final';

        $this->SetFont('Arial', '', 8);
        $this->Cell(95, 5, utf8_decode('Razón Social: ') . $nombre, 0, 0, 'L');
        $this->Cell(95, 5, utf8_decode('Condición IVA: ') . utf8_decode($condCli), 0, 1, 'L');
        $this->Cell(95, 5, 'CUIT / DNI: ' . $cuitCli, 0, 0, 'L');
        if (!empty($fac['docreferencia'])) {
            $this->Cell(95, 5, utf8_decode('Ref.: ' . $fac['docreferencia']), 0, 1, 'L');
        } else {
            $this->Ln(5);
        }
        if ($domicilio !== ' ') {
            $this->Cell(0, 5, utf8_decode('Domicilio: ') . $domicilio, 0, 1, 'L');
        }

        $this->Ln(2);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);
    }

    // ── Tabla de ítems ──────────────────────────────────────────────────────
    public function tablaItems(array $items): void
    {
        // Cabecera
        $this->SetFillColor(30, 30, 50);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(15,  7, 'Cant.',          1, 0, 'C', true);
        $this->Cell(100, 7, utf8_decode('Descripción'), 1, 0, 'L', true);
        $this->Cell(30,  7, 'P. neto unit.',  1, 0, 'R', true);
        $this->Cell(15,  7, 'IVA %',          1, 0, 'C', true);
        $this->Cell(30,  7, 'Total neto',     1, 1, 'R', true);
        $this->SetTextColor(0, 0, 0);

        // Filas
        $this->SetFont('Arial', '', 8);
        $neto   = 0.0;
        $ivaMap = []; // alicuota (string) => monto acumulado

        foreach ($items as $item) {
            if ($this->GetY() > 245) {
                $this->AddPage();
            }

            $cant  = (float)($item['cantidad'] ?? 1);
            $prec  = (float)($item['precio']   ?? 0);
            $alic  = (float)($item['alicuota'] ?? 21.0);
            $tot   = round($cant * $prec, 2);
            $iva   = round($tot * $alic / 100.0, 2);
            $neto += $tot;
            $ivaMap[(string)$alic] = ($ivaMap[(string)$alic] ?? 0.0) + $iva;

            $cantFmt = ($cant == floor($cant))
                ? number_format($cant, 0, ',', '.')
                : number_format($cant, 2, ',', '.');

            $desc = utf8_decode($item['descripcion'] ?? $item['detalle'] ?? '');

            $this->Cell(15,  6, $cantFmt, 1, 0, 'C');
            $this->Cell(100, 6, $desc,    1, 0, 'L');
            $this->Cell(30,  6, '$ ' . number_format($prec, 2, ',', '.'), 1, 0, 'R');
            $this->Cell(15,  6, number_format($alic, 0) . '%', 1, 0, 'C');
            $this->Cell(30,  6, '$ ' . number_format($tot,  2, ',', '.'), 1, 1, 'R');
        }

        // Totales
        $ivaTotal = array_sum($ivaMap);
        $total    = round($neto + $ivaTotal, 2);
        $this->totalFac = $total;

        $this->Ln(2);
        $xTot = 120;

        $this->SetFont('Arial', '', 9);
        $this->SetX($xTot);
        $this->Cell(40, 6, 'Subtotal neto:', 0, 0, 'R');
        $this->Cell(30, 6, '$ ' . number_format($neto, 2, ',', '.'), 0, 1, 'R');

        foreach ($ivaMap as $pct => $monto) {
            $this->SetX($xTot);
            $this->Cell(40, 6, 'IVA ' . $pct . '%:', 0, 0, 'R');
            $this->Cell(30, 6, '$ ' . number_format($monto, 2, ',', '.'), 0, 1, 'R');
        }

        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(220, 220, 220);
        $this->SetX($xTot);
        $this->Cell(40, 7, 'TOTAL:', 1, 0, 'R', true);
        $this->Cell(30, 7, '$ ' . number_format($total, 2, ',', '.'), 1, 1, 'R', true);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────
    private function cbteTipoCod(string $tipo, string $letra): int
    {
        $t = strtoupper(trim($tipo));
        $l = strtoupper(trim($letra));
        if ($t === 'FACTURA')   { return match ($l) { 'A' => 1, 'B' => 6, default => 11 }; }
        if ($t === 'NOTA CRED') { return match ($l) { 'A' => 3, 'B' => 8, default => 13 }; }
        if ($t === 'NOTA DEB')  { return match ($l) { 'A' => 2, 'B' => 7, default => 12 }; }
        return 6;
    }
}

// ── Función pública ───────────────────────────────────────────────────────────

/**
 * Genera el PDF de una factura y lo devuelve como string binario.
 *
 * @throws Exception si la factura no existe
 */
function generar_pdf_factura(mysqli $conn, int $idFactura): string
{
    // Datos de la factura, emisor y cliente
    $stmt = $conn->prepare("
        SELECT f.*,
               d.razonsocial, d.fantasia, d.cuit         AS emit_cuit,
               d.condicioniva AS emit_condiva,
               d.direccion    AS emit_dir,
               d.ciudad       AS emit_ciudad,
               c.nombre, c.apellido,
               c.cuit         AS cli_cuit,
               c.condicioniva AS cli_condiva,
               c.direccion    AS cli_dir,
               c.ciudad       AS cli_ciudad,
               c.nrodir
        FROM facturas f
        LEFT JOIN datoscuit d ON d.id = f.idcuit
        LEFT JOIN clientes  c ON c.id = f.idcli
        WHERE f.id = ?
    ");
    $stmt->bind_param('i', $idFactura);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception("Factura #{$idFactura} no encontrada");
    }

    $fac = $row;
    $emit = [
        'razonsocial'  => $row['razonsocial']  ?? 'STADIUM',
        'fantasia'     => $row['fantasia']      ?? '',
        'cuit'         => $row['emit_cuit']     ?? '',
        'condicioniva' => $row['emit_condiva']  ?? 'Responsable Inscripto',
        'direccion'    => $row['emit_dir']      ?? '',
        'ciudad'       => $row['emit_ciudad']   ?? '',
    ];
    $cli = [
        'nombre'       => $row['nombre']        ?? '',
        'apellido'     => $row['apellido']       ?? '',
        'cuit'         => $row['cli_cuit']       ?? '',
        'condicioniva' => (string)($row['cli_condiva'] ?? '0'),
        'direccion'    => $row['cli_dir']        ?? '',
        'ciudad'       => $row['cli_ciudad']     ?? '',
        'nrodir'       => $row['nrodir']         ?? '',
    ];

    // Ítems del detalle
    $stmt2 = $conn->prepare(
        "SELECT cantidad, descripcion, precio, alicuota FROM facturas_det WHERE idfis = ? ORDER BY id"
    );
    $stmt2->bind_param('i', $idFactura);
    $stmt2->execute();
    $items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();

    // Construir PDF
    $pdf = new PDF_Factura('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 28);
    $pdf->SetAuthor('Stadium');
    $pdf->SetTitle('Factura ' . ($fac['tipocom'] ?? '') . ' ' . ($fac['letra'] ?? '') . ' N° ' . $idFactura);
    $pdf->setData($fac, $emit, $cli);
    $pdf->AddPage();
    $pdf->bloqueCliente();
    $pdf->tablaItems($items);

    return $pdf->Output('S'); // devuelve string
}
