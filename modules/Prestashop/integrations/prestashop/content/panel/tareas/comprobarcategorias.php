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

function Vercategoria($datos, $dbh)
{

    // SELECT id, id_padre, elemento, orden, url FROM navegacion order by id_padre, orden limit 10

    $mid = $datos['id'];
    $mid_padre = $datos['id_padre'];
    $melemento = $datos['elemento'];

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

    $nombre = str_replace('&amp;', '&', $nombre);
    $nombreen = str_replace('&amp;', '&', $nombreen);
    $nombrede = str_replace('&amp;', '&', $nombrede);
    $nombrefr = str_replace('&amp;', '&', $nombrefr);
    $nombrept = str_replace('&amp;', '&', $nombrept);

    if ($nombre != '') {

        // ver si existe la categoria en aalv_category_import

        $idcat = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$datos['id']);

        if ($idcat == '') {

            echo '<br/>'.$nombre.' no está en category_import';

            $catpadres = Db::getInstance()->ExecuteS('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$datos['id_padre']);

            if ($catpadres) {

                foreach ($catpadres as $catpadreitem) {

                    $catpadre = $catpadreitem['id_cat'];

                    echo '<br/>'.$catpadre.' no está en category_import';

                    $catpadreid = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category WHERE id_category='.$catpadre);

                    if ($catpadreid != '') {
                        echo '<br/>'.$catpadreid.' no está en category_import';

                    } else {
                        echo '<br/>Cat padre '.$catpadre.' no existe en category';
                    }
                }

            } else {
                echo '<br/>'.$datos['id_padre'].' no está en category_import';
            }

        } else {

            $idcats = Db::getInstance()->ExecuteS('SELECT id, id_cat, id_padre, id_nav FROM aalv_category_import WHERE id_nav='.$datos['id']);

            foreach ($idcats as $idcatsitem) {

                // echo "<br/>Existe en category_import ".$idcatsitem['id_cat'];
                $catexiste = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category WHERE id_category='.$idcatsitem['id_cat']);

                if ($catexiste != '') {
                    // echo "<br/>Existe en category ".$idcatsitem['id_cat'];

                    if ($idcatsitem['id_padre'] == $datos['id_padre']) {

                    } else {
                        echo '<br/>'.$nombre;

                        echo '<br/>Padres distintos: '.$idcatsitem['id_padre'].' '.$datos['id_padre'];

                        // cambiar padre a al categoría OJO

                        $catpadre = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$datos['id_padre']);

                        if ($catpadre == '') {
                            echo '<br/>UPDATE NO Existe en category_import padre'.$datos['id_padre'];
                        } else {
                            $catexiste2 = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category WHERE id_category='.$catpadre);
                            if ($catexiste2 == '') {
                                echo '<br/>UPDATE NO Existe en category padre'.$catexiste2;
                            } else {
                                echo '<br/>UPDATE Existe en category padre'.$catexiste2;

                                // update category con id $idcatsitem['id_cat'] y parent = $catexiste2

                                // crear la categoria con id

                                // *********************//

                                $category = new Category($idcatsitem['id_cat']);

                                $category->id_parent = (int) $catexiste2;
                                $category->active = 1;

                                $category->id_shop_default = 1;
                                $category->name[1] = $nombre;
                                $category->name[2] = $nombreen;
                                $category->name[3] = $nombrefr;
                                $category->name[4] = $nombrept;
                                $category->name[5] = $nombrede;

                                $category->link_rewrite[1] = Tools::link_rewrite($category->name[1]);
                                $category->link_rewrite[2] = Tools::link_rewrite($category->name[2]);
                                $category->link_rewrite[3] = Tools::link_rewrite($category->name[3]);
                                $category->link_rewrite[4] = Tools::link_rewrite($category->name[4]);
                                $category->link_rewrite[5] = Tools::link_rewrite($category->name[5]);
                                $lacrea = $category->update();

                                if ($lacrea) {
                                    echo 'UPDATE '.$category->id;
                                } else {
                                    echo 'UPDATE '.$category->id;
                                }
                                $category->addGroupsIfNoExist(1);
                                $category->addGroupsIfNoExist(2);
                                $category->addGroupsIfNoExist(3);
                                $category->addGroupsIfNoExist(5);

                                $orden = ''.$datos['orden'];
                                if ($orden == '') {
                                    $orden = '0';
                                }

                                $idnav = $datos['id'];
                                $sql = 'REPLACE INTO aalv_category_import(id, id_cat, id_origen, id_padre, url, orden, id_nav) VALUES ('.$idcatsitem['id_cat'].','.$category->id.','.$datos['elemento'].','.$datos['id_padre'].",'".$datos['url']."',".$orden.','.$idnav.')';
                                Db::getInstance()->Execute($sql);

                                $shopping = ''.getfieldvalue($dbh, 'SELECT in_google_shopping FROM valores_nav where id='.$datos['elemento']);

                                if ($shopping == '') {
                                    $shopping = '0';
                                }

                                $sql = 'REPLACE INTO aalv_category_gshopping(id_category, id_gshopping) VALUES ('.$category->id.','.$shopping.')';
                                Db::getInstance()->Execute($sql);

                            }

                        }

                    }

                    // update
                } else {

                    // ver padre

                    $elemento = ''.getfieldvalue($dbh, 'select elemento from navegacion where id='.$datos['id']);
                    $id_padre = ''.getfieldvalue($dbh, 'select id_padre from navegacion where id='.$datos['id']);

                    if ($elemento != '') {

                        echo "<br/>id $mid id_padre $mid_padre elemento $melemento";
                        echo '<br/>'.$nombre;
                        echo "<br/>->Elemento $elemento padre $id_padre";
                        echo '<br/>NO Existe en category '.$idcatsitem['id_cat'];

                        // ver si está en category import el padre

                        $catpadre = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$id_padre);

                        if ($catpadre == '') {
                            echo '<br/>NO Existe en category_import padre'.$id_padre;

                            // ver si existe el padre $id_padre en migracion
                            $idnavpadre = ''.getfieldvalue($dbh, 'select id from navegacion where id='.$id_padre);

                            if ($idnavpadre == '') {

                                echo '<br/>NO Existe en migracion padre'.$id_padre;

                            } else {
                                echo '<br/>SI Existe en migracion padre'.$id_padre;

                            }

                        } else {
                            $catexiste2 = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category WHERE id_category='.$catpadre);
                            if ($catexiste2 == '') {
                                echo '<br/>NO Existe en category padre'.$catexiste2;
                            } else {
                                echo '<br/>Existe en category padre'.$catexiste2;

                                // crear la categoria con id

                                // *********************//

                                $category = new Category;

                                $category->force_id = true;
                                $category->id = (int) $idcatsitem['id_cat'];

                                $category->id_parent = (int) $catexiste2;
                                $category->active = 1;

                                $category->id_shop_default = 1;
                                $category->name[1] = $nombre;
                                $category->name[2] = $nombreen;
                                $category->name[3] = $nombrefr;
                                $category->name[4] = $nombrept;
                                $category->name[5] = $nombrede;

                                $category->link_rewrite[1] = Tools::link_rewrite($category->name[1]);
                                $category->link_rewrite[2] = Tools::link_rewrite($category->name[2]);
                                $category->link_rewrite[3] = Tools::link_rewrite($category->name[3]);
                                $category->link_rewrite[4] = Tools::link_rewrite($category->name[4]);
                                $category->link_rewrite[5] = Tools::link_rewrite($category->name[5]);
                                $lacrea = $category->add();

                                if ($lacrea) {
                                    echo 'Creada '.$category->id;
                                } else {
                                    echo 'No Creada '.$category->id;
                                }

                                $category->addGroupsIfNoExist(1);
                                $category->addGroupsIfNoExist(2);
                                $category->addGroupsIfNoExist(3);
                                $category->addGroupsIfNoExist(5);

                                $orden = ''.$datos['orden'];
                                if ($orden == '') {
                                    $orden = '0';
                                }

                                $idnav = $datos['id'];
                                $sql = 'REPLACE INTO aalv_category_import(id, id_cat, id_origen, id_padre, url, orden, id_nav) VALUES ('.$idcatsitem['id_cat'].','.$category->id.','.$datos['elemento'].','.$datos['id_padre'].",'".$datos['url']."',".$orden.','.$idnav.')';
                                Db::getInstance()->Execute($sql);

                                $shopping = ''.getfieldvalue($dbh, 'SELECT in_google_shopping FROM valores_nav where id='.$datos['elemento']);

                                if ($shopping == '') {
                                    $shopping = '0';
                                }

                                $sql = 'REPLACE INTO aalv_category_gshopping(id_category, id_gshopping) VALUES ('.$category->id.','.$shopping.')';
                                Db::getInstance()->Execute($sql);

                            }

                        }

                    }

                    /*
                    $padre = "".Db::getInstance()->getValue("SELECT id_cat FROM aalv_category_import WHERE id_nav=".$idcatsitem['id_padre']);

                    if ($padre!=""){
                         echo "<br/>Existe padre en import ".$idcatsitem['id_padre'];
                        $padre = "".Db::getInstance()->getValue("SELECT id_category FROM aalv_category WHERE id_category=".$padre);

                    }
                    else{
                        echo "<br/>No existe padre en import ".$idcatsitem['id_padre'];
                    }

                    if ($padre!=""){
                        echo "<br/>Existe padre ".$padre;

                        //crear con el mismo id


                    }
                    else{
                        echo "<br/>No existe padre para ".$idcatsitem['id_cat'];
                    }
                    */

                }

            }

        }

    } else {
        // echo "<br/>Categoria sin nombre elemento".$datos['elemento'];

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT id, id_padre, elemento, orden, url FROM navegacion where id_padre<>0 order by id_padre, orden');
foreach ($rows as $row) {
    Vercategoria($row, $dbh);
}

/*
$categoriasimport = Db::getInstance()->ExecuteS("SELECT id_nav, id_cat, id_origen, id_padre FROM aalv_category_import");

foreach($categoriasimport as $row){
   // Verificarcategoria($row, $dbh);
}
*/

echo 'Proceso acabado';
