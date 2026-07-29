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

function addlog($message)
{

    $stdout = fopen(dirname(__FILE__).'/imageattrasign2.txt', 'a');
    fwrite($stdout, $message);
    fwrite($stdout, "\n");
    fclose($stdout);

}

function CrearSQLImagenes($row, $dbh)
{
    $filename = $row['path_imagen'];
    $idproductoalv = $row['id_producto'];
    echo 'file '.$filename;
    $product_attribute = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen='.$idproductoalv);
    if ($product_attribute != '') {
        // buscar imagen

        echo 'pattribute '.$product_attribute;
        $idimage = ''.Db::getInstance()->getValue("SELECT id_image FROM aalv_image_import WHERE filename='".$filename."'");

        echo 'image '.$idimage;

        if ($idimage != '') {

            $idpa = ''.Db::getInstance()->getValue('select id_product_attribute from aalv_product_attribute_image where id_product_attribute='.$product_attribute.' and id_image='.$idimage);
            if ($idpa != '') {
                addlog('REPLACE INTO aalv_product_attribute_image(id_product_attribute, id_image) VALUES ('.$product_attribute.','.$idimage.');');
            }

        }
    }
}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT id_producto, orden, path_imagen FROM producto_imagen where estado=1 and id_producto=100007741 order by 1,2');
foreach ($rows as $row) {
    CrearSQLImagenes($row, $dbh);
}

echo '<br/>acaba';

addlog('acaba');
