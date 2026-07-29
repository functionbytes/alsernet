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

function RellenarSeoCategorias($datos, $dbh)
{

    try {

        $categoriasorigen = $datos['id_categoria'];

        $meta_title = $datos['title'];

        $meta_desc = $datos['description'];

        $descripcion = $datos['texto_superior'].$datos['texto_inferior'];

        $idioma = $datos['idioma'];

        if ($idioma == 'es') {
            $idlang = 1;
        }
        if ($idioma == 'pt') {
            $idlang = 4;
        }

        $listacat = explode('-', $categoriasorigen);

        foreach ($listacat as $idorigen) {

            $idcat = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_origen='.$idorigen);

            if ($idcat != '') {
                echo '<br/>llega '.$idcat;
                $category = new Category($idcat);

                $category->meta_title[$idlang] = $meta_title;
                $category->meta_description[$idlang] = $meta_desc;
                $category->description[$idlang] = $descripcion;
                $category->update();

            }

        }

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(dirname(__FILE__).'/seocategoriaserrores.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$datos[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

try {

    $dsn = 'mysql:host=195.55.36.104;dbname=alvarezseo';
    $dbh = new PDO($dsn, 'tiendalvad', 'Nov.299909');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT * FROM textos_categorias where id_categoria is not null');
foreach ($rows as $row) {
    RellenarSeoCategorias($row, $dbh);
}
echo 'acaba';
