<?php

ini_set('max_execution_time', 176000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../../config/config.inc.php';

$last_processed_record = Db::getInstance()->getValue('SELECT last_processed_record FROM '._DB_PREFIX_.'banner_email_notification_config');
$etiqueta = 'BANNER';
// $last_processed_record = 20000000;

echo 'ULTIMO RECORD PROCESADO --> '.$last_processed_record."\n\n";

$max_id_tarifa_cabecera = Db::getInstance()->getValue("SELECT MAX(id) FROM aalv_integracion_cambios aic WHERE tabla = 'v_sinc_tarifa_cabecera' AND id >= ".$last_processed_record);

if (intval($max_id_tarifa_cabecera) <= intval($last_processed_record)) {
    echo "NO HAY CAMBIOS DE PRECIOS \n\n";
    exit();
}

$sql_tarifa_cabecera = "SELECT JSON_EXTRACT(data, '$.idarticulo')id_articulo, JSON_UNQUOTE(JSON_EXTRACT(data, '$.codigo_iso_pais')) AS iso
                           FROM "._DB_PREFIX_."integracion_cambios aic
                           WHERE tabla = 'v_sinc_tarifa_cabecera'
                           AND data <> 'null'
                           AND id > ".$last_processed_record.'
                           AND tipo = 1
                           ORDER BY id';

$data_tarifa_cabecera = Db::getInstance()->executeS($sql_tarifa_cabecera);
$array_id_articulo = [];
$array_productos = [];

foreach ($data_tarifa_cabecera as $tarifa) {
    if (! array_key_exists($tarifa['id_articulo'], $array_id_articulo)) {
        // Append del nuevo valor al existente
        $array_id_articulo[$tarifa['id_articulo']] = [];
        $array_id_articulo[$tarifa['id_articulo']][] = ($tarifa['iso'] != '' ? $tarifa['iso'] : 'ES');
    } else {
        // Crea la llave y asigna el valor
        $array_id_articulo[$tarifa['id_articulo']][] = ($tarifa['iso'] != '' ? $tarifa['iso'] : 'ES');
    }
}

$sql_query = 'SELECT * FROM
                    (SELECT DISTINCT id_product, etiqueta, id_articulo
                     FROM
                        ((SELECT apa.id_product AS id_product, etiqueta, aci.id_articulo AS id_articulo
                          FROM '._DB_PREFIX_.'combinaciones_import aci
                          LEFT JOIN '._DB_PREFIX_."product_attribute apa
                          ON apa.id_product_attribute = aci.id_product_attribute
                          WHERE etiqueta LIKE '%".$etiqueta."%')
                         UNION
                         (SELECT aci2.id_product AS id_product, etiqueta, aci2.id_articulo AS id_articulo
                          FROM "._DB_PREFIX_."combinacionunica_import aci2
                          WHERE etiqueta LIKE '%".$etiqueta."%')) AS product_list
                     WHERE id_product IS NOT NULL AND id_articulo IN (".implode(', ', array_keys($array_id_articulo)).')) AS pll';

$records = Db::getInstance()->executeS($sql_query);
foreach ($records as $record) {
    if (! array_key_exists($record['id_product'], $array_productos)) {
        // Append del nuevo valor al existente
        $array_productos[$record['id_product']] = [];
        $array_productos[$record['id_product']] = $array_id_articulo[$record['id_articulo']];
    } else {
        // Crea la llave y asigna el valor
        $array_productos[$record['id_product']] = array_merge($array_productos[$record['id_product']], $array_id_articulo[$record['id_articulo']]);
    }
}

$mensaje = 'A continuación se muestra el listado de productos que han cambiado su precio y que están marcados para ser modificado el banner: '.'<br><br>';
foreach ($array_productos as $key => $value) {
    $product = new Product($key);
    $idiomas = implode(',', array_unique($value));
    $mensaje .= $product->id.' ----- <a href="https://a-alvarez.com/'.$product->id.'-'.$product->link_rewrite['1'].'">'.$product->name['1'].'</a>'.' ----- ('.$idiomas.')'.'<br>';
}

if (count($records)) {
    $total_productos = count($array_productos);
    $update_query = 'UPDATE '._DB_PREFIX_.'banner_email_notification_config SET updated_at= CURRENT_TIMESTAMP(), last_processed_record = '.$max_id_tarifa_cabecera;
    $r = Db::getInstance()->execute($update_query);
    if ($r) {
        try {
            sendMailAlerta($mensaje);
            echo "MENSAJE ENVIADO. SE MARCARON UN TOTAL DE  $total_productos PRODUCTOS \n\n";
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
} else {
    echo "NO HAY CAMBIOS DE PRECIOS \n\n";
    exit();
}

function sendMailAlerta($mensaje)
{
    $dest = [];
    $dest[] = 'alvarez@alsernet.es';
    $dest[] = 'anacup@a-alvarez.com';
    $dest[] = 'paola@a-alvarez.com';
    /*$dest[] = "alvarez@alsernet.es";
    $dest[] = "anacup@a-alvarez.com";
    $dest[] = "galvarez@a-alvarez.com";
    $dest[] = "admin@alsernet.es";
    $dest[] = "fcastro@alsernet.es";*/
    // $dest[] = "kettyfernandez@alsernet.es";

    $data = ['{message}' => $mensaje];
    Mail::Send(1,
        'integracion',
        'ALERTA CAMBIO DE BANNERS POR PRECIO',
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
