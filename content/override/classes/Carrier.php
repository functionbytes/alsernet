<?php

/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author ADDIS Network <info@addis.es>
*  @copyright  2021-2021 ADDIS Network
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
class Carrier extends CarrierCore
{

    /** @var string Name to be shown in checkout with translations */
    public $show_name;


    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'carrier',
        'primary' => 'id_carrier',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => [
            /* Classic fields */
            'id_reference' => ['type' => self::TYPE_INT],
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isCarrierName', 'required' => true, 'size' => 64],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'is_free' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'url' => ['type' => self::TYPE_STRING, 'validate' => 'isAbsoluteUrl'],
            'shipping_handling' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'shipping_external' => ['type' => self::TYPE_BOOL],
            'range_behavior' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'shipping_method' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'max_width' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'max_height' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'max_depth' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'max_weight' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'grade' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'size' => 1],
            'external_module_name' => ['type' => self::TYPE_STRING, 'size' => 64],
            'is_module' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'need_range' => ['type' => self::TYPE_BOOL],
            'position' => ['type' => self::TYPE_INT],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            /* Lang fields */
            'delay' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 512],
            'show_name' => ['type' => self::TYPE_STRING, 'lang' => true, 'size' => 100],
        ],
    ];

    public static function getCarriers($id_lang, $active = false, $delete = false, $id_zone = false, $ids_group = null, $modules_filters = self::PS_CARRIERS_ONLY)
    {
        // Filter by groups and no groups => return empty array
        if ($ids_group && (!is_array($ids_group) || !count($ids_group))) {
            return [];
        }

        $sql = '
		SELECT c.*, cl.delay,cl.show_name
		FROM `' . _DB_PREFIX_ . 'carrier` c
		LEFT JOIN `' . _DB_PREFIX_ . 'carrier_lang` cl ON (c.`id_carrier` = cl.`id_carrier` AND cl.`id_lang` = ' . (int)$id_lang . Shop::addSqlRestrictionOnLang('cl') . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'carrier_zone` cz ON (cz.`id_carrier` = c.`id_carrier`)' .
            ($id_zone ? 'LEFT JOIN `' . _DB_PREFIX_ . 'zone` z ON (z.`id_zone` = ' . (int)$id_zone . ')' : '') . '
		' . Shop::addSqlAssociation('carrier', 'c') . '
		WHERE c.`deleted` = ' . ($delete ? '1' : '0');
        if ($active) {
            $sql .= ' AND c.`active` = 1 ';
        }
        if ($id_zone) {
            $sql .= ' AND cz.`id_zone` = ' . (int)$id_zone . ' AND z.`active` = 1 ';
        }
        if ($ids_group) {
            $sql .= ' AND EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'carrier_group
									WHERE ' . _DB_PREFIX_ . 'carrier_group.id_carrier = c.id_carrier
									AND id_group IN (' . implode(',', array_map('intval', $ids_group)) . ')) ';
        }

        switch ($modules_filters) {
            case 1:
                $sql .= ' AND c.is_module = 0 ';

                break;
            case 2:
                $sql .= ' AND c.is_module = 1 ';

                break;
            case 3:
                $sql .= ' AND c.is_module = 1 AND c.need_range = 1 ';

                break;
            case 4:
                $sql .= ' AND (c.is_module = 0 OR c.need_range = 1) ';

                break;
        }
        $sql .= ' GROUP BY c.`id_carrier` ORDER BY c.`position` ASC';

        $cache_id = 'Carrier::getCarriers_' . md5($sql);
        if (!Cache::isStored($cache_id)) {
            $carriers = Db::getInstance()->executeS($sql);
            Cache::store($cache_id, $carriers);
        } else {
            $carriers = Cache::retrieve($cache_id);
        }

        foreach ($carriers as $key => $carrier) {
            if ($carrier['name'] == '0') {
                $carriers[$key]['name'] = Carrier::getCarrierNameFromShopName();
            }
        }

        return $carriers;
    }

    /*
    * module: alvarezcarrierandpaymentlock
    * date: 2022-05-20 09:14:00
    * version: 1.0.0
    */
    public static function getAvailableCarrierList(Product $product, $id_warehouse, $id_address_delivery = null, $id_shop = null, $cart = null, &$error = [])
    {
        $carrier_list = parent::getAvailableCarrierList($product, $id_warehouse, $id_address_delivery, $id_shop, $cart, $error);
        if (Module::isEnabled('alvarezcarrierandpaymentlock')) {
            require_once _PS_MODULE_DIR_ . 'alvarezcarrierandpaymentlock/classes/CarrierPaymentLock.php';
            $carriers = [];
            foreach ($carrier_list as $carrier) {
                $sql = 'SELECT c.`id_carrier`, c.`id_reference` 
                        FROM `' . _DB_PREFIX_ . 'carrier` c 
                        WHERE c.id_carrier=' . (int)$carrier;
                $carrier_data = DB::getInstance()->getRow($sql);
                if ($carrier_data) {
                    $carriers[] = $carrier_data;
                }
            }
            if (count($carriers)) {
                foreach ($carriers as $key => $carrier) {
                    $restrictions = CarrierPaymentLock::getRestrictionsByCarrierReference($carrier['id_reference']);
                    if ($restrictions) {
                        foreach ($restrictions as $restriction) {
                            $products = Context::getContext()->cart->getProducts();
                            foreach ($products as $product) {
                                $modelo_erp = false;
                                $category = false;
                                $feature_value = false;
                                $familia = false;
                                $subfamilia = false;
                                $grupo = false;
                                if (!empty($restriction['id_model_erp']) && $restriction['id_model_erp'] != '0' && is_numeric($restriction['id_model_erp'])) {
                                    $sql = 'SELECT pi.* 
                                            FROM `' . _DB_PREFIX_ . 'product_import` pi 
                                            WHERE pi.`id_product`=' . (int)$product['id_product'] . ' AND pi.`id_modelo`=' . (int)$restriction['id_model_erp'];
                                    if (DB::getInstance()->getRow($sql)) {
                                        $modelo_erp = true;
                                    }
                                } else {
                                    $modelo_erp = true;
                                }
                                if (!empty($restriction['id_category']) && $restriction['id_category'] != '0' && is_numeric($restriction['id_category'])) {
                                    $sql = 'SELECT cp.* 
                                            FROM `' . _DB_PREFIX_ . 'category_product` cp 
                                            WHERE cp.`id_product`=' . (int)$product['id_product'] . ' AND cp.`id_category`=' . (int)$restriction['id_category'];
                                    if (DB::getInstance()->getRow($sql)) {
                                        $category = true;
                                    }
                                } else {
                                    $category = true;
                                }
                                if (!empty($restriction['id_feature_value']) && $restriction['id_feature_value'] != '0' && is_numeric($restriction['id_feature_value'])) {
                                    $sql = 'SELECT fp.*
                                            FROM `' . _DB_PREFIX_ . 'feature_product` fp 
                                            WHERE fp.`id_product`=' . (int)$product['id_product'] . ' AND fp.`id_feature_value`=' . (int)$restriction['id_feature_value'];
                                    if (DB::getInstance()->getRow($sql)) {
                                        $feature_value = true;
                                    }
                                } else {
                                    $feature_value = true;
                                }
                                if (!empty($restriction['id_familia']) && $restriction['id_familia'] != '0' && is_numeric($restriction['id_familia'])) {
                                    $sql = 'SELECT ci.*
                                            FROM `' . _DB_PREFIX_ . 'combinacionunica_import` ci 
                                            WHERE ci.`id_product`=' . (int)$product['id_product'] . ' AND ci.`familia`=' . (int)$restriction['id_familia'];

                                    if ((int)$product['id_product_attribute']) {
                                        $sql = 'SELECT ci.*
                                                FROM `' . _DB_PREFIX_ . 'combinaciones_import` ci 
                                                WHERE ci.`id_product_attribute`=' . (int)$product['id_product_attribute'] . ' AND ci.`familia`=' . (int)$restriction['id_familia'];
                                    }

                                    if (DB::getInstance()->getRow($sql)) {
                                        $familia = true;
                                    }
                                } else {
                                    $familia = true;
                                }
                                if (!empty($restriction['id_subfamilia']) && $restriction['id_subfamilia'] != '0' && is_numeric($restriction['id_subfamilia'])) {
                                    $sql = 'SELECT ci.*
                                            FROM `' . _DB_PREFIX_ . 'combinacionunica_import` ci 
                                            WHERE ci.`id_product`=' . (int)$product['id_product'] . ' AND ci.`subfamilia`=' . (int)$restriction['id_subfamilia'];

                                    if ((int)$product['id_product_attribute']) {
                                        $sql = 'SELECT ci.*
                                                FROM `' . _DB_PREFIX_ . 'combinaciones_import` ci 
                                                WHERE ci.`id_product_attribute`=' . (int)$product['id_product_attribute'] . ' AND ci.`subfamilia`=' . (int)$restriction['id_subfamilia'];
                                    }

                                    if (DB::getInstance()->getRow($sql)) {
                                        $subfamilia = true;
                                    }
                                } else {
                                    $subfamilia = true;
                                }
                                if (!empty($restriction['id_grupo']) && $restriction['id_grupo'] != '0' && is_numeric($restriction['id_grupo'])) {
                                    $sql = 'SELECT ci.*
                                            FROM `' . _DB_PREFIX_ . 'combinacionunica_import` ci 
                                            WHERE ci.`id_product`=' . (int)$product['id_product'] . ' AND ci.`grupo`=' . (int)$restriction['id_grupo'];

                                    if ((int)$product['id_product_attribute']) {
                                        $sql = 'SELECT ci.*
                                                FROM `' . _DB_PREFIX_ . 'combinaciones_import` ci 
                                                WHERE ci.`id_product_attribute`=' . (int)$product['id_product_attribute'] . ' AND ci.`grupo`=' . (int)$restriction['id_grupo'];
                                    }

                                    if (DB::getInstance()->getRow($sql)) {
                                        $grupo = true;
                                    }
                                } else {
                                    $grupo = true;
                                }
                                if ($modelo_erp && $category && $feature_value && $familia && $subfamilia && $grupo) {
                                    unset($carriers[$key]);
                                }
                            }
                        }
                    }
                }
            }

            // elimino de la lista de transportistas de PS los que no se encuentran en la lista de transportistas habilitados
            foreach ($carrier_list as $key => $carrier) {
                $is_enabled = false;

                foreach ($carriers as $carrier_enabled) {
                    if ($carrier_enabled['id_carrier'] == $carrier) {
                        $is_enabled = true;
                    }
                }

                if (!$is_enabled) {
                    unset($carrier_list[$key]);
                }
            }
        }

        /* JLP - no mostrar "recogida en tienda" ni "recogida en correos" si hay productos con envío especial*/
        if (Configuration::get('BAN_CARRIER_REFERENCES_NO_SPECIAL_SHIPPING') && !empty(Configuration::get('BAN_CARRIER_REFERENCES_NO_SPECIAL_SHIPPING'))) {
            $carrier_references_no_special_shipping = Configuration::get('BAN_CARRIER_REFERENCES_NO_SPECIAL_SHIPPING');
            $carrier_references_no_special_shipping = explode(',', $carrier_references_no_special_shipping);

            if ($carrier_references_no_special_shipping && !empty($carrier_references_no_special_shipping)) {
                $special_shipping_in_cart = false;
                $products = Context::getContext()->cart->getProducts();
                foreach ($products as $key => $value) {
                    $sql = 'SELECT pp.`id` FROM `' . _DB_PREFIX_ . 'portes_producto` pp WHERE pp.`id_product`=' . (int)$value['id_product'] . ' AND pp.`id_product_attribute`=' . (int)$value['id_product_attribute'];
                    $id_portes_product = DB::getInstance()->getValue($sql);
                    if ($id_portes_product) {
                        $special_shipping_in_cart = true;
                        break;
                    }
                }

                if ($special_shipping_in_cart) {
                    $carriers = [];
                    foreach ($carrier_list as $carrier) {
                        $sql = 'SELECT c.`id_carrier`, c.`id_reference` 
                                FROM `' . _DB_PREFIX_ . 'carrier` c 
                                WHERE c.id_carrier=' . (int)$carrier;
                        $carrier_data = DB::getInstance()->getRow($sql);
                        if ($carrier_data) {
                            $carriers[] = $carrier_data;
                        }
                    }

                    foreach ($carriers as $key => $carrier) {
                        if (in_array($carrier['id_reference'], $carrier_references_no_special_shipping)) {
                            unset($carriers[$key]);
                        }
                    }

                    foreach ($carrier_list as $key => $carrier) {
                        $is_enabled = false;
                        foreach ($carriers as $carrier_enabled) {
                            if ($carrier_enabled['id_carrier'] == $carrier) {
                                $is_enabled = true;
                            }
                        }
                        if (!$is_enabled) {
                            unset($carrier_list[$key]);
                        }
                    }
                }
            }
        }
        /* FIN */

        return $carrier_list;
    }
}
