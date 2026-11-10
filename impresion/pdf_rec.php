<?php
/*
 * Creado el 18/10/2018 10:12:05
 * Autor: gus
 * Archivo: prv_ctacte.php
 * planbsistemas.com.ar
 */

require('pdf_function.php');

class pdf_REC extends pdf_function {
    // private variables
    var $colonnes;
    var $format;
    var $angle=0;

    function Header() {
        require_once 'clases/datesupport.php';
        require_once 'clases/support.php';
        $dsup=new datesupport();
        $sup=new support();
        global $nombreemp;
        global $telefonoemp;
        global $colu;
        global $tipo;
        global $cliente;
        
        $logo=$sup->vercliente();
        if(file_exists("images/$logo.jpg"))
            $this->Image("images/$logo.jpg",5,5);        

//    $this->Titulo( "");
        $this->fact_dev( "RECIBOS",0,100);

    //$pdf->temporaire( $cen->getNombre() );
        $this->addDate( $dsup->getFechaNormalCorta(date("Y-m-d")));
    //$pdf->addClient($cli->getId());
        $this->addPageNumber($this->PageNo());
        $r1=5;
        $r2=205;
        $y1=35;
        $y2=290;
        $this->RoundedRect($r1, $y1, ($r2 - $r1), ($y2-$y1), 2.5, 'D');    
        $this->SetX(10);
        $this->SetFont("Arial", "B",10);
        $this->Cell(10,5, utf8_decode($cliente),0,0,"L");
        $this->Ln(10);        
        $this->SetFont("Arial","B",8);        
        $this->SetX($colu[0]);
        $this->Cell(20,5,"Fecha",0,0,"C");
        $this->SetX($colu[1]);
        $this->Cell(10,5,"Cliente",0,0,"L");
        $this->SetX($colu[2]);
        $this->Cell(20,5,"Tipo",0,0,"R");    
        $this->SetX($colu[3]);
        $this->Cell(20,5,"Detalle",0,0,"R");    
        $this->SetX($colu[4]);
        $this->Cell(20,5,"Importe",0,1,"R");    
    }  


    function addDetalle() {
        require_once 'clases/datesupport.php';
        $dsup=new datesupport();
        global $cartel;
        global $a_fec;
        global $a_cli;
        global $a_tip;
        global $a_det;
        global $a_imp;
        global $colu;


        $this->SetFont('Arial','',8);
        for($i=0;$i<count($a_fec);$i++) {
            $this->SetX($colu[0]);
            $this->Cell(20,5,$dsup->getFechaNormalCorta($a_fec[$i]),0,0,"C");
            $this->SetX($colu[1]);
            $this->Cell(5,5,$a_cli[$i],0,0,"L");
            $this->SetX($colu[2]);
            $this->Cell(20,5,$a_tip[$i],0,0,"R");    
            $this->SetX($colu[3]);
            $this->Cell(20,5,$a_det[$i],0,0,"R");                
            $this->SetX($colu[4]);
            $this->Cell(20,5,number_format($a_imp[$i],2),0,1,"R");               

            $this->line(5,$this->GetY(),205,$this->GetY());
        }

    }
}
