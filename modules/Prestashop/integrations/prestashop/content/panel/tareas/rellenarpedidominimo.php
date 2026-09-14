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

function RellenarCantidadMinimaProductos($datos)
{

    try {

        $idproduct = $datos['id_product'];
        $etiquetas = $datos['etiqueta'];

        // coger etiqueta

        preg_match("/(.*?)PEDIDO_MINIMO\_([0-9]*)/", $etiquetas, $coincidencias);
        // dump($coincidencias);

        $unidmin = $coincidencias[2];

        if (''.$unidmin != '') {

            Db::getInstance()->ExecuteS('UPDATE aalv_product SET minimal_quantity='.$unidmin.' WHERE id_product='.$idproduct);
            Db::getInstance()->ExecuteS('UPDATE aalv_product_shop SET minimal_quantity='.$unidmin.' WHERE id_product='.$idproduct);

        }

        echo $idproduct.' '.$unidmin.' '.$etiquetas.'<br/>';

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/pedidominimo.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$datos[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

function RellenarCantidadMinimaComb($datos)
{

    try {

        $idproductattribute = $datos['id_product_attribute'];
        $etiquetas = $datos['etiqueta'];

        // coger etiqueta

        preg_match("/(.*?)PEDIDO_MINIMO\_([0-9]*)/", $etiquetas, $coincidencias);
        // dump($coincidencias);

        $unidmin = $coincidencias[2];

        if (''.$unidmin != '') {

            Db::getInstance()->ExecuteS('UPDATE aalv_product_attribute SET minimal_quantity='.$unidmin.' WHERE id_product_attribute='.$idproductattribute);
            Db::getInstance()->ExecuteS('UPDATE aalv_product_attribute_shop SET minimal_quantity='.$unidmin.' WHERE id_product_attribute='.$idproductattribute);

        }

        echo $idproductattribute.' '.$unidmin.' '.$etiquetas.'<br/>';

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/pedidominimo.txt', 'a');
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
$rows = Db::getInstance()->ExecuteS("SELECT * FROM aalv_combinacionunica_import WHERE etiqueta LIKE '%PEDIDO_MINIMO%'");

foreach ($rows as $row) {
    RellenarCantidadMinimaProductos($row);
}

echo 'Combinaciones<br/>';
$rows = Db::getInstance()->ExecuteS("SELECT * FROM aalv_combinaciones_import WHERE etiqueta LIKE '%PEDIDO_MINIMO%'");

foreach ($rows as $row) {
    RellenarCantidadMinimaComb($row);
}
