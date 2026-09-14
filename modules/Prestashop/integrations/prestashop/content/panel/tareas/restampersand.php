<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

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

function CrearProducto($datos, $dbh)
{

    // SELECT id, nombre, descripcion, activo, descripcion_destacado, precio_consultar_ficha, id_marca, venta_telefono, imagen_seo FROM modelo
    // https://www.a-alvarez.com/productsimages/gafas-polarizadas-niza-518902.jpg/1080/fill/ffffff

    try {

        $nombre = $datos['nombre'];
        $nombreen = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$datos['id']." and idioma='en'");
        $nombrede = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$datos['id']." and idioma='de'");
        $nombrefr = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$datos['id']." and idioma='fr'");
        $nombrept = getfieldvalue($dbh, 'SELECT nombre FROM modelo_idioma where id_modelo='.$datos['id']." and idioma='pt'");

        if ($nombreen == '') {
            $nombreen = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$datos['id']." and idioma='en'");
        }
        if ($nombrede == '') {
            $nombrede = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$datos['id']." and idioma='de'");
        }
        if ($nombrefr == '') {
            $nombrefr = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$datos['id']." and idioma='fr'");
        }
        if ($nombrept == '') {
            $nombrept = getfieldvalue($dbh, 'SELECT nombre FROM modelo_traduccion_auto where id_modelo='.$datos['id']." and idioma='pt'");
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

        $nombre = str_replace('#', '', $nombre);
        $nombreen = str_replace('#', '', $nombreen);
        $nombrede = str_replace('#', '', $nombrede);
        $nombrefr = str_replace('#', '', $nombrefr);
        $nombrept = str_replace('#', '', $nombrept);

        $nombre = str_replace('&#39;', "'", $nombre);
        $nombreen = str_replace('&#39;', "'", $nombreen);
        $nombrede = str_replace('&#39;', "'", $nombrede);
        $nombrefr = str_replace('&#39;', "'", $nombrefr);
        $nombrept = str_replace('&#39;', "'", $nombrept);

        $nombre = str_replace('&amp;', '&', $nombre);
        $nombreen = str_replace('&amp;', '&', $nombreen);
        $nombrede = str_replace('&amp;', '&', $nombrede);
        $nombrefr = str_replace('&amp;', '&', $nombrefr);
        $nombrept = str_replace('&amp;', '&', $nombrept);

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

        echo '<br/>'.$nombre.' - '.$datos['id'];
        echo '<br/>'.$nombreen.' - '.$datos['id'];
        echo '<br/>'.$nombrede.' - '.$datos['id'];
        echo '<br/>'.$nombrefr.' - '.$datos['id'];
        echo '<br/>'.$nombrept.' - '.$datos['id'];

        $idproduct = Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$datos['id']);

        if ($idproduct != '') {

            // update
            $product = new Product($idproduct);

            $product->name[1] = $nombre;
            $product->name[2] = $nombreen;
            $product->name[3] = $nombrefr;
            $product->name[4] = $nombrept;
            $product->name[5] = $nombrede;

            $product->link_rewrite[1] = Tools::link_rewrite(substr($product->name[1], 0, 128));
            $product->link_rewrite[2] = Tools::link_rewrite(substr($product->name[2], 0, 128));
            $product->link_rewrite[3] = Tools::link_rewrite(substr($product->name[3], 0, 128));
            $product->link_rewrite[4] = Tools::link_rewrite(substr($product->name[4], 0, 128));
            $product->link_rewrite[5] = Tools::link_rewrite(substr($product->name[5], 0, 128));

            $product->update();

        }

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/erroresrestampersand.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$datos[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$productosampersand = Db::getInstance()->ExecuteS("SELECT id_modelo FROM aalv_product_import WHERE id_product in (SELECT id_product FROM aalv_product_lang WHERE id_lang = 1 AND name LIKE '% and %')");

$idprods = [];
foreach ($productosampersand as $rowamp) {
    $idprods[] = $rowamp['id_modelo'];
}

$listaids = implode(',', $idprods);

$rows = getdatarows($dbh, 'SELECT id, nombre, descripcion, activo, descripcion_destacado, precio_consultar_ficha, id_marca, venta_telefono, imagen_seo, fecha_creacion, texto_productos_no_vendibles FROM modelo where id in ('.$listaids.')');
foreach ($rows as $row) {
    CrearProducto($row, $dbh);
}
