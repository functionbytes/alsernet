<?php

class DeliveryOptionsFinder extends DeliveryOptionsFinderCore
{

    public function getDeliveryOptions()
    {
        $carriers_available = parent::getDeliveryOptions();

        if (Module::isEnabled('kbgcstorelocatorpickup')) {
            $id_carrier_pickup_gc = 0;
            $id_feature_product_type = 0;
            $id_feature_value_product_type_pickup_gc = '';

            if (Configuration::get('KB_GC_PICKUP_AT_STORE_SHIPPING') && is_numeric(Configuration::get('KB_GC_PICKUP_AT_STORE_SHIPPING'))) {
                $id_carrier_pickup_gc = (int)Configuration::get('KB_GC_PICKUP_AT_STORE_SHIPPING');
            }

            if (Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE'))) {
                $id_feature_product_type = (int)Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE');
            }

            if (Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_PICKUP_GC')) {
                $id_feature_value_product_type_pickup_gc = Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_PICKUP_GC');
            }

            if ($id_carrier_pickup_gc && $id_feature_product_type && $id_feature_value_product_type_pickup_gc) {
                $is_pickup_gc = false;

                $cart = Context::getContext()->cart;
                if ($cart) {
                    $products_list = $cart->getProducts();
                    if ($products_list) {
                        foreach ($products_list as $key => $product) {
                            if ($product['features']) {
                                foreach ($product['features'] as $feature) {
                                    if ((int)$feature['id_feature'] == $id_feature_product_type) {
                                        if (strpos(',' . $id_feature_value_product_type_pickup_gc . ',', ',' . $feature['id_feature_value'] . ',') !== false) {
                                            $is_pickup_gc = true;
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                foreach ($carriers_available as $key => $carrier) {
                    if (strpos(',' . $key . ',', ',' . $id_carrier_pickup_gc . ',') !== false) {
                        if (!$is_pickup_gc) {
                            unset($carriers_available[$key]);
                        }
                    } else {
                        if ($is_pickup_gc) {
                            unset($carriers_available[$key]);
                        }
                    }
                }
            }
        }

        $carrier_selected = Context::getContext()->cart->getDeliveryOption(null, false, true);
        $is_carrier_selected_in_list = false;
        foreach ($carrier_selected as $id_carrier) {
            foreach ($carriers_available as $key => $value) {
                if ($id_carrier == $key) {
                    $is_carrier_selected_in_list = true;
                }
            }
        }

        if (!$is_carrier_selected_in_list) {
            $carriers_available_cart = [];
            foreach ($carriers_available as $key => $value) {
                $carriers_available_cart[Context::getContext()->cart->id_address_delivery] = $key;
                //$carriers_available_cart[Context::getContext()->cart->id_address_delivery][$key] = $value;
                break;
            }

            Context::getContext()->cart->setDeliveryOption($carriers_available_cart);
            Context::getContext()->cart->update();
        }

        return $carriers_available;
    }
}
