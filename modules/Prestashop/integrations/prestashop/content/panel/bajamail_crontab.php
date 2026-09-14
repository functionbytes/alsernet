<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}

// Cargar el contexto de PrestaShop
require _PS_ADMIN_DIR_.'/../config/config.inc.php';

// Verificar que el script se ejecute desde CLI
if (php_sapi_name() !== 'cli') {
    exit('Este script solo puede ejecutarse desde la línea de comandos.');
}

// Obtener los registros no procesados
$sql = 'SELECT id_log, id_user, email, description, date_add FROM '._DB_PREFIX_.'alsernet_baja_mail WHERE processed = 0';
$logs = Db::getInstance()->executeS($sql);

if (! $logs) {
    echo "No hay registros pendientes.\n";
    exit;
}

// Procesar los registros
foreach ($logs as $log) {
    echo "Procesando ID: {$log['id_log']} - Email: {$log['email']}\n";

    // Aquí puedes agregar lógica adicional, como enviar un correo o hacer otro proceso
    $fecha_erp = str_replace(' ', 'T', date('Y-m-d H:i:s'));
    $cliente_no_info_comercial = 1;
    $cliente_no_datos_a_terceros = 1;

    $baja_retail = '';
    $resp = '';

    $baja_retail = baja_retail_rocker($log['email']);

    $resp = json_encode([AlvarezERP::savelopd($log['email'], $fecha_erp, $cliente_no_info_comercial, $cliente_no_datos_a_terceros)]);

    // Marcar como procesado
    $updateSQL = 'UPDATE '._DB_PREFIX_."alsernet_baja_mail SET processed = 1, rr_response = '".$baja_retail."', english_management = '".$resp."', processed_date = NOW() WHERE id_log = ".(int) $log['id_log'];

    Db::getInstance()->execute($updateSQL);
}

function baja_retail_rocker($email)
{
    $url = "https://api.retailrocket.ru/api/1.0/partner/6202390b97a5281b48e23cd6/unsubscribe/?email=$email&apiKey=6202390b97a5281b48e23cd7"; // PrestaShop

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // $result = curl_getinfo($ch);
    curl_close($ch);

    return json_encode([
        'http_code' => $http_code,
        'response' => $result,
    ]);
}

echo "Proceso finalizado.\n";
