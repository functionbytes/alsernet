<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

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

function escribesql($sql)
{
    $stdout = fopen(dirname(__FILE__).'/queriescatproduct.txt', 'a');
    fwrite($stdout, $sql);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function ExisteProducto($data, $dbh)
{

    $id_modelo = $data['id_modelo'];

    $id_productps = $data['id_product'];

    $existeorigen = ''.getfieldvalue($dbh, 'select id from modelo where id='.$id_modelo);

    if ($existeorigen == '') {

        echo '<br/>Debería borrarse '.$id_productps;

    }
}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = Db::getInstance()->ExecuteS('select id_product, id_modelo from aalv_product_import');

// $rows = getdatarows($dbh,"SELECT id FROM modelo order by id lim
foreach ($rows as $row) {
    ExisteProducto($row, $dbh);
}

echo 'acaba';
