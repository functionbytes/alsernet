<?php
use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;
class Cart extends CartCore
{
    public $need_invoice;
    public $step;
    public static $definition = [
        'table' => 'cart',
        'primary' => 'id_cart',
        'fields' => [
            'id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_address_delivery' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_address_invoice' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_carrier' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_guest' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'recyclable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'gift' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'gift_message' => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'step' => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'mobile_theme' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'delivery_option' => ['type' => self::TYPE_STRING],
            'secure_key' => ['type' => self::TYPE_STRING, 'size' => 32],
            'allow_seperated_package' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'need_invoice' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];
    public function getPackageShippingCost(
        $id_carrier = null,
        $use_tax = true,
        Country $default_country = null,
        $product_list = null,
        $id_zone = null,
        bool $keepOrderPrices = false
    ) {
        if ($this->isVirtualCart()) {
            return 0;
        }
        if (!$default_country) {
            $default_country = Context::getContext()->country;
        }
        if (null === $product_list) {
            $products = $this->getProducts(false, false, null, true, $keepOrderPrices);
        } else {
            $products = $product_list;
        }
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
            $address_id = (int) $this->id_address_invoice;
        } elseif (is_array($product_list) && count($product_list)) {
            $prod = current($product_list);
            $address_id = (int) $prod['id_address_delivery'];
        } else {
            $address_id = null;
        }
        if (!Address::addressExists($address_id)) {
            $address_id = null;
        }
        if (null === $id_carrier && !empty($this->id_carrier)) {
            $id_carrier = (int) $this->id_carrier;
        }
        $cache_id = 'getPackageShippingCost_' . (int) $this->id . '_' . (int) $address_id . '_' . (int) $id_carrier . '_' . (int) $use_tax . '_' . (int) $default_country->id . '_' . (int) $id_zone;
        if ($products) {
            foreach ($products as $product) {
                $cache_id .= '_' . (int) $product['id_product'] . '_' . (int) $product['id_product_attribute'];
            }
        }
        $order_total = $this->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, $product_list, $id_carrier, false, $keepOrderPrices);
        $suma_portes = 0;
        $es_virtual = false;
        $count_es_virtual = 0;
        $suma_total = 0;
        $fitting = false;
        $portes_producto = false;
        foreach ($products as $value_portes) {
            $dato_portes = Db::getInstance()->getValue("SELECT
                                            importe
                                        FROM
                                            aalv_portes_producto a
                                            inner join aalv_portes b on a.codigo=b.codigo
                                        WHERE
                                            id_product=" . $value_portes['id_product'] . "
                                            and id_product_attribute =" . $value_portes['id_product_attribute'] . "
                                            and id_pais=" . $default_country->id . "
                                        ORDER BY id_origen DESC");
            if($dato_portes != false){
                $portes_producto = true;
                $suma_portes = ($dato_portes * $value_portes['cart_quantity']) + $suma_portes;
                $suma_total = $value_portes['total_wt'] + $suma_total;
                if (is_numeric($value_portes['total_wt'])) {
                    $order_total -= $value_portes['total_wt']; // Restamos el valor de $wt a $order_total
                }
            }
            if($value_portes['is_virtual']){
                $es_virtual = true;
                $count_es_virtual++;
                if (is_numeric($value_portes['total_wt'])) {
                    $order_total -= $value_portes['total_wt']; // Restamos el valor de $wt a $order_total
                }
            }
            if($value_portes['id_product'] == 56764){
                $es_virtual = true;
                $fitting = true;
                if (is_numeric($value_portes['total_wt'])) {
                    $order_total -= $value_portes['total_wt']; // Restamos el valor de $wt a $order_total
                }
            }
            if($value_portes['id_product'] == 65732){
                $es_virtual = true;
                $count_es_virtual++;
            }
        }
        $aux_order_total = $order_total;
        if($es_virtual && count($products) == 2){
            $order_total = $suma_total;
        }elseif($order_total == 0 && count($products) == 1){
            $order_total = $suma_total;
        }
        if($order_total == 0.0){
            $order_total = 0;
        }
        $shipping_cost = 0;
        if (!count($products)) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }
        if (!isset($id_zone)) {
            if (!$this->isMultiAddressDelivery()
                && isset($this->id_address_delivery) // Be careful, id_address_delivery is not useful one 1.5
                && $this->id_address_delivery
                && Customer::customerHasAddress($this->id_customer, $this->id_address_delivery)
            ) {
                $id_zone = Address::getZoneById((int) $this->id_address_delivery);
            } else {
                if (!Validate::isLoadedObject($default_country)) {
                    $default_country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'), Configuration::get('PS_LANG_DEFAULT'));
                }
                $id_zone = (int) $default_country->id_zone;
            }
        }
        if ($id_carrier && !$this->isCarrierInRange((int) $id_carrier, (int) $id_zone)) {
            $id_carrier = '';
        }
        if (empty($id_carrier) && $this->isCarrierInRange((int) Configuration::get('PS_CARRIER_DEFAULT'), (int) $id_zone)) {
            $id_carrier = (int) Configuration::get('PS_CARRIER_DEFAULT');
        }
        if (empty($id_carrier)) {
            if ((int) $this->id_customer) {
                $customer = new Customer((int) $this->id_customer);
                $result = Carrier::getCarriers((int) Configuration::get('PS_LANG_DEFAULT'), true, false, (int) $id_zone, $customer->getGroups());
                unset($customer);
            } else {
                $result = Carrier::getCarriers((int) Configuration::get('PS_LANG_DEFAULT'), true, false, (int) $id_zone);
            }
            foreach ($result as $k => $row) {
                if ($row['id_carrier'] == Configuration::get('PS_CARRIER_DEFAULT')) {
                    continue;
                }
                if (!isset(self::$_carriers[$row['id_carrier']])) {
                    self::$_carriers[$row['id_carrier']] = new Carrier((int) $row['id_carrier']);
                }
                
                $carrier = self::$_carriers[$row['id_carrier']];
                $shipping_method = $carrier->getShippingMethod();
                if (($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && $carrier->getMaxDeliveryPriceByWeight((int) $id_zone) === false)
                    || ($shipping_method == Carrier::SHIPPING_METHOD_PRICE && $carrier->getMaxDeliveryPriceByPrice((int) $id_zone) === false)) {
                    unset($result[$k]);
                    continue;
                }
                if ($row['range_behavior']) {
                    $check_delivery_price_by_weight = Carrier::checkDeliveryPriceByWeight($row['id_carrier'], $this->getTotalWeight(), (int) $id_zone);
                    $check_delivery_price_by_price = Carrier::checkDeliveryPriceByPrice($row['id_carrier'], $order_total, (int) $id_zone, (int) $this->id_currency);
                    if (($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && !$check_delivery_price_by_weight)
                        || ($shipping_method == Carrier::SHIPPING_METHOD_PRICE && !$check_delivery_price_by_price)) {
                        unset($result[$k]);
                        continue;
                    }
                }
                if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shipping = $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), (int) $id_zone);
                } else {
                    $shipping = $carrier->getDeliveryPriceByPrice($order_total, (int) $id_zone, (int) $this->id_currency);
                }
                if (!isset($min_shipping_price)) {
                    $min_shipping_price = $shipping;
                }
                if ($shipping <= $min_shipping_price) {
                    $id_carrier = (int) $row['id_carrier'];
                    $min_shipping_price = $shipping;
                }
            }
        }
        if (empty($id_carrier)) {
            $id_carrier = Configuration::get('PS_CARRIER_DEFAULT');
        }
        if (!isset(self::$_carriers[$id_carrier])) {
            self::$_carriers[$id_carrier] = new Carrier((int) $id_carrier, Configuration::get('PS_LANG_DEFAULT'));
        }
        $carrier = self::$_carriers[$id_carrier];
        if (!Validate::isLoadedObject($carrier)) {
            Cache::store($cache_id, 0);
            return 0;
        }
        $shipping_method = $carrier->getShippingMethod();
        if (!$carrier->active) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }
        if ($carrier->is_free == 1) {
            Cache::store($cache_id, 0);
            return 0;
        }
        if ($use_tax && !Tax::excludeTaxeOption()) {
            $address = Address::initialize((int) $address_id);
            if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                $carrier_tax = 0;
            } else {
                $carrier_tax = $carrier->getTaxesRate($address);
            }
        }
        $configuration = Configuration::getMultiple([
            'PS_SHIPPING_FREE_PRICE',
            'PS_SHIPPING_HANDLING',
            'PS_SHIPPING_METHOD',
            'PS_SHIPPING_FREE_WEIGHT',
        ]);
        $free_fees_price = 0;
        if (isset($configuration['PS_SHIPPING_FREE_PRICE'])) {
            $free_fees_price = Tools::convertPrice((float) $configuration['PS_SHIPPING_FREE_PRICE'], Currency::getCurrencyInstance((int) $this->id_currency));
        }
        $orderTotalwithDiscounts = $this->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, null, null, false);
        if ($orderTotalwithDiscounts >= (float) ($free_fees_price) && (float) ($free_fees_price) > 0) {
            $shipping_cost = $this->getPackageShippingCostFromModule($carrier, $shipping_cost, $products);
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }
        if (isset($configuration['PS_SHIPPING_FREE_WEIGHT'])
            && $this->getTotalWeight() >= (float) $configuration['PS_SHIPPING_FREE_WEIGHT']
            && (float) $configuration['PS_SHIPPING_FREE_WEIGHT'] > 0) {
            $shipping_cost = $this->getPackageShippingCostFromModule($carrier, $shipping_cost, $products);
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }
        if ($carrier->range_behavior) {
            if (!isset($id_zone)) {
                if (isset($this->id_address_delivery)
                    && $this->id_address_delivery
                    && Customer::customerHasAddress($this->id_customer, $this->id_address_delivery)) {
                    $id_zone = Address::getZoneById((int) $this->id_address_delivery);
                } else {
                    $id_zone = (int) $default_country->id_zone;
                }
            }
            if (($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && !Carrier::checkDeliveryPriceByWeight($carrier->id, $this->getTotalWeight(), (int) $id_zone))
                || (
                    $shipping_method == Carrier::SHIPPING_METHOD_PRICE && !Carrier::checkDeliveryPriceByPrice($carrier->id, $order_total, $id_zone, (int) $this->id_currency)
                )) {
                $shipping_cost += 0;
            } else {
                if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shipping_cost += $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), $id_zone);
                } else { // by price
                    $shipping_cost += $carrier->getDeliveryPriceByPrice($order_total, $id_zone, (int) $this->id_currency);
                }
            }
        } else {
            if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                $shipping_cost += $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), $id_zone);
            } else {
                $shipping_cost += $carrier->getDeliveryPriceByPrice($order_total, $id_zone, (int) $this->id_currency);
            }
        }
        if (isset($configuration['PS_SHIPPING_HANDLING']) && $carrier->shipping_handling) {
            $shipping_cost += (float) $configuration['PS_SHIPPING_HANDLING'];
        }
        foreach ($products as $product) {
            if (!$product['is_virtual']) {
                $shipping_cost += $product['additional_shipping_cost'] * $product['cart_quantity'];
            }
        }
        $shipping_cost = Tools::convertPrice($shipping_cost, Currency::getCurrencyInstance((int) $this->id_currency));
        $shipping_cost = $this->getPackageShippingCostFromModule($carrier, $shipping_cost, $products);
        if ($shipping_cost === false) {
            Cache::store($cache_id, false);
            return false;
        }
        if (Configuration::get('PS_ATCP_SHIPWRAP')) {
            if (!$use_tax) {
                $shipping_cost /= (1 + $this->getAverageProductsTaxRate());
            }
        } else {
            if ($use_tax && isset($carrier_tax)) {
                $shipping_cost *= 1 + ($carrier_tax / 100);
            }
        }
        $shipping_cost = (float) Tools::ps_round((float) $shipping_cost, Context::getContext()->getComputingPrecision());
        Cache::store($cache_id, $shipping_cost);
        if($fitting && count($products) == 1){
            $shipping_cost = $shipping_cost - $shipping_cost;
        }elseif($portes_producto && $suma_total > 99.99){
            $shipping_cost = $shipping_cost - $shipping_cost;
        }elseif($es_virtual && $aux_order_total > 99.99){
            $shipping_cost = $shipping_cost - $shipping_cost;
        }elseif(count($products) == $count_es_virtual){
            $shipping_cost = $shipping_cost - $shipping_cost;
        }
        $shipping_cost = $suma_portes + $shipping_cost;
        return $shipping_cost;
    }
    /*
    * module: currencyformat
    * date: 2022-02-02 10:07:53
    * version: 1.1.6
    */
    /*
    * module: currencyformat
    * date: 2022-02-07 09:16:22
    * version: 1.1.6
    */
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    public function addCartRule($id_cart_rule, bool $useOrderPrices = false)
    {
        $result = parent::addCartRule($id_cart_rule, $useOrderPrices);
        if (Module::isEnabled('quantitydiscountpro')) {
            include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
            $quantityDiscountRulesAtCart = QuantityDiscountRule::getQuantityDiscountRulesAtCart((int)Context::getContext()->cart->id);
            if (is_array($quantityDiscountRulesAtCart) && count($quantityDiscountRulesAtCart)) {
                foreach ($quantityDiscountRulesAtCart as $quantityDiscountRuleAtCart) {
                    $quantityDiscountRuleAtCartObj = new QuantityDiscountRule((int)$quantityDiscountRuleAtCart['id_quantity_discount_rule']);
                    if (!$quantityDiscountRuleAtCartObj->compatibleCartRules()) {
                        QuantityDiscountRule::removeQuantityDiscountCartRule($quantityDiscountRuleAtCart['id_cart_rule'], (int)Context::getContext()->cart->id);
                    }
                }
            }
        }
        return $result;
    }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:58
    * version: 2.1.37
    */
    public function getCartRules($filter = CartRule::FILTER_ACTION_ALL, $autoAdd = true, $useOrderPrices = false)
    {
        $cartRules = parent::getCartRules($filter, $autoAdd, $useOrderPrices);
        if (Module::isEnabled('quantitydiscountpro')) {
            include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
            foreach ($cartRules as &$cartRule) {
                if (QuantityDiscountRule::isQuantityDiscountRule($cartRule['id_cart_rule'])
                    && !QuantityDiscountRule::isQuantityDiscountRuleWithCode($cartRule['id_cart_rule'])) {
                    $cartRule['code'] = '';
                }
            }
            unset($cartRule);
        }
        return $cartRules;
    }
    public function canApplyCartRule() {
        $cart_rules = $this->getCartRules();
        if ($cart_rules) {
            foreach ($cart_rules as $cart_rule) {
                if (!empty($cart_rule['code'])) {
                    return false;
                }
            }
        }
        return true;
    }
    public function getProductsFitting() {
        $product_fitting = false;
        $products = $this->getProducts();
        foreach ($products as $product) {
            if (Product::esFitting($product['id_product'])) {
                $product_fitting[(int) $product['id_product']] = $product;
                $product_fitting[(int) $product['id_product']]['attributes_array_custom'] = Product::getAttributesArray((int) $product['id_product'], (int) $product['id_product_attribute'], Context::getContext()->language->id, Context::getContext()->shop->id);
                $product_fitting[(int) $product['id_product']]['fitting_day'] = '';
                $product_fitting[(int) $product['id_product']]['fitting_hour'] = '';
                $product_fitting[(int) $product['id_product']]['fitting_location'] = '';
                if ($product_fitting[(int) $product['id_product']]['attributes_array_custom'] && count($product_fitting[(int) $product['id_product']]['attributes_array_custom']) >= 2) {
                    $product_fitting[(int) $product['id_product']]['fitting_day'] = $product_fitting[(int) $product['id_product']]['attributes_array_custom'][0]['attribute_name'];
                    $product_fitting[(int) $product['id_product']]['fitting_hour'] = $product_fitting[(int) $product['id_product']]['attributes_array_custom'][1]['attribute_name'];
                }
                if (Configuration::get('BAN_PRODUCT_FEATURE_ID_LOCATION') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_ID_LOCATION'))) {
                    $id_feature_location = (int) Configuration::get('BAN_PRODUCT_FEATURE_ID_LOCATION');
                    $features = Product::getFrontFeaturesStatic(1, $product['id_product']);
                    if ($features) {
                        foreach ($features as $feature) {
                            if ((int) $feature['id_feature'] == $id_feature_location) {
                                $product_fitting[(int) $product['id_product']]['fitting_location'] = $feature['value'];
                            }
                        }
                    }
                }
            }
        }
        return $product_fitting;
    }
    public function getProductsLicenceCaza() {
        $product_licence = false;
        $products = $this->getProducts();
        foreach ($products as $product) {
            if (Configuration::get('BAN_PRODUCT_FEATURE_ID_LICENCE_TYPE') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_ID_LICENCE_TYPE')) && Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LICENCE_TYPE_CAZA') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LICENCE_TYPE_CAZA'))) {
                $features_value = Product::getFeaturesStatic((int) $product['id_product']);
                if ($features_value) {
                    foreach ($features_value as $feature_value) {
                        if ((int) $feature_value['id_feature'] == (int) Configuration::get('BAN_PRODUCT_FEATURE_ID_LICENCE_TYPE') && (int) $feature_value['id_feature_value'] == (int) Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LICENCE_TYPE_CAZA')) {
                            $product_licence[(int) $product['id_product']] = $product;
                        }
                    }
                }
            }
        }
        return $product_licence;
    }
    public function getProductsLicencePesca() {
        $product_licence = false;
        $products = $this->getProducts();
        foreach ($products as $product) {
            if (Configuration::get('BAN_PRODUCT_FEATURE_ID_LICENCE_TYPE') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_ID_LICENCE_TYPE')) && Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LICENCE_TYPE_PESCA') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LICENCE_TYPE_PESCA'))) {
                $features_value = Product::getFeaturesStatic((int) $product['id_product']);
                if ($features_value) {
                    foreach ($features_value as $feature_value) {
                        if ((int) $feature_value['id_feature'] == (int) Configuration::get('BAN_PRODUCT_FEATURE_ID_LICENCE_TYPE') && (int) $feature_value['id_feature_value'] == (int) Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LICENCE_TYPE_PESCA')) {
                            $product_licence[(int) $product['id_product']] = $product;
                        }
                    }
                }
            }
        }
        return $product_licence;
    }
    public function isVirtualCart()
    {
        if (isset(self::$_isVirtualCart[$this->id])) {
            return self::$_isVirtualCart[$this->id];
        }
        $is_virtual_cart = false;
        $products_list = $this->getProducts();
        if ($products_list && count($products_list) > 0) {
            $is_virtual_cart = true; // Asumimos que es virtual hasta encontrar un producto físico
            foreach ($products_list as $product) {
                $product_properties = Product::getProductProperties(
                    (int)Context::getContext()->language->id,
                    $product,
                    Context::getContext()
                );
                $is_fitting = isset($product_properties['fittings']) && $product_properties['fittings'] == 1;
                $is_gift_card = isset($product_properties['card']) && $product_properties['card'] == 1;
                $is_licence_service = isset($product_properties['view']) &&  $product_properties['view'] == 'licence';
                $is_prestashop_virtual = isset($product_properties['is_virtual']) &&  $product_properties['is_virtual'] == 1;
                $is_licence_feature = false;
                if (Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE') &&
                    Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_LICENCE')) {
                    $id_feature_product_type = (int) Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE');
                    $licence_feature_values = explode(',', Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_LICENCE'));
                    if (isset($product_properties['features']) && $product_properties['features']) {
                        foreach ($product_properties['features'] as $feature) {
                            if ((int) $feature['id_feature'] == $id_feature_product_type &&
                                in_array($feature['id_feature_value'], $licence_feature_values)) {
                                $is_licence_feature = true;
                                break;
                            }
                        }
                    }
                }
                $is_current_product_virtual = $is_fitting || $is_gift_card || $is_licence_service || $is_licence_feature || $is_prestashop_virtual;
                if (!$is_current_product_virtual) {
                    $is_virtual_cart = false;
                    break; // Salir inmediatamente - un solo producto físico hace el carrito físico
                }
            }
        }
        self::$_isVirtualCart[$this->id] = $is_virtual_cart;
        if ($is_virtual_cart) {
            return true;
        } else {
            return parent::isVirtualCart();
        }
    }
    public function updateQty(
        $quantity,
        $id_product,
        $id_product_attribute = null,
        $id_customization = false,
        $operator = 'up',
        $id_address_delivery = 0,
        Shop $shop = null,
        $auto_add_cart_rule = true,
        $skipAvailabilityCheckOutOfStock = false,
        bool $preserveGiftRemoval = true,
        bool $useOrderPrices = false,
        $precio = 0
    ) {
        if (!$shop) {
            $shop = Context::getContext()->shop;
        }
        if (Validate::isLoadedObject(Context::getContext()->customer)) {
            if ($id_address_delivery == 0 && (int) $this->id_address_delivery) {
                $id_address_delivery = $this->id_address_delivery;
            } elseif ($id_address_delivery == 0) {
                $id_address_delivery = (int) Address::getFirstCustomerAddressId(
                    (int) Context::getContext()->customer->id
                );
            } elseif (!Customer::customerHasAddress(Context::getContext()->customer->id, $id_address_delivery)) {
                $id_address_delivery = 0;
            }
        } else {
            $id_address_delivery = 0;
        }
        $quantity = (int) $quantity;
        $id_product = (int) $id_product;
        $id_product_attribute = (int) $id_product_attribute;
        $product = new Product($id_product, false, Configuration::get('PS_LANG_DEFAULT'), $shop->id);
        if ($id_product_attribute) {
            $combination = new Combination((int) $id_product_attribute);
            if ($combination->id_product != $id_product) {
                return false;
            }
        }
        if (!empty($id_product_attribute)) {
            $minimal_quantity = (int) Attribute::getAttributeMinimalQty($id_product_attribute);
        } else {
            $minimal_quantity = (int) $product->minimal_quantity;
        }
        if (!Validate::isLoadedObject($product)) {
            die(Tools::displayError());
        }
        if (isset(self::$_nbProducts[$this->id])) {
            unset(self::$_nbProducts[$this->id]);
        }
        if (isset(self::$_totalWeight[$this->id])) {
            unset(self::$_totalWeight[$this->id]);
        }
        $data = [
            'cart' => $this,
            'product' => $product,
            'id_product_attribute' => $id_product_attribute,
            'id_customization' => $id_customization,
            'quantity' => $quantity,
            'operator' => $operator,
            'id_address_delivery' => $id_address_delivery,
            'shop' => $shop,
            'auto_add_cart_rule' => $auto_add_cart_rule,
        ];
        Hook::exec('actionCartUpdateQuantityBefore', $data);
        if ((int) $quantity <= 0) {
            return $this->deleteProduct($id_product, $id_product_attribute, (int) $id_customization, (int) $id_address_delivery, $preserveGiftRemoval, $useOrderPrices);
        }
        if (!$product->available_for_order
            || (
                Configuration::isCatalogMode()
                && !defined('_PS_ADMIN_DIR_')
            )
        ) {
            return false;
        }
        $cartProductQuantity = $this->getProductQuantity(
            $id_product,
            $id_product_attribute,
            (int) $id_customization,
            (int) $id_address_delivery
        );
        if (!empty($cartProductQuantity['quantity'])) {
            $productQuantity = Product::getQuantity($id_product, $id_product_attribute, null, $this);
            $availableOutOfStock = Product::isAvailableWhenOutOfStock(StockAvailable::outOfStock($product->id));
            if ($operator == 'up') {
                $updateQuantity = '+ ' . $quantity;
                $newProductQuantity = $productQuantity - $quantity;
                if ($newProductQuantity < 0 && !$availableOutOfStock && !$skipAvailabilityCheckOutOfStock) {
                    return false;
                }
            } elseif ($operator == 'down') {
                $cartFirstLevelProductQuantity = $this->getProductQuantity(
                    (int) $id_product,
                    (int) $id_product_attribute,
                    $id_customization
                );
                $updateQuantity = '- ' . $quantity;
                if ($cartFirstLevelProductQuantity['quantity'] <= 1
                    || $cartProductQuantity['quantity'] - $quantity <= 0
                ) {
                    return $this->deleteProduct((int) $id_product, (int) $id_product_attribute, (int) $id_customization, (int) $id_address_delivery, $preserveGiftRemoval, $useOrderPrices);
                }
            } else {
                return false;
            }
            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'cart_product`
                    SET `quantity` = `quantity` ' . $updateQuantity . '
                    WHERE `id_product` = ' . (int) $id_product .
                ' AND `id_customization` = ' . (int) $id_customization .
                (!empty($id_product_attribute) ? ' AND `id_product_attribute` = ' . (int) $id_product_attribute : '') . '
                    AND `id_cart` = ' . (int) $this->id . (Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery() ? ' AND `id_address_delivery` = ' . (int) $id_address_delivery : '') . '
                    LIMIT 1'
            );
        } elseif ($operator == 'up') {
            $sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity
                        FROM ' . _DB_PREFIX_ . 'product p
                        ' . Product::sqlStock('p', $id_product_attribute, true, $shop) . '
                        WHERE p.id_product = ' . $id_product;
            $result2 = Db::getInstance()->getRow($sql);
            if (Pack::isPack($id_product)) {
                $result2['quantity'] = Pack::getQuantity($id_product, $id_product_attribute, null, $this);
            }
            if (!Product::isAvailableWhenOutOfStock((int) $result2['out_of_stock']) && !$skipAvailabilityCheckOutOfStock) {
                if ((int) $quantity > $result2['quantity']) {
                    return false;
                }
            }
            if ((int) $quantity < $minimal_quantity) {
                return -1;
            }
            $result_add = Db::getInstance()->insert('cart_product', [
                'id_product' => (int) $id_product,
                'id_product_attribute' => (int) $id_product_attribute,
                'id_cart' => (int) $this->id,
                'id_address_delivery' => (int) $id_address_delivery,
                'id_shop' => $shop->id,
                'quantity' => (int) $quantity,
                'date_add' => date('Y-m-d H:i:s'),
                'id_customization' => (int) $id_customization,
            ]);
            $logger = new FileLogger(0);
            $logger->setFilename(_PS_ROOT_DIR_."/log/debug.log");
                if ($result_add && Tools::getValue('current-price-value') > 0) {
                    $sql = "UPDATE `"._DB_PREFIX_."customized_data` SET `price`='".Tools::getValue('current-price-value')."' WHERE `id_customization` = ".$id_customization;
                    Db::getInstance()->execute($sql);
                }else if ($result_add && $precio > 0) {
                    $sql = "UPDATE `"._DB_PREFIX_."customized_data` SET `price`='".$precio."' WHERE `id_customization` = ".$id_customization;
                    Db::getInstance()->execute($sql);
                }
            if (!$result_add) {
                return false;
            }
        }
        $this->_products = $this->getProducts(true);
        $this->update();
        $context = Context::getContext()->cloneContext();
        $context->cart = $this;
        Cache::clean('getContextualValue_*');
        CartRule::autoRemoveFromCart(null, $useOrderPrices);
        if ($auto_add_cart_rule) {
            CartRule::autoAddToCart($context, $useOrderPrices);
        }
        if ($product->customizable) {
            return $this->_updateCustomizationQuantity(
                (int) $quantity,
                (int) $id_customization,
                (int) $id_product,
                (int) $id_product_attribute,
                (int) $id_address_delivery,
                $operator
            );
        }
        return true;
    }
    public static function haveMultipleProductTypes($id_cart) {
        $cart = new Cart($id_cart);
        $products_pickup_gc = [];
        $id_feature_product_type = 0;
        if (Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE'))) {
            $id_feature_product_type = (int) Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE');
        }
        $id_feature_value_product_type_pickup_gc = '';
        if (Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_PICKUP_GC')) {
            $id_feature_value_product_type_pickup_gc = Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_PICKUP_GC');
        }
        if ($id_feature_product_type && $id_feature_value_product_type_pickup_gc != '') {
            $product_list = $cart->getProducts();
            if ($product_list && count($product_list) > 1) { // lo comparo con > 1 porque si sólo hay uno es imposible que haya más de un tipo de producto
                foreach ($product_list as $product_cart) {
                    if ($product_cart['features']) {
                        foreach ($product_cart['features'] as $feature) {
                            if ((int) $feature['id_feature'] == $id_feature_product_type) {
                                if (str_contains(','.$id_feature_value_product_type_pickup_gc.',', ','.$feature['id_feature_value'].',')) {
                                    $products_pickup_gc[] = $product_cart;
                                }
                            }
                        }
                    }
                }
            }
            if (count($products_pickup_gc) > 0 && count($products_pickup_gc) != count($product_list)) {
                return $products_pickup_gc;
            }
        }
        return false;
    }
    /*
    * module: idxrcustomproduct
    * date: 2025-10-07 10:54:09
    * version: 1.8.4
    */
    public function duplicate()
    {
        $new_cart = parent::duplicate();
        if ((bool) Module::isEnabled('idxrcustomproduct')) {
            $new_id = false;
            if (is_object($new_cart)) {
                $new_id = $new_cart->id;
            }
            if (is_array($new_cart) && isset($new_cart['success']) && $new_cart['success']) {
                $new_id = $new_cart['cart']->id;
            }
            if ($new_id) {
                $module = Module::getInstanceByName('idxrcustomproduct');
                $module->duplicateCartInfo($this->id, $new_id);
            }
        }
        return $new_cart;
    }
}
