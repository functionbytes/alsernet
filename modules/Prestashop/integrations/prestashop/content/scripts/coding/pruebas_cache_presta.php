<?php

ini_set('max_execution_time', 176000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
exit();
@mkdir(_PS_CACHE_DIR_, 0777, true);
$dir = dirname(_PS_CACHE_DIR_);
$tmpFile = tempnam($dir, 'cache_log_'.date('Ymd').'_');

@file_put_contents($tmpFile, 'Memoria:'.ini_get('memory_limit').PHP_EOL.PHP_EOL, FILE_APPEND);
/*
Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'configuration`
                SET value=0
                WHERE name=\'PS_SHOP_ENABLE\'');
@file_put_contents($tmpFile, 'Web en mantenimiento('.date('d/m/Y H:i:s').')'.PHP_EOL.PHP_EOL, FILE_APPEND);

sleep(120);
*/
@file_put_contents($tmpFile, 'inicia => regenerateEntireNtree ('.date('d/m/Y H:i:s').')'.PHP_EOL, FILE_APPEND);
$tiempo_inicial_total = microtime(true);
Category::regenerateEntireNtree();
$tiempo_final_total = microtime(true);
$tiempo_final_total = round($tiempo_final_total - $tiempo_inicial_total, 8);
@file_put_contents($tmpFile, 'fin => regenerateEntireNtree ('.date('d/m/Y H:i:s').' '.$tiempo_final_total.')'.PHP_EOL.PHP_EOL, FILE_APPEND);

@file_put_contents($tmpFile, 'No se ejecuta => clearSmartyCache ('.date('d/m/Y H:i:s').' '.$tiempo_final_total.')'.PHP_EOL, FILE_APPEND);
/*
$tiempo_inicial_total = microtime(true);
Tools::clearSmartyCache();
$tiempo_final_total = microtime(true);
$tiempo_final_total = round($tiempo_final_total - $tiempo_inicial_total, 8);
@file_put_contents($tmpFile, 'fin => clearSmartyCache ('.date('d/m/Y H:i:s').' '.$tiempo_final_total.')'.PHP_EOL.PHP_EOL, FILE_APPEND);
*/

@file_put_contents($tmpFile, 'inicio => clearXMLCache ('.date('d/m/Y H:i:s').' '.$tiempo_final_total.')'.PHP_EOL, FILE_APPEND);
$tiempo_inicial_total = microtime(true);
Tools::clearXMLCache();
$tiempo_final_total = microtime(true);
$tiempo_final_total = round($tiempo_final_total - $tiempo_inicial_total, 8);
@file_put_contents($tmpFile, 'fin => clearXMLCache ('.date('d/m/Y H:i:s').' '.$tiempo_final_total.')'.PHP_EOL.PHP_EOL, FILE_APPEND);

file_put_contents($tmpFile, 'inicio => generateIndex ('.date('d/m/Y H:i:s').' '.$tiempo_final_total.')'.PHP_EOL, FILE_APPEND);
$tiempo_inicial_total = microtime(true);
Tools::generateIndex();
$tiempo_final_total = microtime(true);
$tiempo_final_total = round($tiempo_final_total - $tiempo_inicial_total, 8);
file_put_contents($tmpFile, 'fin => generateIndex ('.date('d/m/Y H:i:s').' '.$tiempo_final_total.')'.PHP_EOL.PHP_EOL, FILE_APPEND);

/*
sleep(120);

Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'configuration`
                SET value=1
                WHERE name=\'PS_SHOP_ENABLE\'');
@file_put_contents($tmpFile, 'Web en producción ('.date('d/m/Y H:i:s').')'.PHP_EOL, FILE_APPEND);
*/

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}
