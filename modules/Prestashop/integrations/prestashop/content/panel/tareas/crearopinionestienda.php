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

function CrearReviews($pr, $dbh, $i)
{

    $lang = ''.$pr['language'];
    $idlang = 1;
    if ($lang == 'en') {
        $idlang = 2;

    }

    Db::getInstance()->Execute('INSERT INTO aalv_lgcomments_storecomments(id_order, id_customer, id_lang, stars, nick, title, comment, answer, active, position, `date`) VALUES (0,0,'.$idlang.','.($pr['stars'] * 2).",'".$pr['client_name']."','".$pr['title']."','".str_replace("'", '´', $pr['opinion'])."','".$pr['response_text']."',".$pr['approved'].','.$i.",'".$pr['date']."')");

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

Db::getInstance()->Execute('truncate table aalv_lgcomments_storecomments');

$rows = getdatarows($dbh, 'SELECT * FROM product_reviews where model_id is null order by `date` asc');
$i = 1;
foreach ($rows as $row) {
    CrearReviews($row, $dbh, $i);
    $i = $i + 1;
}

echo 'Proceso acabado';
