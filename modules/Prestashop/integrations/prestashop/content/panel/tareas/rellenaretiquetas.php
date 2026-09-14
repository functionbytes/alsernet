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

function RellenarEtiqueta($datos, $dbh)
{

    try {

        $id_product_attribute = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import where id_origen='.$datos['id']);

        if ($id_product_attribute != '') {

            Db::getInstance()->Execute('UPDATE aalv_combinaciones_import set unidades_oferta='.$datos['unidades_oferta'].",etiqueta='".$datos['etiqueta']."',estado_gestion=".$datos['estado_gestion'].',es_segunda_mano='.$datos['es_segunda_mano'].',externo_disponibilidad='.$datos['externo_disponibilidad'].",codigo_proveedor='".$datos['codigo_proveedor']."',precio_costo_proveedor=".$datos['precio_costo_proveedor'].',tarifa_proveedor='.$datos['tarifa_proveedor'].' WHERE id_product_attribute='.$id_product_attribute);

        } else {

            $id_product = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_origen='.$datos['id']);
            if ($id_product != '') {

                Db::getInstance()->Execute('UPDATE aalv_combinacionunica_import set unidades_oferta='.$datos['unidades_oferta'].",etiqueta='".$datos['etiqueta']."',estado_gestion=".$datos['estado_gestion'].',es_segunda_mano='.$datos['es_segunda_mano'].',externo_disponibilidad='.$datos['externo_disponibilidad'].",codigo_proveedor='".$datos['codigo_proveedor']."',precio_costo_proveedor=".$datos['precio_costo_proveedor'].',tarifa_proveedor='.$datos['tarifa_proveedor'].' WHERE id_product='.$id_product);

            } else {

                $d = new DateTime;
                $stdout = fopen(dirname(__FILE__).'/etiquetaserrores.txt', 'a');
                fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error No existe');
                fwrite($stdout, "\n");
                fwrite($stdout, ' --- datos '.$datos[0]);
                fwrite($stdout, "\n");
                fclose($stdout);

            }

        }

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/etiquetaserrores.txt', 'a');
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

$rows = getdatarows($dbh, 'SELECT * FROM producto');
foreach ($rows as $row) {
    RellenarEtiqueta($row, $dbh);
}
