<?php

use PrestaShop\PrestaShop\Adapter\Cart\CartPresenter;

class CartController extends CartControllerCore
{

    // public function init()
    // {
    //     parent::init();
    //     if (Tools::getValue('add')) {
    //         require_once _PS_MODULE_DIR_ . 'wkbundleproduct/wkbundleproduct.php';
    //         $objBundle = new WkBundle();
    //         $this->id_product = (int) Tools::getValue('id_product', null);
    //         $isBundle = $objBundle->isBundleProduct(
    //             $this->id_product
    //         );
    //         if ($isBundle) {
    //             if (!$this->context->cart->id && isset($_COOKIE[$this->context->cookie->getName()])) {
    //                 $this->context->cart->add();
    //                 $this->context->cookie->id_cart = (int) $this->context->cart->id;
    //             }
    //             $objFinalCart = new WkBundleCartDataFinal();
    //             $tempData = $objFinalCart->getTempCartData(
    //                 Tools::getValue('id_product'),
    //                 $this->context->cookie->id_wk_bundle_identifier,
    //                 $this->context->shop->id
    //             );
    //             $isSameBundle = $objFinalCart->checkBundleAreSame($this->context->cart, $tempData);
    //             if ($isSameBundle) {
    //                 $this->customization_id = $isSameBundle;
    //                 $this->context->cookie->wk_id_customization = $isSameBundle;
    //             } else {
    //                 $this->textRecord(new Product($this->id_product));
    //                 $customization_datas = $this->context->cart->getProductCustomization($this->id_product, null, true);
    //                 $this->customization_id = empty($customization_datas) ? null :
    //                     $customization_datas[0]['id_customization'];
    //                 $this->context->cookie->wk_id_customization = $this->customization_id;
    //                 if (!empty($tempData)) {
    //                     foreach ($tempData as $data) {
    //                         $data['id_customization'] = $this->customization_id;
    //                         $data['id_cart'] = $this->context->cart->id;
    //                         $objFinalCart->insertDataTempToFinal($data);
    //                     }
    //                 }
    //             }
    //             $this->context->cookie->write();
    //         }
    //     }
    // }


    public function displayAjaxRefresh()
    {
        if (Configuration::isCatalogMode()) {
            return;
        }

        ob_end_clean();
        header('Content-Type: application/json');
        $this->ajaxRender(Tools::jsonEncode([
            'cart_detailed' => $this->render('checkout/_partials/cart-detailed'),
            'cart_detailed_totals' => $this->render('checkout/_partials/cart-detailed-totals'),
            'cart_summary_items_subtotal' => $this->render('checkout/_partials/cart-summary-items-subtotal'),
            'cart_summary_products' => $this->render('checkout/_partials/cart-summary-products'),
            'cart_summary_subtotals_container' => $this->render('checkout/_partials/cart-summary-subtotals'),
            'cart_summary_totals' => $this->render('checkout/_partials/cart-summary-totals'),
            'cart_detailed_actions' => $this->render('checkout/_partials/cart-detailed-actions'),
            'cart_voucher' => $this->render('checkout/_partials/cart-voucher'),
            'cart_summary_top' => $this->render('checkout/_partials/cart-summary-top'),
            'cart_related' => $this->render('checkout/_partials/cart-related'),
        ]));
    }


    public function initContent()
    {

        parent::initContent();

        if (Configuration::isCatalogMode() && Tools::getValue('action') === 'show') {
            Tools::redirect('index.php');
        }

        $presenter = new CartPresenter();
        $presented_cart = $presenter->present($this->context->cart, $shouldSeparateGifts = true,$this->context->language->id);


        $this->context->smarty->assign([
            'PS_CONDITIONS_CMS_ID' => Configuration::get('PS_CONDITIONS_CMS_ID'),
            'id_lang' => $this->context->language->id,
            'cart' => $presented_cart,
            'static_token' => Tools::getToken(false),
        ]);

        $suma_descuentos = 0;
        foreach ($presented_cart['products'] as $producto) {
            $suma_descuentos += $producto['quantity_wanted']*(float)$producto['reduction'];
        }
        $this->context->smarty->assign([
            'suma_descuentos' => $suma_descuentos,
        ]);
        if (!Context::getContext()->cookie->id_cart || !$this->context->cart->id) {
            AddisLogger::log(__FILE__, __FUNCTION__, null, 'No tengo ID de carrito en la cookie');
            if (Context::getContext()->customer->logged) {
                $sql = "SELECT id_cart FROM "._DB_PREFIX_."cart WHERE id_customer = ".Context::getContext()->customer->id." ORDER BY id_cart DESC";
                AddisLogger::log(__FILE__, __FUNCTION__, null, 'SQL: '.$sql);
                $id_carrito_recu = Db::getInstance()->getValue($sql);
            } else {
                if (Context::getContext()->cookie->id_guest) {
                    $guest = new Guest(Context::getContext()->cookie->id_guest);
                    $this->context->cart->mobile_theme = $guest->mobile_theme;
                    $sql = "SELECT id_cart FROM "._DB_PREFIX_."cart WHERE id_guest = ".Context::getContext()->cookie->id_guest." ORDER BY id_cart DESC";
                    $id_carrito_recu = Db::getInstance()->getValue($sql);
                }
            }
            if (isset($id_carrito_recu)) {
                Context::getContext()->cart = new Cart($id_carrito_recu);
                Context::getContext()->cookie->id_cart = $id_carrito_recu;
            }
        }
        // if (Tools::getValue('action') != 'update') {
        //     $checkMinimumQtyMunition = $this->checkMinimumQtyMunition(null);
        //     if ($checkMinimumQtyMunition) {
        //         $this->errors[] = $checkMinimumQtyMunition;
        //     }
        // }

        if (count($presented_cart['products']) > 0) {
            $this->setTemplate('checkout/cart');
        } else {
            $this->context->smarty->assign([
                'allProductsLink' => $this->context->link->getCategoryLink(Configuration::get('PS_HOME_CATEGORY')),
            ]);
            $this->setTemplate('checkout/cart-empty');
        }
    }


    public function postProcess()
    {
        // if (Tools::getValue('ajax') && Tools::getValue('action') == 'selectTarifaPlana') {
        //     $id_product = Tools::getValue('product_tarifa_plana');
        //     $id_product_attribute = Tools::getValue('product_attribute_tarifa_plana');
        //     $customization_id = 0;
        //     $qty = 1;
        //     $add_tariaplana = Tools::getvalue('add_tarifaplana');
        //     if ($id_product) {
        //         if ($add_tariaplana) {
        //             if (!$this->context->cart->containsProduct((int) $id_product, (int) $id_product_attribute, (int) $customization_id)) {
        //                 $this->context->cart->updateQty($qty, (int) $id_product, (int) $id_product_attribute, (int) $customization_id, $operator = 'up', $this->context->cart->id_address_delivery, $shop = null, $auto_add_cart_rule = true);
        //             }
        //         } else {
        //             if ($this->context->cart->containsProduct((int) $id_product, (int) $id_product_attribute, (int) $customization_id)) {
        //                 $this->context->cart->deleteProduct((int) $id_product, (int) $id_product_attribute, (int) $customization_id, $this->context->cart->id_address_delivery);
        //             }
        //         }
        //     }
        // }
        if (Tools::getValue('action') == 'show') {
            $products = $this->context->cart->getProducts();
            foreach ($products as $product) {
                $prod_obj = new Product((int) $product['id_product'], true, $this->context->language->id);
                if ($this->customShouldAvailabilityErrorBeRaised($prod_obj, (int) $product['id_product_attribute'], $product['id_customization'], $product['quantity'])) {
                    $this->errors[] = $this->trans('The product is no longer available in this quantity. Prod: %product%', ['%product%' => $product['name']], 'Shop.Notifications.Error');
                }
            }
        }
        parent::postProcess();
    }
    protected function customShouldAvailabilityErrorBeRaised($product, $id_product_attribute, $customization_id, $qtyToCheck)
    {
        if (($id_product_attribute)) {
            return !Product::isAvailableWhenOutOfStock($product->out_of_stock)
                && !Attribute::checkAttributeQty($id_product_attribute, $qtyToCheck);
        } elseif (Product::isAvailableWhenOutOfStock($product->out_of_stock)) {
            return false;
        }
        $availableProductQuantity = StockAvailable::getQuantityAvailableByProduct(
            $product->id,
            $id_product_attribute
        );
        if ($availableProductQuantity <= 0) {
            return true;
        }
        $productQuantityAvailableAfterCartItemsHaveBeenRemovedFromStock = Product::getQuantity(
            $product->id,
            $id_product_attribute,
            null,
            $this->context->cart,
            $customization_id
        );
        return $productQuantityAvailableAfterCartItemsHaveBeenRemovedFromStock < 0;
    }
    protected function processChangeProductInCart()
    {
        $mode = (Tools::getIsset('update') && $this->id_product) ? 'update' : 'add';
        $ErrorKey = ('update' === $mode) ? 'updateOperationError' : 'errors';
        $logger = new FileLogger(0);
        $logger->setFilename(_PS_ROOT_DIR_."/log/debug.log");
        if(Tools::getValue('tr') == 1 || Tools::getValue('tipo_tr') == 1){
            if (!isset($this->context->cart->id) || !$this->context->cart->id) {
                $this->context->cart = new Cart();
                $this->context->cart->id_lang = $this->context->cookie->id_lang;
                $this->context->cart->id_currency = $this->context->cookie->id_currency;
                $this->context->cart->id_customer = $this->context->cookie->id_customer;
                $this->context->cart->id_guest = $this->context->cookie->id_guest;
                $this->context->cart->add();
                $this->context->cookie->__set('id_cart', $this->context->cart->id);
                $this->context->cookie->write();
                $this->customization_id = ProductController::textRecordTr(new Product($this->id_product), $this->context->cart);
            }else{
                $this->customization_id = ProductController::textRecordTr(new Product($this->id_product), $this->context->cart);
            }
            if ($this->customization_id['9'] == 'nombre_incorrecto') {
                $this->errors[] = 'nombre_incorrecto';
            }
            if ($this->customization_id['10'] == 'apellidos_incorrecto') {
                $this->errors[] = 'apellidos_incorrecto';
            }
            if ($this->customization_id['11'] == 'importe_incorrecto') {
                $this->errors[] = 'importe_incorrecto';
            }
            if ($this->customization_id['12'] == 'email_incorrecto') {
                $this->errors[] = 'email_incorrecto';
            }
            if ($this->customization_id['13'] == 'msg_incorrecto') {
                $this->errors[] = 'msg_incorrecto';
            }
            if ($this->errors) {
                return $this->errors;
            }
        }
        if (Tools::getIsset('group')) {
            $this->id_product_attribute = (int) Product::getIdProductAttributeByIdAttributes(
                $this->id_product,
                Tools::getValue('group')
            );
        }
        if ($this->qty == 0) {
            $this->{$ErrorKey}[] = $this->trans(
                'Null quantity.',
                [],
                'Shop.Notifications.Error'
            );
        } elseif (!$this->id_product) {
            $this->{$ErrorKey}[] = $this->trans(
                'Product not found',
                [],
                'Shop.Notifications.Error'
            );
        }
        $product = new Product($this->id_product, true, $this->context->language->id);
        if (!$product->id || !$product->active || !$product->checkAccess($this->context->cart->id_customer)) {
            $this->{$ErrorKey}[] = $this->trans(
                'This product (%product%) is no longer available.',
                ['%product%' => $product->name],
                'Shop.Notifications.Error'
            );
            return;
        }
        if (!$this->id_product_attribute && $product->hasAttributes()) {
            $minimum_quantity = ($product->out_of_stock == 2)
                ? !Configuration::get('PS_ORDER_OUT_OF_STOCK')
                : !$product->out_of_stock;
            $this->id_product_attribute = Product::getDefaultAttribute($product->id, $minimum_quantity);
            if (!$this->id_product_attribute) {
                Tools::redirectAdmin($this->context->link->getProductLink($product));
            }
        }
        $qty_to_check = $this->qty;
        $cart_products = $this->context->cart->getProducts();
        if (is_array($cart_products)) {
            foreach ($cart_products as $cart_product) {
                if ($this->productInCartMatchesCriteria($cart_product)) {
                    $qty_to_check = $cart_product['cart_quantity'];
                    if (Tools::getValue('op', 'up') == 'down') {
                        $qty_to_check -= $this->qty;
                    } else {
                        $qty_to_check += $this->qty;
                    }
                    break;
                }
            }
        }


        if ('update' !== $mode && $this->shouldAvailabilityErrorBeRaised($product, $qty_to_check)) {
            $this->{$ErrorKey}[] = $this->trans(
                'The product is no longer available in this quantity. Prod: %product%',
                ['%product%' => $product->name],
                'Shop.Notifications.Error'
            );
        }
        if (!$this->id_product_attribute) {
            if ($qty_to_check < $product->minimal_quantity) {
                $this->errors[] = $this->trans(
                    'The minimum purchase order quantity for the product %product% is %quantity%.',
                    ['%product%' => $product->name, '%quantity%' => $product->minimal_quantity],
                    'Shop.Notifications.Error'
                );
                return;
            }
        } else {
            $combination = new Combination($this->id_product_attribute);
            if ($qty_to_check < $combination->minimal_quantity) {
                $this->errors[] = $this->trans(
                    'The minimum purchase order quantity for the product %product% is %quantity%.',
                    ['%product%' => $product->name, '%quantity%' => $combination->minimal_quantity],
                    'Shop.Notifications.Error'
                );
                return;
            }
        }
        if (!$this->errors) {
            if (!$this->context->cart->id) {
                if (Context::getContext()->cookie->id_guest) {
                    $guest = new Guest(Context::getContext()->cookie->id_guest);
                    $this->context->cart->mobile_theme = $guest->mobile_theme;
                }
                $this->context->cart->add();
                if ($this->context->cart->id) {
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
            }
            if (!$product->hasAllRequiredCustomizableFields() && !$this->customization_id) {
                $this->{$ErrorKey}[] = $this->trans(
                    'Please fill in all of the required fields, and then save your customizations.',
                    [],
                    'Shop.Notifications.Error'
                );
            }
            if ($product->price == 0 && (Tools::getValue('tr') != 1 || Tools::getValue('tipo_tr') != 1)) {
                $this->errors[] = 'precio_0';
                $this->errors[] = $this->trans(
                    'This product is not available.',
                    [],
                    'Shop.Notifications.Error'
                );
                $this->context->cart->deleteProduct($product->id, $this->id_product_attribute, $this->customization_id);
            }
            if (!$this->errors) {



                $precio = 0;
                if (Tools::getValue('current-price-value') > 0) {
                    $precio = Tools::getValue('current-price-value');
                }
                $update_quantity = $this->context->cart->updateQty(
                    $this->qty,
                    $this->id_product,
                    $this->id_product_attribute,
                    $this->customization_id,
                    Tools::getValue('op', 'up'),
                    $this->id_address_delivery,
                    null,
                    true,
                    true,
                    $precio
                );



                if ($update_quantity < 0) {
                    $minimal_quantity = ($this->id_product_attribute)
                        ? Attribute::getAttributeMinimalQty($this->id_product_attribute)
                        : $product->minimal_quantity;
                    $this->{$ErrorKey}[] = $this->trans(
                        'You must add %quantity% minimum quantity',
                        ['%quantity%' => $minimal_quantity],
                        'Shop.Notifications.Error'
                    );
                } elseif (!$update_quantity) {
                    $this->errors[] = $this->trans(
                        'You already have the maximum quantity available for this product. Prod: %product%',
                        ['%product%' => $product->name, '%quantity%' => $combination->minimal_quantity],
                        'Shop.Notifications.Error'
                    );
                } elseif ($this->shouldAvailabilityErrorBeRaised($product, $qty_to_check)) {
                    $this->{$ErrorKey}[] = $this->trans(
                        'The product is no longer available in this quantity. Prod: %product%',
                        ['%product%' => $product->name],
                        'Shop.Notifications.Error'
                    );
                }
                // if (Tools::getIsset('op')) { // que solo me lo compruebe cuando estoy en el carrito
                //     $checkMinimumQtyMunition = $this->checkMinimumQtyMunition(null);
                //     if ($checkMinimumQtyMunition) {
                //         $this->{$ErrorKey}[] = $checkMinimumQtyMunition;
                //     }
                // }
            }
        }


        $removed = CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
    }
    protected function updateCart()
    {

        if ($this->context->cookie->exists()
            && !$this->errors
            && !($this->context->customer->isLogged() && !$this->isTokenValid())
        ) {
            if (Tools::getIsset('add') || Tools::getIsset('update')) {
                $this->processChangeProductInCart();
            } elseif (Tools::getIsset('delete')) {
                $this->processDeleteProductInCart();
            } elseif (CartRule::isFeatureActive()) {
                if (Tools::getIsset('addDiscount') || Tools::getIsset('searchcoupon')) {
                    if (!($code = trim((Tools::getValue('discount_name') ? Tools::getValue('discount_name') : Tools::getValue('coupon'))))) {
                        $this->errors[] = $this->trans(
                            'You must enter a voucher code.',
                            [],
                            'Shop.Notifications.Error'
                        );
                    }
                    if ($this->context->cart->canApplyCartRule()) {
                        if (!($verifcode = trim(Tools::getValue('verif_name')))) {
                            if (!Validate::isCleanHtml($code)) {
                                $this->errors[] = $this->trans(
                                    'The voucher code is invalid.',
                                    [],
                                    'Shop.Notifications.Error'
                                );
                            } else {
                                if (Module::isEnabled('quantitydiscountpro')) {
                                    include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
                                    $quantityDiscount = new QuantityDiscountRule();
                                    if (($quantityDiscount = new quantityDiscountRule(QuantityDiscountRule::getQuantityDiscountRuleByCode($code))) && Validate::isLoadedObject($quantityDiscount)) {
                                        if ($quantityDiscount->createAndRemoveRules($code) !== true) {
                                            $this->errors[] = $this->trans('The voucher code is invalid.', array(), 'Shop.Notifications.Error');
                                        }
                                    } elseif (($cartRule = new CartRule(CartRule::getIdByCode($code))) && Validate::isLoadedObject($cartRule)) {
                                        if ($quantityDiscount->cartRuleGeneratedByAQuantityDiscountRuleCode($code)) {
                                            $this->errors[] = $this->trans('The voucher code is invalid.', array(), 'Shop.Notifications.Error');
                                        } elseif ($error = $cartRule->checkValidity($this->context, false, true)) {
                                            $this->errors[] = $error;
                                        } else {
                                            $this->context->cart->addCartRule($cartRule->id);
                                        }
                                    } else {
                                        $this->errors[] = $this->trans('This voucher does not exist.', array(), 'Shop.Notifications.Error');
                                    }
                                } else {
                                    if (($cartRule = new CartRule(CartRule::getIdByCode($code)))
                                        && Validate::isLoadedObject($cartRule)
                                    ) {
                                        if ($error = $cartRule->checkValidity($this->context, false, true)) {
                                            $this->errors[] = $error;
                                        } else {
                                            $this->context->cart->addCartRule($cartRule->id);
                                        }
                                    } else {
                                        $this->errors[] = $this->trans(
                                            'This voucher does not exist.',
                                            [],
                                            'Shop.Notifications.Error'
                                        );
                                    }
                                }
                            }
                        } else { // SI ME RELLENAN EL CODIGO DE VERIFICACION LO COMPRUEBO EN EL ERP
                            $cart_total = $this->context->cart->getOrderTotal();
                            $bono = AlvarezERP::consultabono($code, $verifcode, $cart_total, AlvarezERP::BONO_ORIGEN_WEB);

                            if ($bono) {
                                if ($bono['success']) {
                                    $general_error = false;
                                    $estado_extendido = 0;
                                    $importe_cupon = 0;
                                    $importe_minimo = 0;
                                    if (isset($bono['data']) && $bono['data']) {
                                        if (isset($bono['data']['estado_extendido']) && is_numeric($bono['data']['estado_extendido'])) {
                                            $estado_extendido = (int) $bono['data']['estado_extendido'];
                                            switch ($estado_extendido) {
                                                case 0: //cupon anulado
                                                    $this->errors[] = $this->trans('This voucher is disabled', [], 'Shop.Notifications.Error');
                                                    break;
                                                case 1: // OK
                                                    $otherCartRules = $this->context->cart->getCartRules(CartRule::FILTER_ACTION_ALL, false);
                                                    if (count($otherCartRules)) {
                                                        foreach ($otherCartRules as $otherCartRule) {
                                                            if ($otherCartRule['code'] == CartRule::getCartRuleCodeAlvarez($code, $verifcode, $this->context->cart->id)) {
                                                                $this->errors[] = $this->trans('This voucher is already in your cart', [], 'Shop.Notifications.Error');
                                                                break;
                                                            }
                                                        }
                                                        $this->errors[] = $this->trans(
                                                            'No more coupons can be applied.',
                                                            [],
                                                            'Shop.Notifications.Error'
                                                        );
                                                        break;
                                                    }
                                                    $bono_date_from = strtotime($bono['data']['fvalidez_desde'].' 00:00:00');
                                                    $bono_date_to = strtotime($bono['data']['fvalidez_hasta'].' 23:59:59');
                                                    if ($bono_date_from > time()) {
                                                        $this->errors[] = $this->trans('This voucher is not valid yet', [], 'Shop.Notifications.Error');
                                                        break;
                                                    }
                                                    if ($bono_date_to < time()) {
                                                        $this->errors[] = $this->trans('This voucher has expired', [], 'Shop.Notifications.Error');
                                                        break;
                                                    }
                                                    if (isset($bono['data']['importeminimoventa']) && is_numeric($bono['data']['importeminimoventa'])) {
                                                        $importe_minimo = (float) $bono['data']['importeminimoventa'];
                                                        if ($importe_minimo > $cart_total) {
                                                            $this->trans('You have not reached the minimum amount required to use this voucher', [], 'Shop.Notifications.Error');
                                                            break;
                                                        }
                                                    }
                                                    $canUse = true;
                                                    if (!empty($productsCart = $this->context->cart->getProducts())) {
                                                        foreach ($productsCart as $prod) {
                                                            if (!empty($prod['is_virtual'])) {
                                                                $canUse = false;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    if (!$canUse) {
                                                        $this->errors[] = $this->trans('La Lotería no permite descuentos' , [], 'Shop.Notifications.Error');
                                                    } elseif (isset($bono['data']['importe']) && is_numeric($bono['data']['importe'])) {
                                                        $importe_cupon = (float) $bono['data']['importe'];
                                                        $cartRule = CartRule::createCartRuleAlvarez($code, $verifcode, $importe_cupon, $cart_total, $importe_minimo, $bono['data'], $this->context);
                                                        if ($cartRule) {
                                                            $cartRule = new CartRule((int) $cartRule);
                                                            if ($cartRule && Validate::isLoadedObject($cartRule)) {
                                                                if ($error = $cartRule->checkValidity($this->context, false, true)) {
                                                                    $this->errors[] = $error;
                                                                } else {
                                                                    $this->context->cart->addCartRule($cartRule->id);
                                                                }
                                                            } else {
                                                                $general_error = true;
                                                            }
                                                        } else {
                                                            $general_error = true;
                                                        }
                                                    } else {
                                                        $general_error = true;
                                                    }
                                                    break;
                                                case 2: //cupon consumido
                                                    $this->errors[] = $this->trans('This voucher has already been used', [], 'Shop.Notifications.Error');
                                                    break;
                                                case 3: //cupon caducado
                                                    $this->errors[] = $this->trans('This voucher has expired', [], 'Shop.Notifications.Error');
                                                    break;
                                            }
                                        } else {
                                            $general_error = true;
                                        }
                                    } else {
                                        $general_error = true;
                                    }
                                    if ($general_error) {
                                        $this->errors[] = $this->trans(
                                            'The voucher code is invalid.',
                                            [],
                                            'Shop.Notifications.Error'
                                        );
                                    }
                                } else {
                                    if (empty($bono['message'])) {
                                        $this->errors[] = $this->trans(
                                            'The voucher code is invalid.',
                                            [],
                                            'Shop.Notifications.Error'
                                        );
                                    } else {
                                        if (str_contains($bono['message'], '404')) {
                                            $this->errors[] = $this->trans(
                                                'The voucher code is invalid.',
                                                [],
                                                'Shop.Notifications.Error'
                                            );
                                        } else {
                                            $this->errors[] = pSQL($bono['message'], false);
                                        }
                                    }
                                }
                            } else {
                                $this->errors[] = $this->trans(
                                    'This voucher does not exist.',
                                    [],
                                    'Shop.Notifications.Error'
                                );
                            }
                        }
                    } else {
                        $this->errors[] = $this->trans(
                            'No more coupons can be applied.',
                            [],
                            'Shop.Notifications.Error'
                        );
                    }
                } elseif (($id_cart_rule = (int) Tools::getValue('deleteDiscount'))
                    && Validate::isUnsignedId($id_cart_rule)
                ) {
                    $this->context->cart->removeCartRule($id_cart_rule);
                    CartRule::autoAddToCart($this->context);
                }
            }
        } elseif (!$this->isTokenValid() && Tools::getValue('action') !== 'show' && !Tools::getValue('ajax')) {
            Tools::redirect('index.php');
        }
    }

    protected function areProductsAvailable()
    {
        $products = $this->context->cart->getProducts();
        // $checkMinimumQtyMunition = $this->checkMinimumQtyMunition($products);
        // if ($checkMinimumQtyMunition) {
        //     return $checkMinimumQtyMunition;
        // }
        // if (Module::isEnabled('wkbundleproduct')) {
        //     require_once _PS_MODULE_DIR_ . 'wkbundleproduct/wkbundleproduct.php';
        //     if (!empty($products)) {
        //         $objBundle = new WkBundle();
        //         $objTempData = new WkBundleCartDataFinal();
        //         $objSubproduct = new WkBundleSubProduct();
        //         foreach ($products as $productList) {
        //             if ($objBundle->isBundleProduct($productList['id_product'])) {
        //                 $productIdArray = [];
        //                 $bundleProductInformation = $objTempData->getSelectedBundleProduct(
        //                     $productList['id_product'],
        //                     $this->context->cart->id,
        //                     $this->context->shop->id
        //                 );
        //                 if (!empty($bundleProductInformation)) {
        //                     foreach ($bundleProductInformation as $bundleInfo) {
        //                         $availQty = $objSubproduct->checkProductQuantity(
        //                             $bundleInfo['id_wk_bundle_section'],
        //                             $bundleInfo['id_product'],
        //                             $bundleInfo['id_product_attribute']
        //                         );
        //                         $availableStock = StockAvailable::getQuantityAvailableByProduct(
        //                             $bundleInfo['id_product'],
        //                             $bundleInfo['id_product_attribute'],
        //                             $this->context->shop->id
        //                         );
        //                         if ($availableStock
        //                                         >= ($productList['cart_quantity'] * $bundleInfo['product_qty'])
        //                         ) {
        //                             if ($availQty) {
        //                                 if ($availQty['quantity']
        //                                                 < ($productList['cart_quantity'] * $bundleInfo['product_qty'])
        //                                 ) {
        //                                     $productIdArray[] = $bundleInfo['id_product'];
        //                                 }
        //                             }
        //                         } else {
        //                             $productIdArray[] = $bundleInfo['id_product'];
        //                         }
        //                     }
        //                 } else {
        //                     $this->context->cart->deleteProduct($productList['id_product']);
        //                 }
        //                 if (!empty($productIdArray)) {
        //                     $nameArray = [];
        //                     foreach ($productIdArray as $product) {
        //                         $nameArray[] = Product::getProductName(
        //                             $product,
        //                             0,
        //                             $this->context->language->id
        //                         );
        //                     }
        //                     if ($nameArray) {
        //                         $nameArray = implode(',', $nameArray);
        //                     }

        //                     return $this->trans(
        //                         'Decrease bundle quantity some subproducts are out of stock. Product(s) are %product%',
        //                         ['%product%' => $nameArray],
        //                         'Shop.Notifications.Error'
        //                     );
        //                 }
        //             }
        //             if (Configuration::get('WK_BUNDLE_PRODUCT_RESERVED_QTY')) {
        //                 if ($objSubproduct->getAllAvailableProduct(0)) {
        //                     if (in_array(
        //                         $productList['id_product'],
        //                         $objSubproduct->getAllAvailableProduct(0)
        //                     )) {
        //                         $qty = $objSubproduct->getProductMaximumQuantity(
        //                             $productList['id_product'],
        //                             $productList['id_product_attribute']
        //                         );
        //                         if ($qty) {
        //                             if ($productList['cart_quantity'] > $qty) {
        //                                 return $this->trans(
        //                                     'Some Product(s) are out of stock',
        //                                     [],
        //                                     'Shop.Notifications.Error'
        //                                 );
        //                             }
        //                         } else {
        //                             return $this->trans(
        //                                 'Some Product(s) are out of stock',
        //                                 [],
        //                                 'Shop.Notifications.Error'
        //                             );
        //                         }
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }

        foreach ($products as $product) {
            $currentProduct = new Product();
            $currentProduct->hydrate($product);
            if ($currentProduct->hasAttributes() && $product['id_product_attribute'] === '0') {
                return $this->trans(
                   'The item %product% in your cart is now a product with attributes. Please delete it and choose one of its combinations to proceed with your order.',
                    ['%product%' => $product['name']],
                    'Shop.Notifications.Error'
                );
            }
        }
        $product = $this->context->cart->checkQuantities(true);
        if (true === $product || !is_array($product)) {
            return true;
        }
        if ($product['active']) {
            return $this->trans(
                'The item %product% in your cart is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.',
                ['%product%' => $product['name']],
                'Shop.Notifications.Error'
            );
        }
        return $this->trans(
            'This product (%product%) is no longer available.',
            ['%product%' => $product['name']],
            'Shop.Notifications.Error'
        );
    }
    // protected function checkMinimumQtyMunition($products = null) {
    //     if (!$products) {
    //         $products = $this->context->cart->getProducts();
    //     }
    //     $qty_balines = 0;
    //     $qty_cartuchos = 0;
    //     $qty_cartuchos_imperator = 0;
    //     $nn_pt = 2;
    //     $bloqueo_pt = Db::getInstance()->executeS("select id_product from (
    //         select
    //             apa.id_product
    //         from
    //             aalv_combinaciones_import aci
    //             left join aalv_product_attribute apa on aci.id_product_attribute = apa.id_product_attribute
    //         WHERE
    //             aci.etiqueta LIKE '%OB0724%'
    //         UNION
    //         select aci2.id_product from aalv_combinacionunica_import aci2 where aci2.etiqueta LIKE '%OB0724%') AS bloqueo
    //         GROUP BY id_product");
    //     foreach ($products as $product) {
    //         $es_balin = false;
    //         $es_cartucho = false;
    //         $es_cartucho_imperator = false;
    //         $subfamily_cartuchos = Configuration::get('BAN_FAMILY_CARTUCHOS');
    //         $group_balines = Configuration::get('BAN_GROUP_BALINES');
    //         $marca_imperator = Configuration::get('BAN_MARCA_IMPERATOR');
    //         if ($group_balines && !empty($group_balines)) {
    //             foreach (explode(',', $group_balines) as $group_balines_item) {
    //                 if (Product::getGrupoAlvarez((int) $product['id_product'], (int) $product['id_product_attribute'], null) == $group_balines_item) {
    //                     $es_balin = true;
    //                     break;
    //                 }
    //             }
    //         }
    //         if ($bloqueo_pt && !empty($bloqueo_pt)) {
    //             foreach ($bloqueo_pt as $group_pt_item) {
    //                 if ($product['id_product'] == $group_pt_item['id_product']) {
    //                     if((int) $product['quantity'] < 2){
    //                         return $this->trans('Minimum order: [qty] EQUAL BOXES of golf balls', ['[qty]' => $nn_pt], 'Shop.Notifications.Error');
    //                     }
    //                 }
    //             }
    //         }
    //         if ($subfamily_cartuchos && !empty($subfamily_cartuchos)) {
    //             foreach (explode(',', $subfamily_cartuchos) as $subfamily_cartuchos_item) {
    //                 if (Product::getSubfamiliaAlvarez((int) $product['id_product'], (int) $product['id_product_attribute'], null) == $subfamily_cartuchos_item) {
    //                     $es_cartucho = true;
    //                     if ($marca_imperator && !empty($marca_imperator) && is_numeric($marca_imperator)) {
    //                         if ($product['id_manufacturer'] == $marca_imperator) {
    //                             $es_cartucho_imperator = true;
    //                         }
    //                     }
    //                     break;
    //                 }
    //             }
    //         }
    //         if ($es_cartucho_imperator) {
    //             $es_cartucho = false;
    //         }
    //         if ($es_balin) {
    //             $qty_balines += (int) $product['quantity'];
    //         }
    //         if ($es_cartucho) {
    //             $qty_cartuchos += (int) $product['quantity'];
    //         }
    //         if ($es_cartucho_imperator) {
    //             $qty_cartuchos_imperator += (int) $product['quantity'];
    //         }
    //     }
    //     $min_balines = 4;
    //     $min_cartuchos_imperator = 10;
    //     $min_cartuchos = 20;
    //     if ($qty_balines > 0 && $qty_balines < $min_balines) {
    //         return $this->trans('The minimum quantity of pellets in the cart is [qty] units', ['[qty]' => $min_balines], 'Shop.Notifications.Error');
    //     }
    //     if ($qty_cartuchos_imperator > 0 && $qty_cartuchos_imperator < $min_cartuchos_imperator) {
    //         return $this->trans('The minimum quantity of Imperator cartridges in the cart is [qty] units', ['[qty]' => $min_cartuchos_imperator], 'Shop.Notifications.Error');
    //     }
    //     if ($qty_cartuchos > 0 && $qty_cartuchos < $min_cartuchos) {
    //         return $this->trans('The minimum quantity of cartridges in the cart is [qty] units', ['[qty]' => $min_cartuchos], 'Shop.Notifications.Error');
    //     }
    //     return false;
    // }

    // /**
    //  * Custom function to add custonization data dynamically
    //  *
    //  * @param object $objProduct
    //  *
    //  * @return void
    //  */
    // protected function textRecord($objProduct)
    // {
    //     if (!$fieldIds = $objProduct->getCustomizationFieldIds()) {
    //         return false;
    //     }

    //     $authorizedTextFields = [];
    //     foreach ($fieldIds as $fieldId) {
    //         if ($fieldId['type'] == Product::CUSTOMIZE_TEXTFIELD) {
    //             $authorizedTextFields[(int) $fieldId['id_customization_field']] = 'textField' .
    //              (int) $fieldId['id_customization_field'];
    //         }
    //     }

    //     $indexes = array_flip($authorizedTextFields);
    //     foreach ($authorizedTextFields as $fieldName) {
    //         $value = 'hidden field bundle' . rand(10, 1000);
    //         if (in_array($fieldName, $authorizedTextFields) && $value != '') {
    //             $this->context->cart->addTextFieldToProduct(
    //                 $objProduct->id,
    //                 $indexes[$fieldName],
    //                 Product::CUSTOMIZE_TEXTFIELD,
    //                 $value
    //             );
    //         } elseif (in_array($fieldName, $authorizedTextFields) && $value == '') {
    //             $this->context->cart->deleteCustomizationToProduct((int) $objProduct->id, $indexes[$fieldName]);
    //         }
    //     }
    // }




}
