<?php

class CartRule extends CartRuleCore
{
    const ALVAREZ_CUPON_SPLIT_DESCRIPTION = '-';
    public static function createCartRuleAlvarez($id_bono, $verification_code, $amount, $amount_total, $importe_minimo, $bono, $context) {

        $id_cart_rule = Db::getInstance()->getValue("SELECT id_cart_rule FROM `"._DB_PREFIX_."cart_rule` WHERE `code` = '".self::getCartRuleCodeAlvarez($id_bono, $verification_code)."'");

        if ($id_cart_rule) {
            return $id_cart_rule;
        }

        $cartRule = new CartRule();
        $cartRule->id_customer = 0; //$context->customer->id;
        
        $cartRule->date_from = $bono['fvalidez_desde'].' 00:00:00';
        $cartRule->date_to = $bono['fvalidez_hasta'].' 23:59:59';
        $cartRule->description = self::getCartRuleDescriptionAlvarez($id_bono, $verification_code);
        $cartRule->quantity = 1;
        $cartRule->quantity_per_user = 1;
        $cartRule->priority = 1;
        $cartRule->partial_use = 0;
        $cartRule->code = self::getCartRuleCodeAlvarez($id_bono, $verification_code);
        $cartRule->minimum_amount = $importe_minimo;
        $cartRule->minimum_amount_tax = 1;
        $cartRule->minimum_amount_currency = $context->currency->id;
        $cartRule->minimum_amount_shipping = 1;
        $cartRule->country_restriction = false;
        $cartRule->carrier_restriction = false;
        $cartRule->group_restriction = false;
        $cartRule->cart_rule_restriction = false;
        $cartRule->product_restriction = false;
        $cartRule->shop_restriction = false;
        $cartRule->free_shipping = 0;
        $cartRule->reduction_percent = 0;
        $cartRule->reduction_amount = $amount;
        $cartRule->reduction_tax = 1;
        $cartRule->reduction_currency = $context->currency->id;
        $cartRule->reduction_product = 0;
        $cartRule->reduction_exclude_special = 0;
        $cartRule->gift_product = 0;
        $cartRule->gift_product_attribute = 0;
        $cartRule->highlight = 0;
        $cartRule->active = 1;
        foreach(Language::getLanguages(false) as $lang){
            $cartRule->name[$lang['id_lang']] = $bono['descripcion_tipo'];
        }
        if ($cartRule->save()) {
            return $cartRule->id;
        } else {
            return 0;
        }
    }
    public static function getCartRuleCodeAlvarez($id_bono, $verification_code) {
        return $id_bono.self::ALVAREZ_CUPON_SPLIT_DESCRIPTION.$verification_code;
    }
    public static function getCartRuleDescriptionAlvarez($id_bono, $verification_code) {
        return $id_bono.self::ALVAREZ_CUPON_SPLIT_DESCRIPTION.$verification_code;
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    public static function autoRemoveFromCart(Context $context = null, bool $useOrderPrice = false)
    {
        if (Module::isEnabled('quantitydiscountpro')) {
            include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
            $quantityDiscount = new QuantityDiscountRule();
            $quantityDiscount->createAndRemoveRules(null, $context);
        }
        parent::autoRemoveFromCart($context, $useOrderPrice);
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    public static function autoAddToCart(Context $context = null, bool $useOrderPrices = false)
    {
        parent::autoAddToCart($context, $useOrderPrices);
        if (Module::isEnabled('quantitydiscountpro')) {
            include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
            $quantityDiscount = new QuantityDiscountRule();
            $quantityDiscount->createAndRemoveRules(null, $context);
        }
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    public function update($null_values = false)
    {
        $r = parent::update($null_values);
        if (Module::isEnabled('quantitydiscountpro')) {
            include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
            if (CartRule::isCurrentlyUsed('cart_rule', true) || QuantityDiscountRule::isCurrentlyUsed(null, true)) {
                Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', true);
            } else {
                Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', false);
            }
        }
        return $r;
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    public function delete()
    {
        $r = parent::delete();
        if (Module::isEnabled('quantitydiscountpro')) {
            include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
            if (CartRule::isCurrentlyUsed('cart_rule', true) || QuantityDiscountRule::isCurrentlyUsed(null, true)) {
                Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', true);
            } else {
                Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', false);
            }
        }
        return $r;
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    public static function getCustomerCartRules(
        $id_lang,
        $id_customer,
        $active = false,
        $includeGeneric = true,
        $inStock = false,
        Cart $cart = null,
        $free_shipping_only = false,
        $highlight_only = false
    ) {
        $result = parent::getCustomerCartRules($id_lang, $id_customer, $active, $includeGeneric, $inStock, $cart, $free_shipping_only, $highlight_only);
        if (!Module::isEnabled('quantitydiscountpro') || !$highlight_only) {
            return $result;
        }
        include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
        $quantityDiscountRule = new QuantityDiscountRule();
        $quantityDiscountProRules = $quantityDiscountRule->getHighlightedQuantityDiscountRules();
        foreach ($quantityDiscountProRules as &$quantityDiscountProRule) {
            if ($id = $quantityDiscountRule->getIdCartFruleFromIdQuantityDiscountRuleFromThisCart($quantityDiscountProRule['id_quantity_discount_rule'], $cart->id)) {
                $quantityDiscountProRule['id_cart_rule'] = $id;
            } else {
                $quantityDiscountProRule['id_cart_rule'] = PHP_INT_MAX;
            }
            unset($quantityDiscountProRule);
        }
        return array_merge($result, $quantityDiscountProRules);
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    public static function getCustomerHighlightedDiscounts(
        $languageId,
        $customerId,
        Cart $cart
    ) {
        return static::getCustomerCartRules(
            $languageId,
            $customerId,
            $active = true,
            $includeGeneric = true,
            $inStock = true,
            $cart,
            $freeShippingOnly = false,
            $highlightOnly = true
        );
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    protected function getCartRuleCombinations($offset = null, $limit = null, $search = '')
    {
        if (!Module::isEnabled('quantitydiscountpro')) {
            return parent::getCartRuleCombinations($offset, $limit, $search);
        }
        $array = array();
        if ($offset !== null && $limit !== null) {
            $sql_limit = ' LIMIT ' . (int) $offset . ', ' . (int) ($limit + 1);
        } else {
            $sql_limit = '';
        }
        $array['selected'] = Db::getInstance()->executeS('
        SELECT cr.*, crl.*, 1 as selected
        FROM ' . _DB_PREFIX_ . 'cart_rule cr
        LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_lang crl ON (cr.id_cart_rule = crl.id_cart_rule AND crl.id_lang = ' . (int) Context::getContext()->language->id . ')
        WHERE cr.id_cart_rule != ' . (int) $this->id . ($search ? ' AND crl.name LIKE "%' . pSQL($search) . '%"' : '') . '
        AND (
            cr.cart_rule_restriction = 0
            OR EXISTS (
                SELECT 1
                FROM ' . _DB_PREFIX_ . 'cart_rule_combination
                WHERE cr.id_cart_rule = ' . _DB_PREFIX_ . 'cart_rule_combination.id_cart_rule_1 AND ' . (int) $this->id . ' = id_cart_rule_2
            )
            OR EXISTS (
                SELECT 1
                FROM ' . _DB_PREFIX_ . 'cart_rule_combination
                WHERE cr.id_cart_rule = ' . _DB_PREFIX_ . 'cart_rule_combination.id_cart_rule_2 AND ' . (int) $this->id . ' = id_cart_rule_1
            )
        )
        AND cr.id_cart_rule NOT IN (SELECT id_cart_rule FROM `'._DB_PREFIX_.'quantity_discount_rule_cart`)
        ORDER BY cr.id_cart_rule' . $sql_limit);
        $array['unselected'] = Db::getInstance()->executeS('
        SELECT cr.*, crl.*, 1 as selected
        FROM ' . _DB_PREFIX_ . 'cart_rule cr
        INNER JOIN ' . _DB_PREFIX_ . 'cart_rule_lang crl ON (cr.id_cart_rule = crl.id_cart_rule AND crl.id_lang = ' . (int) Context::getContext()->language->id . ')
        LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_combination crc1 ON (cr.id_cart_rule = crc1.id_cart_rule_1 AND crc1.id_cart_rule_2 = ' . (int) $this->id . ')
        LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_combination crc2 ON (cr.id_cart_rule = crc2.id_cart_rule_2 AND crc2.id_cart_rule_1 = ' . (int) $this->id . ')
        WHERE cr.cart_rule_restriction = 1
        AND cr.id_cart_rule != ' . (int) $this->id . ($search ? ' AND crl.name LIKE "%' . pSQL($search) . '%"' : '') . '
        AND crc1.id_cart_rule_1 IS NULL
        AND crc2.id_cart_rule_1 IS NULL
        AND cr.id_cart_rule NOT IN (SELECT id_cart_rule FROM `'._DB_PREFIX_.'quantity_discount_rule_cart`)
        AND crc2.id_cart_rule_1 IS NULL  ORDER BY cr.id_cart_rule' . $sql_limit);
        return $array;
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    public function getAssociatedRestrictions(
        $type,
        $active_only,
        $i18n,
        $offset = null,
        $limit = null,
        $search_cart_rule_name = ''
    ) {
        if (!Module::isEnabled('quantitydiscountpro')) {
            return parent::getAssociatedRestrictions($type, $active_only, $i18n, $offset, $limit, $search_cart_rule_name);
        }
        $array = array('selected' => array(), 'unselected' => array());
        if (!in_array($type, array('country', 'carrier', 'group', 'cart_rule', 'shop'))) {
            return false;
        }
        $shop_list = '';
        if ($type == 'shop') {
            $shops = Context::getContext()->employee->getAssociatedShops();
            if (count($shops)) {
                $shop_list = ' AND t.id_shop IN (' . implode(',', array_map('intval', $shops)) . ') ';
            }
        }
        if ($offset !== null && $limit !== null) {
            $sql_limit = ' LIMIT ' . (int) $offset . ', ' . (int) ($limit + 1);
        } else {
            $sql_limit = '';
        }
        if (!Validate::isLoadedObject($this) || $this->{$type . '_restriction'} == 0) {
            $array['selected'] = Db::getInstance()->executeS('
            SELECT t.*' . ($i18n ? ', tl.*' : '') . ', 1 as selected
            FROM `' . _DB_PREFIX_ . $type . '` t
            ' . ($i18n ? 'LEFT JOIN `' . _DB_PREFIX_ . $type . '_lang` tl ON (t.id_' . $type . ' = tl.id_' . $type . ' AND tl.id_lang = ' . (int) Context::getContext()->language->id . ')' : '') . '
            WHERE 1
            ' . ($active_only ? 'AND t.active = 1' : '') . '
            ' . (in_array($type, array('carrier', 'shop')) ? ' AND t.deleted = 0' : '') . '
            ' . ($type == 'cart_rule' ? 'AND t.id_cart_rule != '.(int)$this->id.' AND t.id_cart_rule NOT IN (SELECT id_cart_rule FROM `'._DB_PREFIX_.'quantity_discount_rule_cart`)' : '') .
                $shop_list .
                (in_array($type, array('carrier', 'shop')) ? ' ORDER BY t.name ASC ' : '') .
                (in_array($type, array('country', 'group', 'cart_rule')) && $i18n ? ' ORDER BY tl.name ASC ' : '') .
                $sql_limit);
        } else {
            if ($type == 'cart_rule') {
                $array = $this->getCartRuleCombinations($offset, $limit, $search_cart_rule_name);
            } else {
                $resource = Db::getInstance()->executeS(
                    'SELECT t.*' . ($i18n ? ', tl.*' : '') . ', IF(crt.id_' . $type . ' IS NULL, 0, 1) as selected
                    FROM `' . _DB_PREFIX_ . $type . '` t
                    ' . ($i18n ? 'LEFT JOIN `' . _DB_PREFIX_ . $type . '_lang` tl ON (t.id_' . $type . ' = tl.id_' . $type . ' AND tl.id_lang = ' . (int) Context::getContext()->language->id . ')' : '') . '
                    LEFT JOIN (SELECT id_' . $type . ' FROM `' . _DB_PREFIX_ . 'cart_rule_' . $type . '` WHERE id_cart_rule = ' . (int) $this->id . ') crt ON t.id_' . ($type == 'carrier' ? 'reference' : $type) . ' = crt.id_' . $type . '
                    WHERE 1 ' . ($active_only ? ' AND t.active = 1' : '') .
                        $shop_list
                        . (in_array($type, array('carrier', 'shop')) ? ' AND t.deleted = 0' : '') .
                        (in_array($type, array('carrier', 'shop')) ? ' ORDER BY t.name ASC ' : '') .
                        (in_array($type, array('country', 'group')) && $i18n ? ' ORDER BY tl.name ASC ' : '') .
                        $sql_limit,
                    false
                );
                while ($row = Db::getInstance()->nextRow($resource)) {
                    $array[($row['selected'] || $this->{$type . '_restriction'} == 0) ? 'selected' : 'unselected'][] = $row;
                }
            }
        }
        return $array;
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    public static function getCartsRuleByCode($name, $id_lang, $extended = false)
    {
        if (!Module::isEnabled('quantitydiscountpro')) {
            return parent::getCartsRuleByCode($name, $id_lang, $extended);
        }
        $query = '
            SELECT cr.`id_cart_rule`, cr.`code`, crl.`name`
            FROM '._DB_PREFIX_.'cart_rule cr
            LEFT JOIN '._DB_PREFIX_.'cart_rule_lang crl ON (cr.id_cart_rule = crl.id_cart_rule AND crl.id_lang = '.(int)$id_lang.')
            WHERE cr.`id_cart_rule` NOT IN (SELECT qdrc.`id_cart_rule` FROM '._DB_PREFIX_.'quantity_discount_rule_cart qdrc) AND (code LIKE \'%'.pSQL($name).'%\''
            .($extended ? ' OR name LIKE \'%'.pSQL($name).'%\'' : '').')
            UNION
            SELECT CONCAT("QDP~", qdr.`id_quantity_discount_rule`) as `id_cart_rule`, qdr.`code`, qdrl.`name`
            FROM '._DB_PREFIX_.'quantity_discount_rule qdr
            LEFT JOIN '._DB_PREFIX_.'quantity_discount_rule_lang qdrl ON (qdr.id_quantity_discount_rule = qdrl.id_quantity_discount_rule AND qdrl.id_lang = '.(int)$id_lang.')
            WHERE code LIKE \'%'.pSQL($name).'%\''
            .($extended ? ' OR name LIKE \'%'.pSQL($name).'%\'' : '');
        return Db::getInstance()->executeS($query);
    }
}
