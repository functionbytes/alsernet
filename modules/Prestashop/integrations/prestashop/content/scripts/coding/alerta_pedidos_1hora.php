<?php

ini_set('max_execution_time', 176000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../../config/config.inc.php';

// Buscamos el ultimo pedido que ingreso
$orden = Db::getInstance()->ExecuteS('SELECT `date_add` FROM aalv_orders ORDER BY id_order DESC LIMIT 1');

if (validarFechaHora($orden[0]['date_add'])) {
    // Crear un objeto DateTime
    $date = new DateTime($orden[0]['date_add']);

    // Dar formato a la fecha y hora
    $fechaFormateada = $date->format('d/m/Y H:i:s');
    sendMailAlerta('El ultimo pedido fue '.$fechaFormateada);
}

// Función para validar si una fecha y hora ha pasado más de una hora
function validarFechaHora($fechaHora)
{
    // Convertir las horas a objetos DateTime
    $hora_servidor_dt = new DateTime;
    $hora_comparar_dt = new DateTime($fechaHora);

    // Calcular la diferencia
    $diferencia = $hora_servidor_dt->diff($hora_comparar_dt);

    // Convertir la diferencia a segundos
    $diferencia_segundos = ($diferencia->h * 3600) + ($diferencia->i * 60) + $diferencia->s;

    // Verificar si la diferencia es mayor a una hora (3600 segundos)
    if ($diferencia_segundos > 3600) {
        return true;
    }

    return false;
}

function sendMailAlerta($mensaje)
{
    $dest = [];
    $dest[] = 'alvarez@alsernet.es';
    $dest[] = 'anacup@a-alvarez.com';
    $dest[] = 'galvarez@a-alvarez.com';
    $dest[] = 'admin@alsernet.es';
    $dest[] = 'fcastro@alsernet.es';

    $data = ['{message}' => $mensaje];
    Mail::Send(1,
        'integracion',
        'Alerta de Pedidos',
        $data,
        $dest,
        Configuration::get('PS_SHOP_NAME'),
        'desarrollotest@a-alvarez.com',
        'desarrollotest',
        [],
        null,
        _PS_MAIL_DIR_,
        false,
        1
    );
}
