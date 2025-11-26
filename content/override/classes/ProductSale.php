<?php

class ProductSale extends ProductSaleCore
{
    public static function getNbSalesSport($id_category)
    {
        $sql = 'SELECT COUNT(ps.`id_product`) AS nb
				FROM `' . _DB_PREFIX_ . 'product_sale` ps
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = ps.`id_product`
				' . Shop::addSqlAssociation('product', 'p', false) . '
				WHERE product_shop.`active` = 1
                AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp2 WHERE cp2.`id_product` = ps.`id_product` and cp2.id_category='.$id_category.')

                ';

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    
    public static function getBestSalesSport($idLang, $pageNumber = 0, $nbProducts = 10, $orderBy = null, $orderWay = null, $id_category)
    {
        $context = Context::getContext();
        if ($pageNumber < 1) {
            $pageNumber = 1;
        }
        if ($nbProducts < 1) {
            $nbProducts = 10;
        }
        $finalOrderBy = $orderBy;
        $orderTable = '';

        $invalidOrderBy = !Validate::isOrderBy($orderBy);
        if ($invalidOrderBy || null === $orderBy) {
            $orderBy = 'quantity';
            $orderTable = 'ps';
        }

        if ($orderBy == 'date_add' || $orderBy == 'date_upd') {
            $orderTable = 'product_shop';
        }

        $invalidOrderWay = !Validate::isOrderWay($orderWay);
        if ($invalidOrderWay || null === $orderWay || $orderBy == 'sales') {
            $orderWay = 'DESC';
        }

        $interval = Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;

        // no group by needed : there's only one attribute with default_on=1 for a given id_product + shop
        // same for image with cover=1
        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity,
					' . (Combination::isFeatureActive() ? 'product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity,IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute,' : '') . '
					pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
					pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
					m.`name` AS manufacturer_name, p.`id_manufacturer` as id_manufacturer,
					image_shop.`id_image` id_image, il.`legend`,
					ps.`quantity` AS sales, t.`rate`, pl.`meta_keywords`, pl.`meta_title`, pl.`meta_description`,
					DATEDIFF(p.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00",
					INTERVAL ' . (int) $interval . ' DAY)) > 0 AS new'
            . ' FROM `' . _DB_PREFIX_ . 'product_sale` ps
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON ps.`id_product` = p.`id_product`
				' . Shop::addSqlAssociation('product', 'p', false);
        if (Combination::isFeatureActive()) {
            $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
							ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')';
        }

        $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
					ON p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = ' . (int) $idLang . Shop::addSqlRestrictionOnLang('pl') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $idLang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
				LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON (product_shop.`id_tax_rules_group` = tr.`id_tax_rules_group`)
					AND tr.`id_country` = ' . (int) $context->country->id . '
					AND tr.`id_state` = 0
				LEFT JOIN `' . _DB_PREFIX_ . 'tax` t ON (t.`id_tax` = tr.`id_tax`)
				' . Product::sqlStock('p', 0);

        $sql .= '
				WHERE product_shop.`active` = 1
					AND product_shop.`visibility` != \'none\' AND stock.quantity>0';

        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql .= ' AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
            JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::getCurrent()->id) . ')
            WHERE cp.`id_product` = p.`id_product`)';
        }


        $sql .= ' AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp2 WHERE cp2.`id_product` = ps.`id_product` and cp2.id_category='.$id_category.')';




        if ($finalOrderBy != 'price') {
            $sql .= '
					ORDER BY ' . (!empty($orderTable) ? '`' . pSQL($orderTable) . '`.' : '') . '`' . pSQL($orderBy) . '` ' . pSQL($orderWay) . '
					LIMIT ' . (int) (($pageNumber - 1) * $nbProducts) . ', ' . (int) $nbProducts;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if ($finalOrderBy == 'price') {
            Tools::orderbyPrice($result, $orderWay);
            $result = array_slice($result, (int) (($pageNumber - 1) * $nbProducts), (int) $nbProducts);
        }
        if (!$result) {
            return false;
        }

        return Product::getProductsProperties($idLang, $result);
    }

   
    
}
