<?php
/*
 * Creado el 21/02/2021 15:50:38
 * Autor: gus
 * Archivo: afip_json.php
 * planbsistemas.com.ar
 */

class afip_json {
    var $respuesta="";
    var $base64="";
    var $url="";
    
    function __construct($id) {
        require_once 'clases/adm_fis.php';
        require_once 'clases/adm_cuit.php';
        global $cfg;
        $fis=new adm_fis_1($id);
        $adm=new adm_cuit_1(1);
        $fecha=$fis->getFecha();
        $cuit=$adm->getCuit();
        $ptovta=$adm->getPtovta();
        $numero=$fis->getNumero();
        $tipocmp=$fis->getCodigocomp();
        $importe=$fis->getTotal();
        $moneda="PES";
        $tipodocrec=$fis->getTipodocrec();
//        echo $tipodocrec;
        $nrodocrec=$fis->getNrocuit();
        $codaut=$fis->getNumerocae();
        $a=array(
            'ver' => 1,
            'fecha' => $fecha,
            'cuit'  => (int)$cuit,
            'ptoVta' => (int)$ptovta,
            'tipoCmp' => (int)$tipocmp,
            'nroCmp' => (int)$numero,
            'importe' => (float)$importe,
            'moneda' => $moneda,
            'ctz' => (float)1,
            'tipoDocRec' => (int)$tipodocrec,
            'nroDocRec' => (int)$nrodocrec,
            'tipoCodAut' => 'E',
            'codAut' => (int)$codaut
        );
        $this->respuesta= json_encode($a);
        $this->base64= base64_encode($this->respuesta);
        $this->url="https://www.afip.gob.ar/fe/qr/?p=".$this->base64;
       
    }
    
    function getRespuesta() { return $this->respuesta; }
    function getBase64() { return $this->base64; }
    function getUrl() { return $this->url; }
}