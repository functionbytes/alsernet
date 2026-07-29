<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

header('Content-Type: text/plain');

function getfieldvalue($dbh, $sql)
{
    $rows = $dbh->query($sql);
    foreach ($rows as $row) {
        return $row[0];
    }
}

function getdatarows($dbh, $sql)
{
    return $dbh->query($sql);
}

// copyImg($manufacturer->id, null, $img_url, 'manufacturers');

function CrearProductReviews($pr, $dbh, $i)
{

    $idmodel = $pr['model_id'];

    $idproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodel);

    if ($idproduct != '') {

        Db::getInstance()->Execute('INSERT INTO aalv_lgcomments_productcomments(id_product, id_product_attribute, id_customer, id_lang, stars, nick, title, comment, answer, active, position, `date`) VALUES ('.$idproduct.',0,1,1,'.($pr['stars'] * 2).",'".$pr['client_name']."','".$pr['title']."','".$pr['opinion']."','".$pr['response_text']."',".$pr['approved'].','.$i.",'".$pr['date']."')");

    } else {
        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/reviewserrores.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error No existe el modelo '.$idmodel);
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$pr[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarezps_migracion_db';
    $dbh = new PDO($dsn, 'alvarezps_migracion_dbu', 'eyb54%X45');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

Db::getInstance()->Execute('truncate table aalv_lgcomments_productcomments');

$rows = getdatarows($dbh, 'SELECT * FROM product_reviews');
$i = 1;
foreach ($rows as $row) {
    CrearProductReviews($row, $dbh, $i);
    $i = $i + 1;
}

echo 'Proceso acabado';
