<?php
/*
 * Creado el 29/12/2025 11:02
 * Autor: gus
 * Archivo: index.php
 */

// Headers CORS - deben estar primero
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Manejar peticiones OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ .  '/vendor/autoload.php';

// Tokens de MercadoPago (definidos en config/mercadopago.php, no versionado)
require_once __DIR__ . '/../../config/mercadopago.php';

try {
    // Recibir el JSON del request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log para debug
    error_log("Input recibido: " . $input);
    error_log("Data decodificado: " . print_r($data, true));
    
    if (!$data) {
        throw new Exception('No se recibieron datos válidos. Input: ' . substr($input, 0, 200));
    }
    
    // Determinar qué token usar según el modo
    $mode = isset($data['mode']) ? $data['mode'] : 'sandbox';
    $token = ($mode === 'production') ? MP_TOKEN_PRODUCTION_CHECKOUT : MP_TOKEN_SANDBOX_CHECKOUT;
    
    // Configurar el token según el modo seleccionado
    MercadoPago\SDK::setAccessToken($token);
    
    // Crear objeto de preferencia
    $preference = new MercadoPago\Preference();
    
    // Crear items
    $items = array();
    foreach ($data['items'] as $itemData) {
        $item = new MercadoPago\Item();
        $item->title = $itemData['title'];
        $item->quantity = $itemData['quantity'];
        $item->unit_price = $itemData['unit_price'];
        $items[] = $item;
    }
    
    // Asignar items a la preferencia
    $preference->items = $items;
    
    // Configurar información del pagador
    if (isset($data['payer'])) {
        $payer = new MercadoPago\Payer();
        $payer->name = $data['payer']['first_name'];
        $payer->surname = $data['payer']['last_name'];
        $payer->email = $data['payer']['email'];
        
        if (isset($data['payer']['phone'])) {
            $payer->phone = array(
                "number" => (string)$data['payer']['phone']['number']
            );
        }
        
        if (isset($data['payer']['identification'])) {
            $payer->identification = array(
                "type" => $data['payer']['identification']['type'],
                "number" => (string)$data['payer']['identification']['number']
            );
        }
        
        if (isset($data['payer']['address'])) {
            $address = array(
                "street_name" => $data['payer']['address']['street_name']
            );
            
            // Agregar campos opcionales si existen
            if (isset($data['payer']['address']['street_number'])) {
                $address['street_number'] = $data['payer']['address']['street_number'];
            }
            if (isset($data['payer']['address']['zip_code'])) {
                $address['zip_code'] = $data['payer']['address']['zip_code'];
            }
            
            $payer->address = $address;
        }
        
        $preference->payer = $payer;
    }
    
    // Configurar URLs de retorno
    if (isset($data['back_urls'])) {
        $preference->back_urls = array(
            "success" => $data['back_urls']['success'],
            "failure" => $data['back_urls']['failure'],
            "pending" => $data['back_urls']['pending']
        );
    }
    
    // Configurar retorno automático tras el pago
    if (isset($data['auto_return'])) {
        $preference->auto_return = $data['auto_return'];
    } else {
        $preference->auto_return = "approved";
    }
    
    // Guardar preferencia
    $preference->save();
    
    // Log de la respuesta
    error_log("Preference saved - ID: " . $preference->id);
    error_log("Init point: " . $preference->init_point);
    error_log("Sandbox init point: " . $preference->sandbox_init_point);
    
    // Responder con el link de pago
    echo json_encode([
        'success' => true,
        'mode' => $mode,
        'init_point' => $preference->init_point, // Link para producción
        'sandbox_init_point' => $preference->sandbox_init_point, // Link para testing
        'preference_id' => $preference->id
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error en MercadoPago API: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
