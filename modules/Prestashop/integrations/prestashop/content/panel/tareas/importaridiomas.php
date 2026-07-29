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

function addsql($texto)
{
    $stdout = fopen(dirname(__FILE__).'/corregiridiomas.txt', 'a');
    fwrite($stdout, $texto);
    fwrite($stdout, "\n");
    fclose($stdout);
}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

function getDescripcion($dbh, $id_modelo)
{

    $descripcion = getfieldvalue($dbh, 'SELECT descripcion FROM modelo where id='.$id_modelo);

    $descripcionen = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_idioma where id_modelo='.$id_modelo." and idioma='en'");
    $descripcionde = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_idioma where id_modelo='.$id_modelo." and idioma='de'");
    $descripcionfr = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_idioma where id_modelo='.$id_modelo." and idioma='fr'");
    $descripcionpt = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_idioma where id_modelo='.$id_modelo." and idioma='pt'");

    if ($descripcionen == '') {
        $descripcionen = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_traduccion_auto where id_modelo='.$id_modelo." and idioma='en'");
    }
    if ($descripcionde == '') {
        $descripcionde = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_traduccion_auto where id_modelo='.$id_modelo." and idioma='de'");
    }
    if ($descripcionfr == '') {
        $descripcionfr = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_traduccion_auto where id_modelo='.$id_modelo." and idioma='fr'");
    }
    if ($descripcionpt == '') {
        $descripcionpt = getfieldvalue($dbh, 'SELECT descripcion FROM modelo_traduccion_auto where id_modelo='.$id_modelo." and idioma='pt'");
    }

    if ($descripcionen == '') {
        $descripcionen = $descripcion;
    }
    if ($descripcionde == '') {
        $descripcionde = $descripcion;
    }
    if ($descripcionfr == '') {
        $descripcionfr = $descripcion;
    }
    if ($descripcionpt == '') {
        $descripcionpt = $descripcion;
    }

    $desc = [];
    $desc[1] = $descripcion;
    $desc[2] = $descripcionen;
    $desc[3] = $descripcionfr;
    $desc[4] = $descripcionpt;
    $desc[5] = $descripcionde;

    return $desc;

}

function getNombre($dbh, $id_modelo)
{

    $nombre = getfieldvalue($dbh, 'SELECT nombre FROM modelo where id='.$id_modelo);

    $nombreen = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$id_modelo." and idioma='en'");
    $nombrede = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$id_modelo." and idioma='de'");
    $nombrefr = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$id_modelo." and idioma='fr'");
    $nombrept = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$id_modelo." and idioma='pt'");

    if ($nombreen == '') {
        $nombreen = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$id_modelo." and idioma='en'");
    }
    if ($nombrede == '') {
        $nombrede = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$id_modelo." and idioma='de'");
    }
    if ($nombrefr == '') {
        $nombrefr = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$id_modelo." and idioma='fr'");
    }
    if ($nombrept == '') {
        $nombrept = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$id_modelo." and idioma='pt'");
    }

    if ($nombreen == '') {
        $nombreen = $nombre;
    }
    if ($nombrede == '') {
        $nombrede = $nombre;
    }
    if ($nombrefr == '') {
        $nombrefr = $nombre;
    }
    if ($nombrept == '') {
        $nombrept = $nombre;
    }

    $nombre = str_replace('&quot;', '"', $nombre);
    $nombreen = str_replace('&quot;', '"', $nombreen);
    $nombrede = str_replace('&quot;', '"', $nombrede);
    $nombrefr = str_replace('&quot;', '"', $nombrefr);
    $nombrept = str_replace('&quot;', '"', $nombrept);

    $nombre = str_replace('&#39;', "'", $nombre);
    $nombreen = str_replace('&#39;', "'", $nombreen);
    $nombrede = str_replace('&#39;', "'", $nombrede);
    $nombrefr = str_replace('&#39;', "'", $nombrefr);
    $nombrept = str_replace('&#39;', "'", $nombrept);

    $nombre = str_replace('#', '', $nombre);
    $nombreen = str_replace('#', '', $nombreen);
    $nombrede = str_replace('#', '', $nombrede);
    $nombrefr = str_replace('#', '', $nombrefr);
    $nombrept = str_replace('#', '', $nombrept);

    $nombre = str_replace('&amp;', '&', $nombre);
    $nombreen = str_replace('&amp;', '&', $nombreen);
    $nombrede = str_replace('&amp;', '&', $nombrede);
    $nombrefr = str_replace('&amp;', '&', $nombrefr);
    $nombrept = str_replace('&amp;', '&', $nombrept);

    $nombre = str_replace('&empty;', ' ', $nombre);
    $nombreen = str_replace('&empty;', ' ', $nombreen);
    $nombrede = str_replace('&empty;', ' ', $nombrede);
    $nombrefr = str_replace('&empty;', ' ', $nombrefr);
    $nombrept = str_replace('&empty;', ' ', $nombrept);

    $nombre = str_replace(';', ',', $nombre);
    $nombreen = str_replace(';', ',', $nombreen);
    $nombrede = str_replace(';', ',', $nombrede);
    $nombrefr = str_replace(';', ',', $nombrefr);
    $nombrept = str_replace(';', ',', $nombrept);

    $nombre = str_replace('=', ' ', $nombre);
    $nombreen = str_replace('=', ' ', $nombreen);
    $nombrede = str_replace('=', ' ', $nombrede);
    $nombrefr = str_replace('=', ' ', $nombrefr);
    $nombrept = str_replace('=', ' ', $nombrept);

    $nombre = strip_tags($nombre);
    $nombreen = strip_tags($nombreen);
    $nombrede = strip_tags($nombrede);
    $nombrefr = strip_tags($nombrefr);
    $nombrept = strip_tags($nombrept);

    $nombre = substr($nombre, 0, 128);
    $nombreen = substr($nombreen, 0, 128);
    $nombrede = substr($nombrede, 0, 128);
    $nombrefr = substr($nombrefr, 0, 128);
    $nombrept = substr($nombrept, 0, 128);

    $name = [];
    $name[1] = $nombre;
    $name[2] = $nombreen;
    $name[3] = $nombrefr;
    $name[4] = $nombrept;
    $name[5] = $nombrede;

    return $name;

}

function getNombrePS($id_product)
{
    $name = [];
    for ($i = 1; $i <= 5; $i++) {
        $name[$i] = ''.Db::getInstance()->getValue('SELECT name FROM aalv_product_lang WHERE id_product='.$id_product.' and id_lang='.$i);
    }

    return $name;
}

function getDescripcionPS($id_product)
{
    $desc = [];
    for ($i = 1; $i <= 5; $i++) {
        $desc[$i] = ''.Db::getInstance()->getValue('SELECT description FROM aalv_product_lang WHERE id_product='.$id_product.' and id_lang='.$i);
    }

    return $desc;
}

// $rowsp =  Db::getInstance()->ExecuteS("SELECT id_product, id_modelo FROM aalv_product_import WHERE id_modelo in (SELECT fila FROM aalv_integracion_cambios WHERE tabla ='v_sinc_w_modelo') union SELECT id_product, id_modelo FROM aalv_product_import WHERE id_product>=55053 order by id_product");

$rowsp = Db::getInstance()->ExecuteS("SELECT id_product, id_modelo FROM aalv_product_import WHERE id_modelo in (SELECT fila FROM aalv_integracion_cambios WHERE tabla ='v_sinc_w_modelo') order by id_product");

foreach ($rowsp as $prod) {

    $producto = $prod['id_product'];
    $modelo = $prod['id_modelo'];

    $nombre = getNombre($dbh, $modelo);
    $desc = getDescripcion($dbh, $modelo);

    $nombrePS = getNombrePS($producto);
    $descPS = getDescripcionPS($producto);

    for ($i = 1; $i <= 5; $i++) {

        if ($nombre[$i] != $nombrePS[$i]) {
            addsql("UPDATE aalv_product_lang SET name ='".pSQL($nombre[$i])."' where id_product=".$producto.' and id_lang='.$i.';');
            addsql("UPDATE aalv_product_lang SET link_rewrite ='".Tools::link_rewrite($nombre[$i])."' where id_product=".$producto.' and id_lang='.$i.';');
        }

        if ($desc[$i] != $descPS[$i]) {
            addsql("UPDATE aalv_product_lang SET description ='".pSQL($desc[$i], true)."' where id_product=".$producto.' and id_lang='.$i.';');
        }

    }

}

echo 'acaba';

// dump(getNombre($dbh,100048569));
// dump(getDescripcion($dbh,100048569));

// dump(getNombrePS(55901));
// dump(getDescripcionPS(55901));
