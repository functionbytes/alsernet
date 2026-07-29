<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function escribesql($sql)
{
    $stdout = fopen(dirname(__FILE__).'/queriesimagescomb.txt', 'a');
    fwrite($stdout, $sql);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function ProcesarImagenes($id_product_attribute)
{

    // recuperar id_product

    $id_product = ''.Db::getInstance()->getValue('select id_product from aalv_product_attribute where id_product_attribute = '.$id_product_attribute);

    // recuperar imagenes de tipo modelo (producto=0)

    if ($id_product != '') {
        $sql = 'SELECT id_image FROM aalv_image_import WHERE id_product = '.$id_product.' AND producto = 0 ORDER BY id_image ASC';

        $rows = Db::getInstance()->ExecuteS($sql);

        foreach ($rows as $row) {
            $id_image = $row['id_image'];
            escribesql('REPLACE INTO aalv_product_attribute_image(id_product_attribute, id_image) VALUES ('.$id_product_attribute.','.$id_image.');');
        }
    }

}

$sql = 'SELECT distinct id_product_attribute FROM aalv_product_attribute_image ORDER BY id_product_attribute ASC';

$rows = Db::getInstance()->ExecuteS($sql);

foreach ($rows as $row) {
    ProcesarImagenes($row['id_product_attribute']);
}

echo 'Proceso acabado';
