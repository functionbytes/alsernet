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
    $stdout = fopen(dirname(__FILE__).'/queriescatproductnew.txt', 'a');
    fwrite($stdout, $sql);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function RecrearCombinacion($data, $dbh)
{

    $productoerp = Db::getInstance()->getValue('select id_origen from aalv_combinaciones_import where id_product_attribute='.$data['id_product_attribute']);
    echo '<br>Producto erp: '.$productoerp;
    echo '<br>Producto presta: '.$data['id_product'].' '.$data['id_product_attribute'];

    $rowperfiles = getdatarows($dbh, 'SELECT id_valor, orden FROM perfiles_prod where id_producto='.$productoerp.' order by orden');

    $idattributes = [];
    $errorattr = false;
    foreach ($rowperfiles as $row) {

        echo '<br>Valor: '.$row[0];
        $idattr = ''.Db::getInstance()->getValue('SELECT id_attribute FROM aalv_attribute_import WHERE id_origen='.$row[0]);
        if ($idattr != '') {
            $idattributes[] = (int) $idattr;
        } else {
            $errorattr = true;
            echo '<br>--- error No existe el atributo '.$row[0].' para el producto '.$productoerp;
        }
    }

    if (! $errorattr) {

        $combination = new Combination((int) $data['id_product_attribute']);
        $combination->setAttributes($idattributes);
        $combination->update();
        // dump($idattributes);
    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

// $rows = getdatarows($dbh,"SELECT id FROM modelo order by id limit ". ((int)Tools::getValue("product")) .",1");

$rows = Db::getInstance()->ExecuteS('select id_product_attribute, id_product from aalv_product_attribute where id_product_attribute not in (select id_product_attribute from aalv_product_attribute_combination) and id_product_attribute in (select id_product_attribute from aalv_combinaciones_import)  order by id_product_attribute');

foreach ($rows as $row) {
    RecrearCombinacion($row, $dbh);

}

echo 'acaba';
