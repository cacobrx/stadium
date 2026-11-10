<?php
/*
 * Creado el 21/10/2025
 * Archivo: pdf_proveedores.php
 * Stadium2 - Impresión de Proveedores
 */

require('pdf_function.php');

class pdf_PRV extends pdf_function {
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
        $this->fact_dev("LISTADO DE PROVEEDORES",0,100);

        // Fecha
        $this->addDate(date("d/m/Y"));
        
        // Número de página
        $this->addPageNumber($this->PageNo());
        
        // Marco
        $r1=5;
        $r2=205;
        $y1=35;
        $y2=280;
        $this->RoundedRect($r1, $y1, ($r2 - $r1), ($y2-$y1), 2.5, 'D');    
        
        $this->SetXY(10, 35);
        $this->SetFont("Arial","B",9);        
        
        // Encabezados de columnas
        $this->SetX($colu[0]);
        $this->Cell(15,5,"ID",0,0,"C");
        $this->SetX($colu[1]);
        $this->Cell(40,5,"Proveedor",0,0,"L");    
        $this->SetX($colu[2]);
        $this->Cell(45,5,utf8_decode("Dirección"),0,0,"L");    
        $this->SetX($colu[3]);
        $this->Cell(25,5,utf8_decode("Teléfono"),0,0,"L");
        $this->SetX($colu[4]);
        $this->Cell(25,5,"CUIT",0,1,"L");    
                    
        $this->line(5,$this->GetY(),205,$this->GetY());

    }  


    function addDetalle() {
        global $a_id;
        global $a_apellido;
        global $a_nombre;
        global $a_direccion;
        global $a_telefono;
        global $a_cuit;
        global $colu;

        $this->SetFont('Arial','',8);
        for($i=0;$i<count($a_id);$i++) {
            // Verificar salto de página
            if($this->GetY() > 270) {
                $this->AddPage();
            }
            
            $this->SetX($colu[0]);
            $this->Cell(15,5,$a_id[$i],0,0,"C");
            $this->SetX($colu[1]);
            $this->Cell(40,5,utf8_decode($a_apellido[$i]." ".$a_nombre[$i]),0,0,"L");    
            $this->SetX($colu[2]);
            $this->Cell(45,5,utf8_decode($a_direccion[$i]),0,0,"L");                
            $this->SetX($colu[3]);
            $this->Cell(25,5,utf8_decode($a_telefono[$i]),0,0,"L");
            $this->SetX($colu[4]);
            $this->Cell(25,5,$a_cuit[$i],0,1,"L");

            // Línea divisoria
        }
            $this->line(5,$this->GetY(),205,$this->GetY());

    }
}
