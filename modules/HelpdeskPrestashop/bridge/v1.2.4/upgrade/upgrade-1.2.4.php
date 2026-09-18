<?php

if (! defined('_PS_VERSION_')) {
    exit;
}

/**
 * Acciones nuevas de bono: voucher.redemptions y voucher.status.
 *
 * Solo codigo (helpers/voucher.php y los case de api.php): no hay tablas ni
 * configuracion que crear. Se sube la cache de version igualmente porque
 * api.php cambio como construye y guarda las claves de las acciones sin
 * cliente, y las entradas anteriores ya no encajan con ese esquema.
 *
 * @param  Alsernetbridge  $module
 * @return bool
 */
function upgrade_module_1_2_4($module)
{
    $current = (int) Configuration::get('ALSERNETBRIDGE_CACHE_VERSION') ?: 1;

    return Configuration::updateValue('ALSERNETBRIDGE_CACHE_VERSION', (string) ($current + 1));
}
