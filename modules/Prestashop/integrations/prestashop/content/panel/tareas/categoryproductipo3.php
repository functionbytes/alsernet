<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

function addsql($texto)
{
    $stdout = fopen(dirname(__FILE__).'/borradocategoryproduct.txt', 'a');
    fwrite($stdout, $texto);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function escomunrec($id)
{

    $padre = Db::getInstance()->getValue('SELECT id_parent FROM '._DB_PREFIX_.'category WHERE id_category='.$id);
    $escomun = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_cat= '.$id);

    if ($escomun == '') {

        if ($padre <= 2) {
            return false;
        } else {
            return escomunrec($padre);
        }

    } else {
        return true;
    }

}

function escomun($id_nav)
{

    $existecomun = ''.Db::getInstance()->getValue('SELECT id FROM aalv_categorias_comunes WHERE id_nav='.$id_nav);

    if ($existecomun != '') {
        return true;
    } else {
        return false;
    }

}

function ExistePathCategory($producto, $id_nav)
{

    if (! escomun($id_nav) && ($id_nav != 0)) {

        $elemento = Db::getInstance()->getValue('SELECT id_origen FROM aalv_category_import WHERE id_nav='.$id_nav);
        $id_padre = Db::getInstance()->getValue('SELECT id_padre FROM aalv_category_import WHERE id_nav='.$id_nav);
        $id_cat = Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$id_nav);

        // ver si existe id_cat  y producto en  category_import

        if (''.$id_cat != '') {
            $existe = ''.Db::getInstance()->getValue('select id_category from aalv_category_product where id_category='.$id_cat.' and id_product='.$producto);

            if ($existe != '') {
                return ExistePathCategory($producto, (int) $id_padre);
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return true;
    }

}

function ProcesarPerfilesNav($datajson, $fila, $tipo)
{

    if ($tipo <= 2) {

        echo 'llega';

        $data = json_decode($datajson, true);

        if (! $data) {
            // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }

        $idmodelo = $data['id_modelo'];
        $idvalor = $data['id_valor'];
        $principal = $data['principal'];

        $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);

        if ($idprodps != '') {

            $catimport = Db::getInstance()->ExecuteS('SELECT * FROM aalv_category_import WHERE id_origen='.$data['id_valor']);

            foreach ($catimport as $catim) {
                $idcatps = $catim['id_cat'];
                $idnav = $catim['id_nav'];

                $existe = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category_product WHERE id_category = '.$idcatps.' and id_product='.$idprodps);

                if ($existe != '') {
                    // update, no hacer nada ya que está, pero mirar si cambia principal
                    Db::getInstance()->Execute('REPLACE INTO aalv_category_product(id_category, id_product, position) VALUES ('.$idcatps.','.$idprodps.',0)');
                    if (ExistePathCategory($idprodps, $idnav)) {
                        if ($principal) {
                            // Db::getInstance()->Execute("UPDATE aalv_product SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                            // Db::getInstance()->Execute("UPDATE aalv_product_shop SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                            if (escomunrec($idcatps)) {

                                $cat = new Category($idcatps);
                                if ($cat->sport == 5) {

                                    Db::getInstance()->Execute('UPDATE aalv_product SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                    Db::getInstance()->Execute('UPDATE aalv_product_shop SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);

                                }

                            } else {
                                Db::getInstance()->Execute('UPDATE aalv_product SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                Db::getInstance()->Execute('UPDATE aalv_product_shop SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);

                            }

                        }

                    }
                } else {
                    echo 'llega';

                    Db::getInstance()->Execute('REPLACE INTO aalv_category_product(id_category, id_product, position) VALUES ('.$idcatps.','.$idprodps.',0)');

                    if (ExistePathCategory($idprodps, $idnav)) {

                        if ($principal) {

                            // Db::getInstance()->Execute("UPDATE aalv_product SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                            // Db::getInstance()->Execute("UPDATE aalv_product_shop SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);

                            if (escomunrec($idcatps)) {

                                $cat = new Category($idcatps);
                                if ($cat->sport == 5) {

                                    Db::getInstance()->Execute('UPDATE aalv_product SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                    Db::getInstance()->Execute('UPDATE aalv_product_shop SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                }

                            } else {
                                Db::getInstance()->Execute('UPDATE aalv_product SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                Db::getInstance()->Execute('UPDATE aalv_product_shop SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);

                            }

                        }
                    }

                }

            }

            return $idprodps;

        }

    } else {

        $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_category_product_import WHERE fila='.$fila);
        $idcatpsrows = Db::getInstance()->ExecuteS('SELECT id_category FROM aalv_category_product_import WHERE fila='.$fila);

        if ($idcatpsrows) {

            foreach ($idcatpsrows as $idcatpsrow) {
                $idcatps = $idcatpsrow['id_category'];
                Db::getInstance()->Execute('DELETE FROM aalv_category_product where id_category='.$idcatps.' and id_product='.$idprodps);
            }

            return $idprodps;
        }

    }

}

/*
function DeleteCategoryProduct($datos){

        $fila = $datos["fila"];

        $idprodps="".Db::getInstance()->getValue("SELECT id_product FROM aalv_category_product_import WHERE fila=".$fila);
        $idcatpsrows=Db::getInstance()->ExecuteS("SELECT id_category FROM aalv_category_product_import WHERE fila=".$fila);

        if ($idcatpsrows){

            foreach($idcatpsrows as $idcatpsrow){
                $idcatps = $idcatpsrow["id_category"];
                addsql("DELETE FROM aalv_category_product where id_category=".$idcatps." and id_product=".$idprodps.";");
            }


        }

}
*/

function escomun2($id_cat)
{

    $existecomun = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_cat='.$id_cat);

    if ($existecomun != '') {
        return true;
    } else {
        return false;
    }

}

function ExistePathCategory2($producto, $id_cat)
{

    if (($id_cat > 2) && (! escomun2($id_cat)) && ($id_cat != 2821) && ($id_cat != 2820)) {

        $id_padre = Db::getInstance()->getValue('SELECT id_parent FROM aalv_category WHERE id_category='.$id_cat);
        $existe = ''.Db::getInstance()->getValue('select id_category from aalv_category_product where id_category='.$id_cat.' and id_product='.$producto);

        if ($existe != '') {
            return ExistePathCategory2($producto, (int) $id_padre);
        } else {
            return false;
        }
    } else {
        return true;
    }

}

$rows = Db::getInstance()->ExecuteS("SELECT data, fila, tipo FROM aalv_integracion_cambios WHERE tabla LIKE '%perfiles_nav' order by fecha_confirmacion asc, id asc ");

$products = [];

foreach ($rows as $row) {

    $p = ''.ProcesarPerfilesNav($row['data'], $row['fila'], $row['tipo']);

    if ($p != '') {
        $products[] = $p;
    }

}

foreach ($products as $idproducto) {

    $rows = Db::getInstance()->ExecuteS('select * from aalv_category_product where id_product='.$idproducto);
    foreach ($rows as $row) {
        $id_categoryps = $row['id_category'];
        if (! ExistePathCategory2($idproducto, $id_categoryps)) {
            if (! escomunrec($id_categoryps)) {
                Db::getInstance()->Execute('delete from aalv_category_product where id_category='.$id_categoryps.' and id_product='.$idproducto);
                // Db::getInstance()->Execute("delete from aalv_category_product_import where id_category=".$id_categoryps. " and id_product=".$idproducto);
            }
        }
    }

}

echo 'acaba';
