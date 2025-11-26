<?php


use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\RedirectType;
use PrestaShop\PrestaShop\Core\Domain\Product\ProductSettings;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\Ean13;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\Isbn;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\ProductType;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\Reference;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\Upc;
use PrestaShop\PrestaShop\Core\Util\DateTime\DateTime as DateTimeUtil;

class Product extends ProductCore
{
    public function delete($forzar = false)
    {
        if ($forzar === true) return parent::delete();
        AddisLogger::log(__FILE__, __FUNCTION__, null, "Intento borrado producto " . $this->id);
        return true;
    }

    public function deleteAlsernet($products = array())
    {
        $return = false;
        if (!empty($products)) {
            if (!is_array($products)) $products = array($products);
            $return = $this->deleteSelection($products);
        } elseif ($this->id) {
            $return = $this->delete(true);
        }

        if ($return) {
            AddisLogger::log(__FILE__, __FUNCTION__, null, "Eliminado el producto " . $this->id);
            PrestaShopLogger::addLog("Eliminado el producto " . $this->id, 2, null, "Product", $this->id, false);
        } else {
            AddisLogger::log(__FILE__, __FUNCTION__, null, "No se ha podido eliminar el producto " . $this->id);
            PrestaShopLogger::addLog("No se ha podido eliminar el producto " . $this->id, 3, null, "Product", $this->id, false);
        }
        return $return;
    }

    public static function getPricesDrop(
        $id_lang,
        $page_number = 0,
        $nb_products = 10,
        $count = false,
        $order_by = null,
        $order_way = null,
        $beginning = false,
        $ending = false,
        Context $context = null
    )
    {
        if (!$context) {
            $context = Context::getContext();
        }
        if (isset($context->filtered_result) && $context->filtered_result['controller'] == 'pricesdrop') {
            if ($count) {
                return $context->filtered_result['total'];
            }
            return $context->filtered_result['products'];
        } else {
            return parent::getPricesDrop(
                $id_lang,
                $page_number,
                $nb_products,
                $count,
                $order_by,
                $order_way,
                $beginning,
                $ending,
                $context
            );
        }
    }

    public static function getNewProducts(
        $id_lang,
        $page_number = 0,
        $nb_products = 10,
        $count = false,
        $order_by = null,
        $order_way = null,
        Context $context = null
    )
    {
        if (!$context) {
            $context = Context::getContext();
        }
        if (isset($context->filtered_result) && $context->filtered_result['controller'] == 'newproducts') {
            if ($count) {
                return $context->filtered_result['total'];
            }
            return $context->filtered_result['products'];
        } else {
            return parent::getNewProducts(
                $id_lang,
                $page_number,
                $nb_products,
                $count,
                $order_by,
                $order_way,
                $context
            );
        }
    }

    public static function getProductsProperties($id_lang, $query_result)
    {
        if (!empty(Context::getContext()->properties_not_required)) {
            return $query_result;
        } else {
            return parent::getProductsProperties($id_lang, $query_result);
        }
    }

    // public static function makeLabels($expreg, $etiqueta, $etiquetas, &$params, $colortexto, $colorfondo) {
    //     $translator = \Context::getContext()->getTranslator();


    //     preg_match("/(.*?)".$expreg."(.*)/", $etiquetas, $coincidencias);
    //     if (count($coincidencias)>0){


    //         $params["flags"][$etiqueta] = array(
    //             "type" => $expreg,
    //             "label" => $translator->trans($etiqueta, [], 'Modules.Alvarezflags.Admin'), // Traducción usando el dominio de tu módulo
    //             "colorbg" => $colorfondo." !important",
    //             "colort" => $colortexto." !important",
    //         );
    //         return;
    //     }

    //     preg_match("/".$expreg."(.*)/", $etiquetas, $coincidencias);
    //     if (count($coincidencias)>0){

    //         $params["flags"][$etiqueta] = array(
    //             "type" => $expreg,
    //             "label" => $translator->trans($etiqueta, [], 'Modules.Alvarezflags.Admin'), // Traducción usando el dominio de tu módulo
    //             "colorbg" => $colorfondo." !important",
    //             "colort" => $colortexto." !important",

    //         );
    //         return;
    //     }

    //     preg_match("/".$expreg."/", $etiquetas, $coincidencias);
    //     if (count($coincidencias)>0){

    //         $params["flags"][$etiqueta] = array(
    //             "type" => $expreg,
    //             "label" => $translator->trans($etiqueta, [], 'Modules.Alvarezflags.Admin'), // Traducción usando el dominio de tu módulo
    //             "colorbg" => $colorfondo." !important",
    //             "colort" => $colortexto." !important",

    //         );
    //         return;
    //     }

    // }


    public static function getEvent($product)
    {

        $id_lang = Context::getContext()->language->id;
        $id_product = (int)$product['id_product'];
        $allFeatures = $product['features'];

        $viewEvent = array_filter($allFeatures, function ($feature) {
            return isset($feature['id_feature']) && $feature['id_feature'] == 20
                && isset($feature['id_feature_value']) && $feature['id_feature_value'] == 213655;
        });

        return !empty($viewEvent) ? true : false;

    }

    public static function getEventIva($product)
    {

        $module = \Module::getInstanceByName('alserneteventmanager');

        if ($module && $module->active) {
            $events = $module->getActiveEvents();
            if ($events) {
                foreach ($events as $event) {
                    if ($event['iva'] > 0) {

                        $id_feature = $event['featured'];
                        $id_amazing= $event['amazing'];
                        $allFeatures = $product['features'];

                        $viewEvent = array_filter($allFeatures, function ($feature) use ($id_feature,$id_amazing) {
                            return isset($feature['id_feature']) && $feature['id_feature'] == $id_feature && isset($feature['id_feature_value']) && $feature['id_feature_value'] == $id_amazing;
                        });

                        return !empty($viewEvent) ? true : false;

                    }
                }

            }
        }


    }

    public static function getProductFlags($params)
    {
        $idProduct = (int)$params['id_product'];
        $idProductAttribute = (int)$params['id_product_attribute'];

        $db = Db::getInstance();
        $labels = [];

        if ($idProductAttribute === 0) {
            $labelsQuery = "SELECT etiqueta
                        FROM aalv_combinacionunica_import
                        WHERE id_product = $idProduct";
            $labels = $db->getValue($labelsQuery);
        } else {
            $labelsQuery = "SELECT GROUP_CONCAT(c.etiqueta)
                        FROM aalv_combinaciones_import c
                        INNER JOIN aalv_product_attribute pa
                        ON c.id_product_attribute = pa.id_product_attribute
                        WHERE pa.id_product = $idProduct";
            $labels = $db->getValue($labelsQuery);
        }

        $isSecondHandQuery = "SELECT es_segunda_mano
                          FROM aalv_combinacionunica_import
                          WHERE id_product = $idProduct";
        $isSecondHand = (bool)$db->getValue($isSecondHandQuery);

        if ($isSecondHand) {
            $financingFeatureQuery = "SELECT COUNT(*)
                                  FROM aalv_feature_product
                                  WHERE id_product = $idProduct
                                  AND id_feature = 18
                                  AND id_feature_value = 96843";
            if ((int)$db->getValue($financingFeatureQuery) > 0) {
                $labels .= ($labels ? ',' : '') . 'FINSI23';
            }
        }

        $processedLabels = array_unique(array_map('trim', explode(',', $labels)));

        if (empty($processedLabels)) {
            return [];
        }

        $escapedLabels = array_map([$db, 'escape'], $processedLabels);
        $placeholders = "'" . implode("','", $escapedLabels) . "'";

        $flagQuery = "SELECT
                      af.color_texto AS colort,
                      af.color_fondo AS colorbg,
                      afl.etiqueta_front AS label,
                      af.etiqueta AS type
                  FROM aalv_flags af
                  LEFT JOIN aalv_flags_lang afl
                  ON af.id = afl.id
                  WHERE af.etiqueta IN ($placeholders)
                  AND af.activo = 1
                  AND afl.etiqueta_front != ''
                  AND afl.id_lang = " . (int)Context::getContext()->language->id . "
                  ORDER BY af.priority ASC";

        $flags = $db->executeS($flagQuery);
        //dump($flags);


        return $flags ?: [];
    }


    public static function getAttributesColorList(array $products, $have_stock = true)
    {
        if (Module::isEnabled('totswitchattribute')) {
            include_once(_PS_MODULE_DIR_ . 'totswitchattribute/totswitchattribute.php');
            if (TotSwitchAttribute::MODE_HIDE == Configuration::get('TOT_SWITCH_MODE')) {
                # select with a join to tot_switch_attribute_disabled
                if (!count($products)) {
                    return array();
                }
                $id_lang = Context::getContext()->language->id;
                $check_stock = !Configuration::get('PS_DISP_UNAVAILABLE_ATTR');
                $sql = '
			SELECT pa.`id_product`, a.`color`, pac.`id_product_attribute`, ' . ($check_stock ? 'SUM(IF(stock.`quantity` > 0, 1, 0))' : '0') . ' qty, a.`id_attribute`, al.`name`, IF(color = "", a.id_attribute, color) group_by
			FROM `' . _DB_PREFIX_ . 'product_attribute` pa
			' . Shop::addSqlAssociation('product_attribute', 'pa') .
                    ($check_stock ? Product::sqlStock('pa', 'pa') : '') . '
			JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = product_attribute_shop.`id_product_attribute`)
			JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
			JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int)$id_lang . ')
			JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (a.id_attribute_group = ag.`id_attribute_group`)
			LEFT JOIN `' . _DB_PREFIX_ . 'tot_switch_attribute_disabled` sad ON (sad.`id_product_attribute` = pa.`id_product_attribute`)
				' . Shop::addSqlRestriction(false, 'sad') . '
			WHERE pa.`id_product` IN (' . implode(array_map('intval', $products), ',') . ') AND ag.`is_color_group` = 1
			AND sad.`id_tot_switch_attribute_disabled` IS NULL
			GROUP BY pa.`id_product`, a.`id_attribute`, `group_by`
			' . ($check_stock ? 'HAVING qty > 0' : '') . '
			ORDER BY a.`position` ASC;';
                if (!$res = Db::getInstance()->executeS($sql)) {
                    return false;
                }
                if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                    $colors = array();
                    foreach ($res as $row) {
                        $row['texture'] = '';
                        if (
                            Tools::isEmpty($row['color'])
                            && !@filemtime(_PS_COL_IMG_DIR_ . $row['id_attribute'] . '.jpg')
                        ) {
                            continue;
                        } elseif (
                            Tools::isEmpty($row['color'])
                            && @filemtime(_PS_COL_IMG_DIR_ . $row['id_attribute'] . '.jpg')
                        ) {
                            $row['texture'] = _THEME_COL_DIR_ . $row['id_attribute'] . '.jpg';
                        }
                        $colors[(int)$row['id_product']][] = array(
                            'id_product_attribute' => (int)$row['id_product_attribute'],
                            'color' => $row['color'],
                            'texture' => $row['texture'],
                            'id_product' => $row['id_product'],
                            'name' => $row['name'],
                            'id_attribute' => $row['id_attribute']
                        );
                    }
                } else {
                    $colors = array();
                    foreach ($res as $row) {
                        if (
                            Tools::isEmpty($row['color'])
                            && !@filemtime(_PS_COL_IMG_DIR_ . $row['id_attribute'] . '.jpg')
                        ) {
                            continue;
                        }
                        $colors[(int)$row['id_product']][] = array(
                            'id_product_attribute' => (int)$row['id_product_attribute'],
                            'color' => $row['color'],
                            'id_product' => $row['id_product'],
                            'name' => $row['name'],
                            'id_attribute' => $row['id_attribute']
                        );
                    }
                }
                return $colors;
            }
        }
        return parent::getAttributesColorList($products, $have_stock);
    }

    public function getAttributesGroups($id_lang, $id_product_attribute = null)
    {
        if (Module::isEnabled('totswitchattribute')) {
            include_once(_PS_MODULE_DIR_ . 'totswitchattribute/totswitchattribute.php');
            # select with a join to tot_switch_attribute_disabled
            if (!Combination::isFeatureActive()) {
                return array();
            }
            $mpnAndIsbn = 'pa.`mpn`, pa.`isbn`,';
            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                $mpnAndIsbn = '';
            }
            $sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, agl.`public_name` AS public_group_name,
                a.`id_attribute`, al.`name` AS attribute_name, a.`color` AS attribute_color, product_attribute_shop.`id_product_attribute`, IFNULL(stock.quantity, 0) AS quantity,
                IF(sad.`id_tot_switch_attribute_disabled` IS NOT NULL, 0, 1) AS enabled_attribute, product_attribute_shop.`price`, product_attribute_shop.`ecotax`, product_attribute_shop.`weight`,
                product_attribute_shop.`default_on`, pa.`reference`, pa.`ean13`, pa.`upc`, ' . $mpnAndIsbn . '  product_attribute_shop.`unit_price_impact`,
                product_attribute_shop.`minimal_quantity`, product_attribute_shop.`available_date`, ag.`group_type`
            FROM `' . _DB_PREFIX_ . 'product_attribute` pa
            ' . Shop::addSqlAssociation('product_attribute', 'pa') . '
            ' . Product::sqlStock('pa', 'pa') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group`)
            ' . Shop::addSqlAssociation('attribute', 'a') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'tot_switch_attribute_disabled` sad ON (sad.`id_product_attribute` = pa.`id_product_attribute`)
                ' . Shop::addSqlRestriction(false, 'sad') . '
            WHERE pa.`id_product` = ' . (int)$this->id . '
                AND al.`id_lang` = ' . (int)$id_lang . '
                AND agl.`id_lang` = ' . (int)$id_lang;
            if (version_compare(_PS_VERSION_, '1.7', '<') && TotSwitchAttribute::MODE_HIDE == Configuration::get('TOT_SWITCH_MODE')) {
                $sql .= ' AND sad.`id_tot_switch_attribute_disabled` IS NULL';
            }
            $sql .= ' GROUP BY id_attribute_group, id_product_attribute
            ORDER BY ag.`position` ASC, a.`position` ASC, agl.`name` ASC';
            $result = Db::getInstance()->executeS($sql);
            if ($this->esFitting($this->id) || $this->esDemoday($this->id)) {
                $ids_prd_attr = array();
                foreach ($result as $key => $res) {
                    if ($res['id_attribute_group'] == 192) {
                        $fecha = date_create_from_format('d/m/Y', $res['attribute_name']);
                        $fecha = date_format($fecha, 'Y-m-d');
                        if ($fecha < date('Y-m-d')) {
                            $ids_prd_attr[] = $res['id_product_attribute'];
                            unset($result[$key]);
                        }
                    }
                }
                $sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, agl.`public_name` AS public_group_name,
                    a.`id_attribute`, al.`name` AS attribute_name, a.`color` AS attribute_color, product_attribute_shop.`id_product_attribute`, IF(stock.quantity > 0, stock.quantity, pa.quantity) AS quantity,
                    IF(sad.`id_tot_switch_attribute_disabled` IS NOT NULL, 0, 1) AS enabled_attribute, product_attribute_shop.`price`, product_attribute_shop.`ecotax`, product_attribute_shop.`weight`,
                    product_attribute_shop.`default_on`, pa.`reference`, pa.`ean13`, pa.`upc`, ' . $mpnAndIsbn . '  product_attribute_shop.`unit_price_impact`,
                    product_attribute_shop.`minimal_quantity`, product_attribute_shop.`available_date`, ag.`group_type`
                FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                ' . Shop::addSqlAssociation('product_attribute', 'pa') . '
                ' . Product::sqlStock('pa', 'pa') . '
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group`)
                ' . Shop::addSqlAssociation('attribute', 'a') . '
                LEFT JOIN `' . _DB_PREFIX_ . 'tot_switch_attribute_disabled` sad ON (sad.`id_product_attribute` = pa.`id_product_attribute`)
                    ' . Shop::addSqlRestriction(false, 'sad') . '
                WHERE pa.`id_product` = ' . (int)$this->id . '
                    AND al.`id_lang` = ' . (int)$id_lang . '
                    AND agl.`id_lang` = ' . (int)$id_lang;
                if (version_compare(_PS_VERSION_, '1.7', '<') && TotSwitchAttribute::MODE_HIDE == Configuration::get('TOT_SWITCH_MODE')) {
                    $sql .= ' AND sad.`id_tot_switch_attribute_disabled` IS NULL';
                }
                $sql .= ' GROUP BY id_attribute_group, id_product_attribute
                ORDER BY ag.`position` ASC, a.`position` ASC, agl.`name` ASC';
                $result = Db::getInstance()->executeS($sql);
            }
            # We call needDefaultAttribute to be sure there is a default attribute, to be selected in product page
            return $this->needDefaultAttribute($result, 'default_on', true, 'group_name');
        }
        return parent::getAttributesGroups($id_lang, $id_product_attribute);
    }

    public function getAttributesGroupsFDS_Todos($id_lang, $id_product_attribute = null)
    {
        if (Module::isEnabled('totswitchattribute')) {
            include_once(_PS_MODULE_DIR_ . 'totswitchattribute/totswitchattribute.php');
            # select with a join to tot_switch_attribute_disabled
            if (!Combination::isFeatureActive()) {
                return array();
            }
            $mpnAndIsbn = 'pa.`mpn`, pa.`isbn`,';
            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                $mpnAndIsbn = '';
            }
            $sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, agl.`public_name` AS public_group_name,
                a.`id_attribute`, al.`name` AS attribute_name, a.`color` AS attribute_color, product_attribute_shop.`id_product_attribute`, IFNULL(stock.quantity, 0) AS quantity,
                product_attribute_shop.`price`, product_attribute_shop.`ecotax`, product_attribute_shop.`weight`,
                product_attribute_shop.`default_on`, pa.`reference`, pa.`ean13`, pa.`upc`, ' . $mpnAndIsbn . '  product_attribute_shop.`unit_price_impact`,
                product_attribute_shop.`minimal_quantity`, product_attribute_shop.`available_date`, ag.`group_type`
            FROM `' . _DB_PREFIX_ . 'product_attribute` pa
            ' . Shop::addSqlAssociation('product_attribute', 'pa') . '
            ' . Product::sqlStock('pa', 'pa') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group`)
            ' . Shop::addSqlAssociation('attribute', 'a') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'tot_switch_attribute_disabled` sad ON (sad.`id_product_attribute` = pa.`id_product_attribute`)
                ' . Shop::addSqlRestriction(false, 'sad') . '
            WHERE pa.`id_product` = ' . (int)$this->id . '
                AND al.`id_lang` = ' . (int)$id_lang . '
                AND agl.`id_lang` = ' . (int)$id_lang;
            if (version_compare(_PS_VERSION_, '1.7', '<') && TotSwitchAttribute::MODE_HIDE == Configuration::get('TOT_SWITCH_MODE')) {
                $sql .= ' AND sad.`id_tot_switch_attribute_disabled` IS NULL';
            }
            if ($id_product_attribute !== null) {
                $sql .= ' AND product_attribute_shop.`id_product_attribute` = ' . (int)$id_product_attribute . ' ';
            }
            $sql .= ' GROUP BY a.id_attribute
            ORDER BY ag.`position` ASC, a.`position` ASC, agl.`name` ASC';
            $result = Db::getInstance()->executeS($sql);
            # We call needDefaultAttribute to be sure there is a default attribute, to be selected in product page
            return $this->needDefaultAttribute($result, 'default_on', true, 'group_name');
        }
        return parent::getAttributesGroups($id_lang, $id_product_attribute);
    }

    public function getAttributesGroupsFDS($id_lang, $id_product_attribute = null)
    {
        if (Module::isEnabled('totswitchattribute')) {
            include_once(_PS_MODULE_DIR_ . 'totswitchattribute/totswitchattribute.php');
            # select with a join to tot_switch_attribute_disabled
            if (!Combination::isFeatureActive()) {
                return array();
            }
            $mpnAndIsbn = 'pa.`mpn`, pa.`isbn`,';
            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                $mpnAndIsbn = '';
            }
            $sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, agl.`public_name` AS public_group_name,
                a.`id_attribute`, al.`name` AS attribute_name, a.`color` AS attribute_color, product_attribute_shop.`id_product_attribute`, IFNULL(stock.quantity, 0) AS quantity,
                IF(sad.`id_tot_switch_attribute_disabled` IS NOT NULL, 0, 1) AS enabled_attribute, product_attribute_shop.`price`, product_attribute_shop.`ecotax`, product_attribute_shop.`weight`,
                product_attribute_shop.`default_on`, pa.`reference`, pa.`ean13`, pa.`upc`, ' . $mpnAndIsbn . '  product_attribute_shop.`unit_price_impact`,
                product_attribute_shop.`minimal_quantity`, product_attribute_shop.`available_date`, ag.`group_type`
            FROM `' . _DB_PREFIX_ . 'product_attribute` pa
            ' . Shop::addSqlAssociation('product_attribute', 'pa') . '
            ' . Product::sqlStock('pa', 'pa') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group`)
            ' . Shop::addSqlAssociation('attribute', 'a') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'tot_switch_attribute_disabled` sad ON (sad.`id_product_attribute` = pa.`id_product_attribute`)
                ' . Shop::addSqlRestriction(false, 'sad') . '
            WHERE pa.`id_product` = ' . (int)$this->id . '
                AND al.`id_lang` = ' . (int)$id_lang . '
                AND agl.`id_lang` = ' . (int)$id_lang;
            if (version_compare(_PS_VERSION_, '1.7', '<') && TotSwitchAttribute::MODE_HIDE == Configuration::get('TOT_SWITCH_MODE')) {
                $sql .= ' AND sad.`id_tot_switch_attribute_disabled` IS NULL';
            }
            if ($id_product_attribute !== null) {
                $sql .= ' AND product_attribute_shop.`id_product_attribute` = ' . (int)$id_product_attribute . ' ';
            }
            $sql .= ' GROUP BY a.id_attribute
            ORDER BY ag.`position` ASC, a.`position` ASC, agl.`name` ASC';
            $result = Db::getInstance()->executeS($sql);
            # We call needDefaultAttribute to be sure there is a default attribute, to be selected in product page
            return $this->needDefaultAttribute($result, 'default_on', true, 'group_name');
        }
        return parent::getAttributesGroups($id_lang, $id_product_attribute);
    }

    public function getAttributesGroupsFDS2($id_lang, $id_product_attribute = null)
    {
        $sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, agl.`public_name` AS public_group_name,
                    a.`id_attribute`, al.`name` AS attribute_name, a.`color` AS attribute_color, product_attribute_shop.`id_product_attribute`,
                    IFNULL(stock.quantity, 0) as quantity, product_attribute_shop.`price`, product_attribute_shop.`ecotax`, product_attribute_shop.`weight`,
                    product_attribute_shop.`default_on`, pa.`reference`, pa.`ean13`, pa.`mpn`, pa.`upc`, pa.`isbn`, product_attribute_shop.`unit_price_impact`,
                    product_attribute_shop.`minimal_quantity`, product_attribute_shop.`available_date`, ag.`group_type`
                FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                ' . Shop::addSqlAssociation('product_attribute', 'pa') . '
                ' . Product::sqlStock('pa', 'pa') . '
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group`)
                ' . Shop::addSqlAssociation('attribute', 'a') . '
                WHERE pa.`id_product` = ' . (int)$this->id . '
                    AND al.`id_lang` = ' . (int)$id_lang . '
                    AND agl.`id_lang` = ' . (int)$id_lang . '
                ';
        if ($id_product_attribute !== null) {
            $sql .= ' AND product_attribute_shop.`id_product_attribute` = ' . (int)$id_product_attribute . ' ';
        }
        $sql .= 'GROUP BY id_attribute_group, id_product_attribute ORDER BY ag.`position` ASC, a.`position` ASC, agl.`name` ASC';
        return Db::getInstance()->executeS($sql);
    }

    public static function getIdProductAttributeByIdAttributes($idProduct, $idAttributes, $findBest = false)
    {
        $idProduct = (int)$idProduct;
        if (!is_array($idAttributes) && is_numeric($idAttributes)) {
            $idAttributes = [(int)$idAttributes];
        }
        if (!is_array($idAttributes) || empty($idAttributes)) {
            return 0;
        }
        $idAttributesImploded = implode(',', array_map('intval', $idAttributes));
        $addTot = false;
        if (Module::isEnabled('totswitchattribute')) {
            include_once(_PS_MODULE_DIR_ . 'totswitchattribute/totswitchattribute.php');
            if (TotSwitchAttribute::MODE_HIDE == Configuration::get('TOT_SWITCH_MODE')) {
                $addTot = true;
            }
        }
        $sql = '
        SELECT
            pac.`id_product_attribute`
        FROM
            `' . _DB_PREFIX_ . 'product_attribute_combination` pac
            INNER JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON pa.id_product_attribute = pac.id_product_attribute ';
        $sql .= ($addTot) ? ' LEFT JOIN `' . _DB_PREFIX_ . 'tot_switch_attribute_disabled` atsad ON (pa.id_product_attribute = atsad.id_product_attribute) ' : '';
        $sql .= ' WHERE
            pa.id_product = ' . $idProduct . '
            AND pac.id_attribute IN (' . $idAttributesImploded . ') ';
        $sql .= ($addTot) ? ' AND atsad.id_product_attribute IS NULL ' : '';
        $sql .= ' GROUP BY
            pac.`id_product_attribute`
            HAVING
            COUNT(pa.id_product) = ' . count($idAttributes);
        $idProductAttribute = Db::getInstance()->getValue($sql);

        /*chequear si tiene stock la variante seleccionada*/
        $stock = 0;
        if ($idProductAttribute !== false) {
            $sqlStock = 'SELECT COALESCE(SUM(asa.quantity), 0) AS total_qty FROM ' . _DB_PREFIX_ . 'stock_available asa WHERE asa.id_product_attribute = ' . $idProductAttribute;
            $stock = Db::getInstance()->getValue($sqlStock);
            if ((int)$stock == 0) {
                $idProductAttribute = false;
            }

        }

        if (($idProductAttribute === false && $findBest)) {

            $orderred = [];
            $result = Db::getInstance()->executeS(
                '
                SELECT
                    a.`id_attribute`
                FROM
                    `' . _DB_PREFIX_ . 'attribute` a
                    INNER JOIN `' . _DB_PREFIX_ . 'attribute_group` g ON a.`id_attribute_group` = g.`id_attribute_group`
                WHERE
                    a.`id_attribute` IN (' . $idAttributesImploded . ')
                ORDER BY
                    g.`position` ASC'
            );


            foreach ($result as $row) {
                $orderred[] = $row['id_attribute'];
            }


            while ($idProductAttribute === false && count($orderred) > 1) {
                array_pop($orderred);

                /*$idProductAttribute = Db::getInstance()->getValue(
                    '
                    SELECT
                        pac.`id_product_attribute`
                    FROM
                        `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                        INNER JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON pa.id_product_attribute = pac.id_product_attribute
                    WHERE
                        pa.id_product = ' . (int)$idProduct . '
                        AND pac.id_attribute IN (' . implode(',', array_map('intval', $orderred)) . ')
                    GROUP BY
                        pac.id_product_attribute' . count($orderred)
                );*/

                $q = 'SELECT pa.id_product_attribute FROM ' . _DB_PREFIX_ . 'product_attribute pa
                JOIN ' . _DB_PREFIX_ . 'stock_available s ON s.id_product = pa.id_product AND s.id_product_attribute = pa.id_product_attribute
                WHERE pa.id_product = '.$idProduct.' AND s.quantity > 0 AND
                   EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac2
                      WHERE pac2.id_product_attribute = pa.id_product_attribute  AND pac2.id_attribute = '.$orderred[0].' )
                ORDER BY s.quantity DESC, pa.default_on DESC, pa.id_product_attribute ASC
                LIMIT 1';


                $result = Db::getInstance()->executeS($q);
                if($result && count($result)){
                    $idProductAttribute = $result[0]['id_product_attribute'];
                }

            }

        }
        if (empty($idProductAttribute)) {
            return 0;
        }
        return $idProductAttribute;
    }

    public function needDefaultAttribute($array, $defaultField, $defaultValue, $groupAttributeField)
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $switch_attr_groups = array();
            foreach ($array as $key => $attribute) {
                $switch_attr_groups[$attribute['id_attribute']]['enabled_attribute'][] = $attribute['enabled_attribute'];
                $switch_attr_groups[$attribute['id_attribute']]['key'][] = $key;
            }
            foreach ($switch_attr_groups as $key => $attribute) {
                if (array_unique($attribute['enabled_attribute']) == array('0')) {
                    foreach ($attribute['key'] as $key_attr => $value_attr_key) {
                        unset($array[$value_attr_key]);
                    }
                }
            }
        }
        if (
            version_compare(_PS_VERSION_, '1.7', '<')
            && TotSwitchAttribute::MODE_HIDE != Configuration::get('TOT_SWITCH_MODE')
        ) {
            $switch_attr_groups = array();
            foreach ($array as $key => $attribute) {
                $switch_attr_groups[$attribute['id_attribute']]['enabled_attribute'][$key] = $attribute['enabled_attribute'];
                $switch_attr_groups[$attribute['id_attribute']]['key'][$key] = $key;
            }
            foreach ($switch_attr_groups as $key => $attribute) {
                if (
                    array_unique($attribute['enabled_attribute']) != array('0')
                    && array_unique($attribute['enabled_attribute']) != array('1')
                ) {
                    foreach ($attribute['key'] as $key_attr => $value_attr_key) {
                        if ($attribute['enabled_attribute'][$key_attr] == 0) {
                            unset($array[$key_attr]);
                        }
                    }
                }
            }
        }
        foreach ($array as $attribute) {
            if ($attribute[$defaultField] == $defaultValue) {
                return $array;
            }
        }
        $groupAttributeFields = array();
        foreach ($array as $key => $attribute) {
            if (!in_array($attribute[$groupAttributeField], $groupAttributeFields)) {
                $groupAttributeFields[] = $attribute[$groupAttributeField];
                $array[$key][$defaultField] = $defaultValue;
            }
        }
        return $array;
    }

    public function getImages($id_lang, Context $context = null)
    {
        return Db::getInstance()->executeS(
            '
            SELECT image_shop.`cover`, i.`id_image`, il.`legend`, i.`position`
            FROM `' . _DB_PREFIX_ . 'image` i
            ' . Shop::addSqlAssociation('image', 'i') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int)$id_lang . ')
            WHERE i.`id_product` = ' . (int)$this->id . '
            ORDER BY image_shop.`cover` DESC, i.`position` ASC '
        );
    }

    public static function getProductProperties2($id_lang, $row, Context $context = null)
    {
        $row = parent::getProductProperties($id_lang, $row, $context);


        if (Module::isInstalled('wkbundleproduct') && Module::isEnabled('wkbundleproduct')) {
            include_once _PS_MODULE_DIR_ . 'wkbundleproduct/classes/WkBundleProductRequiredClasses.php';
            $objSubProduct = new WkBundleSubProduct();
            $forBundleOnly = $objSubProduct->getAllAvailableProduct();
            if ($forBundleOnly) {
                if (in_array($row['id_product'], $forBundleOnly)) {
                    $row['available_for_order'] = 0;
                }
            }
            $objBundle = new WkBundle();
            if ($objBundle->isBundleProduct($row['id_product'])) {
                $objBpHelper = new WkBundleProductHelper();
                $price = 0;
                $price = $objBpHelper->bundleProductPriceCalculation($row['id_product'], $price);
                if ($price <= 0) {
                    $row['available_for_order'] = 0;
                    $row['show_price'] = 0;
                }
            }
            if (Configuration::get('WK_BUNDLE_PRODUCT_RESERVED_QTY')) {
                if ($objSubProduct->getAllAvailableProduct(0)) {
                    if (in_array($row['id_product'], $objSubProduct->getAllAvailableProduct(0))) {
                        $qty = $objSubProduct->getProductMaximumQuantity(
                            $row['id_product'],
                            $row['id_product_attribute']
                        );
                        if ($qty) {
                            $row['quantity'] = $qty;
                        } else {
                            $row['quantity'] = 0;
                        }
                    }
                }
            }
        }
        return $row;
    }

    public static function isArmasBalines(array $product){
        $context = Context::getContext();
        $categoryDefault = new Category($product['id_category_default']);
        $category_list = [(int)$categoryDefault->id];
        $category_armas_balines_ids = [176,180,213,239,1449,1452,1457,1458,1459];

        foreach ($categoryDefault->getAllParents() as $category) {
            if ($category->id_parent != 0 && !$category->is_root_category && $category->active) {
                $category_list[] = (int)$category->id;
            }
        }
        $interseccion = array_intersect($category_list, $category_armas_balines_ids);
        if (!empty($interseccion)) {
            return true;
        }

        return false;

    }

    public static function anadirGestionManual($id_product = null){
        $existe = Db::getInstance()->getValue("SELECT count(*) FROM aalv_feature_product WHERE id_feature=24 and id_feature_value=270352 and id_product=" . $id_product);
        return ($existe > 0);
    }

    public static function isPing(array $product){
        $context = Context::getContext();

        $id_country = 6; //default España
        if ($context->language->id == 1) $id_country = 6;
        if ($context->language->id == 2) $id_country = 17;
        if ($context->language->id == 3) $id_country = 8;
        if ($context->language->id == 4) $id_country = 15;
        if ($context->language->id == 5) $id_country = 1;
        if ($context->language->id == 6) $id_country = 10;

        if (is_object($context->cart) && !empty($context->cart->id_address_delivery)) {
            $address = new Address($context->cart->id_address_delivery);
            $id_country = $address->id_country;
        }

        if($product['id_manufacturer'] == 4 && ($product['request_price'] || $product['phone_sale'])){
             return true;
        }

        return false;

    }

    public static function isBlockedByCountry($id_product = null,$id_country = 6){
        if (Product::bloqueoMarcasCategorias($id_product, $id_country, 1)) {
            return true;
        }
        if (Product::bloqueoMarcasCategorias($id_product, $id_country, 2)) {
            return true;
        }
        if (Product::bloqueoFeature($id_product, $id_country)) {
            return true;
        }
        if (Product::bloqueoEtiqueta($id_product, $id_country)) {
            return true;
        }
        return false;
    }


    public static function isBlocked($id_product = null)
    {
        $context = Context::getContext();

        $id_country = 6; //default España
        if ($context->language->id == 1) $id_country = 6;
        if ($context->language->id == 2) $id_country = 17;
        if ($context->language->id == 3) $id_country = 8;
        if ($context->language->id == 4) $id_country = 15;
        if ($context->language->id == 5) $id_country = 1;
        if ($context->language->id == 6) $id_country = 10;

        if (is_object($context->cart) && !empty($context->cart->id_address_delivery)) {
            $address = new Address($context->cart->id_address_delivery);
            $id_country = $address->id_country;
        }


        if (Product::bloqueoMarcasCategorias($id_product, $id_country, 1)) {
            return true;
        }
        if (Product::bloqueoMarcasCategorias($id_product, $id_country, 2)) {
            return true;
        }
        if (Product::bloqueoFeature($id_product, $id_country)) {
            return true;
        }
        if (Product::bloqueoEtiqueta($id_product, $id_country)) {
            return true;
        }
        return false;
    }


    public static function isCongratulation($params)
    {

        $id_product = $params["id_product"];
        $id_product_attribute = $params["id_product_attribute"];

        $etiquetas = "";
        if ($id_product_attribute == 0) {
            $etiquetas = Db::getInstance()->getValue("SELECT etiqueta FROM aalv_combinacionunica_import WHERE id_product = " . $id_product);
        } else {
            $etiquetas = Db::getInstance()->getValue("SELECT group_concat(etiqueta) FROM aalv_combinaciones_import WHERE id_product_attribute IN (SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product = " . $id_product . ")");
        }

        $datos_etiquetas = explode(',', $etiquetas);
        $datos_etiquetas = array_unique(array_map('trim', $datos_etiquetas));
        return in_array("FELICITACION", $datos_etiquetas) || $params["view"] == 'fitting';

    }


    private function getCurrentCountry($context)
    {
        if (is_object($context->cart) && !empty($context->cart->id_address_delivery)) {
            $address = new Address($context->cart->id_address_delivery);
            return $address->id_country;
        } elseif (!empty($context->cookie->th_country_selected)) {
            return $context->cookie->th_country_selected;
        } else {
            return Configuration::get('PS_COUNTRY_DEFAULT');
        }
    }

    private function isDefaultAllowedCountry($id_country)
    {
        return $id_country == _PSALV_COUNTRY_ID_ES_PENINSULA_ || $id_country == _PSALV_COUNTRY_ID_ES_BALEARES_;
    }

    private function isUECountry($id_country)
    {
        if (in_array($id_country, _PSALV_COUNTRY_ID_UE_)) {
            return true;
        }
    }

    private function isCategoryBlocked($id_country, $cart_products)
    {
        $bloqueos = Db::getInstance()->executeS("SELECT valor, excepcion FROM " . _DB_PREFIX_ . "bloqueos WHERE id_country = 0 AND id_tipo = 2");
        if ($cart_products != null) {
            foreach ($bloqueos as $bloqueo) {
                $blockedcat = Db::getInstance()->getValue("SELECT id_category
                    FROM aalv_category_product
                    WHERE id_product = {$cart_products} AND id_category = {$bloqueo['valor']}");
                $excepciones = explode(",", $bloqueo['excepcion']);
                if (!in_array($id_country, $excepciones)) {
                    if ($blockedcat) {
                        return true;
                    }
                }
            }
        }
        $cart_products_pickup_gc = Cart::haveMultipleProductTypes($context->cart->id);
        if ($cart_products_pickup_gc) {
            if ((int)$context->cart->id_address_invoice) {
                $address = new Address($context->cart->id_address_invoice);
                $id_country = $address->id_country;
                foreach ($cart_products_pickup_gc as $cart_product_pickup_gc) {
                    if ($this->id == (int)$cart_product_pickup_gc['id_product']) {
                        $id_country = Configuration::get('PS_COUNTRY_DEFAULT');
                    }
                }
            }
        }
        $bloqueos = Db::getInstance()->executeS("SELECT b.id_tipo, bt.codigo, GROUP_CONCAT(b.id_country) AS countries, b.valor
                                                            FROM " . _DB_PREFIX_ . "bloqueos b
                                                            INNER JOIN " . _DB_PREFIX_ . "bloqueos_tipo bt ON (b.id_tipo = bt.id)
                                                            GROUP BY bt.codigo, b.valor");
        if ($bloqueos) {
            $allFeatures = $this->getFeatures();
            $features = array_column($allFeatures, 'id_feature_value', 'id_feature');
            foreach ($bloqueos as $bloqueo) {
                $tipo = $bloqueo["id_tipo"];
                $valor = $bloqueo["valor"];
                $code = $bloqueo['codigo'];
                $countriesAllowed = explode(",", $bloqueo['countries']);
                $inCountries = (!empty($countriesAllowed) && in_array($id_country, $countriesAllowed));
                switch ($tipo) {
                    case $idTypeBrand: // Se bloquea el pais, por MARCA
                        if ($inCountries && $valor == $this->id_manufacturer) {
                            return true;
                        }
                        break;
                    case $idTypeCategory: // Se bloquea el pais, por CATEGORIA
                        if ($inCountries && in_array($valor, $this->getCategories())) {
                            return true;
                        }
                        break;
                    default: // Tipo de producto
                        if ($tipo == $idTypeBlock) {
                            $idFeat = _PSALV_CONFIG_BLOCKSALE_FEATURE_;
                        } else {
                            $idFeat = (!empty(Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE'))) ? (int)Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE') : _PSALV_CONFIG_PRODUCTTYPE_FEATURE_;
                        }
                        if (!empty($features[$idFeat]) && ($features[$idFeat] == $code)) {
                            if ($inCountries) {
                                return !$valor;
                            } else { // !$inCountries
                                return $valor;
                            }
                        }
                        break;
                } // switch
            } // foreach $bloqueos
        }
        return false;
    } // function isBlocked

    public static function isBlockedByProductType($id_product = null)
    {
        $prodType = _PSALV_CONFIG_PRODUCTTYPE_LOTTERY_; // 'BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_PICKUP_GC';
        $product_blocked = false;
        $cart_is_pickup_gc = false;
        $product_is_pickup_gc = false;
        $id_feature_product_type = 0;
        if (Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE'))) {
            $id_feature_product_type = (int)Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE');
        }
        $id_feature_value_product_type_pickup_gc = '';
        if (Configuration::get($prodType)) {
            $id_feature_value_product_type_pickup_gc = Configuration::get($prodType);
        }
        if ($id_feature_product_type && $id_feature_value_product_type_pickup_gc != '') {
            $cart = Context::getContext()->cart;
            $product_list = $cart->getProducts();
            if ($product_list && count($product_list) > 1) { // lo comparo con > 1 porque el producto que bloqueamos actualmente está en la cesta. Y si en la cesta sólo hay 1 producto significa que la cesta estaba vacía
                foreach ($product_list as $product_cart) {
                    if ((int)$product_cart['id_product'] != $id_product) {
                        if ($product_cart['features']) {
                            foreach ($product_cart['features'] as $feature) {
                                if ((int)$feature['id_feature'] == $id_feature_product_type) {
                                    if (str_contains(',' . $id_feature_value_product_type_pickup_gc . ',', ',' . $feature['id_feature_value'] . ',')) {
                                        $cart_is_pickup_gc = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                }
                $features = Product::getFeaturesStatic($id_product);
                if ($features) {
                    foreach ($features as $feature) {
                        if ((int)$feature['id_feature'] == $id_feature_product_type) {
                            if (str_contains(',' . $id_feature_value_product_type_pickup_gc . ',', ',' . $feature['id_feature_value'] . ',')) {
                                $product_is_pickup_gc = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
        if ($cart_is_pickup_gc != $product_is_pickup_gc) {
            $product_blocked = true;
        }
        return $product_blocked;
    }

    public static function isCategoryRelationByProductType($prod)
    {

        $relation = false;
        $active = true;

        $sql = 'SELECT *
                    FROM `' . _DB_PREFIX_ . 'categories_relation` cr
                    INNER JOIN `' . _DB_PREFIX_ . 'categories_relation_lang` crl ON cr.`id_categories_relation`=crl.`id_categories_relation` AND crl.`id_lang`=' . (int)Context::getContext()->language->id . '

                    WHERE cr.`id_category_source`=' . (int)$prod->id_category_default . ($active ? ' AND cr.`active`=1' : '');

        $category_relation = DB::getInstance()->getRow($sql);

        if ($category_relation) {
            $relation = true;
        }

        return $relation;

    }

    public static function getAttributesArray($id_product, $id_product_attribute, $id_lang = null, $id_shop = null)
    {
        $attributes = [];
        if (!$id_lang) {
            $id_lang = Context::getContext()->language->id;
        }
        if (!$id_shop) {
            $id_shop = Context::getContext()->shop->id;
        }
        $sql = 'SELECT ag.`id_attribute_group`, agl.`name` AS \'group_name\', agl.`public_name`, a.`id_attribute`, al.`name` AS \'attribute_name\'
                FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                INNER JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute`=pac.`id_attribute`
                INNER JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON al.`id_attribute`=a.`id_attribute` AND al.id_lang=' . (int)$id_lang . '
                INNER JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group`=a.`id_attribute_group`
                INNER JOIN `' . _DB_PREFIX_ . 'attribute_group_shop` ags ON ags.`id_attribute_group`=ag.`id_attribute_group` AND ags.`id_shop`=' . (int)$id_shop . '
                INNER JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON agl.`id_attribute_group`=ag.`id_attribute_group` AND agl.`id_lang`=' . (int)$id_lang . '
                WHERE pac.`id_product_attribute`=' . (int)$id_product_attribute . '
                ORDER BY ag.`position` ASC, a.`position`';
        $result = DB::getInstance()->executeS($sql);
        if ($result) {
            foreach ($result as $attribute) {
                $attributes[] = [
                    'id_attribute_group' => $attribute['id_attribute_group'],
                    'group_name' => $attribute['group_name'],
                    'public_name' => $attribute['public_name'],
                    'id_attribute' => $attribute['id_attribute'],
                    'attribute_name' => $attribute['attribute_name'],
                ];
            }
        }
        return $attributes;
    }

    public static function featuresView($product)
    {

        $id_lang = Context::getContext()->language->id;
        $id_product = (int)$product['id_product'];
        $allFeatures = $product['features'];

        $viewFound = array_filter($allFeatures, function ($feature) {
            return isset($feature['id_feature']) && $feature['id_feature'] == 21;
        });

        $foundFeature = !empty($viewFound) ? reset($viewFound) : null;

        return $foundFeature ? $foundFeature['value'] : 'default';
    }

    public static function featuresCustom($product)
    {

        $id_lang = Context::getContext()->language->id;
        $id_product = (int)$product['id_product'];
        $allFeatures = $product['features'];

        $viewFound = array_filter($allFeatures, function ($feature) {
            return isset($feature['id_feature']) && $feature['id_feature'] == 25;
        });

        $foundFeature = !empty($viewFound) ? reset($viewFound) : null;

        return $foundFeature ? $foundFeature['value'] : 'default';
    }

    public static function featuresFrom($product)
    {

        $id_lang = Context::getContext()->language->id;
        if (isset($product['id_product'])) {
            $id_product = (int)$product['id_product'];
            $allFeatures = $product['features'];

            $viewFound = array_filter($allFeatures, function ($feature) {
                return isset($feature['id_feature']) && $feature['id_feature'] == 9;
            });

            $foundFeature = !empty($viewFound) ? reset($viewFound) : null;
            return $foundFeature ? $foundFeature['value'] : null;

        } else {
            return false;
        }

    }

    public static function featuresFilm($product)
    {

        $sql = 'SELECT `provider`, `url`
        FROM `' . _DB_PREFIX_ . 'product_film`
        WHERE `id_product` = ' . (int)$product['id_product'] . '
        AND `available` = ' . 1 . '
        AND `id_lang` = ' . (int)Context::getContext()->language->id;

        $results = DB::getInstance()->executeS($sql);

        $url = null;

        if (!empty($results)) {

            $last_result = end($results);

            if (strtolower($last_result['provider']) === 'youtube') {
                $video_id = '';

                if (strpos($last_result['url'], 'youtube.com/watch?v=') !== false) {
                    parse_str(parse_url($last_result['url'], PHP_URL_QUERY), $query);
                    $video_id = isset($query['v']) ? $query['v'] : '';
                } elseif (strpos($last_result['url'], 'youtu.be/') !== false) {
                    $url_parts = explode('/', $last_result['url']);
                    $video_id = end($url_parts);
                }

                if ($video_id) {
                    $url = 'https://www.youtube.com/embed/' . pSQL($video_id);
                }
            }
        }

        return $url;

    }

    public static function featureViews($product, $view)
    {

        if (isset($product['features'])) {
            $allFeatures = $product['features'];

            $viewFound = array_filter($allFeatures, function ($feature) {
                return isset($feature['id_feature']) && $feature['id_feature'] == 21;
            });

            $foundFeature = !empty($viewFound) ? reset($viewFound) : null;
            $row['view'] = $foundFeature ? $foundFeature['value'] : 'default';

            return $row['view'] === $view;
        } else {
            return false;
        }

    }

    public static function esFitting($product)
    {
        return self::featureViews($product, "fitting");
    }

    public static function esDemoday($product)
    {
        return self::featureViews($product, "day");
    }

    public static function getProductFeatureValueStatic($id_product, $id_feature, $id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = Context::getContext()->language->id;
        }
        foreach (Product::getFrontFeaturesStatic((int)$id_lang, (int)$id_product) as $product_feature) {
            if ((int)$product_feature['id_feature'] == (int)$id_feature) {
                return $product_feature;
            }
        }
        return false;
    }

    public static function getFamiliaAlvarez($id_product, $id_product_attribute = 0, $id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = Context::getContext()->language->id;
        }
        $feature_value = '';
        $id_feature = 0;
        if (Configuration::get('BAN_PRODUCT_FEATURE_FAMILIA') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_FAMILIA'))) {
            $id_feature = (int)Configuration::get('BAN_PRODUCT_FEATURE_FAMILIA');
        }
        $feature = self::getProductFeatureValueStatic((int)$id_product, $id_feature, $id_lang);
        if ($feature && count($feature) > 0) {
            $feature_value = $feature['value'];
        }
        if (empty($feature_value)) {
            if (!$id_product_attribute) {
                $sql = 'SELECT `familia`
                        FROM `' . _DB_PREFIX_ . 'combinacionunica_import`
                        WHERE `id_product`=' . (int)$id_product;
                $feature_value = DB::getInstance()->getValue($sql);
            } else {
                $sql = 'SELECT `familia`
                        FROM `' . _DB_PREFIX_ . 'combinaciones_import`
                        WHERE `id_product_attribute`=' . (int)$id_product_attribute;
                $feature_value = DB::getInstance()->getValue($sql);
            }
        }
        return $feature_value;
    }

    public static function getSubfamiliaAlvarez($id_product, $id_product_attribute = 0, $id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = Context::getContext()->language->id;
        }
        $feature_value = '';
        $id_feature = 0;
        if (Configuration::get('BAN_PRODUCT_FEATURE_SUBFAMILIA') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_SUBFAMILIA'))) {
            $id_feature = (int)Configuration::get('BAN_PRODUCT_FEATURE_SUBFAMILIA');
        }
        $feature = self::getProductFeatureValueStatic((int)$id_product, $id_feature, $id_lang);
        if ($feature && count($feature) > 0) {
            $feature_value = $feature['value'];
        }
        if (empty($feature_value)) {
            if (!$id_product_attribute) {
                $sql = 'SELECT `subfamilia`
                        FROM `' . _DB_PREFIX_ . 'combinacionunica_import`
                        WHERE `id_product`=' . (int)$id_product;
                $feature_value = DB::getInstance()->getValue($sql);
            } else {
                $sql = 'SELECT `subfamilia`
                        FROM `' . _DB_PREFIX_ . 'combinaciones_import`
                        WHERE `id_product_attribute`=' . (int)$id_product_attribute;
                $feature_value = DB::getInstance()->getValue($sql);
            }
        }
        return $feature_value;
    }

    public static function getGrupoAlvarez($id_product, $id_product_attribute = 0, $id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = Context::getContext()->language->id;
        }
        $feature_value = '';
        $id_feature = 0;
        if (Configuration::get('BAN_PRODUCT_FEATURE_GRUPO') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_GRUPO'))) {
            $id_feature = (int)Configuration::get('BAN_PRODUCT_FEATURE_GRUPO');
        }
        $feature = self::getProductFeatureValueStatic((int)$id_product, $id_feature, $id_lang);
        if ($feature && count($feature) > 0) {
            $feature_value = $feature['value'];
        }
        if (empty($feature_value)) {
            if (!$id_product_attribute) {
                $sql = 'SELECT `grupo`
                        FROM `' . _DB_PREFIX_ . 'combinacionunica_import`
                        WHERE `id_product`=' . (int)$id_product;
                $feature_value = DB::getInstance()->getValue($sql);
            } else {
                $sql = 'SELECT `grupo`
                        FROM `' . _DB_PREFIX_ . 'combinaciones_import`
                        WHERE `id_product_attribute`=' . (int)$id_product_attribute;
                $feature_value = DB::getInstance()->getValue($sql);
            }
        }
        return $feature_value;
    }

    public static function getCategoryAlvarez($id_product, $id_product_attribute = 0, $id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = Context::getContext()->language->id;
        }
        $feature_value = '';
        $id_feature = 0;
        if (Configuration::get('BAN_PRODUCT_FEATURE_CATEGORIA') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_CATEGORIA'))) {
            $id_feature = (int)Configuration::get('BAN_PRODUCT_FEATURE_CATEGORIA');
        }
        $feature = self::getProductFeatureValueStatic((int)$id_product, $id_feature, $id_lang);
        if ($feature && count($feature) > 0) {
            $feature_value = $feature['value'];
        }
        if (empty($feature_value)) {
            if (!$id_product_attribute) {
                $sql = 'SELECT `categoria`
                        FROM `' . _DB_PREFIX_ . 'combinacionunica_import`
                        WHERE `id_product`=' . (int)$id_product;
                $feature_value = DB::getInstance()->getValue($sql);
            } else {
                $sql = 'SELECT `categoria`
                        FROM `' . _DB_PREFIX_ . 'combinaciones_import`
                        WHERE `id_product_attribute`=' . (int)$id_product_attribute;
                $feature_value = DB::getInstance()->getValue($sql);
            }
        }
        return $feature_value;
    }

    public static function getAttributesInformationsByProductFiDe($id_product, $attr_fecha)
    {
        $result = Db::getInstance()->executeS('
        SELECT pa.`id_product_attribute`, a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`,pa.`reference`, pa.`ean13`, pa.`isbn`, pa.`upc`, pa.`mpn`
        FROM `' . _DB_PREFIX_ . 'attribute` a
        LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int)Context::getContext()->language->id . ')
        LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int)Context::getContext()->language->id . ')
        LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (a.`id_attribute` = pac.`id_attribute`)
        LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (pac.`id_product_attribute` = pa.`id_product_attribute`)' . Shop::addSqlAssociation('product_attribute', 'pa') . '' . Shop::addSqlAssociation('attribute', 'pac') . '
        WHERE pa.`id_product` = ' . (int)$id_product . ' AND pac.`id_attribute` = ' . (int)$attr_fecha);
        return $result;
    }

    public static function hayStockPocomaco($id_product, $id_product_attribute)
    {
        if (!in_array(strtolower(Context::getContext()->country->iso_code), ["es", "pt", "fr"])) {
            return false;
        }
        $sql = 'SELECT
                    stock.`quantity_pocomaco`
                FROM
                    `' . _DB_PREFIX_ . 'repositorio_stock` stock
                    INNER JOIN ' . _DB_PREFIX_ . 'product ap on ap.id_product = stock.id_product
                    LEFT JOIN ' . _DB_PREFIX_ . 'combinacionunica_import unica ON unica.id_product = stock.id_product
	                LEFT JOIN ' . _DB_PREFIX_ . 'combinaciones_import comb ON comb.id_product_attribute = stock.id_product_attribute
                WHERE
                    stock.`id_product`=' . (int)$id_product . '
                    AND stock.`id_product_attribute`=' . (int)$id_product_attribute . '
                    AND ap.`is_virtual` != 1
                    AND (unica.etiqueta NOT LIKE "%NO48H%"
	                OR comb.etiqueta  NOT LIKE "%NO48H%")';
        $hay_stock_pocomaco = DB::getInstance()->getValue($sql);
        return $hay_stock_pocomaco > 0 ? true : false;
    }

    public static function controlStock($id_product, $id_product_attribute = false, $stock = 0, $debug = false)
    {
        if ($id_product_attribute > 0) {
            $product_import = Db::getInstance()->ExecuteS("SELECT estado_gestion, etiqueta, externo_disponibilidad
                                                      FROM aalv_combinaciones_import
                                                      WHERE id_product_attribute=" . $id_product_attribute);
        } else {
            $combinaciones = Db::getInstance()->ExecuteS("SELECT id_product_attribute
                                                FROM aalv_product_attribute
                                                WHERE id_product=" . $id_product);
            $producto_con_combinaciones = count($combinaciones) > 0 ? true : false;
            if ($producto_con_combinaciones) {
                return $stock;
            }
            $product_import = Db::getInstance()->ExecuteS("SELECT estado_gestion, etiqueta, externo_disponibilidad
                                                      FROM aalv_combinacionunica_import
                                                      WHERE id_product=" . $id_product);
        }
        if (empty($product_import)) {
            return false;
        }

        if (Product::ocultarVeranoInvierno($product_import[0]['etiqueta'])) {
            return 0;
        } elseif (Product::controlEtiquetaStockWeb($product_import[0]['etiqueta']) || $product_import[0]['estado_gestion'] == 2) {
            return $stock;
        } elseif ($product_import[0]['externo_disponibilidad']) {
            return 999999;
        } else {
            return $stock;
        }
    }

    public static function getVisibilidad($id_product, $id_product_attribute = false, $debug = false)
    {
        $stock = [];
        $product_import = [];
        $id_lote = Db::getInstance()->getValue("SELECT id_wk_bundle_product FROM aalv_wk_bundle_product WHERE id_ps_product=" . $id_product);
        if ($id_product_attribute) {
            $stock = Db::getInstance()->ExecuteS("SELECT
                                                    p.id_product,
                                                    pl.name AS product_name,
                                                    SUM(s.quantity) AS total_stock
                                                FROM
                                                    aalv_product AS p
                                                    LEFT JOIN aalv_product_lang AS pl ON p.id_product = pl.id_product
                                                    LEFT JOIN aalv_stock_available AS s ON p.id_product = s.id_product
                                                WHERE
                                                    p.id_product = " . $id_product . "
                                                    and s.id_product_attribute = " . $id_product_attribute . "
                                                    and pl.id_lang = 1");
        } else {
            $combinaciones = Db::getInstance()->ExecuteS("SELECT id_product_attribute
                                                FROM aalv_product_attribute
                                                WHERE id_product=" . $id_product);
            $producto_con_combinaciones = count($combinaciones) > 0 ? true : false;
            $stock = Db::getInstance()->ExecuteS("SELECT
                                                    p.id_product,
                                                    pl.name AS product_name,
                                                    SUM(s.quantity) AS total_stock
                                                  FROM
                                                    aalv_product AS p
                                                    LEFT JOIN aalv_product_lang AS pl ON p.id_product = pl.id_product
                                                    LEFT JOIN aalv_stock_available AS s ON p.id_product = s.id_product
                                                  WHERE
                                                    p.id_product = " . $id_product . "
                                                    and s.id_product_attribute != (select if(count(1) = 1,1,0) from aalv_stock_available where id_product = " . $id_product . ")
                                                    and pl.id_lang = 1");
        }
        if ($id_product_attribute) {
            $product_import = Db::getInstance()->ExecuteS("SELECT etiqueta, estado_gestion, activo, externo_disponibilidad, id_product_attribute
                                                      FROM aalv_combinaciones_import
                                                      WHERE id_product_attribute=" . $id_product_attribute);
        } elseif ($producto_con_combinaciones) {
            $product_import[0]['activo'] = 0;
            $product_import[0]['estado_gestion'] = 0;
            $data['externo_disponibilidad'] = 0;
            $import = Db::getInstance()->ExecuteS("SELECT etiqueta, estado_gestion, activo, externo_disponibilidad, id_product_attribute
                                                        FROM
                                                            aalv_combinaciones_import
                                                        WHERE id_product_attribute IN (
                                                            SELECT
                                                                id_product_attribute
                                                            FROM
                                                                aalv_product_attribute
                                                            WHERE
                                                                id_product = " . $id_product . ")");
            foreach ($import as $data) {
                if ($data['id_product_attribute'] && Product::getVisibilidad($id_product, $data['id_product_attribute'])) {
                    $product_import[0]['activo'] = 1;
                }
                if ($data['estado_gestion'] > 0) {
                    $product_import[0]['estado_gestion'] = $data['estado_gestion'];
                }
                if ($data['externo_disponibilidad'] > 0) {
                    $product_import[0]['externo_disponibilidad'] = $data['externo_disponibilidad'];
                }
            }
            if ($debug) {
                if ($product_import[0]['activo']) {
                    var_dump('Algún modelo activo');
                } else {
                    var_dump('Todos los modelos no activos');
                }
            }
        } else {
            $product_import = Db::getInstance()->ExecuteS("SELECT etiqueta, estado_gestion, activo, externo_disponibilidad
                                                      FROM aalv_combinacionunica_import
                                                      WHERE id_product=" . $id_product);
        }
        if (empty($product_import)) {
            PrestaShopLogger::addLog("Error al determinar la visibilidad. Producto, " . $id_product . " (" . $id_product_attribute . "), sin registro en la tabla import.", 3, null, "Product", $id_product, false);
            return;
        }
        $specific_price = Db::getInstance()->getValue("SELECT ifnull(sum(price),0) FROM aalv_specific_price WHERE id_product=" . $id_product);
        if ($specific_price <= 0 && !$id_lote) {
            if ($debug) var_dump('Precio');
            return false;
        }
        if ($product_import[0]['estado_gestion'] == 0) {
            if ($debug) var_dump('No activo en gestión');
            return false;
        }
        if ($product_import[0]['activo'] == 0) {
            if ($debug) var_dump('No visible en gestión');
            return false;
        }
        if (strpos($product_import[0]['etiqueta'], 'OCULTO WEB') !== false) {
            if ($debug) var_dump('Oculto web');
            return false;
        }
        if (Product::ocultarVeranoInvierno($product_import[0]['etiqueta'])) {
            if ($debug) var_dump('Estación del año');
            return false;
        }
        if ($id_lote) {
            $productoslote = Db::getInstance()->ExecuteS("SELECT id_product, id_wk_bundle_section FROM aalv_wk_bundle_sub_product where id_wk_bundle_section in (SELECT id_wk_bundle_section FROM aalv_wk_bundle_section_map where id_wk_bundle_product=" . $id_lote . ")");
            $partestock = [];
            foreach ($productoslote as $productosloteitem) {
                $partestock[$productosloteitem["id_wk_bundle_section"]] = 0;
            }
            foreach ($productoslote as $productosloteitem) {
                $partestock[$productosloteitem["id_wk_bundle_section"]] = $partestock[$productosloteitem["id_wk_bundle_section"]] + StockAvailable::getQuantityAvailableByProduct($productosloteitem["id_product"], 0);
            }
            if (!$partestock) {
                if ($debug) var_dump('Lote sin productos');
                return false;
            }
            foreach ($partestock as $partestockitem) {
                if ($partestockitem <= 0) {
                    if ($debug) var_dump('Lote sin stock');
                    return false;
                }
            }

            if ($debug) var_dump('Lote visible');
            return true;
        }
        if ($stock[0]["total_stock"] > 0) {
            if ($debug) var_dump('Visible por stock');
            return true;
        } else {
            if ($debug) var_dump('Sin stock');
            return false;
        }
        PrestaShopLogger::addLog("Error al determinar la visibilidad. Producto, " . $id_product . " (" . $id_product_attribute . ").", 2, null, "Product", $id_product, false);
        return;
    }

    private static function controlEtiquetaStockWeb($etiquetas)
    {
        $tags_exclude = Db::getInstance()->getValue("SELECT GROUP_CONCAT(etiqueta) from aalv_etiqueta_stock");
        $tags_exclude = explode(",", $tags_exclude);
        $tags_exclude = array_map('trim', $tags_exclude);
        return array_intersect($tags_exclude, explode(", ", $etiquetas)) ? true : false;
    }

    private static function ocultarVeranoInvierno($etiquetas)
    {
        if ($etiquetas != "") {
            $etiquetasarray = explode(",", $etiquetas);
            foreach ($etiquetasarray as $key => $value) {
                $etiquetasarray[$key] = trim($value);
            }
            if (count($etiquetasarray) > 0) {
                $mes = (int)date("m");
                $dia = (int)date("d");
                if (in_array("TEMPORADA_INVIERNO", $etiquetasarray)) {
                    switch ($mes) {
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                            return true;
                            break;
                        case 8:
                            if ($dia <= 15) {
                                return true;
                            }
                            break;
                    }
                }
                if (in_array("TEMPORADA_VERANO", $etiquetasarray)) {
                    switch ($mes) {
                        case 10:
                        case 11:
                        case 12:
                        case 1:
                            return true;
                            break;
                        case 2:
                            if ($dia <= 16) {
                                return true;
                            }
                            break;
                    }
                }
                return false;
            }
        }
        return false;
    }

    public function getTemplateVarProduct()
    {
        $productSettings = $this->getProductPresentationSettings();
        $extraContentFinder = new ProductExtraContentFinder();

        $product = $this->objectPresenter->present($this->product);
        $product['id_product'] = (int)$this->product->id;
        $product['out_of_stock'] = (int)$this->product->out_of_stock;
        $product['new'] = (int)$this->product->new;
        $product['id_product_attribute'] = $this->getIdProductAttributeByGroupOrRequestOrDefault();
        $product['minimal_quantity'] = $this->getProductMinimalQuantity($product);
        $product['quantity_wanted'] = $this->getRequiredQuantity($product);
        $product['extraContent'] = $extraContentFinder->addParams(['product' => $this->product])->present();
        $product['view'] = $this->featuresView($product->id);
        $product['ecotax'] = Tools::convertPrice($this->getProductEcotax($product), $this->context->currency, true, $this->context);

        $product_full = Product::getProductProperties($this->context->language->id, $product, $this->context);

        $product_full = $this->addProductCustomizationData($product_full);

        $product_full['show_quantities'] = (bool)(
            Configuration::get('PS_DISPLAY_QTIES')
            && Configuration::get('PS_STOCK_MANAGEMENT')
            && $this->product->quantity > 0
            && $this->product->available_for_order
            && !Configuration::isCatalogMode()
        );
        $product_full['quantity_label'] = ($this->product->quantity > 1) ? $this->trans('Items', [], 'Shop.Theme.Catalog') : $this->trans('Item', [], 'Shop.Theme.Catalog');
        $product_full['quantity_discounts'] = $this->quantity_discounts;

        if ($product_full['unit_price_ratio'] > 0) {
            $unitPrice = ($productSettings->include_taxes) ? $product_full['price'] : $product_full['price_tax_exc'];
            $product_full['unit_price'] = $unitPrice / $product_full['unit_price_ratio'];
        }

        $group_reduction = GroupReduction::getValueForProduct($this->product->id, (int)Group::getCurrent()->id);
        if ($group_reduction === false) {
            $group_reduction = Group::getReduction((int)$this->context->cookie->id_customer) / 100;
        }
        $product_full['customer_group_discount'] = $group_reduction;
        $product_full['title'] = $this->getProductPageTitle();

        $product_full['rounded_display_price'] = Tools::ps_round(
            $product_full['price'],
            Context::getContext()->currency->precision
        );

        $presenter = $this->getProductPresenter();

        return $presenter->present(
            $productSettings,
            $product_full,
            $this->context->language
        );
    }

    public static function bloqueoMarcasCategorias($id_product, $id_country, $tipo)
    {
        if ($tipo == 1) {
            $buscar = DB::getInstance()->getValue("SELECT id_manufacturer FROM aalv_product WHERE id_product = " . $id_product);
            $buscar_bloqueo = Db::getInstance()->executeS("SELECT id_country,excepcion FROM aalv_bloqueos WHERE id_tipo = 1 AND valor = " . $buscar);
        } else {
            $buscar = DB::getInstance()->executeS("SELECT id_category FROM aalv_category_product WHERE id_product = " . $id_product);
            $id_categories = array_map(function ($item) {
                return $item["id_category"];
            }, $buscar);
            $buscar = implode(",", $id_categories);
            if (!empty($buscar)) {
                $buscar_bloqueo = Db::getInstance()->executeS(
                    "SELECT id_country, excepcion FROM aalv_bloqueos WHERE id_tipo = 2 AND valor IN (" . $buscar . ")"
                );
            } else {
                $buscar_bloqueo = []; // o null, según lo que necesites
            }
        }
        foreach ($buscar_bloqueo as $value) {
            if ($value['id_country'] != 0) {
                if ($value['id_country'] == $id_country) {
                    return true;
                }
            } else if ($value['id_country'] == 0) {
                $excepcion = explode(",", $value['excepcion']);
                $excepcion = array_map('trim', $excepcion);
                if (in_array($id_country, $excepcion)) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }


    public static function bloqueoFeature($id_product, $id_country)
    {
        $buscar_feature = DB::getInstance()->executeS("SELECT id_feature_value FROM aalv_feature_product afp WHERE id_product = " . $id_product);
        foreach ($buscar_feature as $value) {
            $buscar = DB::getInstance()->executeS("SELECT ab.id_country,ab.valor,ab.excepcion FROM aalv_bloqueos_tipo abt LEFT JOIN aalv_bloqueos ab ON ab.id_tipo = abt.id WHERE abt.codigo != 0 AND abt.codigo = " . $value['id_feature_value']);
            if (count($buscar) != 0) {
                foreach ($buscar as $val) {
                    if ($val['valor'] == 1) {
                        if ($val['id_country'] != 0) {
                            if ($val['id_country'] == $id_country) {
                                return true;
                            }
                        } else if ($val['id_country'] == 0) {
                            $excepcion = explode(",", $val['excepcion']);
                            $excepcion = array_map('trim', $excepcion);
                            if (in_array($id_country, $excepcion)) {
                                return false;
                            } else {
                                return true;
                            }
                        }
                    }
                }
            }
        }
    }

    public static function bloqueoEtiqueta($id_product, $id_country)
    {

        try {
            $buscamos_etiquetas = DB::getInstance()->executeS("SELECT id_country, valor FROM aalv_bloqueos WHERE valor NOT REGEXP '[0-9]'");
            foreach ($buscamos_etiquetas as $value) {
                $id_products = DB::getInstance()->executeS(" SELECT
                                                                    apa.id_product
                                                            FROM
                                                                aalv_combinaciones_import aci
                                                                LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                                            WHERE
                                                                apa.id_product = " . $id_product . "
                                                                AND aci.etiqueta LIKE '%" . $value['valor'] . "%'
                                                            UNION
                                                            SELECT id_product FROM aalv_combinacionunica_import WHERE id_product = " . $id_product . " AND etiqueta LIKE '%" . $value['valor'] . "%'");
                if (count($id_products) > 0) {
                    if ($id_product) {
                        if ($value['id_country'] == $id_country) {
                            return true;
                        }
                    }
                }
            }
            return false;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error en bloqueoEtiqueta[' . $e->getMessage() . ']');
            return false;
        }
    }

    public static function priceCalculation(
        $id_shop,
        $id_product,
        $id_product_attribute,
        $id_country,
        $id_state,
        $zipcode,
        $id_currency,
        $id_group,
        $quantity,
        $use_tax,
        $decimals,
        $only_reduc,
        $use_reduc,
        $with_ecotax,
        &$specific_price,
        $use_group_reduction,
        $id_customer = 0,
        $use_customer_price = true,
        $id_cart = 0,
        $real_quantity = 0,
        $id_customization = 0
    )
    {


        static $address = null;
        static $context = null;
        if ($context == null) {
            $context = Context::getContext()->cloneContext();
        }
        if ($address === null) {
            if (is_object($context->cart) && $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')} != null) {
                $id_address = $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
                $address = new Address($id_address);
            } else {
                $address = new Address();
            }
        }
        if ($id_shop !== null && $context->shop->id != (int)$id_shop) {
            $context->shop = new Shop((int)$id_shop);
        }
        if (!$use_customer_price) {
            $id_customer = 0;
        }
        if ($id_product_attribute === null) {
            $id_product_attribute = Product::getDefaultAttribute($id_product);
        }
        $cache_id = (int)$id_product . '-' . (int)$id_shop . '-' . (int)$id_currency . '-' .
            (int)$id_country . '-' . $id_state . '-' . $zipcode . '-' . (int)$id_group .
            '-' . (int)$quantity . '-' . (int)$id_product_attribute . '-' . (int)$id_customization .
            '-' . (int)$with_ecotax . '-' . (int)$id_customer . '-' . (int)$use_group_reduction . '-' .
            (int)$id_cart . '-' . (int)$real_quantity .
            '-' . ($only_reduc ? '1' : '0') . '-' . ($use_reduc ? '1' : '0') . '-' . ($use_tax ? '1' : '0') . '-' .
            (int)$decimals;
        $specific_price = SpecificPrice::getSpecificPrice(
            (int)$id_product,
            $id_shop,
            $id_currency,
            $id_country,
            $id_group,
            $quantity,
            $id_product_attribute,
            $id_customer,
            $id_cart,
            $real_quantity
        );
        if (isset(self::$_prices[$cache_id])) {
        }
        $cache_id_2 = $id_product . '-' . $id_shop;
        if (!isset(self::$_pricesLevel2[$cache_id_2])) {
            $sql = new DbQuery();
            $sql->select('product_shop.`price`, product_shop.`ecotax`');
            $sql->from('product', 'p');
            $sql->innerJoin('product_shop', 'product_shop', '(product_shop.id_product=p.id_product
            AND product_shop.id_shop = ' . (int)$id_shop . ')');
            $sql->where('p.`id_product` = ' . (int)$id_product);
            if (Combination::isFeatureActive()) {
                $sql->select('IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute,
                product_attribute_shop.`price` AS attribute_price, product_attribute_shop.default_on');
                $sql->leftJoin(
                    'product_attribute_shop',
                    'product_attribute_shop',
                    '(product_attribute_shop.id_product = p.id_product AND product_attribute_shop.id_shop = ' .
                    (int)$id_shop . ')'
                );
            } else {
                $sql->select('0 as id_product_attribute');
            }
            $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            if (is_array($res) && count($res)) {
                foreach ($res as $row) {
                    $array_tmp = array(
                        'price' => $row['price'],
                        'ecotax' => $row['ecotax'],
                        'attribute_price' => (isset($row['attribute_price']) ? $row['attribute_price'] : null),
                    );
                    self::$_pricesLevel2[$cache_id_2][(int)$row['id_product_attribute']] = $array_tmp;
                    if (isset($row['default_on']) && $row['default_on'] == 1) {
                        self::$_pricesLevel2[$cache_id_2][0] = $array_tmp;
                    }
                }
            }
        }
        if (!isset(self::$_pricesLevel2[$cache_id_2][(int)$id_product_attribute])) {
            return;
        }
        $result = self::$_pricesLevel2[$cache_id_2][(int)$id_product_attribute];
        if (!$specific_price || $specific_price['price'] < 0) {
            $price = (float)$result['price'];
            if (Module::isInstalled('wkbundleproduct') && Module::isEnabled('wkbundleproduct')) {
                include_once _PS_MODULE_DIR_ . 'wkbundleproduct/classes/WkBundleProductRequiredClasses.php';
                $objBpHelper = new WkBundleProductHelper();
                if (isset(Context::getContext()->cookie->wk_id_customization)) {
                    $id_customization = Context::getContext()->cookie->wk_id_customization;
                }
                if ($price <= 0) {
                    $price = $objBpHelper->bundleProductPriceCalculation(
                        $id_product,
                        $price,
                        $use_tax,
                        $id_customization,
                        $id_cart
                    );
                }
            }
        } else {
            $price = (float)$specific_price['price'];
        }
        if (!$specific_price || !($specific_price['price'] >= 0 && $specific_price['id_currency'])) {
            $price = Tools::convertPrice($price, $id_currency);
            if (isset($specific_price['price']) && $specific_price['price'] >= 0) {
                $specific_price['price'] = $price;
            }
        }
        if (is_array($result) && (!$specific_price || !$specific_price['id_product_attribute'] ||
                $specific_price['price'] < 0)) {
            $attribute_price = Tools::convertPrice(
                $result['attribute_price'] !== null ? (float)$result['attribute_price'] : 0,
                $id_currency
            );
            if ($id_product_attribute !== false) {
                $price += $attribute_price;
            }
        }
        if ((int)$id_customization) {
            $price_2 = Customization::getCustomizationPrice($id_customization);
            if ($price_2 == 0) {
                $price += Tools::convertPrice(Customization::getCustomizationPrice($id_customization), $id_currency);
            } else {
                $price = Tools::convertPrice(Customization::getCustomizationPrice($id_customization), $id_currency);
            }
        }


        $address->id_country = $id_country;
        $address->id_state = $id_state;
        $address->postcode = $zipcode;
        $tax_manager = TaxManagerFactory::getManager(
            $address,
            Product::getIdTaxRulesGroupByIdProduct((int)$id_product, $context)
        );
        $product_tax_calculator = $tax_manager->getTaxCalculator();
        if ($use_tax) {
            $price = $product_tax_calculator->addTaxes($price);
        }
        if (($result['ecotax'] || isset($result['attribute_ecotax'])) && $with_ecotax) {
            $ecotax = $result['ecotax'];
            if (isset($result['attribute_ecotax']) && $result['attribute_ecotax'] > 0) {
                $ecotax = $result['attribute_ecotax'];
            }
            if ($id_currency) {
                $ecotax = Tools::convertPrice($ecotax, $id_currency);
            }
            if ($use_tax) {
                static $psEcotaxTaxRulesGroupId = null;
                if ($psEcotaxTaxRulesGroupId === null) {
                    $psEcotaxTaxRulesGroupId = (int)Configuration::get('PS_ECOTAX_TAX_RULES_GROUP_ID');
                }
                $tax_manager = TaxManagerFactory::getManager(
                    $address,
                    $psEcotaxTaxRulesGroupId
                );
                $ecotax_tax_calculator = $tax_manager->getTaxCalculator();
                $price += $ecotax_tax_calculator->addTaxes($ecotax);
            } else {
                $price += $ecotax;
            }
        }
        $specific_price_reduction = 0;
        if (($only_reduc || $use_reduc) && $specific_price) {
            if ($specific_price['reduction_type'] == 'amount') {
                $reduction_amount = $specific_price['reduction'];
                if (!$specific_price['id_currency']) {
                    $reduction_amount = Tools::convertPrice($reduction_amount, $id_currency);
                }
                $specific_price_reduction = $reduction_amount;
                if (!$use_tax && $specific_price['reduction_tax']) {
                    $specific_price_reduction = $product_tax_calculator->removeTaxes($specific_price_reduction);
                }
                if ($use_tax && !$specific_price['reduction_tax']) {
                    $specific_price_reduction = $product_tax_calculator->addTaxes($specific_price_reduction);
                }
            } else {
                $specific_price_reduction = $price * $specific_price['reduction'];
            }
        }
        if ($use_reduc) {
            $price -= $specific_price_reduction;
        }
        if ($use_group_reduction) {
            $reduction_from_category = GroupReduction::getValueForProduct($id_product, $id_group);
            if ($reduction_from_category !== false) {
                $group_reduction = $price * (float)$reduction_from_category;
            } else { // apply group reduction if there is no group reduction for this category
                $group_reduction = ((
                    $reduc = Group::getReductionByIdGroup($id_group)
                    ) != 0) ? ($price * $reduc / 100) : 0;
            }
            $price -= $group_reduction;
        }


        if (Module::isEnabled('currencyformat')) {
            $decimals = 2;
            $context = Context::getContext()->cloneContext();
            include_once(_PS_MODULE_DIR_ . 'currencyformat/classes/CurrencyformatConfiguration.php');
            $config = new CurrencyformatConfiguration();
            $id_shop = $context->shop->id;
            $config = $config->getConfigurations($id_shop, $id_currency);
            if (!empty($config)) {
                if ($config['only_exact']) {
                    $whole = floor($price);
                    $fraction = round($price - $whole, 2);
                    if (strval($fraction) == '0.00') {
                        $decimals = $config['decimals_number'];
                    }
                } else {
                    $decimals = $config['decimals_number'];
                }
            }
        }

        // $isBundleProduct = false;
        // if (Module::isInstalled('wkbundleproduct') && Module::isEnabled('wkbundleproduct')) {
        //     include_once _PS_MODULE_DIR_ . 'wkbundleproduct/classes/WkBundleProductRequiredClasses.php';
        //     $objBundle = new WkBundle();
        //     if ($objBundle->isBundleProduct($id_product)) {
        //         $isBundleProduct = true;
        //     }
        // }
        // if ($isBundleProduct) {
        //     if (!Configuration::get('WK_BUNDLE_PRODUCT_DISABLE_GROUP_DISCOUNT')) {
        //         if ($use_group_reduction) {
        //             $reduction_from_category = GroupReduction::getValueForProduct($id_product, $id_group);
        //             if ($reduction_from_category !== false) {
        //                 $group_reduction = $price * (float) $reduction_from_category;
        //             } else { // apply group reduction if there is no group reduction for this category
        //                 $group_reduction = ((
        //                     $reduc = Group::getReductionByIdGroup($id_group)
        //                 ) != 0) ? ($price * $reduc / 100) : 0;
        //             }
        //             $price -= $group_reduction;
        //         }
        //     }
        // } else {
        //     if ($use_group_reduction) {
        //         $reduction_from_category = GroupReduction::getValueForProduct($id_product, $id_group);
        //         if ($reduction_from_category !== false) {
        //             $group_reduction = $price * (float) $reduction_from_category;
        //         } else { // apply group reduction if there is no group reduction for this category
        //             $group_reduction = ((
        //                 $reduc = Group::getReductionByIdGroup($id_group)
        //             ) != 0) ? ($price * $reduc / 100) : 0;
        //         }
        //         $price -= $group_reduction;
        //     }
        // }


        if ($only_reduc) {
            return Tools::ps_round($specific_price_reduction, $decimals);
        }
        $price = Tools::ps_round($price, $decimals);
        if ($price < 0) {
            $price = 0;
        }
        self::$_prices[$cache_id] = $price;
        return self::$_prices[$cache_id];
    }

    public static function getProductProperties($id_lang, $row, Context $context = null)
    {
        Hook::exec('actionGetProductPropertiesBefore', [
            'id_lang' => $id_lang,
            'product' => &$row,
            'context' => $context,
        ]);

        if (!$row['id_product']) {
            return false;
        }

        if ($context == null) {
            $context = Context::getContext();
        }

        $id_product_attribute = $row['id_product_attribute'] = (!empty($row['id_product_attribute']) ? (int)$row['id_product_attribute'] : null);

        $row['allow_oosp'] = Product::isAvailableWhenOutOfStock($row['out_of_stock']);
        if (
            Combination::isFeatureActive() &&
            $id_product_attribute === null &&
            (
                (isset($row['cache_default_attribute']) && ($ipa_default = $row['cache_default_attribute']) !== null)
                || ($ipa_default = Product::getDefaultAttribute($row['id_product'], (int)!$row['allow_oosp']))
            )
        ) {
            $id_product_attribute = $row['id_product_attribute'] = $ipa_default;
        }
        if (!Combination::isFeatureActive() || !isset($row['id_product_attribute'])) {
            $id_product_attribute = $row['id_product_attribute'] = 0;
        }

        $usetax = !Tax::excludeTaxeOption();

        $cache_key = $row['id_product'] . '-' . $id_product_attribute . '-' . $id_lang . '-' . (int)$usetax;
        if (isset($row['id_product_pack'])) {
            $cache_key .= '-pack' . $row['id_product_pack'];
        }

        if (!isset($row['cover_image_id'])) {
            $cover = static::getCover($row['id_product']);
            if (isset($cover['id_image'])) {
                $row['cover_image_id'] = $cover['id_image'];
            }
        }

        if (isset($row['cover_image_id'])) {
            $cache_key .= '-cover' . (int)$row['cover_image_id'];
        }

        if (isset(self::$productPropertiesCache[$cache_key])) {
            return array_merge($row, self::$productPropertiesCache[$cache_key]);
        }

        $row['category'] = Category::getLinkRewrite((int)$row['id_category_default'], (int)$id_lang);
        $row['category_name'] = Db::getInstance()->getValue('SELECT name FROM ' . _DB_PREFIX_ . 'category_lang WHERE id_shop = ' . (int)$context->shop->id . ' AND id_lang = ' . (int)$id_lang . ' AND id_category = ' . (int)$row['id_category_default']);
        $row['link'] = $context->link->getProductLink((int)$row['id_product'], $row['link_rewrite'], $row['category'], $row['ean13']);

        if (empty($row['manufacturer_name'])) {
            $row['manufacturer_name'] = null;

            if (!empty($row['id_manufacturer'])) {
                $manufacturerName = Manufacturer::getNameById((int)$row['id_manufacturer']);
                if (!empty($manufacturerName)) {
                    $row['manufacturer_name'] = $manufacturerName;
                }
            }
        }

        $row['attribute_price'] = 0;
        if ($id_product_attribute) {
            $row['attribute_price'] = (float)Combination::getPrice($id_product_attribute);
        }

        if (isset($row['quantity_wanted'])) {
            $quantity = max((int)$row['minimal_quantity'], (int)$row['quantity_wanted']);
        } elseif (isset($row['cart_quantity'])) {
            $quantity = max((int)$row['minimal_quantity'], (int)$row['cart_quantity']);
        } else {
            $quantity = (int)$row['minimal_quantity'];
        }

        $row['price_tax_exc'] = $priceTaxExcluded = Product::getPriceStatic(
            (int)$row['id_product'],
            false,
            $id_product_attribute,
            (self::$_taxCalculationMethod == PS_TAX_EXC ? Context::getContext()->getComputingPrecision() : 6),
            null,
            false,
            true,
            $quantity
        );

        if (self::$_taxCalculationMethod == PS_TAX_EXC) {
            $row['price_tax_exc'] = Tools::ps_round($priceTaxExcluded, Context::getContext()->getComputingPrecision());
            $row['price'] = $priceTaxIncluded = Product::getPriceStatic(
                (int)$row['id_product'],
                true,
                $id_product_attribute,
                6,
                null,
                false,
                true,
                $quantity
            );
            $row['price_without_reduction'] = $row['price_without_reduction_without_tax'] = Product::getPriceStatic(
                (int)$row['id_product'],
                false,
                $id_product_attribute,
                2,
                null,
                false,
                false,
                $quantity
            );
        } else {
            $priceTaxIncluded = Product::getPriceStatic(
                (int)$row['id_product'],
                true,
                $id_product_attribute,
                6,
                null,
                false,
                true,
                $quantity
            );
            $row['price'] = Tools::ps_round($priceTaxIncluded, Context::getContext()->getComputingPrecision());
            $row['price_without_reduction'] = Product::getPriceStatic(
                (int)$row['id_product'],
                true,
                $id_product_attribute,
                6,
                null,
                false,
                false,
                $quantity
            );
            $row['price_without_reduction_without_tax'] = Product::getPriceStatic(
                (int)$row['id_product'],
                false,
                $id_product_attribute,
                6,
                null,
                false,
                false,
                $quantity
            );
        }

        $row['reduction'] = Product::getPriceStatic(
            (int)$row['id_product'],
            (bool)$usetax,
            $id_product_attribute,
            6,
            null,
            true,
            true,
            $quantity,
            true,
            null,
            null,
            null,
            $specific_prices
        );

        $row['reduction_without_tax'] = Product::getPriceStatic(
            (int)$row['id_product'],
            false,
            $id_product_attribute,
            6,
            null,
            true,
            true,
            $quantity,
            true,
            null,
            null,
            null,
            $specific_prices
        );

        $priceTaxIncluded2 = Product::getPriceStatic(
            (int)$row['id_product'],
            true,
            $id_product_attribute,
            6,
            null,
            false,
            true,
            2,
            false,
            null,
            null,
            null,
            $specific_prices_2
        );
        $price2 =  $priceTaxIncluded2 * 2 - $priceTaxIncluded;
        $row['price2'] = Tools::ps_round($price2, Context::getContext()->getComputingPrecision());
        $row['specific_prices'] = $specific_prices;
        $row['multiple_price_quantity'] = ((int)$specific_prices_2["from_quantity"] > 1) ? true : false;

        /* Get quantity of the base product.
         * For products without combinations - self explanatory.
         * For products with combinations - this value is a SUM of quantities of all combinations.
         * You have 2 black shirts + 2 white shirts = $quantity 4.
         */
        $row['quantity'] = Product::getQuantity(
            (int)$row['id_product'],
            0,
            isset($row['cache_is_pack']) ? $row['cache_is_pack'] : null,
            $context->cart
        );

        $row['quantity_all_versions'] = $row['quantity'];

        if ($row['id_product_attribute']) {
            $row['quantity'] = Product::getQuantity(
                (int)$row['id_product'],
                $id_product_attribute,
                isset($row['cache_is_pack']) ? $row['cache_is_pack'] : null,
                $context->cart
            );

            $row['available_date'] = Product::getAvailableDate(
                (int)$row['id_product'],
                $id_product_attribute
            );
        }

        $row['id_image'] = Product::defineProductImage($row, $id_lang);
        $row['features'] = Product::getFrontFeaturesStatic(Context::getContext()->language->id, $row['id_product']);

        $row['attachments'] = [];
        if (!isset($row['cache_has_attachments']) || $row['cache_has_attachments']) {
            $row['attachments'] = Product::getAttachmentsStatic((int)$id_lang, $row['id_product']);
        }


        $row['iva'] = Product::getEventIva($row);

        $row['virtual'] = ((!isset($row['is_virtual']) || $row['is_virtual']) ? 1 : 0);
        $row['defaul_on'] = Product::getDefaultFrom($row);
        $row['request_price'] = Product::getRequestPrice($row);
        $row['phone_sale'] = Product::getPhoneSale($row);

        $row['pack'] = (!isset($row['cache_is_pack']) ? Pack::isPack($row['id_product']) : (int)$row['cache_is_pack']);
        $row['packItems'] = $row['pack'] ? Pack::getItemTable($row['id_product'], $id_lang) : [];
        $row['nopackprice'] = $row['pack'] ? Pack::noPackPrice($row['id_product']) : 0;
        $row['flag'] = Product::getProductFlags($row);
        $row['fitting'] = Product::detalleFitting($row);
        $row['view'] = Product::featuresView($row);
        $row['show_price_from'] = Product::featuresFrom($row);
        $row['film'] = Product::featuresFilm($row);
        $row['blocked'] = Product::isBlocked($row['id_product']);
        $row['isPing'] = Product::isPing($row);
        $row['congratulation'] = Product::isCongratulation($row);
        $row['hour'] = Product::getProductWorkHourAndShippingInfo($row);
        // $row['hour'] = 0;//Quitar hasta que termine el inventario
        $row['fittings'] = Product::esFitting($row);
        $row['card'] = Product::hasFeature($row['id_product'],"Tarjeta regalo");
        $row['armero'] = Product::hasFeature($row['id_product'],"Armero");
        $row['cartucho'] = Product::hasFeature($row['id_product'],"Cartuchos");
        $row['armas'] = Product::hasFeature($row['id_product'],"Armas");
        $row['licencia'] = Product::hasFeature($row['id_product'],"Licencia");
        $row['armas_balines'] = Product::isArmasBalines($row);
        $row['lottery'] = Product::hasFeature($row['id_product'],"Lotería");
        $row['anadir_gestion_manual'] = Product::anadirGestionManual($row['id_product']);
        if ($row['pack'] && !Pack::isInStock($row['id_product'], $quantity, $context->cart)) {
            $row['quantity'] = 0;
        }
        $row['is_new'] = Product::isNewProduct($row['id_product']);
        $row['segunda_mano'] = in_array('SEGUNDA MANO', array_column($row['flag'], 'type'))??true;

        $row['customization_required'] = false;

        $row['customization_required'] = false;

        if (Module::isEnabled('idxrcustomproduct')) {
            $module = Module::getInstanceByName('idxrcustomproduct');

            // Obtener el id_cart apropiado según el contexto
            $id_cart = false;
            if (isset($context->cart) && $context->cart->id) {
                $id_cart = (int)$context->cart->id;
            } elseif (isset($row['id_cart']) && $row['id_cart']) {
                // Si el producto viene de un pedido/carrito, usar ese id_cart
                $id_cart = (int)$row['id_cart'];
            }

            $row['custom'] = Product::featureCustom($row);
            $product_extra = $module->getExtraByProduct((int)$row['id_product'], $id_cart);

            if ($product_extra !== null) {
                $row['product_customization'] = $product_extra;
                $row['is_customized'] = true;

                // Reemplazar description y description_short con las del producto original (padre)
                // Las opciones personalizadas quedan en product_customization
                if (isset($product_extra['original_description'])) {
                    $row['description'] = $product_extra['original_description'];
                }
                if (isset($product_extra['original_description_short'])) {
                    $row['description_short'] = $product_extra['original_description_short'];
                }
            } else {
                $row['is_customized'] = false;
            }
        }


        if (!isset($row['attributes'])) {
            $attributes = Product::getAttributesParams($row['id_product'], $row['id_product_attribute']);

            foreach ($attributes as $attribute) {
                $row['attributes'][$attribute['id_attribute_group']] = $attribute;
            }
        }

        $row = Product::getTaxesInformations($row, $context);

        $row['ecotax_rate'] = (float)Tax::getProductEcotaxRate($context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});

        // if (Module::isInstalled('wkbundleproduct') && Module::isEnabled('wkbundleproduct')) {
        //     include_once _PS_MODULE_DIR_ . 'wkbundleproduct/classes/WkBundleProductRequiredClasses.php';
        //     $objSubProduct = new WkBundleSubProduct();
        //     $forBundleOnly = $objSubProduct->getAllAvailableProduct();
        //     if ($forBundleOnly) {
        //         if (in_array($row['id_product'], $forBundleOnly)) {
        //             $row['available_for_order'] = 0;
        //         }
        //     }
        //     $objBundle = new WkBundle();
        //     if ($objBundle->isBundleProduct($row['id_product'])) {
        //         $objBpHelper = new WkBundleProductHelper();
        //         $price = 0;
        //         $price = $objBpHelper->bundleProductPriceCalculation($row['id_product'], $price);
        //         if ($price <= 0) {
        //             $row['available_for_order'] = 0;
        //             $row['show_price'] = 0;
        //         }
        //     }
        //     if (Configuration::get('WK_BUNDLE_PRODUCT_RESERVED_QTY')) {
        //         if ($objSubProduct->getAllAvailableProduct(0)) {
        //             if (in_array($row['id_product'], $objSubProduct->getAllAvailableProduct(0))) {
        //                 $qty = $objSubProduct->getProductMaximumQuantity(
        //                     $row['id_product'],
        //                     $row['id_product_attribute']
        //                 );
        //                 if ($qty) {
        //                     $row['quantity'] = $qty;
        //                 } else {
        //                     $row['quantity'] = 0;
        //                 }
        //             }
        //         }
        //     }
        // }

        return $row;

    }

    function getCategoryParent($idCategory = null, $idLang = 1)
    {
        $sql = '
        SELECT cl.id_category, cl.name
        FROM '._DB_PREFIX_.'category_lang cl
        WHERE cl.id_lang = '.(int)$idLang.'
          AND cl.link_rewrite = (
              SELECT SUBSTRING_INDEX(cl2.category_url_path, "/", 1)
              FROM '._DB_PREFIX_.'category_lang cl2
              WHERE cl2.id_category = '.(int)$idCategory.'
                AND cl2.id_lang = '.(int)$idLang.'
          )
        LIMIT 1
    ';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
    }

    function getTopParentCategory($idCategory, $idLang = null, $idShop = null)
    {

        if (!$idCategory) {
            return null;
        }

        $lang = $idLang ?: (int)Context::getContext()->language->id;
        $shop = $idShop ?: (int)Context::getContext()->shop->id;

        $cat = new Category((int)$idCategory, $lang, $shop);
        if (!Validate::isLoadedObject($cat)) {
            return null;
        }

        // En PS normalmente el root es 1 (Root) y el "Home" suele ser 2.
        // Ajusta estos IDs si en tu tienda difieren.
        $rootIds = [ (int)Configuration::get('PS_ROOT_CATEGORY'), (int)Configuration::get('PS_HOME_CATEGORY') ];

        if (in_array((int)$cat->id_parent, $rootIds, true) || in_array((int)$cat->id, $rootIds, true)) {
            return $cat;
        }

        // Llamada recursiva subiendo un nivel
        return getTopParentCategory((int)$cat->id_parent, $lang, $shop);
    }

    public static function isNewProduct($id_product = null){
        $nbDaysNewProduct = Configuration::get('PS_NB_DAYS_NEW_PRODUCT');
        if (!Validate::isUnsignedInt($nbDaysNewProduct)) {
            $nbDaysNewProduct = 20;
        }

        $query = 'SELECT COUNT(p.id_product)
            FROM `' . _DB_PREFIX_ . 'product` p
            WHERE p.id_product = ' . (int) $id_product . '
            AND p.date_add > DATE_SUB(CURDATE(), INTERVAL '.$nbDaysNewProduct.' DAY)'  ;

        return (bool) Db::getInstance()->getValue($query, false);

    }


    public static function getProductWorkHourAndShippingInfo($product)
    {
        // dump(Context::getContext());die();

        $context = Context::getContext();

        if ($context->controller->controller_type != "admin") {
            $product_id = isset($product) ? $product['id_product'] : 0;
            $has_stock = Product::hayStockPocomaco($product_id, $product['id_product_attribute']);

            return $has_stock
                && (
                in_array(strtolower($context->country->iso_code), ["es", "pt", "fr"]));
        }
    }


    public static function getDefaultFrom($product)
    {

        $id_producto_attribute_default = (int)Product::getDefaultAttribute($product['id_product']);
        $show_price_from = (int)Product::featuresFrom($product);

        return (($product['id_product_attribute'] == $id_producto_attribute_default) && $show_price_from) ? true : false;
    }


    public static function getRequestPrice(array $product): ?string
    {
        return self::getFeatureById($product, 'BAN_PRODUCT_FEATURE_REQUEST_PRICE');
    }

    public static function getPhoneSale(array $product): ?string
    {

        return self::getFeatureById($product, 'BAN_PRODUCT_FEATURE_ID_PHONE_SALE');
    }

    public static function hasFeature($id_product = null, $feature_value)
    {
        $hasFeature = false;
        $id_feature = 5; //Tipo producto

        $sql = 'SELECT pfv.value
            FROM ' . _DB_PREFIX_ . 'feature_product pf
            INNER JOIN ' . _DB_PREFIX_ . 'feature_value_lang pfv ON pfv.id_feature_value = pf.id_feature_value
            WHERE pf.id_product = ' . (int)$id_product . '
            AND pf.id_feature = ' . (int)$id_feature . '
            AND pfv.value = "' . pSQL($feature_value) . '" AND pfv.id_lang = ' . (int)Context::getContext()->language->id;

        $result = Db::getInstance()->executeS($sql);

        if ($result) {
            $hasFeature = true;
        }
        return $hasFeature;

    }

    private static function getFeatureById(array $product, string $featureKey): ?string
    {
        $allFeatures = $product['features'];
        $featureId = (int)Configuration::get($featureKey);


        $filteredFeatures = array_filter($allFeatures, function ($feature) use ($featureId) {
            return isset($feature['id_feature']) && $feature['id_feature'] == $featureId;
        });

        $foundFeature = reset($filteredFeatures);


        if (!$foundFeature) {
            return null;
        }

        return $foundFeature['value'] ?? null;
    }

    public static function alsernetNewVisibilidad($id_articulo)
    {

        $datos = Db::getInstance()->getRow("SELECT
                                                *,
                                                0 AS id_product_attribute,
                                                1 AS es_simple
                                            FROM
                                                aalv_combinacionunica_import aci
                                            WHERE
                                                id_articulo =" . $id_articulo);

        if (!$datos) {
            $datos = Db::getInstance()->getRow("SELECT
                                                    apa.id_product,
                                                    0 AS es_simple,
                                                    aci.*
                                                FROM
                                                    aalv_combinaciones_import aci
                                                    LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                                WHERE
                                                    aci.id_articulo =" . $id_articulo);
        }
        $lote = Db::getInstance()->getValue("SELECT id_ps_product FROM aalv_alsernet_lotes_copia awbp WHERE active = 0 AND id_ps_product = " . $datos['id_product']);

        if ($lote) {
            return 1;
        }
        if(!isset($datos['id_product'])){
            return 1;
        }
        $product = new Product($datos['id_product']);

        if ((int)$datos['estado_gestion'] != 0) {

            // Validamos si tiene la etiqueta OCULTO WEB
            $array = explode(", ", $datos['etiqueta']);

            if (in_array("OCULTO WEB", $array)) {
                // Si tiene la etiqueta, forzamos el Stock a 0
                StockAvailable::setQuantity($datos['id_product'], $datos['id_product_attribute'], 0, 1, false);

                if ($datos['es_simple']) {
                    $product->visibility = 'none';
                }
            } else {
                $repositorio_stock = Db::getInstance()->getRow("SELECT * FROM aalv_repositorio_stock ars WHERE id_product_attribute = " . $datos['id_product_attribute'] . " AND id_product = " . $datos['id_product']);

                if (!$repositorio_stock) {
                    $repositorio_stock = ['quantity' => 0];
                }

                $stock = self::controlStock($datos['id_product'], $datos['id_product_attribute'], $repositorio_stock['quantity']);

                if ($stock < 0) {
                    $stock = 0;
                }

                // Si no se ecuenta, sigue todo normal
                StockAvailable::setQuantity($datos['id_product'], $datos['id_product_attribute'], $stock, 1, false);

                if ($datos['es_simple']) {
                    $product->visibility = 'both';
                }
            }
            if ($datos['es_simple']) {
                $product->active = 1;
            }
        } else {
            // Entonces el producto esta extinto en gestion

            //Actualizamos su Stock a 0, lo forzamos
            StockAvailable::setQuantity($datos['id_product'], $datos['id_product_attribute'], 0, 1, false);

            if ($datos['es_simple']) {
                $product->visibility = 'none';
                $product->active = 0;
            }
        }
        if ($datos['es_simple']) {
            $product->save();
        } else {
            // buscamos que el stock de todas las combinaciones esten a 0
            $cero_stock = Db::getInstance()->getRow("SELECT sum(quantity) AS quantity FROM aalv_stock_available asa WHERE id_product = " . $datos['id_product'] . " AND id_product_attribute != 0");

            if ((int)$cero_stock['quantity'] == 0) {

                $product->visibility = 'none';
                $product->active = 1;

            }
            if ((int)$cero_stock['quantity'] > 0) {

                $product->active = 1;
                $product->visibility = 'both';
            }

            $desactivar = Db::getInstance()->executeS("SELECT DISTINCT apa.id_product FROM aalv_combinaciones_import aci
            JOIN aalv_product_attribute apa ON aci.id_product_attribute = apa.id_product_attribute
            where apa.id_product = ".$datos['id_product']."
            GROUP BY apa.id_product
            HAVING sum(aci.estado_gestion) = 0");

            if(count($desactivar) > 0){
                $product->visibility = 'none';
                $product->active = 0;
            }

            $product->save();
        }
    }

    /**
     * @param $product
     * @return void
     */
    public static function getSportByDefaultCategory($row)
    {
        $default_category_id = $row->id_category_default;

        $sports_ids = [3, 4, 5, 6, 7, 8, 9, 10, 11];


        if ($default_category_id != 2) {
            $default_category = new Category($default_category_id);
            $categories = $default_category->getParentsCategories(1);
            foreach ($categories as $category) {
                if (in_array($category['id_category'], $sports_ids))
                    return $category;
            }
        }


    }

    public static function getFrontFeaturesStatic($id_lang, $id_product)
    {
        if (!Feature::isFeatureActive()) {
            return [];
        }
        if (!array_key_exists($id_product . '-' . $id_lang, self::$_frontFeaturesCache)) {
            self::$_frontFeaturesCache[$id_product . '-' . $id_lang] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
                SELECT name, value, pf.id_feature, f.position , pf.id_feature_value
                FROM ' . _DB_PREFIX_ . 'feature_product pf
                LEFT JOIN ' . _DB_PREFIX_ . 'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = ' . (int) $id_lang . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = ' . (int) $id_lang . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'feature f ON (f.id_feature = pf.id_feature AND fl.id_lang = ' . (int) $id_lang . ')
                ' . Shop::addSqlAssociation('feature', 'f') . '
                WHERE pf.id_product = ' . (int) $id_product . '
                ORDER BY f.position ASC'
            );
        }

        return self::$_frontFeaturesCache[$id_product . '-' . $id_lang];
    }


    public static function detalleFitting($row){
        $product_fitting = [];
        $product_fitt['attributes_array_custom'] = Product::getAttributesArray((int) $row['id_product'], (int) $row['id_product_attribute'], Context::getContext()->language->id, Context::getContext()->shop->id);
        if ($product_fitt['attributes_array_custom'] && count($product_fitt['attributes_array_custom']) >= 2) {
            $product_fitting['fitting_day'] = $product_fitt['attributes_array_custom'][0]['attribute_name'];
            $product_fitting['fitting_hour'] = $product_fitt['attributes_array_custom'][1]['attribute_name'];
        }
        if (Configuration::get('BAN_PRODUCT_FEATURE_ID_LOCATION') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_ID_LOCATION'))) {
            $id_feature_location = (int) Configuration::get('BAN_PRODUCT_FEATURE_ID_LOCATION');
            $features = Product::getFrontFeaturesStatic(1, $row['id_product']);
            if ($features) {
                foreach ($features as $feature) {
                    if ((int) $feature['id_feature'] == $id_feature_location) {
                        $product_fitting['fitting_location'] = $feature['value'];
                    }
                }
            }
        }
        return $product_fitting;
    }

    public function getExtraByProduct($id_product, $id_cart = false)
    {
        if (!$id_cart) {
            $id_cart = (int) $this->context->cart->id;
        }

        // Verificar si es un producto customizado (clon)
        $sql = 'SELECT 1 FROM `'._DB_PREFIX_.'idxrcustomproduct_clones`
            WHERE `id_clon` = '.(int)$id_product;

        if (!Db::getInstance()->getValue($sql)) {
            return null; // No es customizado, retorna null
        }

        // Inicializar datos del producto
        $data = array();
        $data['id_product'] = $id_product;

        // 1. Obtener imagen del producto
        $product_img = Product::getCover($id_product);
        $data['product_image_id'] = $product_img ? $product_img['id_image'] : false;
        $data['product_image_url'] = $this->context->link->getImageLink(
            Product::getProductName($id_product),
            $data['product_image_id'],
            ImageType::getFormattedName('home')
        );

        // 2. Obtener producto original (base)
        $data['original_product'] = $this->getProductoOriginal($id_product);
        if (!$data['original_product']) {
            return null;
        }

        $parent_product = new Product($data['original_product']);
        $data['original_url'] = $this->context->link->getProductLink($parent_product);

        // 3. Obtener opciones de customización
        $extra_options = IdxCustomerExtra::getExtraByCart($id_cart, $id_product);

        if (!empty($extra_options)) {
            $front_token = Configuration::get(Tools::strtoupper($this->name .'_TOKEN'));
            $file_controller = $this->context->link->getModuleLink(
                    $this->name,
                    'file',
                    array('token' => $front_token, 'ajax' => true)
                ).'&key=';

            // Renderizar customizaciones
            $this->smarty->assign(array(
                'clean' => false,
                'file_controller' => $file_controller,
                'product' => array('id_product' => $id_product),
                'extra_options' => $extra_options,
            ));

            $data['customization'] = html_entity_decode(
                $this->display(__FILE__, 'views/templates/hook/customization.tpl')
            );
        }

        // 4. Botón de edición
        $edit_url = $this->context->link->getProductLink(
            $parent_product,
            null, null, null, null, null, null,
            false, false, false,
            array('icp_edit' => $id_product)
        );
        $this->smarty->assign(array('edit_link' => $edit_url));
        $data['edit_button'] = $this->display(__FILE__, 'views/templates/hook/editbutton.tpl');

        return $data;
    }

    /**
     * FUNCIÓN REFACTORIZADA: Obtener extras del contexto actual (ahora usa getExtraByProduct)
     * @param bool $clean Limpiar notas
     * @param int|bool $id_cart ID del carrito opcional
     * @return array Array de productos customizados
     */
    public function getExtraByContext($clean = false, $id_cart = false)
    {
        if (!$id_cart) {
            $id_cart = (int) $this->context->cart->id;
        }

        $cart = new Cart($id_cart);
        $products = $cart->getProducts();
        $extra_info = array();

        foreach ($products as $product) {
            // Usar la nueva función para cada producto
            $product_extra = $this->getExtraByProduct((int)$product['id_product'], $id_cart);

            if ($product_extra !== null) {
                $extra_info[] = $product_extra;
            }
        }

        return $extra_info;
    }

    public static function featureCustom($product)
    {

        if (isset($product['features'])) {
            $allFeatures = $product['features'];

            $viewFound = array_filter($allFeatures, function ($feature) {
                return isset($feature['id_feature']) && $feature['id_feature'] == 25;
            });

            $foundFeature = !empty($viewFound) ? reset($viewFound) : null;
            return $foundFeature ? $foundFeature['value'] : 'default';

        } else {
            return false;
        }

    }


}

