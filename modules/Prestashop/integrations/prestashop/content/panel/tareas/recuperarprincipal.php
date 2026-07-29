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

function ProcesarPrincipal($idmodelo)
{

    $idproducto = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);
    if ($idproducto != '') {

        $datos = ''.Db::getInstance()->getValue("SELECT data  FROM aalv_integracion_cambios WHERE tabla LIKE '%perfiles_nav%' AND data LIKE '%\"id_modelo\":".$idmodelo.",%' and data like '%true%' order by id desc");

        if ($datos != '') {

            $valores = json_decode($datos, true);

            $catimport = Db::getInstance()->ExecuteS('SELECT * FROM aalv_category_import WHERE id_origen='.$valores['id_valor']);

            $ramasexisten = 0;

            foreach ($catimport as $catim) {
                $idcatps = $catim['id_cat'];
                $idnav = $catim['id_nav'];

                $existe = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category_product WHERE id_category = '.$idcatps.' and id_product='.$idproducto);

                if ($existe != '') {

                    if (ExistePathCategory($idproducto, $idnav)) {
                        $ramasexisten = $ramasexisten + 1;
                    }
                }

            }

            foreach ($catimport as $catim) {
                $idcatps = $catim['id_cat'];
                $idnav = $catim['id_nav'];

                $existe = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category_product WHERE id_category = '.$idcatps.' and id_product='.$idproducto);

                if ($existe != '') {

                    if (ExistePathCategory($idproducto, $idnav)) {
                        if (escomunrec($idcatps)) {

                            $cat = new Category($idcatps);
                            if ($cat->sport == 5) {
                                Db::getInstance()->Execute('UPDATE aalv_product set id_category_default='.$idcatps.' where id_product='.$idproducto);
                                Db::getInstance()->Execute('UPDATE aalv_product_shop set id_category_default='.$idcatps.' where id_product='.$idproducto);

                            }
                        } else {

                            if ($ramasexisten > 1) {
                                $cat2 = new Category($idcatps);
                                if ($cat2->sport == 5) {
                                    Db::getInstance()->Execute('UPDATE aalv_product set id_category_default='.$idcatps.' where id_product='.$idproducto);
                                    Db::getInstance()->Execute('UPDATE aalv_product_shop set id_category_default='.$idcatps.' where id_product='.$idproducto);
                                }

                            } else {
                                Db::getInstance()->Execute('UPDATE aalv_product set id_category_default='.$idcatps.' where id_product='.$idproducto);
                                Db::getInstance()->Execute('UPDATE aalv_product_shop set id_category_default='.$idcatps.' where id_product='.$idproducto);
                            }

                            // Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$idcatps." where id_product=".$idproducto);
                            // Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$idcatps." where id_product=".$idproducto);

                        }
                    }
                }
            }

            /*

            $rowscat = Db::getInstance()->ExecuteS("SELECT id_cat FROM aalv_category_import WHERE id_origen = ".$caterp);

            if ($rowscat){

                foreach($rowscat as $rowscatitem){

                    $cat = $rowscatitem["id_cat"];

                    if  (ExistePathCategory2($idproducto,  $cat)){



                        if (escomunrec($cat)){

                            $cat2 = new Category($cat);
                            if ($cat2->sport==5){

                                Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$cat." where id_product=".$idproducto);
                                Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$cat." where id_product=".$idproducto);

                            }
                        }
                        else{


                            if (count($rowscat)>1){
                                $cat2 = new Category($cat);
                                if ($cat2->sport==5){
                                    Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$cat." where id_product=".$idproducto);
                                    Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$cat." where id_product=".$idproducto);
                                }

                            }
                            else{
                                    Db::getInstance()->Execute("UPDATE aalv_product set id_category_default=".$cat." where id_product=".$idproducto);
                                    Db::getInstance()->Execute("UPDATE aalv_product_shop set id_category_default=".$cat." where id_product=".$idproducto);
                            }

                        }

                    }

                }

            }*/

        }

        /*
        //borrado de las no todo path
        $rows = Db::getInstance()->ExecuteS("select * from aalv_category_product where id_product=".$idproducto);
        foreach($rows as $row){
          $id_categoryps = $row["id_category"];
          if (!ExistePathCategory2($idproducto, $id_categoryps)){
              if (!escomunrec($id_categoryps)){
                //Db::getInstance()->Execute("delete from aalv_category_product where id_category=".$id_categoryps. " and id_product=".$idproducto);
              //Db::getInstance()->Execute("delete from aalv_category_product_import where id_category=".$id_categoryps. " and id_product=".$idproducto);
             }
          }
        }
        */
    }
}

$rowsp = Db::getInstance()->ExecuteS('SELECT id_product, id_modelo FROM aalv_product_import WHERE id_product>=55053 and id_product in (SELECT id_product  FROM aalv_product WHERE id_category_default = 2)');

foreach ($rowsp as $prod) {
    $producto = $prod['id_product'];
    $modelo = $prod['id_modelo'];
    ProcesarPrincipal($modelo);
}

echo 'acaba';
