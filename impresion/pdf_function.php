<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require('tfpdf.php');

class pdf_function extends tFPDF {
    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
                            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }

    function Rotate($angle, $x=-1, $y=-1)
    {
        if($x==-1)
            $x=$this->x;
        if($y==-1)
            $y=$this->y;
        if($this->angle!=0)
            $this->_out('Q');
        $this->angle=$angle;
        if($angle!=0)
        {
            $angle*=M_PI/180;
            $c=cos($angle);
            $s=sin($angle);
            $cx=$x*$this->k;
            $cy=($this->h-$y)*$this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
        }
    }

    function _endpage()
    {
        if($this->angle!=0)
        {
            $this->angle=0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    // public functions
    function sizeOfText( $texte, $largeur )
    {
        $index    = 0;
        $nb_lines = 0;
        $loop     = TRUE;
        while ( $loop )
        {
            $pos = strpos($texte, "\n");
            if (!$pos)
            {
                $loop  = FALSE;
                $ligne = $texte;
            }
            else
            {
                $ligne  = substr( $texte, $index, $pos);
                $texte = substr( $texte, $pos+1 );
            }
            $length = floor( $this->GetStringWidth( $ligne ) );
            $res = 1 + floor( $length / $largeur) ;
            $nb_lines += $res;
        }
        return $nb_lines;
    }
    
    // Company
    function addCliente( $nom, $adresse, $fila=0)
    {
        $x1 = 10;
        $y1 = $fila+8;
        //Positionnement en bas
        $this->SetXY( $x1, $y1 );
        $this->SetFont('Arial','B',12);
        $length = $this->GetStringWidth( $nom );
        $this->Cell( $length, 2, utf8_decode($nom));
        if($adresse!="") {
            $this->SetXY( $x1, $y1 + 4 );
            $this->SetFont('Arial','',10);
            $length = $this->GetStringWidth( $adresse );
            //Coordonnées de la société
            $this->Cell( $length, 2, utf8_decode($adresse));
            //$lignes = $this->sizeOfText( $adresse, $length) ;
            //$this->MultiCell($length, 4, $adresse);
        }
    }

    // Label and number of invoice/estimate
    function Titulo( $libelle, $num="" )
    {
        $r1  = $this->w - 80;
        $r2  = $r1 + 68;
        $y1  = 6;
        $y2  = $y1 + 2;
        $mid = ($r1 + $r2 ) / 2;

        $texte  = $libelle . $num;    
        $szfont = 12;
        $loop   = 0;

        while ( $loop == 0 )
        {
           $this->SetFont( "Arial", "B", $szfont );
           $sz = $this->GetStringWidth( $texte );
           if ( ($r1+$sz) > $r2 )
              $szfont --;
           else
              $loop ++;
        }

        $this->SetLineWidth(0.1);
        $this->SetFillColor(192);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 2.5, 'DF');
        $this->SetXY( $r1+1, $y1+2);
        $this->Cell($r2-$r1 -1,5, $texte, 0, 0, "C" );
    }
    
    function addDate2( $date )
    {
        $r1  = $this->w - 50;
        $r2  = $r1 + 40;
        $y1  = 6;
        $y2  = $y1+2 ;
        $mid = $y1 + ($y2 / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
        //$this->Line( $r1, $mid, $r2, $mid);
        //$this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+3 );
        $this->SetXY($r1+1, $y1+2);
        $this->SetFont( "Arial", "B", 10);
        $this->Cell(15,5, "Fecha", 0, 0, "C");
        //$this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+9 );
        $this->SetXY($r1+20, $y1+2);
        $this->SetFont( "Arial", "", 10);
        $this->Cell(10,5,$date, 0,0, "C");
    }
    

    function addDate( $date )
    {
        $r1  = $this->w - 36;
        $r2  = $r1 + 30;
        $y1  = 17;
        $y2  = $y1 ;
        $mid = $y1 + ($y2 / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
        $this->Line( $r1, $mid, $r2, $mid);
        $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+3 );
        $this->SetFont( "Arial", "B", 10);
        $this->Cell(10,5, "Fecha", 0, 0, "C");
        $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+9 );
        $this->SetFont( "Arial", "", 10);
        $this->Cell(10,5,$date, 0,0, "C");
    }

    function addClient( $ref, $tit="Cliente" )
    {
        $r1  = $this->w - 31;
        $r2  = $r1 + 19;
        $y1  = 17;
        $y2  = $y1;
        $mid = $y1 + ($y2 / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
        $this->Line( $r1, $mid, $r2, $mid);
        $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+3 );
        $this->SetFont( "Arial", "B", 10);
        $this->Cell(10,5, $tit, 0, 0, "C");
        $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1 + 9 );
        $this->SetFont( "Arial", "", 10);
        $this->Cell(10,5,$ref, 0,0, "C");
    }


    function addPageNumber( $page, $tex="Pag." )
    {
        $r1  = $this->w - 55;
        $r2  = $r1 + 19;
        $y1  = 17;
        $y2  = $y1;
        $mid = $y1 + ($y2 / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
        $this->Line( $r1, $mid, $r2, $mid);
        $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+3 );
        $this->SetFont( "Arial", "B", 10);
        $this->Cell(10,5, $tex, 0, 0, "C");
        $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1 + 9 );
        $this->SetFont( "Arial", "", 10);
        $this->Cell(10,5,$page, 0,0, "C");
    }
    
    function TituloRec( $texto )
    {
        $r1  = 5;
        $r2  = $this->w - 80;
        $y1  = 17;
        $y2  = $y1;
        $mid = $y1 + ($y2 / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
        //$this->Line( $r1, $mid, $r2, $mid);
        $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+3 );
        //$this->SetFont( "Arial", "B", 12);
        $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1 + 9 );
        $this->SetFont( "Arial", "B", 12);
        $this->Cell(10,5,$texto, 0,0, "C");
    }
    
    function Empresa($nom, $dir, $tel) {
        $this->SetFont("Arial", "B",12);
        $this->SetXY(10,5);
        $this->Cell(100,4,  utf8_decode($nom),0,1);
        $this->SetFont("Arial","",10);
        $this->SetX(10);
        $this->Cell(100,4,utf8_decode($dir),0,1);
        $this->SetX(10);
        $this->Cell(100,4,utf8_decode($tel),0,1);

    }
    
    function addPageNumber2( $page )
    {
        $r1  = $this->w - 80;
        $r2  = $r1 + 30;
        $y1  = 6;
        $y2  = $y1+2;
        $mid = $y1 + ($y2 / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
        //$this->Line( $r1, $mid, $r2, $mid);
        //$this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+3 );
        $this->SetXY($r1+1,$y1+2);
        $this->SetFont( "Arial", "B", 10);
        $this->Cell(10,5, "ID", 0, 0, "C");
        //$this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1 + 9 );
        $this->SetFont( "Arial", "", 10);
        $this->Cell(10,5,$page, 0,0, "C");
    }
    
    
    function fact_dev( $libelle, $cnt=0, $fila=0 )
    {
        $num="";
        $r1  = 10+$fila;
        $r2  = 80+$fila;
        $y1  = $cnt+6;
        $y2  = $y1 + 2;
        //echo "y1: $y1 | y2: $y2<br>";
        $mid = ($r1 + $r2 ) / 2;

        $texte  = $libelle . $num;    
        $szfont = 12;
        $loop   = 0;

        while ( $loop == 0 )
        {
           $this->SetFont( "Arial", "B", $szfont );
           $sz = $this->GetStringWidth( $texte );
           if ( ($r1+$sz) > $r2 )
              $szfont --;
           else
              $loop ++;
        }

        $this->SetLineWidth(0.1);
        $this->SetFillColor(192);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2-$cnt, 2.5, 'DF');
        
        $this->SetXY( $r1+1, $y1+2);
        $this->Cell($r2-$r1 -1,5, $texte, 0, 0, "C" );
    }
    
    function TituloRemito( $libelle, $cnt, $colu=5 )
    {
        $num="";
        $r1  = $colu;
        $r2  = 95 + $colu - 5;
        $y1  = $cnt+6;
        $y2  = $y1 + 2;
        //echo "y1: $y1 | y2: $y2<br>";
        $mid = ($r1 + $r2 ) / 2;

        $texte  = $libelle . $num;    
        $szfont = 12;
        $loop   = 0;

        while ( $loop == 0 )
        {
           $this->SetFont( "Arial", "B", $szfont );
           $sz = $this->GetStringWidth( $texte );
           if ( ($r1+$sz) > $r2 )
              $szfont --;
           else
              $loop ++;
        }

        $this->SetLineWidth(0.1);
        $this->SetFillColor(192);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2-$cnt, 2.5, 'D');
        $this->SetXY( $r1+1, $y1+2);
        $this->Cell($r2-$r1 -1,5, $texte, 0, 0, "C" );
    }
    
    function EAN13($x, $y, $barcode, $h=16, $w=.35)
    {
        $this->Barcode($x,$y,$barcode,$h,$w,13);
    }

    function UPC_A($x, $y, $barcode, $h=16, $w=.35)
    {
        $this->Barcode($x,$y,$barcode,$h,$w,12);
    }

    function GetCheckDigit($barcode)
    {
        //Compute the check digit
        $sum=0;
        for($i=1;$i<=11;$i+=2)
            $sum+=3*$barcode[$i];
        for($i=0;$i<=10;$i+=2)
            $sum+=$barcode[$i];
        $r=$sum%10;
        if($r>0)
            $r=10-$r;
        return $r;
    }

    function TestCheckDigit($barcode)
    {
        //Test validity of check digit
        $sum=0;
        for($i=1;$i<=11;$i+=2)
            $sum+=3*$barcode[$i];
        for($i=0;$i<=10;$i+=2)
            $sum+=$barcode[$i];
        return ($sum+$barcode[12])%10==0;
    }

    function Barcode($x, $y, $barcode, $h, $w, $len)
    {
        //Padding
        //echo $barcode;
        $barcode=str_pad($barcode,$len-1,'0',STR_PAD_LEFT);
        if($len==12)
            $barcode='0'.$barcode;
        //Add or control the check digit
        if(strlen($barcode)==12)
            $barcode.=$this->GetCheckDigit($barcode);
        elseif(!$this->TestCheckDigit($barcode))
            $this->Error('Incorrect check digit');
        //Convert digits to bars
        $codes=array(
            'A'=>array(
                '0'=>'0001101','1'=>'0011001','2'=>'0010011','3'=>'0111101','4'=>'0100011',
                '5'=>'0110001','6'=>'0101111','7'=>'0111011','8'=>'0110111','9'=>'0001011'),
            'B'=>array(
                '0'=>'0100111','1'=>'0110011','2'=>'0011011','3'=>'0100001','4'=>'0011101',
                '5'=>'0111001','6'=>'0000101','7'=>'0010001','8'=>'0001001','9'=>'0010111'),
            'C'=>array(
                '0'=>'1110010','1'=>'1100110','2'=>'1101100','3'=>'1000010','4'=>'1011100',
                '5'=>'1001110','6'=>'1010000','7'=>'1000100','8'=>'1001000','9'=>'1110100')
            );
        $parities=array(
            '0'=>array('A','A','A','A','A','A'),
            '1'=>array('A','A','B','A','B','B'),
            '2'=>array('A','A','B','B','A','B'),
            '3'=>array('A','A','B','B','B','A'),
            '4'=>array('A','B','A','A','B','B'),
            '5'=>array('A','B','B','A','A','B'),
            '6'=>array('A','B','B','B','A','A'),
            '7'=>array('A','B','A','B','A','B'),
            '8'=>array('A','B','A','B','B','A'),
            '9'=>array('A','B','B','A','B','A')
            );
        $code='101';
        $p=$parities[$barcode[0]];
        for($i=1;$i<=6;$i++)
            $code.=$codes[$p[$i-1]][$barcode[$i]];
        $code.='01010';
        //print_r($codes);
        for($i=7;$i<=12;$i++)
            $code.=$codes['C'][$barcode[$i]];
        $code.='101';
        //Draw bars
        for($i=0;$i<strlen($code);$i++)
        {
            if($code[$i]=='1')
                $this->Rect($x+$i*$w,$y,$w,$h,'F');
        }
        //Print text uder barcode
        $this->SetFont('Arial','',8);
        $this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
    }
    
    
    function i25($xpos, $ypos, $code, $basewidth=1, $height=10){

        $wide = $basewidth;
        $narrow = $basewidth / 3 ;

        // wide/narrow codes for the digits
        $barChar['0'] = 'nnwwn';
        $barChar['1'] = 'wnnnw';
        $barChar['2'] = 'nwnnw';
        $barChar['3'] = 'wwnnn';
        $barChar['4'] = 'nnwnw';
        $barChar['5'] = 'wnwnn';
        $barChar['6'] = 'nwwnn';
        $barChar['7'] = 'nnnww';
        $barChar['8'] = 'wnnwn';
        $barChar['9'] = 'nwnwn';
        $barChar['A'] = 'nn';
        $barChar['Z'] = 'wn';

        // add leading zero if code-length is odd
        if(strlen($code) % 2 != 0){
            $code = '0' . $code;
        }

        $this->SetFont('Arial', '', 10);
        $this->Text($xpos, $ypos + $height + 4, $code);
        $this->SetFillColor(0);

        // add start and stop codes
        $code = 'AA'.strtolower($code).'ZA';

        for($i=0; $i<strlen($code); $i=$i+2){
            // choose next pair of digits
            $charBar = $code[$i];
            $charSpace = $code[$i+1];
            // check whether it is a valid digit
            if(!isset($barChar[$charBar])){
                $this->Error('Invalid character in barcode: '.$charBar);
            }
            if(!isset($barChar[$charSpace])){
                $this->Error('Invalid character in barcode: '.$charSpace);
            }
            // create a wide/narrow-sequence (first digit=bars, second digit=spaces)
            $seq = '';
            for($s=0; $s<strlen($barChar[$charBar]); $s++){
                $seq .= $barChar[$charBar][$s] . $barChar[$charSpace][$s];
            }
            for($bar=0; $bar<strlen($seq); $bar++){
                // set lineWidth depending on value
                if($seq[$bar] == 'n'){
                    $lineWidth = $narrow;
                }else{
                    $lineWidth = $wide;
                }
                // draw every second value, because the second digit of the pair is represented by the spaces
                if($bar % 2 == 0){
                    $this->Rect($xpos, $ypos, $lineWidth, $height, 'F');
                }
                $xpos += $lineWidth;
            }
        }
    }
    
    
    

    
}
