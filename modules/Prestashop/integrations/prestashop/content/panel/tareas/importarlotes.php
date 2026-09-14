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

function CrearLllote($idlote, $idbundleproduct, $bundlesection, $idllote, $dbh)
{

    $lllotes = getdatarows($dbh, 'SELECT * FROM lllote where idllote='.$idllote);

    foreach ($lllotes as $lllote) {

        // ver si existe lllote_import

        $id_bundle_sub_product = ''.Db::getInstance()->getValue('SELECT id_bundle_sub_product FROM aalv_lllote_import WHERE idlllote='.$lllote['idlllote']);
        if (''.$id_bundle_sub_product == '') {
            // crear

            $id_bundle_sub_product = 0;
            $id_bundle_sub_product_attribute = 0;

            $idarticulo = $lllote['idarticulo'];
            $idproduct = Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo='.$idarticulo.' UNION select id_product from aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = '.$idarticulo.')');

            // ver si existe seccion producto

            $id_bundle_sub_product = ''.Db::getInstance()->getValue('SELECT id_bundle_sub_product from aalv_wk_bundle_sub_product where id_wk_bundle_section='.$bundlesection.' and id_product='.$idproduct);

            if ($id_bundle_sub_product == '') {
                Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_sub_product(id_wk_bundle_section, id_product, id_shop, active, for_bundle_only) VALUES ('.$bundlesection.','.$idproduct.',1,1,0)');
                $id_bundle_sub_product = Db::getInstance()->Insert_ID();
            }

            $idproductattribute = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = '.$idarticulo);

            if ($idproductattribute == '') {
                $idproductattribute = '0';
            }

            $defaultattr = '0';
            if ($idproductattribute != '0') {
                $defaultattr = ''.Db::getInstance()->getValue('SELECT default_on FROM aalv_product_attribute where id_product_attribute = '.$idproductattribute);
                if ($defaultattr == '') {
                    $defaultattr = '0';
                }
            }

            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_sub_product_attribute(id_wk_bundle_section, id_product, id_product_attribute, quantity, normal_stock, default_attr) VALUES ('.$bundlesection.','.$idproduct.','.$idproductattribute.',0,0,'.$defaultattr.')');

            $id_bundle_sub_product_attribute = Db::getInstance()->Insert_ID();

            Db::getInstance()->Execute('INSERT INTO aalv_lllote_import(idlllote, idarticulo, id_bundle_sub_product, id_bundle_sub_product_attribute) VALUES ('.$lllote['idlllote'].','.$idarticulo.','.$id_bundle_sub_product.','.$id_bundle_sub_product_attribute.')');

        } else {
            // modificar

            $id_bundle_sub_product_attribute = ''.Db::getInstance()->getValue('SELECT id_bundle_sub_product_attribute FROM aalv_lllote_import WHERE idlllote='.$lllote['idlllote']);

            $idarticulo = $lllote['idarticulo'];
            $idproduct = Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo='.$idarticulo.' UNION select id_product from    aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = '.$idarticulo.')');

            $idproductattribute = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = '.$idarticulo);

            if ($idproductattribute == '') {
                $idproductattribute = '0';
            }

            $defaultattr = '0';
            if ($idproductattribute != '0') {
                $defaultattr = ''.Db::getInstance()->getValue('SELECT default_on FROM aalv_product_attribute where id_product_attribute = '.$idproductattribute);
                if ($defaultattr == '') {
                    $defaultattr = '0';
                }
            }

            Db::getInstance()->Execute('UPDATE aalv_wk_bundle_sub_product set id_product='.$idproduct.' WHERE id_bundle_sub_product='.$id_bundle_sub_product);

            Db::getInstance()->Execute('UPDATE aalv_wk_bundle_sub_product_attribute set id_product='.$idproduct.' , id_product_attribute='.$idproductattribute.', default_attr='.$defaultattr.' WHERE id_bundle_sub_product_attribute='.$id_bundle_sub_product_attribute);
            Db::getInstance()->Execute('UPDATE aalv_lllote_import set idarticulo='.$idarticulo.' where idlllote='.$lllote['idlllote']);

        }

    }

}

function CrearLlote($idlote, $idbundleproduct, $idproduct, $dbh)
{

    // coger secciones del bundle

    // SELECT id_wk_bundle_section FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_product=

    // INSERT INTO `aalv_llote_import`(`idllote`, `bundle_section`) VALUES ('[value-1]','[value-2]')

    // coger de la tabla llote

    $llotes = getdatarows($dbh, 'SELECT * FROM llote WHERE idlote='.$idlote);

    foreach ($llotes as $llote) {

        // crear bundle_sections

        $bundlesection = ''.Db::getInstance()->getValue('select bundle_section from aalv_llote_import where idllote='.$llote['idllote']);
        if (''.$bundlesection == '') {
            // crear la bundle section

            // INSERT INTO `aalv_wk_bundle_section`(`id_wk_bundle_section`, `min_quantity`, `choose_quantity`, `quantity_wise_discount`, `active`, `is_required`, `state`, `date_add`, `date_upd`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]')

            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section(min_quantity, choose_quantity, quantity_wise_discount, active, is_required, state, date_add, date_upd) VALUES (1,0,0,'.$llote['estado'].',1,'.$llote['estado'].',now(),now())');
            $bundlesection = Db::getInstance()->Insert_ID();

            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.",1,1,'".$llote['descripcion']."')");

            // 1 es 2 en 3 fr 4 pt 5 de
            $descripen = getfieldvalue($dbh, 'SELECT nombre FROM llote_idioma where id_valor='.$llote['idllote']." and idioma='en'");
            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.",1,2,'".$descripen."')");

            $descripfr = getfieldvalue($dbh, 'SELECT nombre FROM llote_idioma where id_valor='.$llote['idllote']." and idioma='fr'");
            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.",1,3,'".$descripfr."')");

            $descrippt = getfieldvalue($dbh, 'SELECT nombre FROM llote_idioma where id_valor='.$llote['idllote']." and idioma='pt'");
            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.",1,4,'".$descrippt."')");

            $descripde = getfieldvalue($dbh, 'SELECT nombre FROM llote_idioma where id_valor='.$llote['idllote']." and idioma='de'");
            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_lang(id_wk_bundle_section, id_shop, id_lang, section_name) VALUES ('.$bundlesection.",1,5,'".$descripde."')");

            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_shop(id_wk_bundle_section, id_shop, date_add, date_upd) VALUES ('.$bundlesection.',1,now(),now())');

            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_section_map(id_wk_bundle_product, id_wk_bundle_section) VALUES ('.$idbundleproduct.','.$bundlesection.')');

            Db::getInstance()->Execute('INSERT INTO aalv_llote_import(idllote, bundle_section) VALUES ('.$llote['idllote'].','.$bundlesection.')');

        } else {

            Db::getInstance()->Execute('UPDATE aalv_wk_bundle_section SET active='.$llote['estado'].', state='.$llote['estado'].', date_upd=now() where id_wk_bundle_section='.$bundlesection);

            Db::getInstance()->Execute("UPDATE aalv_wk_bundle_section_lang set section_name='".$llote['descripcion']."' where id_wk_bundle_section=".$bundlesection.' and id_lang=1');

            // 1 es 2 en 3 fr 4 pt 5 de
            $descripen = getfieldvalue($dbh, 'SELECT nombre FROM llote_idioma where id_valor='.$llote['idllote']." and idioma='en'");
            Db::getInstance()->Execute("UPDATE aalv_wk_bundle_section_lang set section_name='".$descripen."' where id_wk_bundle_section=".$bundlesection.' and id_lang=2');
            $descripfr = getfieldvalue($dbh, 'SELECT nombre FROM llote_idioma where id_valor='.$llote['idllote']." and idioma='fr'");
            Db::getInstance()->Execute("UPDATE aalv_wk_bundle_section_lang set section_name='".$descripfr."' where id_wk_bundle_section=".$bundlesection.' and id_lang=3');
            $descrippt = getfieldvalue($dbh, 'SELECT nombre FROM llote_idioma where id_valor='.$llote['idllote']." and idioma='pt'");
            Db::getInstance()->Execute("UPDATE aalv_wk_bundle_section_lang set section_name='".$descrippt."' where id_wk_bundle_section=".$bundlesection.' and id_lang=4');
            $descripde = getfieldvalue($dbh, 'SELECT nombre FROM llote_idioma where id_valor='.$llote['idllote']." and idioma='de'");
            Db::getInstance()->Execute("UPDATE aalv_wk_bundle_section_lang set section_name='".$descripde."' where id_wk_bundle_section=".$bundlesection.' and id_lang=5');

        }

        CrearPrecioLote($idlote, $idproduct, $dbh);

        CrearLllote($idlote, $idbundleproduct, $bundlesection, $llote['idllote'], $dbh);

    }

}

function crearFeatureValue($idFeature, $value, $custom)
{

    $idFeatureValue = Db::getInstance()->getValue('
                SELECT fv.`id_feature_value`
                FROM '._DB_PREFIX_.'feature_value fv
                LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.`id_feature_value` = fv.`id_feature_value` AND fvl.`id_lang` = 1)
                WHERE `value` = \''.pSQL($value).'\'
                AND fv.`id_feature` = '.(int) $idFeature.'
                AND fv.`custom` = '.$custom.'
                GROUP BY fv.`id_feature_value`');
    if ($idFeatureValue) {
        return (int) $idFeatureValue;
    } else {

        $feature_value = new FeatureValue;
        $feature_value->id_feature = (int) $idFeature;
        $feature_value->custom = $custom;
        $feature_value->value = array_fill_keys(Language::getIDs(false), $value);

        $feature_value->add();

        return (int) $feature_value->id;

    }
}

function CrearPrecioLote($idlote, $idproduct, $dbh)
{
    $tarifas = getdatarows($dbh, 'SELECT * FROM tarifalote where idllote in (select idllote from llote where idlote='.$idlote.')');

    foreach ($tarifas as $tarifa) {
        $idtarifalote = ''.Db::getInstance()->getValue('SELECT idtarifalote FROM aalv_tarifalote_import where idtarifalote='.$tarifa['idtarifalote']);
        if ($idtarifalote == '') {
            Db::getInstance()->Execute('INSERT INTO aalv_tarifalote_import(idtarifalote, idllote, estado, idttarifa, precio, precio_con_impuestos) VALUES ('.$tarifa['idtarifalote'].','.$tarifa['idllote'].','.$tarifa['estado'].','.$tarifa['idttarifa'].','.$tarifa['precio'].",'".$tarifa['precio_con_impuestos']."')");
        }
    }

    $preciotarifa = Db::getInstance()->getValue('SELECT sum(precio) FROM aalv_tarifalote_import where idttarifa=1 and idllote in (select idllote from aalv_llote_import where bundle_section in (select id_wk_bundle_section from aalv_wk_bundle_section_map where id_wk_bundle_product in (select bundle_product from aalv_lote_import where idlote='.$idlote.')))');

    Db::getInstance()->Execute('update aalv_product_shop set id_tax_rules_group = 7, price='.round($preciotarifa, 6).' where id_product='.$idproduct);
    Db::getInstance()->Execute('update aalv_product set id_tax_rules_group = 7, price='.round($preciotarifa, 6).' where id_product='.$idproduct);

    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=6 and id_product='.$idproduct);
    $idfeaturevalue = crearFeatureValue(6, '1', 0);
    if ($idfeaturevalue != 0) {
        $product = new Product($idproduct);
        $product->addFeatureProductImport($idproduct, 6, $idfeaturevalue);
    }

    Db::getInstance()->Execute('REPLACE INTO aalv_appagebuilder_page(id_product, id_category, page, id_shop) VALUES ('.$idproduct.",0,'detail1988211104',1)");

}

function CrearLote($datos, $dbh)
{

    $codlote = $datos['codlote'];

    // ver si existe en combinacion unica

    $idproduct = ''.Db::getInstance()->getValue("SELECT id_product from aalv_product where reference='".$codlote."'");

    if ($idproduct != '') {

        echo $codlote.' '.$idproduct;

        // existe el producto. Ver si ya es bundle

        $idbundleproduct = ''.Db::getInstance()->getValue('SELECT id_wk_bundle_product FROM aalv_wk_bundle_product WHERE id_ps_product='.$idproduct);
        if ($idbundleproduct == '') {
            // crear en aalv_wk_bundle_product
            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_product(id_ps_product, price_type, tax_type, discount, price, decrease_quantity) VALUES ('.$idproduct.',1,1,0,0,1)');
            $idbundleproduct = Db::getInstance()->Insert_ID();
            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_product_shop(id_wk_bundle_product, id_shop) VALUES ('.$idbundleproduct.',1)');
            CrearLlote($datos['idlote'], $idbundleproduct, $idproduct, $dbh);
            Db::getInstance()->Execute('INSERT INTO aalv_lote_import(idlote, bundle_product) VALUES ('.$datos['idlote'].','.$idbundleproduct.')');
        } else {
            CrearLlote($datos['idlote'], $idbundleproduct, $idproduct, $dbh);
        }

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarezps_migracion_db';
    $dbh = new PDO($dsn, 'alvarezps_migracion_dbu', 'eyb54%X45');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT * FROM lote where estado=1 order by idlote limit '.((int) Tools::getValue('id')).',1');

// $rows = getdatarows($dbh,"SELECT * FROM lote where codlote='H103007K'");

foreach ($rows as $row) {
    CrearLote($row, $dbh);
}
