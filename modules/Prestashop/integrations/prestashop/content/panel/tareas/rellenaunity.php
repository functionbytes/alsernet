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

function RellenarUnityProductos($datos)
{

    try {

        $idproduct = $datos['id_product'];
        $unidades_oferta = $datos['unidades_oferta'];

        Db::getInstance()->ExecuteS("UPDATE aalv_product SET unity='".$unidades_oferta."' WHERE id_product=".$idproduct);
        Db::getInstance()->ExecuteS("UPDATE aalv_product_shop SET unity='".$unidades_oferta."' WHERE id_product=".$idproduct);

        echo $idproduct.' '.$unidades_oferta.'<br/>';

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/unity.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$datos[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

function RellenarUnityComb($datos)
{

    try {

        $idproductattribute = $datos['id_product_attribute'];
        $unidades_oferta = $datos['unidades_oferta'];
        $idproduct = Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattribute);

        Db::getInstance()->ExecuteS("UPDATE aalv_product SET unity='".$unidades_oferta."' WHERE id_product=".$idproduct);
        Db::getInstance()->ExecuteS("UPDATE aalv_product_shop SET unity='".$unidades_oferta."' WHERE id_product=".$idproduct);

        echo $idproductattribute.' '.$idproduct.' '.$unidades_oferta.'<br/>';

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/unity.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$datos[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

try {

    $dsn = 'mysql:host=195.55.36.104;dbname=tienda';
    $dbh = new PDO($dsn, 'tiendalvad', 'Nov.299909');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

echo 'Productos<br/>';
$rows = Db::getInstance()->ExecuteS('SELECT * FROM aalv_combinacionunica_import WHERE unidades_oferta>0');

foreach ($rows as $row) {
    RellenarUnityProductos($row);
}

echo 'Combinaciones<br/>';
$rows = Db::getInstance()->ExecuteS('SELECT * FROM aalv_combinaciones_import WHERE unidades_oferta>0');

foreach ($rows as $row) {
    RellenarUnityComb($row);
}
