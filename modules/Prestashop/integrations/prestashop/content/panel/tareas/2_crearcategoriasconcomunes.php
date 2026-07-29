<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

// <add key="aDSNMYSQL" value="DRIVER={MySQL ODBC 3.51 Driver};SERVER=82.223.36.198;DATABASE=psaddis_lacasadelosaromas;UID=psaddis_aromas;PWD=1@p.i5HS1y;OPTION=3;" />

function safeName($sString)
{
    $sReturn = $sString;

    $sReturn = strtr($sReturn, "()!$'?: ,&+-/.", '');

    $a = ['', '', '', '', '', '', '', '¥', 'µ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'];
    $b = ['S', 'O', 'Z', 's', 'o', 'z', 'Y', 'Y', 'u', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'];
    $sReturn = str_replace($a, $b, $sReturn);
    $sReturn = trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($sReturn))));

    $sReturn = str_replace(' ', '_', $sReturn);

    return $sReturn;
}

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

        $nombre = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav where id='.$datos['elemento']);
        $nombreen = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['elemento']." and idioma='en'");
        $nombrede = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['elemento']." and idioma='de'");
        $nombrefr = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['elemento']." and idioma='fr'");
        $nombrept = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['elemento']." and idioma='pt'");

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

        $nombre = str_replace('&amp;', ' and ', $nombre);
        $nombreen = str_replace('&amp;', ' and ', $nombreen);
        $nombrede = str_replace('&amp;', ' and ', $nombrede);
        $nombrefr = str_replace('&amp;', ' and ', $nombrefr);
        $nombrept = str_replace('&amp;', ' and ', $nombrept);

        $nombre = str_replace('&', ' and ', $nombre);
        $nombreen = str_replace('&', ' and ', $nombreen);
        $nombrede = str_replace('&', ' and ', $nombrede);
        $nombrefr = str_replace('&', ' and ', $nombrefr);
        $nombrept = str_replace('&', ' and ', $nombrept);

        if ($nombre != '') {

            // ver si existe la categoria en aalv_category_import

            $idcat = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$datos['id']);

            if ($idcat == '') {

                // ver si $datos['id_padre'] pertenece a una categoria comun, si es así

                // coger categoria padre

                $catpadres = Db::getInstance()->ExecuteS('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$datos['id_padre']);

                foreach ($catpadres as $catpadreitem) {

                    // ver si existe catpadre

                    $catpadre = $catpadreitem['id_cat'];

                    $catpadreid = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category WHERE id_category='.$catpadre);

                    if ($catpadreid != '') {

                        $category = new Category;
                        $category->id_parent = $catpadre;
                        $category->active = 1;

                        $category->id_shop_default = 1;
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

                        $category->link_rewrite[1] = safeName($category->name[1]);
                        $category->link_rewrite[2] = safeName($category->name[2]);
                        $category->link_rewrite[3] = safeName($category->name[3]);
                        $category->link_rewrite[4] = safeName($category->name[4]);
                        $category->link_rewrite[5] = safeName($category->name[5]);
                        $category->add();
                        $category->addGroupsIfNoExist(1);
                        $category->addGroupsIfNoExist(2);
                        $category->addGroupsIfNoExist(3);

                        $orden = ''.$datos['orden'];
                        if ($orden == '') {
                            $orden = '0';
                        }

                        $idnav = $datos['id'];
                        $sql = 'INSERT INTO aalv_category_import(id_cat, id_origen, id_padre, url, orden, id_nav) VALUES ('.$category->id.','.$datos['elemento'].','.$datos['id_padre'].",'".$datos['url']."',".$orden.','.$idnav.')';
                        Db::getInstance()->Execute($sql);

                        $shopping = ''.getfieldvalue($dbh, 'SELECT in_google_shopping FROM valores_nav where id='.$datos['elemento']);

                        if ($shopping == '') {
                            $shopping = '0';
                        }

                        $sql = 'INSERT INTO aalv_category_gshopping(id_category, id_gshopping) VALUES ('.$category->id.','.$shopping.')';
                        Db::getInstance()->Execute($sql);

                    } else {
                        $d = new DateTime;
                        $stdout = fopen(_PS_ADMIN_DIR_.'/categoriaserrores.txt', 'a');
                        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- no existe padre');
                        fwrite($stdout, "\n");
                        fwrite($stdout, ' --- datos '.$datos[0]);
                        fwrite($stdout, "\n");
                        fclose($stdout);

                    }

                }

            } else {

                $idcats = Db::getInstance()->ExecuteS('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$datos['id']);

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

                    $category->link_rewrite[1] = safeName($category->name[1]);
                    $category->link_rewrite[2] = safeName($category->name[2]);
                    $category->link_rewrite[3] = safeName($category->name[3]);
                    $category->link_rewrite[4] = safeName($category->name[4]);
                    $category->link_rewrite[5] = safeName($category->name[5]);
                    $category->update();

                    $shopping = ''.getfieldvalue($dbh, 'SELECT in_google_shopping FROM valores_nav where id='.$datos['elemento']);

                    if ($shopping == '') {
                        $shopping = '0';
                    }

                    $sql = 'REPLACE INTO aalv_category_gshopping(id_category, id_gshopping) VALUES ('.$category->id.','.$shopping.')';
                    Db::getInstance()->Execute($sql);

                }

            }

        } else {

            $d = new DateTime;
            $stdout = fopen(_PS_ADMIN_DIR_.'/categoriaserrores.txt', 'a');
            fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- categoria vacia');
            fwrite($stdout, "\n");
            fwrite($stdout, ' --- datos '.$datos[0]);
            fwrite($stdout, "\n");
            fclose($stdout);

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

function CrearcategoriaTiendas($datos, $dbh)
{

    // SELECT id, id_padre, elemento, orden, url FROM navegacion order by id_padre, orden limit 10

    $nombre = $datos['nombre'];
    $nombreen = getfieldvalue($dbh, 'SELECT nombre FROM tiendas_idiomas where id_tienda='.$datos['id']." and idioma='en'");
    $nombrede = getfieldvalue($dbh, 'SELECT nombre FROM tiendas_idiomas where id_tienda='.$datos['id']." and idioma='de'");
    $nombrefr = getfieldvalue($dbh, 'SELECT nombre FROM tiendas_idiomas where id_tienda='.$datos['id']." and idioma='fr'");
    $nombrept = getfieldvalue($dbh, 'SELECT nombre FROM tiendas_idiomas where id_tienda='.$datos['id']." and idioma='pt'");

    $category = new Category;
    $category->id_parent = 2;
    $category->active = $datos['activo'];
    $category->id_shop_default = 1;
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

    $category->link_rewrite[1] = safeName($category->name[1]);
    $category->link_rewrite[2] = safeName($category->name[2]);
    $category->link_rewrite[3] = safeName($category->name[3]);
    $category->link_rewrite[4] = safeName($category->name[4]);
    $category->link_rewrite[5] = safeName($category->name[5]);
    $category->add();
    $category->addGroupsIfNoExist(1);
    $category->addGroupsIfNoExist(2);
    $category->addGroupsIfNoExist(3);

    $idnav = getfieldvalue($dbh, 'SELECT id FROM navegacion where id_padre=0 and elemento='.$datos['id']);
    $sql = 'INSERT INTO aalv_category_import(id_cat, id_origen, id_padre, url, orden, id_nav) VALUES ('.$category->id.','.$datos['id'].",0,'',".$datos['orden'].','.$idnav.')';
    Db::getInstance()->Execute($sql);

    $shopping = ''.getfieldvalue($dbh, 'SELECT in_google_shopping FROM valores_nav where id='.$datos['id']);

    if ($shopping == '') {
        $shopping = '0';
    }

    $sql = 'INSERT INTO aalv_category_gshopping(id_category, id_gshopping) VALUES ('.$category->id.','.$shopping.')';
    Db::getInstance()->Execute($sql);

}

function Crearcategoriacomun($datos, $tienda, $dbh)
{

    // SELECT id, id_padre, elemento, orden, url FROM navegacion order by id_padre, orden limit 10

    try {

        $nombre = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav where id='.$datos['elemento']);
        $nombreen = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['elemento']." and idioma='en'");
        $nombrede = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['elemento']." and idioma='de'");
        $nombrefr = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['elemento']." and idioma='fr'");
        $nombrept = getfieldvalue($dbh, 'SELECT nombre FROM valores_nav_idioma where id_valor='.$datos['elemento']." and idioma='pt'");

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

        $nombre = str_replace('&amp;', ' and ', $nombre);
        $nombreen = str_replace('&amp;', ' and ', $nombreen);
        $nombrede = str_replace('&amp;', ' and ', $nombrede);
        $nombrefr = str_replace('&amp;', ' and ', $nombrefr);
        $nombrept = str_replace('&amp;', ' and ', $nombrept);

        $nombre = str_replace('&', ' and ', $nombre);
        $nombreen = str_replace('&', ' and ', $nombreen);
        $nombrede = str_replace('&', ' and ', $nombrede);
        $nombrefr = str_replace('&', ' and ', $nombrefr);
        $nombrept = str_replace('&', ' and ', $nombrept);

        if ($nombre != '') {

            // ver si existe la categoria en aalv_category_import

            $idcat = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_nav='.$datos['id_nav'].' and tienda='.$tienda);

            if ($idcat == '') {

                // coger categoria padre
                $catpadre = Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$tienda);

                // ver si existe catpadre

                $catpadreid = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category WHERE id_category='.$catpadre);

                if ($catpadreid != '') {

                    $category = new Category;
                    $category->id_parent = $catpadre;
                    $category->active = 1;

                    $category->id_shop_default = 1;
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

                    $category->link_rewrite[1] = safeName($category->name[1]);
                    $category->link_rewrite[2] = safeName($category->name[2]);
                    $category->link_rewrite[3] = safeName($category->name[3]);
                    $category->link_rewrite[4] = safeName($category->name[4]);
                    $category->link_rewrite[5] = safeName($category->name[5]);
                    $category->add();
                    $category->addGroupsIfNoExist(1);
                    $category->addGroupsIfNoExist(2);
                    $category->addGroupsIfNoExist(3);

                    $orden = '0';

                    $idnav = $datos['id_nav'];
                    $sql = 'INSERT INTO aalv_categorias_comunes_import(id_cat, id_nav, tienda) VALUES ('.$category->id.','.$idnav.','.$tienda.')';
                    Db::getInstance()->Execute($sql);

                    $idpadre = getfieldvalue($dbh, 'SELECT id FROM navegacion where id_padre=0 and elemento='.$tienda);
                    $sql = 'INSERT INTO aalv_category_import(id_cat, id_origen, id_padre, url, orden, id_nav) VALUES ('.$category->id.','.$datos['elemento'].','.$idpadre.",'',".$orden.','.$idnav.')';
                    Db::getInstance()->Execute($sql);

                    $shopping = ''.getfieldvalue($dbh, 'SELECT in_google_shopping FROM valores_nav where id='.$datos['elemento']);

                    if ($shopping == '') {
                        $shopping = '0';
                    }

                    $sql = 'INSERT INTO aalv_category_gshopping(id_category, id_gshopping) VALUES ('.$category->id.','.$shopping.')';
                    Db::getInstance()->Execute($sql);

                } else {
                    $d = new DateTime;
                    $stdout = fopen(_PS_ADMIN_DIR_.'/categoriascomuneserrores.txt', 'a');
                    fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- no existe padre');
                    fwrite($stdout, "\n");
                    fwrite($stdout, ' --- datos '.$datos[0]);
                    fwrite($stdout, "\n");
                    fclose($stdout);

                }

            } else {

                $category = new Category($idcat);
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

                $category->link_rewrite[1] = safeName($category->name[1]);
                $category->link_rewrite[2] = safeName($category->name[2]);
                $category->link_rewrite[3] = safeName($category->name[3]);
                $category->link_rewrite[4] = safeName($category->name[4]);
                $category->link_rewrite[5] = safeName($category->name[5]);
                $category->update();

                $shopping = ''.getfieldvalue($dbh, 'SELECT in_google_shopping FROM valores_nav where id='.$datos['elemento']);

                if ($shopping == '') {
                    $shopping = '0';
                }

                $sql = 'REPLACE INTO aalv_category_gshopping(id_category, id_gshopping) VALUES ('.$category->id.','.$shopping.')';
                Db::getInstance()->Execute($sql);

            }

        } else {

            $d = new DateTime;
            $stdout = fopen(_PS_ADMIN_DIR_.'/categoriascomuneserrores.txt', 'a');
            fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- categoria vacia');
            fwrite($stdout, "\n");
            fwrite($stdout, ' --- datos '.$datos[0]);
            fwrite($stdout, "\n");
            fclose($stdout);

        }

    } catch (Exception $e) {

        $d = new DateTime;
        $stdout = fopen(_PS_ADMIN_DIR_.'/categoriascomuneserrores.txt', 'a');
        fwrite($stdout, $d->format("Y-m-d\TH:i:sP").' --- error '.$e->getMessage());
        fwrite($stdout, "\n");
        fwrite($stdout, ' --- datos '.$datos[0]);
        fwrite($stdout, "\n");
        fclose($stdout);

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarezps_migracion_db';
    $dbh = new PDO($dsn, 'alvarezps_migracion_dbu', 'eyb54%X45');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

function Crearcomunes($dbh)
{

    $sql = 'select * from aalv_categorias_comunes'; // elemento, id_nav, tiendas
    $comunes = Db::getInstance()->ExecuteS($sql);
    foreach ($comunes as $comun) {
        $tiendas = explode(',', $comun['tiendas']);
        foreach ($tiendas as $tienda) {
            Crearcategoriacomun($comun, $tienda, $dbh);
            echo $comun['elemento'].' '.$tienda.'<br>';

        }
    }

}

$rows = getdatarows($dbh, 'SELECT id, nombre, activo, orden FROM tiendas where id_padre=0 and id not in (0,5015) order by orden');
foreach ($rows as $row) {

    CrearcategoriaTiendas($row, $dbh);

}

Crearcomunes($dbh);

$rows = getdatarows($dbh, 'SELECT id, id_padre, elemento, orden, url FROM navegacion where id_padre<>0 order by id_padre, orden');
foreach ($rows as $row) {
    Crearcategoria($row, $dbh);
}

echo 'Proceso acabado';
