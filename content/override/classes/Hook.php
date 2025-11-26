<?php
class Hook extends HookCore
{
    
    /*
    * module: alvarezcarrierandpaymentlock
    * date: 2022-05-20 09:14:01
    * version: 1.0.0
    */
    public static function exec(
        $hook_name,
        $hook_args = [],
        $id_module = null,
        $array_return = false,
        $check_exceptions = true,
        $use_push = false,
        $id_shop = null,
        $chain = false
    ) {
        $output = parent::exec($hook_name, $hook_args, $id_module, $array_return, $check_exceptions, $use_push, $id_shop, $chain);
        if (Module::isEnabled('alvarezcarrierandpaymentlock')) {
            if ($hook_name == 'paymentOptions') {
                require_once _PS_MODULE_DIR_ . 'alvarezcarrierandpaymentlock/classes/CarrierPaymentLock.php';
                if (count($output)) {
                    foreach ($output as $key => $payment) {
                        $restrictions = CarrierPaymentLock::getRestrictionsByPaymentModule($key);
                        if ($restrictions) {
                            foreach ($restrictions as $restriction) {
                                $products = Context::getContext()->cart->getProducts();
                                foreach ($products as $product) {
                                    $feature_value = false;
                                    $familia = false;
                                    $subfamilia = false;
                                    $grupo = false;
                                    $modelo_erp = false;
                                    $category = false;
                                    if (!empty($restriction['id_model_erp']) && $restriction['id_model_erp'] != '0' && is_numeric($restriction['id_model_erp'])) {
                                        $sql = 'SELECT pi.* 
                                                FROM `'._DB_PREFIX_.'product_import` pi 
                                                WHERE pi.`id_product`='.(int) $product['id_product'].' AND pi.`id_modelo`='.(int) $restriction['id_model_erp'];
                                        if (DB::getInstance()->getRow($sql)) {
                                            $modelo_erp = true;
                                        }
                                    } else {
                                        $modelo_erp = true;
                                    }
                                    if (!empty($restriction['id_category']) && $restriction['id_category'] != '0' && is_numeric($restriction['id_category'])) {
                                        $sql = 'SELECT cp.* 
                                                FROM `'._DB_PREFIX_.'category_product` cp 
                                                WHERE cp.`id_product`='.(int) $product['id_product'].' AND cp.`id_category`='.(int) $restriction['id_category'];
                                        if (DB::getInstance()->getRow($sql)) {
                                            $category = true;
                                        }
                                    } else {
                                        $category = true;
                                    }
                                    if (!empty($restriction['id_feature_value']) && $restriction['id_feature_value'] != '0' && is_numeric($restriction['id_feature_value'])) {
                                        $sql = 'SELECT fp.*
                                                FROM `'._DB_PREFIX_.'feature_product` fp 
                                                WHERE fp.`id_product`='.(int) $product['id_product'].' AND fp.`id_feature_value`='.(int) $restriction['id_feature_value'];
                                        if (DB::getInstance()->getRow($sql)) {
                                            $feature_value = true;
                                        }
                                    } else {
                                        $feature_value = true;
                                    }
                                    if (!empty($restriction['id_familia']) && $restriction['id_familia'] != '0' && is_numeric($restriction['id_familia'])) {
                                        $sql = 'SELECT ci.*
                                                FROM `'._DB_PREFIX_.'combinacionunica_import` ci 
                                                WHERE ci.`id_product`='.(int) $product['id_product'].' AND ci.`familia`='.(int) $restriction['id_familia'];
                                        if ((int) $product['id_product_attribute']) {
                                            $sql = 'SELECT ci.*
                                                    FROM `'._DB_PREFIX_.'combinaciones_import` ci 
                                                    WHERE ci.`id_product_attribute`='.(int) $product['id_product_attribute'].' AND ci.`familia`='.(int) $restriction['id_familia'];
                                        }
                                        if (DB::getInstance()->getRow($sql)) {
                                            $familia = true;
                                        }
                                    } else {
                                        $familia = true;
                                    }
                                    if (!empty($restriction['id_subfamilia']) && $restriction['id_subfamilia'] != '0' && is_numeric($restriction['id_subfamilia'])) {
                                        $sql = 'SELECT ci.*
                                                FROM `'._DB_PREFIX_.'combinacionunica_import` ci 
                                                WHERE ci.`id_product`='.(int) $product['id_product'].' AND ci.`subfamilia`='.(int) $restriction['id_subfamilia'];
                                        if ((int) $product['id_product_attribute']) {
                                            $sql = 'SELECT ci.*
                                                    FROM `'._DB_PREFIX_.'combinaciones_import` ci 
                                                    WHERE ci.`id_product_attribute`='.(int) $product['id_product_attribute'].' AND ci.`subfamilia`='.(int) $restriction['id_subfamilia'];
                                        }
                                        if (DB::getInstance()->getRow($sql)) {
                                            $subfamilia = true;
                                        }
                                    } else {
                                        $subfamilia = true;
                                    }
                                    if (!empty($restriction['id_grupo']) && $restriction['id_grupo'] != '0' && is_numeric($restriction['id_grupo'])) {
                                        $sql = 'SELECT ci.*
                                                FROM `'._DB_PREFIX_.'combinacionunica_import` ci 
                                                WHERE ci.`id_product`='.(int) $product['id_product'].' AND ci.`grupo`='.(int) $restriction['id_grupo'];
                                        if ((int) $product['id_product_attribute']) {
                                            $sql = 'SELECT ci.*
                                                    FROM `'._DB_PREFIX_.'combinaciones_import` ci 
                                                    WHERE ci.`id_product_attribute`='.(int) $product['id_product_attribute'].' AND ci.`grupo`='.(int) $restriction['id_grupo'];
                                        }
                                        if (DB::getInstance()->getRow($sql)) {
                                            $grupo = true;
                                        }
                                    } else {
                                        $grupo = true;
                                    }
                                    if ($modelo_erp && $category && $feature_value && $familia && $subfamilia && $grupo) {
                                        $output[$key] = [];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($hook_name == 'paymentOptions') {
            $payment_module_name = 'paypal';
            if (array_key_exists($payment_module_name, $output)) {
                $families = Configuration::get('BAN_FAMILIES_NO_PAYPAL');
                $countries = Configuration::get('BAN_COUNTRIES_NO_PAYPAL');
                $products = Context::getContext()->cart->getProducts();
                foreach ($products as $product) {
                    $check_families = false;
                    $check_countries = false;
                    if (!empty($families)) {
                        foreach (explode(',', $families) as $family_lock) {
                            if (Product::getFamiliaAlvarez((int) $product['id_product'], (int) $product['id_product_attribute'], null) == $family_lock) {
                                $check_families = true;
                                break;
                            }
                        }
                    }
                    if ($check_families) {
                        if (!empty($countries)) {
                            $id_address = Context::getContext()->cart->id_address_delivery;
                            if ($id_address) {
                                $address = new Address((int) $id_address);
                                foreach (explode(',', $countries) as $country_lock) {
                                    if ((int) $country_lock == $address->id_country) {
                                        $check_countries = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($check_families && $check_countries) {
                        $output[$payment_module_name] = [];
                        break;
                    }
                }
            }
        }
        return $output;
    }
    
    
    
    /*
    * module: lgcookieslaw
    * date: 2024-03-20 09:32:11
    * version: 2.1.7
    */
    public static function getHookModuleExecList($hook_name = null)
    {
        $modules_to_invoke = parent::getHookModuleExecList($hook_name);
        if (!empty($modules_to_invoke)
            && Module::isInstalled('lgcookieslaw')
            && Module::isEnabled('lgcookieslaw')
        ) {
            $lgcookieslaw = Module::getInstanceByName('lgcookieslaw');
            $modules_to_invoke = $lgcookieslaw->getHookModuleExecList($modules_to_invoke);
        }
        return $modules_to_invoke;
    }
    /*
    * module: pagecache
    * date: 2024-05-21 08:49:56
    * version: 9.3.2
    */
    public static function coreCallHook($module, $method, $params)
    {
        if (!Module::isEnabled('pagecache') || !file_exists(_PS_MODULE_DIR_ . 'pagecache/pagecache.php')) {
            return parent::coreCallHook($module, $method, $params);
        }
        else {
            require_once _PS_MODULE_DIR_ . 'pagecache/pagecache.php';
            return PageCache::execHook(PageCache::HOOK_TYPE_MODULE, $module, $method, $params);
        }
    }
    /*
    * module: pagecache
    * date: 2024-05-21 08:49:56
    * version: 9.3.2
    */
    public static function coreRenderWidget($module, $hook_name, $params)
    {
        if (!Module::isEnabled('pagecache') || !file_exists(_PS_MODULE_DIR_ . 'pagecache/pagecache.php')) {
            return parent::coreRenderWidget($module, $hook_name, $params);
        }
        else {
            require_once _PS_MODULE_DIR_ . 'pagecache/pagecache.php';
            return PageCache::execHook(PageCache::HOOK_TYPE_WIDGET, $module, $hook_name, $params);
        }
    }
}
