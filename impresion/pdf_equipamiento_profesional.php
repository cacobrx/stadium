<?php
/*
 * Creado el 11/11/2025
 * Archivo: pdf_equipamiento_profesional.php
 * Stadium2 - Impresión de Equipamiento Profesional
 */

require('pdf_function.php');

class pdf_EQP extends pdf_function {
    // private variables
    var $colonnes;
    var $format;
    var $angle=0;

    function Header() {
        global $nombreemp;
        global $telefonoemp;
        global $colu;
        
        // Logo si existe
        $logo_path = __DIR__ . '/../images/logo.jpg';
        if(file_exists($logo_path))
            $this->Image($logo_path,5,5,30);        

        // Título del reporte
        $this->fact_dev("LISTADO DE EQUIPAMIENTO PROFESIONAL",0,140);

        // Fecha
        $this->addDate(date("d/m/Y"));
        
        // Número de página
        $this->addPageNumber($this->PageNo());
        
        // Marco (en modo horizontal - A4 landscape)
        $r1=5;
        $r2=290;
        $y1=35;
        $y2=200;
        $this->RoundedRect($r1, $y1, ($r2 - $r1), ($y2-$y1), 2.5, 'D');    
        
        $this->SetXY(10, 35);
        $this->SetFont("Arial","B",8);        
        
        // Encabezados de columnas
        $this->SetX($colu[0]);
        $this->Cell(7,5,"ID",0,0,"C");
        $this->SetX($colu[1]);
        $this->Cell(40,5,"Nombre",0,0,"L");    
        $this->SetX($colu[2]);
        $this->Cell(35,5,utf8_decode("Categoría"),0,0,"L");    
        $this->SetX($colu[3]);
        $this->Cell(22,5,"Entrega",0,0,"L");
        $this->SetX($colu[4]);
        $this->Cell(20,5,utf8_decode("Garantía"),0,0,"L");
        $this->SetX($colu[5]);
        $this->Cell(15,5,"Disp.",0,0,"C");
        $this->SetX($colu[6]);
        $this->Cell(15,5,"Instal.",0,0,"C");
        $this->SetX($colu[7]);
        $this->Cell(15,5,"Financ.",0,0,"C");
        $this->Cell(20,5,"Estado",0,1,"C");
                    
        $this->line(5,$this->GetY(),290,$this->GetY());

    }  


    function addDetalle() {
        global $a_id;
        global $a_nombre;
        global $a_categoria;
        global $a_tiempo_entrega;
        global $a_garantia;
        global $a_disponible;
        global $a_instalacion;
        global $a_financiacion;
        global $a_estado;
        global $colu;

        $this->SetFont('Arial','',7);
        for($i=0;$i<count($a_id);$i++) {
            // Verificar salto de página
            if($this->GetY() > 190) {
                $this->AddPage();
            }
            
            // Formatear categoría
            $categoria_formateada = '';
            if ($a_categoria[$i] == 'jirafas') {
                $categoria_formateada = 'Jirafas';
            } elseif ($a_categoria[$i] == 'tableros-marcadores') {
                $categoria_formateada = 'Tableros y Marcadores';
            } else {
                $categoria_formateada = $a_categoria[$i];
            }
            
            $this->SetX($colu[0]);
            $this->Cell(7,5,$a_id[$i],0,0,"C");
            $this->SetX($colu[1]);
            $this->Cell(40,5,utf8_decode(substr($a_nombre[$i], 0, 30)),0,0,"L");    
            $this->SetX($colu[2]);
            $this->Cell(35,5,utf8_decode($categoria_formateada),0,0,"L");                
            $this->SetX($colu[3]);
            $this->Cell(22,5,utf8_decode(substr($a_tiempo_entrega[$i], 0, 15)),0,0,"L");
            $this->SetX($colu[4]);
            $this->Cell(20,5,utf8_decode(substr($a_garantia[$i], 0, 15)),0,0,"L");
            $this->SetX($colu[5]);
            $this->Cell(15,5,$a_disponible[$i],0,0,"C");
            $this->SetX($colu[6]);
            $this->Cell(15,5,$a_instalacion[$i],0,0,"C");
            $this->SetX($colu[7]);
            $this->Cell(15,5,$a_financiacion[$i],0,0,"C");
            $this->Cell(20,5,utf8_decode($a_estado[$i]),0,1,"C");

            // Línea divisoria cada 5 registros para mejor lectura
            if (($i + 1) % 5 == 0) {
                $this->line(5,$this->GetY(),290,$this->GetY());
            }
        }
        $this->line(5,$this->GetY(),290,$this->GetY());
    }
}
?>
