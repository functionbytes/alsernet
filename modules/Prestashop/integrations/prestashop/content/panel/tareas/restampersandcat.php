<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

// <add key="aDSNMYSQL" value="DRIVER={MySQL ODBC 3.51 Driver};SERVER=82.223.36.198;DATABASE=psaddis_lacasadelosaromas;UID=psaddis_aromas;PWD=1@p.i5HS1y;OPTION=3;" />

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

function Crearcategoria($datos, $dbh)
{

    // SELECT id, id_padre, elemento, orden, url FROM navegacion order by id_padre, orden limit 10

    try {

        $nombre = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav where id='.$datos['id']);
        $nombreen = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['id']." and idioma='en'");
        $nombrede = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['id']." and idioma='de'");
        $nombrefr = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['id']." and idioma='fr'");
        $nombrept = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['id']." and idioma='pt'");

        $nombre = str_replace('&#39;', "'", $nombre);
        $nombreen = str_replace('&#39;', "'", $nombreen);
        $nombrede = str_replace('&#39;', "'", $nombrede);
        $nombrefr = str_replace('&#39;', "'", $nombrefr);
        $nombrept = str_replace('&#39;', "'", $nombrept);

        $nombre = str_replace('<span class="notranslate">', '', $nombre);
        $nombreen = str_replace('<span class="notranslate">', '', $nombreen);
        $nombrede = str_replace('<span class="notranslate">', '', $nombrede);
        $nombrefr = str_replace('<span class="notranslate">', '', $nombrefr);
        $nombrept = str_replace('<span class="notranslate">', '', $nombrept);

        $nombre = str_replace('</span>', '', $nombre);
        $nombreen = str_replace('</span>', '', $nombreen);
        $nombrede = str_replace('</span>', '', $nombrede);
        $nombrefr = str_replace('</span>', '', $nombrefr);
        $nombrept = str_replace('</span>', '', $nombrept);

        $nombre = str_replace('&amp;', '&', $nombre);
        $nombreen = str_replace('&amp;', '&', $nombreen);
        $nombrede = str_replace('&amp;', '&', $nombrede);
        $nombrefr = str_replace('&amp;', '&', $nombrefr);
        $nombrept = str_replace('&amp;', '&', $nombrept);

        if ($nombre != '') {

            $idcats = Db::getInstance()->ExecuteS('SELECT id_cat FROM aalv_category_import WHERE id_origen='.$datos['id']);

            foreach ($idcats as $idcatsitem) {

                $category = new Category($idcatsitem['id_cat']);
                $category->name[1] = $nombre;
                $category->name[2] = $nombreen;
                $category->name[3] = $nombrefr;
                $category->name[4] = $nombrept;
                $category->name[5] = $nombrede;

                $category->meta_title[1] = $nombre;
                $category->meta_title[2] = $nombreen;
                $category->meta_title[3] = $nombrefr;
                $category->meta_title[4] = $nombrept;
                $category->meta_title[5] = $nombrede;

                $category->link_rewrite[1] = Tools::link_rewrite($category->name[1]);
                $category->link_rewrite[2] = Tools::link_rewrite($category->name[2]);
                $category->link_rewrite[3] = Tools::link_rewrite($category->name[3]);
                $category->link_rewrite[4] = Tools::link_rewrite($category->name[4]);
                $category->link_rewrite[5] = Tools::link_rewrite($category->name[5]);
                $category->update();

                echo $idcatsitem['id_cat'].' '.$nombre;

            }

        }

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(_PS_ADMIN_DIR_.'/categoriaserrores.txt', 'a');
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

$catampersand = Db::getInstance()->ExecuteS("SELECT id_origen FROM aalv_category_import WHERE id_cat in (SELECT id_category FROM aalv_category_lang WHERE id_lang = 1 AND name LIKE '% and %')");

$idcats = [];
foreach ($catampersand as $rowamp) {
    $idcats[] = $rowamp['id_origen'];
}

$listaids = implode(',', $idcats);

$rows = getdatarows($dbh, 'SELECT id FROM valores_nav where id in ('.$listaids.')');
foreach ($rows as $row) {
    Crearcategoria($row, $dbh);
}

echo 'Proceso acabado';
