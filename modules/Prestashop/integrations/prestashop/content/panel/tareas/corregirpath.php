<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

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

function getCategorypath($id_category)
{
    $category_path = '';

    if ((int) $id_category != (int) Configuration::get('PS_HOME_CATEGORY') && (int) $id_category != 0) {
        $sql = 'SELECT c.`id_category`, c.`id_parent`, cl.`name`, c.`active` 
                    FROM `'._DB_PREFIX_.'category` c 
                    INNER JOIN `'._DB_PREFIX_.'category_shop` cs ON cs.`id_category`=c.`id_category` AND cs.`id_shop`='.Context::getContext()->shop->id.' 
                    INNER JOIN `'._DB_PREFIX_.'category_lang` cl ON cl.`id_category`=c.`id_category` AND cl.`id_shop`='.Context::getContext()->shop->id.' AND cl.`id_lang`='.Context::getContext()->language->id.' 
                    WHERE c.`id_category`='.(int) $id_category;
        $category = Db::getInstance()->getRow($sql);
        if ($category) {
            if ((int) $category['id_parent'] != (int) Configuration::get('PS_HOME_CATEGORY') && (int) $category['id_parent'] != 0 && (int) $category['active'] == 1) {
                $category_path .= $this->getCategorypath($category['id_parent']);
            }

            $category_path .= '('.$category['id_category'].') '.$category['name'].' / ';
        }
    }

    return $category_path;
}

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

function addlog($message)
{
    $d = new DateTime;
    $stdout = fopen(dirname(__FILE__).'/procesarpath.txt', 'a');
    fwrite($stdout, $message);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function ProcesarPath()
{

    $datos = Db::getInstance()->ExecuteS('SELECT id_product, id_category_default FROM aalv_product where id_category_default in(select id_cat from aalv_category_import where id_origen in ( SELECT id_origen FROM aalv_category_import group by id_origen having count(id_cat)>1));');

    foreach ($datos as $datositem) {

        $idproducto = $datositem['id_product'];
        $idcat = $datositem['id_category_default'];
        $id_origen = Db::getInstance()->getValue('SELECT id_origen FROM aalv_category_import WHERE id_cat='.$idcat);

        $catimport = Db::getInstance()->ExecuteS('SELECT * FROM aalv_category_import WHERE id_origen='.$id_origen);

        foreach ($catimport as $catim) {
            $idcatps = $catim['id_cat'];
            $idnav = $catim['id_nav'];

            $existe = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category_product WHERE id_category = '.$idcatps.' and id_product='.$idproducto);

            if ($existe != '') {

                if (ExistePathCategory($idproducto, $idnav)) {
                    if (escomunrec($idcatps)) {

                        $cat = new Category($idcatps);
                        if ($cat->sport == 5) {

                            if ($idcatps != $idcat) {
                                addlog('UPDATE aalv_product set id_category_default='.$idcatps.' where id_product='.$idproducto.';');
                                addlog('UPDATE aalv_product_shop set id_category_default='.$idcatps.' where id_product='.$idproducto.';');
                            }
                        }
                    } else {

                        if (count($catimport) > 1) {
                            $cat2 = new Category($idcatps);
                            if ($cat2->sport == 5) {
                                if ($idcatps != $idcat) {
                                    addlog('UPDATE aalv_product set id_category_default='.$idcatps.' where id_product='.$idproducto.';');
                                    addlog('UPDATE aalv_product_shop set id_category_default='.$idcatps.' where id_product='.$idproducto.';');
                                }
                            }

                        } else {
                            if ($idcatps != $idcat) {
                                addlog('UPDATE aalv_product set id_category_default='.$idcatps.' where id_product='.$idproducto.';');
                                addlog('UPDATE aalv_product_shop set id_category_default='.$idcatps.' where id_product='.$idproducto.';');
                            }
                        }

                    }
                }
            }
        }

    }
}

ProcesarPath();
echo 'acaba';
