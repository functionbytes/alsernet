<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;

class CartController extends Module
{
    public $module;
    private $errors = [];

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->module = Module::getInstanceByName("alsernetshopping");
    }


    public function init()
    {
        $context = $this->context;
        $cart = $context->cart;
        $iso = Tools::getValue('iso');
        $id_lang = Language::getIdByIso($iso);

        $this->setCountry($id_lang);
        $context->language = new Language($id_lang);

        $token = Tools::getToken(false);
        $logged = $context->customer->isLogged();

        $cartPresented = (new CartPresenter)->present($cart, false, $id_lang);
        $this->processCartData($cartPresented, $id_lang, $context);

        $translations = $this->getTranslations($iso);

        $context->smarty->assign([
            'iva' => [],
            'cart' => $cartPresented,
            'products' => $cartPresented['products'],
            'token' => $token,
            'logged' => $logged,
            'cart_link' => $context->link->getPageLink('cart', null, $id_lang),
            'order_link' => $context->link->getPageLink('order', null, $id_lang),
            'translations' => $translations,
        ]);

        return [
            'status' => 'success',
            'cart_link' => $context->link->getPageLink('cart'),
            'count' => $cartPresented['products_count'],
            'cart' => $context->smarty->fetch('module:alsernetshopping/views/templates/front/cart/partials/cart.tpl'),
        ];
    }

    public function count()
    {
        $context = $this->context;
        $cart = $context->cart;
        $language = $context->language->id;
        $cartPresented = (new CartPresenter)->present($cart, false, $language);

        return [
            'status' => 'success',
            'cart_link' => $context->link->getPageLink('cart', null, $language),
            'count' => $cartPresented['products_count'],
        ];
    }


    public function summary()
    {
        $context = $this->context;
        $cart = $context->cart;
        $iso = Tools::getValue('iso');
        $id_lang = Language::getIdByIso($iso);

        $this->setCountry($id_lang);
        $context->language = new Language($id_lang);
        $currency = new Currency($cart->id_currency);

        $cartPresent = (new CartPresenter)->present($cart, false, $id_lang);
        $this->processCartData($cartPresent, $id_lang, $context);

        $products = $cartPresent['products'] ?? [];
        $subtotals = $cartPresent['subtotals'] ?? [];
        $totals = $cartPresent['totals'] ?? [];
        $labels = $cartPresent['labels'] ?? [];
        $vouchers = $cartPresent['vouchers']['added'] ?? [];

        $discount = $this->calculateDiscount($products);
        $total_discounts = $this->calculateTotalDiscounts($vouchers);
        $shipping_progress = $this->calculateShippingProgress($cartPresent);

        $configuration = [
            'display_prices_tax_incl' => Configuration::get('PS_TAX_DISPLAY'),
            'taxes_enabled' => Configuration::get('PS_TAX'),
        ];

        $translations = $this->getTranslations($iso);
        $isAvailable = $this->areProductsAvailable();
        $hasError = (true !== $isAvailable);

        $context->smarty->assign([
            'id_cart' => $cart->id,
            'cart' => $cartPresent,
            'products' => $products,
            'subtotals' => $subtotals,
            'totals' => $totals,
            'vouchers' => $vouchers,
            'total_discounts' => $total_discounts,
            'total_save' => $discount,
            'discount' => $discount,
            'shippingprogress' => $shipping_progress,
            'labels' => $labels,
            'currency' => $currency,
            'configuration' => $configuration,
            'translations' => $translations,
            'checkout' => $context->link->getPageLink('order', null, $id_lang, false, null, true),
            'errors' => $hasError ? $isAvailable : '',
        ]);


        return [
            'error' => $hasError ? $isAvailable : false,
            'status' => 'success',
            'empty' => count($products) == 0,
            'message' => $this->l('Success', 'cartcontroller'),
            'shipping' => $context->smarty->fetch('module:alsernetshopping/views/templates/front/cart/partials/shipping.tpl'),
            'products' => $context->smarty->fetch('module:alsernetshopping/views/templates/front/cart/partials/products.tpl'),
            'summary' => $context->smarty->fetch('module:alsernetshopping/views/templates/front/cart/partials/summary.tpl'),
        ];
    }

    public function add()
    {
        $id_product = (int)Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        $quantity = (int)Tools::getValue('minimal_quantity', 1);
        $logged = $this->context->customer->isLogged();
        $cart = $this->context->cart;
        $customization = array_filter(explode(',', Tools::getValue('custom')));
        $extra         = array_filter(explode('3x7r4', Tools::getValue('extra')));

        try {
            $this->validateProductData($id_product, $id_product_attribute, $quantity);
            $this->ensureCartExists($cart);

            $product = new Product($id_product, true, $this->context->language->id);
            $this->validateProduct($product, $cart);

            if (!empty($customization) || !empty($extra)) {
                return $this->addCustomizableProduct($id_product, $id_product_attribute, $customization, $extra, $quantity);
            }

            $id_product_attribute = $this->resolveProductAttribute($product, $id_product_attribute);
            $this->validateMinimalQuantity($product, $id_product_attribute, $quantity);

            $result = $cart->updateQty(
                $quantity,
                $id_product,
                $id_product_attribute,
                null,
                'up',
                $cart->id_address_delivery,
                null,
                true,
                true
            );

            if ($logged) {
                $cart->step = 'delivery';
            } else {
                $cart->step = 'login';
            }

            $cart->update();

            return $this->handleAddResult($result);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function update()
    {
        $context = $this->context;
        $cart = $context->cart;
        $iso = Tools::getValue('iso');
        $id_product = (int)Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        $quantity = (int)Tools::getValue('quantity');
        $operation = Tools::getValue('op', 'up');

        try {
            $this->validateUpdateData($id_product, $quantity);

            $product = new Product($id_product, true, $context->language->id);
            $this->validateProduct($product, $cart);

            $id_product_attribute = $this->resolveProductAttribute($product, $id_product_attribute);
            $this->validateQuantityUpdate($product, $id_product_attribute, $quantity, $operation, $cart);

            $this->ensureCartExists($cart);

            $result = $cart->updateQty(
                $quantity,
                $id_product,
                $id_product_attribute,
                null,
                $operation,
                $cart->id_address_delivery,
                null,
                true,
                true
            );

            $this->processCartRules();

            return [
                'empty' => count($cart->getProducts()) == 0 ? 'empty' : '',
                'status' => 'success',
                'message' => $this->l('Cart updated successfully', 'cartcontroller'),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function delete()
    {
        $context = $this->context;
        $cart = $context->cart;
        $iso = Tools::getValue('iso');

        $id_product = (int)Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        $id_customization = (int)Tools::getValue('id_customization');
        $id_address_delivery = (int)$cart->id_address_delivery;

        if (!$id_product) {
            return [
                'status' => 'error',
                'message' => 'Product ID missing'
            ];
        }

        try {
            $this->validateMinimalQuantityForDeletion($id_product, $id_product_attribute, $id_customization, $cart);

            $data = [
                'id_cart' => $cart->id,
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'id_customization' => $id_customization,
                'id_address_delivery' => $id_address_delivery,
            ];

            Hook::exec('actionObjectProductInCartDeleteBefore', $data, null, true);

            if ($cart->deleteProduct($id_product, $id_product_attribute, $id_customization, $id_address_delivery)) {
                Hook::exec('actionObjectProductInCartDeleteAfter', $data);
                $this->cleanupEmptyCart($cart);
                $this->processCartRules();

                return [
                    'empty' => count($cart->getProducts()) == 0,
                    'status' => 'success',
                    'message' => $this->l('Product successfully removed.', 'cartcontroller'),
                    'data' => $data,
                ];
            } else {
                throw new Exception($this->l('Could not delete product from cart.', 'cartcontroller'));
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function deletes()
    {
        $context = $this->context;
        $cart = $context->cart;
        $iso = Tools::getValue('iso');

        $id_product = (int)Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        $id_customization = (int)Tools::getValue('id_customization');
        $id_address_delivery = (int)$cart->id_address_delivery;

        // DEBUG LOGGING
        error_log("=== CartController::delete() CALLED ===");
        error_log("Cart ID: " . $cart->id);
        error_log("Product ID: " . $id_product);
        error_log("Product Attribute ID: " . $id_product_attribute);
        error_log("Customization ID: " . $id_customization);
        error_log("Address Delivery ID: " . $id_address_delivery);
        error_log("All POST data: " . print_r($_POST, true));

        if (!$id_product) {
            error_log("ERROR: Product ID missing");
            return [
                'status' => 'error',
                'message' => 'Product ID missing'
            ];
        }

        $this->validateMinimalQuantityForDeletion($id_product, $id_product_attribute, $id_customization, $cart);
        $data = [
            'id_cart' => $cart->id,
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'id_customization' => $id_customization,
            'id_address_delivery' => $id_address_delivery,
        ];


        //Hook::exec('actionObjectProductInCartDeleteBefore', $data, null, true);

        // DEBUG: Verificar productos antes de eliminar
        $productsBefore = $cart->getProducts();
        $productCountBefore = count($productsBefore);
        error_log("BEFORE DELETE: Cart has {$productCountBefore} products");

        foreach ($productsBefore as $idx => $prod) {
            $isTargetProduct = ($prod['id_product'] == $id_product &&
                $prod['id_product_attribute'] == $id_product_attribute &&
                $prod['id_customization'] == $id_customization);
            error_log("Product {$idx}: ID={$prod['id_product']}, Attr={$prod['id_product_attribute']}, Custom={$prod['id_customization']}, Target=" . ($isTargetProduct ? 'YES' : 'NO'));
        }

        error_log("ATTEMPTING DELETE with params: id_product={$id_product}, id_product_attribute={$id_product_attribute}, id_customization={$id_customization}, id_address_delivery={$id_address_delivery}");

        $deleteResult = $cart->deleteProduct($id_product, $id_product_attribute, $id_customization, $id_address_delivery);

        error_log("DELETE RESULT: " . ($deleteResult ? 'SUCCESS' : 'FAILED'));

        // DEBUG: Verificar productos después de eliminar
        $productsAfter = $cart->getProducts();
        $productCountAfter = count($productsAfter);
        error_log("AFTER DELETE: Cart has {$productCountAfter} products");

        if ($deleteResult) {
            Hook::exec('actionObjectProductInCartDeleteAfter', $data);
            $this->cleanupEmptyCart($cart);
            $this->processCartRules();

            // Final verification
            $finalProducts = $cart->getProducts();
            $finalCount = count($finalProducts);
            error_log("FINAL CHECK: Cart has {$finalCount} products after cleanup");

            return [
                'empty' => $finalCount == 0,
                'status' => 'success',
                'message' => $this->l('Product successfully removed.', 'cartcontroller'),
                'data' => $data,
            ];
        } else {
            error_log("ERROR: deleteProduct returned false");
            throw new Exception($this->l('Could not delete product from cart.', 'cartcontroller'));
        }
    }

    public function coupon()
    {
        $context = $this->context;
        $cart = $context->cart;
        $code = trim(Tools::getValue('coupon'));
        $verifcode = trim(Tools::getValue('confirmation'));
        $iso = $context->language->iso_code;

        try {
            $this->validateCouponRequest($cart, $code);
            $this->cleanInvalidCartRules($cart, $context, $code, $verifcode, $iso);

            if (!$cart->canApplyCartRule()) {
                throw new Exception($this->l('No more coupons can be applied.', 'cartcontroller', $iso));
            }

            if (!empty($verifcode)) {
                return $this->handleERPCoupon($code, $verifcode, $cart, $context, $iso);
            } else {
                return $this->handleStandardCoupon($code, $context, $iso);
            }
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function deletecoupon()
    {
        $iso = Tools::getValue('iso');
        $rule = trim(Tools::getValue('rule'));

        try {
            $this->context->cart->removeCartRule($rule);
            CartRule::autoAddToCart($this->context);

            return [
                'status' => 'success',
                'message' => $this->l('Coupon removed successfully', 'cartcontroller'),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function modal()
    {
        $iso = Tools::getValue('iso');
        $id_product = (int)Tools::getValue('id_product', 0);
        $id_product_attribute = (int)Tools::getValue('id_product_attribute', Tools::getValue('ipa', 0));
        $id_lang = Language::getIdByIso($iso);

        $this->setCountry($id_lang);

        // Obtener información completa del producto directamente
        $productItem = $this->getProductInformation($id_product, $id_product_attribute, $id_lang);

        if ($productItem === null) {
            return [
                'status' => 'warning',
                'message' => $this->l('Product not found', 'cartcontroller', $iso),
                'data' => [],
            ];
        }

        return [
            'status' => 'success',
            'data' => $this->context->smarty->fetch($this->processProductModal($productItem, $id_product, $id_lang, $iso)),
        ];
    }

    public function modalcomplementary()
    {
        $iso = Tools::getValue('iso');
        $id_product = (int)Tools::getValue('id_product', 0);
        $id_product_attribute = (int)Tools::getValue('id_product_attribute', Tools::getValue('ipa', 0));
        $id_lang = Language::getIdByIso($iso);

        $this->setCountry($id_lang);

        // Obtener información completa del producto directamente
        $productItem = $this->getProductInformation($id_product, $id_product_attribute, $id_lang);

        if ($productItem === null) {
            return [
                'status' => 'warning',
                'message' => $this->l('Product not found', 'cartcontroller', $iso),
                'data' => [],
            ];
        }

        return [
            'status' => 'success',
            'data' => $this->context->smarty->fetch($this->processProductModal($productItem, $id_product, $id_lang, $iso)),
        ];
    }

    public function gets()
    {
        $token = Tools::getToken(false);
        $iso = Tools::getValue('iso');
        $id_lang = Language::getIdByIso($iso);
        $logged = $this->context->customer->isLogged();
        $cart = (new CartPresenter)->present($this->context->cart, false, $id_lang);

        if ($cart['products_count'] > 0) {
            $data = [
                'cart' => $cart,
                'token' => $token,
                'logged' => $logged,
                'cart_link' => $this->context->link->getPageLink('cart', null, $id_lang, false, null, true),
                'order_link' => $this->context->link->getPageLink('order', null, $id_lang, false, null, true),
            ];

            return [
                'status' => 'warning',
                'message' => $this->l('Success', 'cartcontroller'),
                'data' => $data,
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->l('Cart is empty', 'cartcontroller'),
        ];
    }

    // Private helper methods

    private function processCartData(&$cartPresented, $id_lang, $context)
    {
        $is_virtual = $cartPresented['is_virtual'];
        $productAmount = $cartPresented['subtotals']['products']['amount'];
        $resta = false;

        foreach ($cartPresented['products'] as &$product) {
            if (in_array((int)$product['id'], [65104, 65102])) {
                $resta = true;
                break;
            }
            $productObject = new Product((int)$product['id'], false, $id_lang);
            $product['url_product'] = $context->link->getProductLink($productObject, null, null, null, $id_lang);
        }

        if ($resta) {
            $productAmount -= 25;
        }

        $freeShippingThreshold = 99;
        $amountRemaining = max(0, $freeShippingThreshold - $productAmount);
        $percentage = min(99, round(($productAmount / $freeShippingThreshold) * 99, 2));

        $cartPresented['shipping_progress'] = [
            'active' => !$is_virtual && $cartPresented['subtotals']['products']['amount'] < 100,
            'resta_applied' => $resta,
            'adjusted_amount' => $productAmount,
            'amount_remaining' => $amountRemaining,
            'percentage' => $percentage,
        ];

        if (!empty($cartPresented['iva'])) {
            $cartPresented['iva_message'] = [
                'amount' => Tools::displayPrice($cartPresented['iva']['total_discount_iva'], $context->currency),
                'active' => true,
            ];
        }
    }

    private function calculateDiscount($products)
    {
        $discount = 0;
        foreach ($products as $product) {
            $discount += (float)($product['reduction'] ?? 0) * $product['quantity'];
        }
        return number_format(ceil($discount * 100) / 100, 2, '.', '');
    }

    private function calculateTotalDiscounts($vouchers)
    {
        $total_discounts = 0;
        if (!empty($vouchers) && is_array($vouchers)) {
            foreach ($vouchers as $coupon) {
                $formatted_value = $coupon['reduction_formatted'] ?? '0';
                $numeric_value = str_replace(',', '.', preg_replace('/[^0-9,-]/', '', $formatted_value));
                $total_discounts += (float)$numeric_value;
            }
        }
        return Tools::displayPrice(abs($total_discounts), $this->context->currency);
    }

    private function calculateTotalSave($vouchers)
    {
        $total_discounts = 0;
        if (!empty($vouchers) && is_array($vouchers)) {
            foreach ($vouchers as $coupon) {
                $formatted_value = $coupon['reduction_formatted'] ?? '0';
                $numeric_value = str_replace(',', '.', preg_replace('/[^0-9,-]/', '', $formatted_value));
                $total_discounts += (float)$numeric_value;
            }
        }
        return Tools::displayPrice(abs($total_discounts), $this->context->currency);
    }

    private function calculateShippingProgress($cartPresented)
    {
        $is_virtual = $cartPresented['is_virtual'];
        $productAmount = $cartPresented['subtotals']['products']['amount'];
        $freeShippingThreshold = 99;

        $amountRemaining = max(0, $freeShippingThreshold - $productAmount);
        $percentage = min(99, round(($productAmount / $freeShippingThreshold) * 99, 2));

        return [
            'active' => !$is_virtual && $productAmount < $freeShippingThreshold,
            'adjusted_amount' => $productAmount,
            'amount_remaining' => $amountRemaining,
            'percentage' => $percentage,
        ];
    }

    private function validateProductData($id_product, $id_product_attribute, $quantity)
    {
        if (!$id_product) {
            throw new Exception($this->l('Product not found', 'cartcontroller'));
        }
        if ($quantity <= 0) {
            throw new Exception($this->l('Invalid quantity.', 'cartcontroller'));
        }
    }

    private function validateUpdateData($id_product, $quantity)
    {
        if (!$id_product) {
            throw new Exception($this->l('Product not found', 'cartcontroller'));
        }
        if ($quantity <= 0) {
            throw new Exception($this->l('Invalid quantity.', 'cartcontroller'));
        }
    }

    private function validateProduct($product, $cart)
    {
        if (!$product->id || !$product->active || !$product->checkAccess($cart->id_customer)) {
            throw new Exception($this->l(
                'This product (%product%) is no longer available.',
                ['%product%' => $product->name],
                'cartcontroller'
            ));
        }
    }

    private function ensureCartExists($cart)
    {
        if (!$cart->id) {
            if (Context::getContext()->cookie->id_guest) {
                $guest = new Guest(Context::getContext()->cookie->id_guest);
                $cart->mobile_theme = $guest->mobile_theme;
            }
            $cart->add();
            if ($cart->id) {
                $this->context->cookie->id_cart = (int)$cart->id;
            }
        }
    }

    private function resolveProductAttribute($product, $id_product_attribute)
    {
        if (!$id_product_attribute && $product->hasAttributes()) {
            $minimum_quantity = ($product->out_of_stock == 2)
                ? !Configuration::get('PS_ORDER_OUT_OF_STOCK')
                : !$product->out_of_stock;
            $id_product_attribute = Product::getDefaultAttribute($product->id, $minimum_quantity);

            if (!$id_product_attribute) {
                throw new Exception($this->l('No combination available.', 'cartcontroller'));
            }
        }
        return $id_product_attribute;
    }

    private function validateMinimalQuantity($product, $id_product_attribute, $quantity)
    {
        $minimal_quantity = $id_product_attribute
            ? Attribute::getAttributeMinimalQty($id_product_attribute)
            : $product->minimal_quantity;

        if ($quantity < $minimal_quantity) {
            throw new Exception($this->l(
                'The minimum purchase order quantity for the product %product% is %quantity%.',
                ['%product%' => $product->name, '%quantity%' => $minimal_quantity],
                'cartcontroller'
            ));
        }
    }

    private function validateQuantityUpdate($product, $id_product_attribute, $quantity, $operation, $cart)
    {
        $qty_to_check = $quantity;
        $cart_products = $cart->getProducts();

        if (is_array($cart_products)) {
            foreach ($cart_products as $cart_product) {
                if ($this->productInCartMatchesCriteria($cart_product, $product->id, $id_product_attribute)) {
                    $qty_to_check = $cart_product['cart_quantity'];

                    if ($operation == 'down') {
                        $qty_to_check -= $quantity;
                    } else {
                        $qty_to_check += $quantity;
                    }
                    break;
                }
            }
        }

        $this->validateMinimalQuantity($product, $id_product_attribute, $qty_to_check);
    }

    private function productInCartMatchesCriteria($productInCart, $id_product, $id_product_attribute)
    {
        return (int)$productInCart['id_product'] === (int)$id_product &&
            (int)$productInCart['id_product_attribute'] === (int)$id_product_attribute;
    }

    private function handleAddResult($result)
    {
        if ($result < 0) {
            return [
                'status' => 'warning',
                'message' => $this->l('Not enough stock or invalid quantity.'),
            ];
        } elseif (!$result) {
            return [
                'status' => 'warning',
                'message' => $this->l('Maximum quantity reached.')
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->l('Product successfully added to the cart.'),
        ];
    }

    private function processCartRules()
    {
        CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
    }

    private function cleanupEmptyCart($cart)
    {
        if (!$cart->getProducts()) {
            $cart->setDeliveryOption(null);
            $cart->gift = 0;
            $cart->gift_message = '';
            $cart->update();
        }
    }

    private function validateMinimalQuantityForDeletion($id_product, $id_product_attribute, $id_customization, $cart)
    {
        $customization_product = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'customization`'
                . ' WHERE `id_cart` = ' . (int)$cart->id
                . ' AND `id_product` = ' . (int)$id_product
                . ' AND `id_customization` != ' . (int)$id_customization
                . ' AND `in_cart` = 1'
                . ' AND `quantity` > 0'
        );

        if (count($customization_product)) {
            $product = new Product((int)$id_product);
            $minimal_quantity = ($id_product_attribute > 0)
                ? (int)Attribute::getAttributeMinimalQty($id_product_attribute)
                : (int)$product->minimal_quantity;

            $total_quantity = array_sum(array_column($customization_product, 'quantity'));

            if ($total_quantity < $minimal_quantity) {
                throw new Exception($this->l(
                    'You must add %quantity% minimum quantity',
                    ['%quantity%' => $minimal_quantity],
                    'cartcontroller'
                ));
            }
        }
    }

    private function validateCouponRequest($cart, $code)
    {
        if (!$cart->id) {
            throw new Exception($this->l('Cart not found.', 'cartcontroller'));
        }
        if (!$code) {
            throw new Exception($this->l('You must enter a code.', 'cartcontroller'));
        }
    }

    private function cleanInvalidCartRules($cart, $context, $code, $verifcode, $iso)
    {
        foreach ($cart->getCartRules() as $cartRule) {
            if (!($cr = new CartRule($cartRule['id_cart_rule'])) || !Validate::isLoadedObject($cr)) {
                continue;
            }
            if (!empty($verifcode)) {
                // === A) ¿Existe ya el CartRule con ese code?
                $existingId = (int)CartRule::getIdByCode($code . '-' . $verifcode);

                // === B) ¿Ya está aplicado al carrito? (antes de tocar nada)
                $alreadyAppliedBefore = ($existingId > 0) && $this->isCartRuleIdApplied($cart, $existingId);

                if ($alreadyAppliedBefore) {
                    throw new Exception($this->l('The coupon has already been applied to your cart.', 'cartcontroller', $iso));
                }
            }
            if ($error = $cr->checkValidity($context, false, true)) {
                $cart->removeCartRule($cartRule['id_cart_rule']);
            }
        }
    }

    private function handleERPCoupon($code, $verifcode, $cart, $context, $iso)
    {
        if (!class_exists('AlvarezERP')) {
            throw new Exception($this->l('ERP module not available.', 'cartcontroller', $iso));
        }

        $cart_total = $cart->getOrderTotal();
        $bono = AlvarezERP::consultabono($code, $verifcode, $cart_total, AlvarezERP::BONO_ORIGEN_WEB);

        if (!$bono || !$bono['success']) {
            throw new Exception($this->l($bono['message'] ?? 'Invalid code or not found.', 'cartcontroller', $iso));
        }

        $data = $bono['data'] ?? null;
        if (!$data || !isset($data['estado_extendido'])) {
            throw new Exception($this->l('Invalid coupon data.', 'cartcontroller', $iso));
        }

        $this->validateERPCouponData($data, $cart_total, $cart, $iso);

        $importe = isset($data['importe']) ? (float)$data['importe'] : 0;
        $importe_minimo = isset($data['importeminimoventa']) ? (float)$data['importeminimoventa'] : 0;

        $cartRuleId = CartRule::createCartRuleAlvarez($code, $verifcode, $importe, $cart_total, $importe_minimo, $data, $context);

        if (!$cartRuleId || !(Validate::isLoadedObject($cr = new CartRule($cartRuleId)))) {
            throw new Exception($this->l('Error generating the coupon.', 'cartcontroller', $iso));
        }

        $this->context->cart->addCartRule($cartRuleId);

        return [
            'status' => 'success',
            'message' => $this->l('Coupon applied successfully', 'cartcontroller'),
        ];
    }

    private function handleStandardCoupon($code, $context, $iso)
    {
        if (!Validate::isCleanHtml($code)) {
            throw new Exception($this->l('The code is not valid.', 'cartcontroller', $iso));
        }

        if (Module::isEnabled('quantitydiscountpro')) {
            include_once _PS_MODULE_DIR_ . 'quantitydiscountpro/quantitydiscountpro.php';
            $quantityDiscount = new QuantityDiscountRule(QuantityDiscountRule::getQuantityDiscountRuleByCode($code));

            if (Validate::isLoadedObject($quantityDiscount)) {
                if ($quantityDiscount->createAndRemoveRules($code) !== true) {
                    throw new Exception($this->l('The code is invalid.', 'cartcontroller', $iso));
                }
            }
        }

        $cartRule = new CartRule(CartRule::getIdByCode($code));
        if (!Validate::isLoadedObject($cartRule)) {
            throw new Exception($this->l('This coupon does not exist.', 'cartcontroller', $iso));
        }

        if ($error = $cartRule->checkValidity($context, false, true)) {
            throw new Exception($this->l($error, 'cartcontroller', $iso));
        }

        $this->context->cart->addCartRule($cartRule->id);

        return [
            'status' => 'success',
            'message' => $this->l('Coupon applied successfully', 'cartcontroller'),
        ];
    }

    private function validateERPCouponData($data, $cart_total, $cart, $iso)
    {
        $estado = (int)$data['estado_extendido'];

        if ($estado === 0) {
            throw new Exception($this->l('This coupon is disabled.', 'cartcontroller', $iso));
        }
        if ($estado === 2 || $estado === 3) {
            throw new Exception($this->l('This coupon has expired or has already been used.', 'cartcontroller', $iso));
        }
        if (strtotime($data['fvalidez_desde']) > time()) {
            throw new Exception($this->l('This coupon is not yet valid.', 'cartcontroller', $iso));
        }
        if (strtotime($data['fvalidez_hasta']) < time()) {
            throw new Exception($this->l('This coupon has expired.', 'cartcontroller', $iso));
        }

        $importe_minimo = isset($data['importeminimoventa']) ? (float)$data['importeminimoventa'] : 0;
        if ($importe_minimo > $cart_total) {
            $msg = sprintf(
                $this->l('You do not reach the minimum amount of %s to use this coupon.', 'cartcontroller'),
                Tools::displayPrice($importe_minimo)
            );
            throw new Exception($msg);
        }

        foreach ($cart->getProducts() as $product) {
            if (!empty($product['is_virtual'])) {
                throw new Exception($this->l('The Lottery does not allow discounts.', 'cartcontroller', $iso));
            }
        }
    }

    private function findProductInCart($products, $id_product, $id_product_attribute)
    {
        foreach ($products as $p) {
            if ((int)$p['id_product'] === $id_product && (int)$p['id_product_attribute'] === $id_product_attribute) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Obtiene la información completa del producto usando el ProductPresenter de PrestaShop
     * Maneja tanto productos normales como productos personalizados del módulo idxrcustomproduct
     * @param int $id_product
     * @param int $id_product_attribute
     * @param int $id_lang
     * @return array|null
     */
    private function getProductInformation($id_product, $id_product_attribute, $id_lang)
    {
        try {
            // Cargar el producto completo desde PrestaShop
            $product = new Product($id_product, true, $id_lang);

            if (!Validate::isLoadedObject($product)) {
                error_log("ERROR: Product {$id_product} not found");
                return null;
            }

            $targetAttribute = (int)$id_product_attribute;

            // Validar que la combinación existe si se especificó
            if ($targetAttribute > 0) {
                $combination = new Combination($targetAttribute);
                if (!Validate::isLoadedObject($combination) || $combination->id_product != $id_product) {
                    error_log("ERROR: Combination {$targetAttribute} not found or doesn't belong to product {$id_product}");
                    return null;
                }
            }

            // error_log("=== getProductInformation ===");
            // error_log("Product ID: " . $id_product);
            // error_log("Product Attribute ID (requested): " . $id_product_attribute);
            // error_log("Product Attribute ID (using): " . $targetAttribute);

            // VERIFICAR SI ES UN PRODUCTO PERSONALIZADO (CLON)
            // Los productos personalizados están registrados en la tabla idxrcustomproduct_clones
            $isCustomProduct = false;
            $customProductPrice = null;

            if (Module::isEnabled('idxrcustomproduct')) {

                // Verificar directamente en la tabla de clones si este producto es un clon personalizado
                $cloneCheck = Db::getInstance()->getValue(
                    'SELECT id_clon FROM `' . _DB_PREFIX_ . 'idxrcustomproduct_clones` WHERE `id_clon` = ' . (int)$id_product
                );

                $isCustomProduct = (bool)$cloneCheck;

                // error_log("Product is custom (clone check): " . ($isCustomProduct ? 'YES' : 'NO'));
                // error_log("Clone check result: " . ($cloneCheck ?: 'NOT FOUND'));

                if ($isCustomProduct) {
                    // Obtener el precio real del producto personalizado directamente de BD
                    $customProductPrice = Db::getInstance()->getValue(
                        'SELECT price FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' . (int)$id_product
                    );
                    // error_log("Custom product real price from DB: " . $customProductPrice);
                }
            }

            // Cargar datos completos del producto desde la base de datos
            $productData = Db::getInstance()->getRow(
                'SELECT * FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' . (int)$id_product
            );

            if (!$productData) {
                error_log("ERROR: Could not load product data from database");
                return null;
            }

            // Si es un producto personalizado, mantener su precio real
            if ($isCustomProduct && $customProductPrice !== null) {
                $productData['price'] = (float)$customProductPrice;
                // error_log("Product data price set to custom price: " . $customProductPrice);
            }

            // Si hay combinación específica, cargar sus datos y fusionarlos
            // NOTA: Los productos personalizados NO usan combinaciones, así que esto solo aplica a productos normales
            if ($targetAttribute > 0 && !$isCustomProduct) {
                $combinationData = Db::getInstance()->getRow(
                    'SELECT * FROM `' . _DB_PREFIX_ . 'product_attribute`
                    WHERE `id_product_attribute` = ' . (int)$targetAttribute
                );

                if ($combinationData) {
                    // Fusionar datos de la combinación con los datos del producto
                    // Esto asegura que ProductAssembler use los valores específicos de la combinación
                    $productData['id_product_attribute'] = $targetAttribute;
                    $productData['price'] = (float)$productData['price'] + (float)$combinationData['price'];
                    $productData['wholesale_price'] = $combinationData['wholesale_price'];
                    $productData['ecotax'] = $combinationData['ecotax'];
                    $productData['quantity'] = $combinationData['quantity'];
                    $productData['weight'] = (float)$productData['weight'] + (float)$combinationData['weight'];
                    $productData['unit_price_impact'] = $combinationData['unit_price_impact'];
                    $productData['reference'] = !empty($combinationData['reference']) ?
                        $combinationData['reference'] : $productData['reference'];
                    $productData['ean13'] = !empty($combinationData['ean13']) ?
                        $combinationData['ean13'] : $productData['ean13'];
                    $productData['upc'] = !empty($combinationData['upc']) ?
                        $combinationData['upc'] : $productData['upc'];

                    // error_log("Combination data merged - Price impact: " . $combinationData['price']);
                    // error_log("Final price for presentation: " . $productData['price']);
                } else {
                    error_log("WARNING: Combination data not found in database");
                }
            } else {
                $productData['id_product_attribute'] = 0;
            }

            // Usar el assembler y presenter de PrestaShop con datos completos
            $assembler = new ProductAssembler($this->context);
            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = $presenterFactory->getPresenter();

            // Ensamblar el producto con datos completos (incluyendo combinación fusionada)
            $productForPresentation = $assembler->assembleProduct($productData);

            // Presentar el producto
            $productPresented = $presenter->present(
                $presentationSettings,
                $productForPresentation,
                $this->context->language
            );

            // FORZAR el id_product_attribute en el resultado final para asegurar consistencia
            $productPresented['id_product_attribute'] = $targetAttribute;

            // Obtener la imagen específica de la combinación (solo para productos normales con atributos)
            if ($targetAttribute > 0 && !$isCustomProduct) {
                $images = Image::getImages($id_lang, $id_product, $targetAttribute);
                if (!empty($images)) {
                    $image = array_shift($images);
                    $imageRetriever = new ImageRetriever($this->context->link);
                    $productPresented['cover'] = $imageRetriever->getImage($product, $image['id_image']);
                    // error_log("Using combination-specific image: " . $image['id_image']);
                }
            }

            // error_log("Final Product Presented - ID Attr: " . $productPresented['id_product_attribute']);
            // error_log("Final Product Presented - Price: " . $productPresented['price']);
            // error_log("Final Product Presented - Reference: " . ($productPresented['reference'] ?? 'N/A'));
            // error_log("Final Product Presented - Is Custom: " . ($isCustomProduct ? 'YES' : 'NO'));

            return $productPresented;
        } catch (Exception $e) {
            error_log("ERROR in getProductInformation: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    public function processProductModal($productItem, $id_product, $id_lang, $iso)
    {

        $prod = new Product($id_product, false, $id_lang);

        $complementaryProducts = $this->getDatosProducto($productItem['id_product'], $productItem['id_product_attribute']);

        if ($complementaryProducts) {
            $cart = Context::getContext()->cart;

            $availableComplementaryProducts = [];

            if (count($complementaryProducts) > 0) {
                foreach ($complementaryProducts as $product) {
                    $product_exists_in_cart = false;
                    foreach ($cart->getProducts() as $product_cart) {
                        if ((int)$product_cart['id_product'] == (int)$product['id_product']) {
                            $product_exists_in_cart = true;
                            break;
                        }
                    }

                    if (!$product_exists_in_cart) {
                        $availableComplementaryProducts[] = $product;
                    }
                }
            }
            if (count($availableComplementaryProducts) > 0) {
                return $this->renderComplementaryProductModal($productItem, $availableComplementaryProducts, $id_lang, $iso);
            }
        }

        if (Product::isCategoryRelationByProductType($prod)) {
            return $this->renderCategoryRelationModal($prod, $productItem, $id_lang, $iso);
        }

        return $this->renderStandardProductModal($productItem, $id_lang, $iso);
    }

    private function renderComplementaryProductModal($productItem, $complementaryProducts, $id_lang, $iso)
    {
        $idretail = ($productItem['id_product_attribute'] == 0) ? "1" . $productItem['id_product'] : "2" . $productItem['id_product'] . $productItem['id_product_attribute'];

        $id_category_default = $productItem['id_category_default'];
        $category = new Category($id_category_default, $id_lang);

        if ($category->active) {
            $category_product = Link::getUrlSmarty(['entity' => 'category', 'id' => $id_category_default, 'lang' => $id_lang]);
        } else {
            $category_product = Context::getContext()->link->getPageLink('index', true, $id_lang);
        }

        $translations = [
            'title' => $this->l('Product successfully added to your shopping cart', 'cartcontroller'),
            'checkout' => $this->l('Proceed to checkout', 'cartcontroller'),
            'shopping' => $this->l('Continue shopping', 'cartcontroller'),
            'titlecomplementary' => $this->l('Complementary products', 'cartcontroller'),
            'titlecomplementaryref' => $this->l('Ref', 'cartcontroller'),
            'titlecomplementaryadd' => $this->l('Add', 'cartcontroller'),
            'titlecomplementaryoption' => $this->l('Select option', 'cartcontroller'),
        ];

        $this->context->smarty->assign([
            'translations' => $translations,
            'idretail' => $idretail,
            'complementaries' => $complementaryProducts,
            'product' => $productItem,
            'category_product' => $category_product,
            'cart' => $this->context->link->getPageLink('cart', null, $id_lang, false, null, true),
            'cart_id' => $this->context->cart->id,
            'order_link' => $this->context->link->getPageLink('order', null, $id_lang, false, null, true),
            'has_complementary' => true, // Flag to identify this modal type
        ]);

        return 'module:alsernetshopping/views/templates/hook/shopping/modals/complementary.tpl';
    }

    public function renderBlockedProductModal($productItem, $id_lang, $iso)
    {
        $translations = [
            'title' => $this->l('Product not added to your shopping cart', 'cartcontroller'),
            'shopping' => $this->l('Continue shopping', 'cartcontroller'),
        ];

        $this->context->smarty->assign([
            'translations' => $translations,
            'lang' => $id_lang == 1 || $id_lang == 4 ? 'true' : 'false',
            'product' => $productItem,
            'cart_link' => $this->context->link->getPageLink('cart', null, $id_lang, false, null, true),
            'order_link' => $this->context->link->getPageLink('order', null, $id_lang, false, null, true),
        ]);

        return 'module:alsernetshopping/views/templates/hook/shopping/modals/blockade.tpl';
    }

    public function renderCategoryRelationModal($prod, $productItem, $id_lang, $iso)
    {
        $show_modal_categories_relation = false;
        $id_category_target = 0;
        $category_relation = null;

        if (Module::isEnabled('alvarezcategoriesrelation')) {
            require_once _PS_MODULE_DIR_ . 'alvarezcategoriesrelation/classes/CategoriesRelation.php';
            $category_relation = CategoriesRelation::getRelationDataByIdcategory($prod->id_category_default, true, $id_lang);
            if ($category_relation) {
                $id_category_target = $category_relation['id_category_target'];
                $show_modal_categories_relation = true;
            }
        }

        if ($show_modal_categories_relation && $id_category_target && $category_relation) {
            $translations = [
                'title' => $this->l('Complete your order', 'cartcontroller'),
            ];

            $this->context->smarty->assign([
                'translations' => $translations,
                'category_relation' => $category_relation,
                'url_category_target' => $this->context->link->getCategoryLink((int)$id_category_target, null, $id_lang),
                'url_checkout' => $this->context->link->getPageLink('order', null, $id_lang, false, null, true),
                'url_cart' => $this->context->link->getPageLink('cart', null, $id_lang, false, null, true),
                'product' => $productItem,
            ]);

            return 'module:alsernetshopping/views/templates/hook/shopping/modals/relation.tpl';
        }

        return $this->renderStandardProductModal($productItem, $id_lang, $iso);
    }

    public function renderStandardProductModal($productItem, $id_lang, $iso)
    {
        $idretail = ($productItem['id_product_attribute'] == 0) ? "1" . $productItem['id_product'] : "2" . $productItem['id_product'] . $productItem['id_product_attribute'];

        $cart = Context::getContext()->cart;
        $products_data = [];

        $products_data[] = $this->getDatosProducto($productItem['id_product'], $productItem['id_product_attribute']);

        $id_category_default = $productItem['id_category_default'];

        $category = new Category($id_category_default, $id_lang);

        if ($category->active) {
            $category_product = Link::getUrlSmarty(['entity' => 'category', 'id' => $id_category_default, 'lang' => $id_lang]);
        } else {
            $category_product = Context::getContext()->link->getPageLink('index', true, $id_lang);
        }

        $products = [];

        if (count($products_data) > 0) {
            foreach ($products_data as $product_group) {
                foreach ($product_group as $product) {
                    $product_exists_in_cart = false;
                    foreach ($cart->getProducts() as $product_cart) {
                        if ((int)$product_cart['id_product'] == (int)$product['id_product']) {
                            $product_exists_in_cart = true;
                        }
                    }

                    if (!$product_exists_in_cart) {
                        $products[$product['id_product']] = $product;
                    }
                }
            }
        }

        $translations = [
            'title' => $this->l('Product successfully added to your shopping cart', 'cartcontroller'),
            'checkout' => $this->l('Proceed to checkout', 'cartcontroller'),
            'shopping' => $this->l('Continue shopping', 'cartcontroller'),
            'titlecomplementary' => $this->l('Complementary products', 'cartcontroller'),
            'titlecomplementaryref' => $this->l('Ref', 'cartcontroller'),
            'titlecomplementaryadd' => $this->l('Add', 'cartcontroller'),
            'titlecomplementaryoption' => $this->l('Select option', 'cartcontroller'),
        ];

        $this->context->smarty->assign([
            'translations' => $translations,
            'idretail' => $idretail,
            'products' => $products,
            'product' => $productItem,
            'category_product' => $category_product,
            'cart' => $this->context->link->getPageLink('cart', null, $id_lang, false, null, true),
            'order_link' => $this->context->link->getPageLink('order', null, $id_lang, false, null, true),
        ]);

        return 'module:alsernetshopping/views/templates/hook/shopping/modals/modal.tpl';
    }

    // public function getDatosProducto($id_prod, $id_productattribute)
    // {
    //     $etiquetas = '';

    //     if ('' . $id_productattribute == '0') {
    //         $etiquetas = Db::getInstance()->getValue("SELECT etiqueta FROM aalv_combinacionunica_import WHERE id_product=" . $id_prod);
    //     } else {
    //         $etiquetas = Db::getInstance()->getValue("SELECT etiqueta FROM aalv_combinaciones_import WHERE id_product_attribute=" . $id_productattribute);
    //     }

    //     $rows = Db::getInstance()->ExecuteS("SELECT etiqueta, id_product FROM aalv_complementarios");
    //     $lisproducts = [];

    //     foreach (explode(',', $etiquetas) as $etiqueta) {
    //         foreach ($rows as $complementario) {
    //             if (trim(strtolower($etiqueta)) == trim(strtolower($complementario['etiqueta']))) {
    //                 $lisproducts[] = $complementario["id_product"];
    //             }
    //         }
    //     }

    //     if (empty($lisproducts)) {
    //         return [];
    //     }

    //     $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'product` WHERE id_product in (' . implode(',', $lisproducts) . ') AND id_product <> ' . $id_prod;
    //     $products = Db::getInstance()->executeS($sql);

    //     $assembler = new ProductAssembler($this->context);
    //     $presenterFactory = new ProductPresenterFactory($this->context);
    //     $presentationSettings = $presenterFactory->getPresentationSettings();
    //     $presenter = new ProductListingPresenter(
    //         new ImageRetriever($this->context->link),
    //         $this->context->link,
    //         new PriceFormatter(),
    //         new ProductColorsRetriever(),
    //         $this->context->getTranslator()
    //     );

    //     $products_for_template = [];

    //     if ($products) {
    //         foreach ($products as $rawProduct) {
    //             $ppres = $presenter->present(
    //                 $presentationSettings,
    //                 $assembler->assembleProduct($rawProduct),
    //                 $this->context->language
    //             );

    //             if (($ppres["add_to_cart_url"] != null && ($ppres["id_product_attribute"] == 0)) || $ppres["id_product_attribute"] != 0) {
    //                 $ppres['is_bundle'] = 0;
    //                 $sql = 'SELECT `id_ps_product` FROM `' . _DB_PREFIX_ . 'wk_bundle_product` WHERE `id_ps_product`=' . (int)$ppres['id_product'];
    //                 $isBundle = DB::getInstance()->getValue($sql);
    //                 if ($isBundle) {
    //                     $ppres['is_bundle'] = 1;
    //                 }

    //                 $products_for_template[] = $ppres;
    //             }
    //         }
    //     }

    //     return $products_for_template;
    // }

    public function getDatosProducto($id_prod, $id_productattribute)
    {
        $ctx    = $this->context;
        $idProd = (int)$id_prod;
        $idLang = (int)$ctx->language->id;
        $idShop = (int)$ctx->shop->id;
        $limit  = 5;

        // ========= Helpers =========
        $whereSrc = $this->jsonArrayContainsInt('source_ids', $idProd);
        $notInExcluded = $this->jsonArrayNotContainsInt('excluded_products', $idProd);


        // Categorías del producto
        $catRows = Db::getInstance()->executeS(
            '
        SELECT id_category FROM ' . _DB_PREFIX_ . 'category_product WHERE id_product=' . (int)$idProd
        );
        $catIds = array_map(function ($r) {
            return (int)$r['id_category'];
        }, $catRows);


        // Marca del producto (entero)
        $idManufacturer = Db::getInstance()->executeS('
        SELECT id_manufacturer FROM ' . _DB_PREFIX_ . 'product WHERE id_product=' . (int)$idProd . ' LIMIT 1
        ');



        // Refs del producto y sus combinaciones (para match por source_refs CSV en reglas type="product")
        $refsOfProduct = [];
        $prodRef = Db::getInstance()->executeS('
        SELECT reference FROM ' . _DB_PREFIX_ . 'product WHERE id_product=' . (int)$idProd . ' LIMIT 1
        ');

        if (isset($prodRef) && $prodRef[0]['reference'] !== '') $refsOfProduct[] = $prodRef[0]['reference'];

        $paRefs = Db::getInstance()->executeS('
        SELECT reference FROM ' . _DB_PREFIX_ . 'product_attribute
        WHERE id_product=' . (int)$idProd . ' AND reference IS NOT NULL AND reference <> ""
        ');

        foreach ($paRefs as $r) {
            if (!empty($r['reference'])) $refsOfProduct[] = (string)$r['reference'];
        }
        $refsOfProduct = array_values(array_unique($refsOfProduct));

        // Condición por referencias en source_refs (CSV con comas y posibles espacios)
        $condRefsSource = '0';
        if ($refsOfProduct) {
            $parts = [];
            foreach ($refsOfProduct as $ref) {
                // Ignora espacios en source_refs
                $parts[] = 'FIND_IN_SET("' . pSQL($ref) . '", REPLACE(source_refs, " ", ""))';
            }
            $condRefsSource = '(' . implode(' OR ', $parts) . ')';
        }

        // === ETIQUETAS del producto (para aplicar reglas type="etiqueta")
        $labelsWhere = '0'; // por defecto "falso"
        $labelRows = Db::getInstance()->executeS('
            SELECT ci.etiqueta
            FROM ' . _DB_PREFIX_ . 'combinaciones_import ci
            INNER JOIN ' . _DB_PREFIX_ . 'product_attribute pa
                    ON pa.id_product_attribute = ci.id_product_attribute
            WHERE pa.id_product = ' . (int)$idProd . ' AND ci.etiqueta IS NOT NULL AND ci.etiqueta <> ""
            UNION
            SELECT cui.etiqueta
            FROM ' . _DB_PREFIX_ . 'combinacionunica_import cui
            WHERE cui.id_product = ' . (int)$idProd . ' AND cui.etiqueta IS NOT NULL AND cui.etiqueta <> ""
        ');

        if ($labelRows) {
            $labels = [];
            foreach ($labelRows as $lr) {
                // cada registro puede traer una o varias etiquetas separadas por coma
                $parts = $this->splitRefsCsv((string)$lr['etiqueta'], /* etiqueta */ true);
                foreach ($parts as $p) {
                    if ($p !== '') $labels[] = $p;
                }
            }

            $labels = array_values(array_unique($labels));

            if ($labels) {
                $ors = [];
                foreach ($labels as $lab) {
                    // source_refs se almacena CSV (por comas) en reglas de etiqueta
                    $ors[] = $this->regexpRefExact('source_refs', $lab);
                }

                if ($ors) {
                    $labelsWhere = '(' . implode(' OR ', $ors) . ')';
                }
            }
        }

        // ========= Reglas por prioridad =========
        $rulesByType = [
            'product'  => [],
            'category' => [],
            'brand'    => [],
            'etiqueta' => [], // ← NUEVO
        ];

        // PRODUCT rules: por ID en source_ids o por referencia en source_refs
        $rulesByType['product'] = Db::getInstance()->executeS('
            SELECT id_complementario, type, title, complement_ids, complement_refs, excluded_products
            FROM ' . _DB_PREFIX_ . 'alsernet_complementarios
            WHERE type="product"
            AND (
                    ' . $whereSrc . '
                    OR ' . $condRefsSource . '
                )
            AND ' . $notInExcluded . '
            ORDER BY id_complementario DESC
            LIMIT 50
            ') ?: [];



        // CATEGORY rules: por IDs de categoría en source_ids + (opcional) filtro de marcas en source_brand_ids
        $catWhere = $this->jsonArrayContainsAny('source_ids', $catIds);
        if ($catWhere !== '0') {

            // Si la regla NO tiene marcas -> aplica a todas.
            // Si la regla tiene marcas -> debe contener el id_manufacturer del producto.
            $brandGate = '(source_brand_ids IS NULL
                   OR source_brand_ids = \'\'
                   OR source_brand_ids = "[]" )'; // ← cubrir JSON vacío

            if (count($idManufacturer) > 0) {
                // Usa el mismo truco de JSON envuelto con comas
                $brandGate = '(' . $brandGate . ' OR ' . $this->jsonArrayContainsInt('source_brand_ids', $idManufacturer[0]['id_manufacturer']) . ')';
            }

            $rulesByType['category'] = Db::getInstance()->executeS('
                    SELECT id_complementario, type, title, complement_ids, complement_refs, excluded_products
                    FROM ' . _DB_PREFIX_ . 'alsernet_complementarios
                    WHERE type="category"
                    AND ' . $catWhere . '
                    AND ' . $brandGate . '
                    AND ' . $notInExcluded . '
                    ORDER BY id_complementario DESC
                    LIMIT 50
                ') ?: [];
        }

        // BRAND rules: por id_manufacturer en source_ids
        if (count($idManufacturer) != 0) {
            $rulesByType['brand'] = Db::getInstance()->executeS('
            SELECT id_complementario, type, title, complement_ids, complement_refs, excluded_products
            FROM ' . _DB_PREFIX_ . 'alsernet_complementarios
            WHERE type="brand"
              AND ' . $this->jsonArrayContainsInt('source_ids', $idManufacturer[0]['id_manufacturer']) . '
              AND ' . $notInExcluded . '
            ORDER BY id_complementario DESC
            LIMIT 50
        ') ?: [];
        }

        // ETIQUETA rules: por coincidencia exacta de tokens en source_refs (CSV) con etiquetas del producto
        if ($labelsWhere !== '0') {
            $rulesByType['etiqueta'] = Db::getInstance()->executeS('
            SELECT id_complementario, type, title, source_refs, complement_ids, complement_refs, excluded_products
            FROM ' . _DB_PREFIX_ . 'alsernet_complementarios
            WHERE type="label"
            AND ' . $labelsWhere . '
            AND ' . $notInExcluded . '
            ORDER BY id_complementario DESC
            LIMIT 50
        ') ?: [];

            // Filtrar reglas label: deben aplicar a ESTE producto + IPA actual
            if (!empty($rulesByType['etiqueta'])) {
                $rulesByType['etiqueta'] = array_values(array_filter($rulesByType['etiqueta'], function ($row) use ($idProd, $id_productattribute) {
                    // source_refs en label es CSV de etiquetas (separamos solo por coma, preservando espacios internos)
                    $labels = $this->splitRefsCsv((string)$row['source_refs'], /* etiqueta */ true);
                    return $this->labelsApplyToProductAttribute($labels, $idProd, (int)$id_productattribute);
                }));
            }
        }


        // ========= Candidatos (con trazas para resolver IPA por complement_refs) =========
        $candidates = []; // id_product => [ ['from'=>, 'mapping_id'=>, 'refs_blob'=>], ... ]
        $pushFrom = function (array $reglas, $from) use (&$candidates, $limit) {
            foreach ($reglas as $row) {
                $cmpIds = json_decode((string)$row['complement_ids'], true) ?: [];
                foreach ($cmpIds as $pid) {
                    $pid = (int)$pid;
                    if ($pid <= 0) continue;
                    if (!isset($candidates[$pid])) $candidates[$pid] = [];
                    $candidates[$pid][] = [
                        'from'        => $from,
                        'mapping_id'  => (int)$row['id_complementario'],
                        'refs_blob'   => (string)$row['complement_refs'],
                    ];
                }
                if (count($candidates) >= $limit * 4) break;
            }
        };

        $pushFrom($rulesByType['product'],  'product');
        if (count($candidates) < $limit) $pushFrom($rulesByType['category'], 'category');
        if (count($candidates) < $limit) $pushFrom($rulesByType['brand'],    'brand');
        if (count($candidates) < $limit) $pushFrom($rulesByType['etiqueta'], 'etiqueta'); // ← ÚLTIMO


        if (!$candidates) {
            return [];
        }

        // Excluir el propio producto y validar activo/visible/stock
        $candIds = array_diff(array_keys($candidates), [$idProd]);
        if (!$candIds) return [];

        $validRows = Db::getInstance()->executeS('
        SELECT p.id_product, p.active, p.visibility, IFNULL(sa.quantity,0) AS qty
        FROM ' . _DB_PREFIX_ . 'product p
        LEFT JOIN ' . _DB_PREFIX_ . 'stock_available sa
          ON (sa.id_product=p.id_product AND sa.id_product_attribute=0
              AND (sa.id_shop=' . (int)$idShop . ' OR sa.id_shop=0))
        WHERE p.id_product IN (' . implode(',', array_map('intval', $candIds)) . ')
        ');
        $valid = [];
        foreach ($validRows as $vr) {
            if ((int)$vr['active'] == 1 && $vr['visibility'] != 'none' && (int)$vr['qty'] > 0) {
                $valid[(int)$vr['id_product']] = true;
            }
        }

        // Resolver IPA por referencias del mapping (complement_refs) o default
        $resolveIPA = function ($idP, $refsBlob) {
            $refs = preg_split('/[\s,;\r\n\t]+/', (string)$refsBlob, -1, PREG_SPLIT_NO_EMPTY);
            $refs = array_values(array_unique(array_map('trim', $refs)));

            if ($refs) {
                $in = implode(',', array_map(function ($r) {
                    return '"' . pSQL($r) . '"';
                }, $refs));
                $sql = 'SELECT id_product_attribute FROM ' . _DB_PREFIX_ . 'product_attribute '
                    . 'WHERE id_product=' . (int)$idP . ' AND reference IN (' . $in . ') '
                    . 'ORDER BY default_on DESC, id_product_attribute ASC LIMIT 1';
                $rows = Db::getInstance()->executeS($sql);
                if ($rows && isset($rows[0]['id_product_attribute'])) {
                    return (int)$rows[0]['id_product_attribute'];
                }
            }
            $sql = 'SELECT id_product_attribute FROM ' . _DB_PREFIX_ . 'product_attribute '
                . 'WHERE id_product=' . (int)$idP . ' '
                . 'ORDER BY default_on DESC, id_product_attribute ASC LIMIT 1';
            $rows = Db::getInstance()->executeS($sql);
            return ($rows && isset($rows[0]['id_product_attribute'])) ? (int)$rows[0]['id_product_attribute'] : 0;
        };

        // Selección con prioridad product → category → brand (máx. 5)
        $picked = [];
        $pickedIds = [];

        $pickFrom = function ($from) use (&$picked, &$pickedIds, $candidates, $valid, $resolveIPA, $limit) {
            foreach ($candidates as $pid => $traces) {
                if (count($picked) >= $limit) break;
                if (!isset($valid[$pid]) || isset($pickedIds[$pid])) continue;

                $trace = null;
                foreach ($traces as $t) {
                    if ($t['from'] === $from) {
                        $trace = $t;
                        break;
                    }
                }
                if (!$trace) continue;

                $picked[] = [
                    'id_product'           => (int)$pid,
                    'id_product_attribute' => (int)$resolveIPA($pid, $trace['refs_blob']),
                ];
                $pickedIds[$pid] = true;
            }
        };

        $pickFrom('product');
        if (count($picked) < $limit) $pickFrom('category');
        if (count($picked) < $limit) $pickFrom('brand');
        if (count($picked) < $limit) $pickFrom('etiqueta'); // ← ÚLTIMO


        if (!$picked) return [];

        // ========= Presentación (igual que tu original) =========
        $assembler           = new ProductAssembler($ctx);
        $presenterFactory    = new ProductPresenterFactory($ctx);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter           = new ProductListingPresenter(
            new ImageRetriever($ctx->link),
            $ctx->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $ctx->getTranslator()
        );

        // Raw products
        $idsOnly = array_map(function ($p) {
            return (int)$p['id_product'];
        }, $picked);
        $raws    = Db::getInstance()->executeS('
        SELECT * FROM ' . _DB_PREFIX_ . 'product
        WHERE id_product IN (' . implode(',', array_map('intval', $idsOnly)) . ')
        ');
        $rawById = [];
        foreach ($raws as $rp) $rawById[(int)$rp['id_product']] = $rp;

        $out = [];
        foreach ($picked as $item) {
            $pid = (int)$item['id_product'];
            if (!isset($rawById[$pid])) continue;

            $ppres = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($rawById[$pid]),
                $ctx->language
            );

            // Convertir LazyArray a array normal para poder modificarlo
            $ppres = json_decode(json_encode($ppres), true);

            // Reflejar combinación si la resolvimos
            $ipa = (int)$item['id_product_attribute'];
            if ($ipa > 0) {
                $ppres['id_product_attribute'] = $ipa;
                $combination = new Combination($ipa, $idLang);
                $attrs = $combination->getAttributesName($idLang);
                if (!empty($attrs)) {
                    $parts = [];
                    foreach ($attrs as $a) {
                        if (!empty($a['name'])) {
                            $parts[] = $a['name'];
                        }
                    }
                    if ($parts) $ppres['attributes_small'] = implode(', ', $parts);
                    $ppres['attributes'] = true;
                }
            }

            // Misma lógica de tu función original
            if (($ppres['add_to_cart_url'] != null && ($ppres['id_product_attribute'] == 0)) || $ppres['id_product_attribute'] != 0) {
                // Flag bundle como en tu original
                $ppres['is_bundle'] = 0;
                $isBundle = (int)Db::getInstance()->getValue('
                SELECT `id_ps_product` FROM `' . _DB_PREFIX_ . 'wk_bundle_product`
                WHERE `id_ps_product`=' . (int)$ppres['id_product'] . '
            ');
                if ($isBundle) {
                    $ppres['is_bundle'] = 1;
                }

                $out[] = $ppres;
            }
        }

        return $out;
    }


    protected function areProductsAvailable()
    {
        $products = $this->context->cart->getProducts();

        foreach ($products as $product) {
            if ((new Product($product['id_product']))->hasAttributes() && $product['id_product_attribute'] == 0) {
                return [
                    'error' => true,
                    'message' => sprintf(
                        $this->l(
                            'The item %s in your cart is now a product with attributes. Please delete it and choose one of its combinations to proceed with your order.',
                            'cartcontroller'
                        ),
                        $product['name']
                    ),
                    'id_product' => $product['id_product'],
                    'id_product_attribute' => $product['id_product_attribute'],
                ];
            }
        }

        $productError = $this->context->cart->checkQuantities(true);

        if (true === $productError) {
            return true;
        }

        if (is_array($productError)) {
            $message = $productError['active']
                ? sprintf(
                    $this->l(
                        'The item %s in your cart is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.',
                        'cartcontroller'
                    ),
                    $productError['name']
                )
                : sprintf(
                    $this->l(
                        'This product (%s) is no longer available.',
                        'cartcontroller'
                    ),
                    $productError['name']
                );

            return [
                'error' => true,
                'message' => $message,
                'id_cart' => $this->context->cart->id,
                'id_product' => $productError['id_product'],
                'id_product_attribute' => $productError['id_product_attribute'],
            ];
        }

        return true;
    }

    private function getTranslations($iso)
    {
        return [
            'cart' => $this->l('Cart', 'cartcontroller'),
            'view_cart' => $this->l('View cart', 'cartcontroller'),
            'checkout' => $this->l('Checkout purchase', 'cartcontroller'),
            'missing_for_free_shipping' => $this->l('Missing', 'cartcontroller'),
            'free_shipping_message' => $this->l('for FREE SHIPPING.', 'cartcontroller'),
            'iva_message' => $this->l('VAT FREE DAYS', 'cartcontroller'),
            'iva_message_additional' => $this->l('Get a DISCOUNT VOUCHER for your next purchase.', 'cartcontroller'),
            'order_summary' => $this->l('Order Summary', 'cartcontroller'),
            'with_this_purchase_you_save' => $this->l('With this purchase you save', 'cartcontroller'),
            'vat_discount_day' => $this->l('VAT discount day', 'cartcontroller'),
            'i_have_a' => $this->l('I have a', 'cartcontroller'),
            'promotional_code' => $this->l('promotional code', 'cartcontroller'),
            'or' => $this->l('or', 'cartcontroller'),
            'gift_card' => $this->l('gift card', 'cartcontroller'),
            'have_a_promo_code' => $this->l('Have a promo code?', 'cartcontroller'),
            'apply' => $this->l('Apply', 'cartcontroller'),
            'close' => $this->l('Close', 'cartcontroller'),
            'proceed_to_checkout' => $this->l('Proceed to checkout', 'cartcontroller'),
            'discount_voucher_message' => $this->l('Discount voucher message', 'cartcontroller'),
            'enter_coupon_code_here' => $this->l('Enter coupon code here...', 'cartcontroller'),
            'enter_verification_code_here' => $this->l('Enter coupon verification here...', 'cartcontroller'),
        ];
    }

    private function setCountry($lang = 1)
    {
        $context = $this->context;
        $id_country = 6;

        $countryMap = [
            1 => 6,  // Español → España
            2 => 17, // Inglés → USA
            3 => 8,  // Francés → Francia
            4 => 15, // Italiano → Italia
            5 => 1,  // Portugués → Portugal
            6 => 10, // Alemán → Alemania
        ];

        $id_country = $countryMap[$lang] ?? 6;

        $country = new Country($id_country, (int)$lang);
        $context->country = $country;
        $context->cookie->iso_code_country = strtoupper($context->country->iso_code);
        $context->language = new Language($lang);

        return $context;
    }



    public function l($string, $specific = false, $locale = null)
    {

        return $this->getModuleTranslation(
            $this->module,
            $string,
            ($specific) ? $specific : $this->name,
            null,
            false,
            $locale
        );
    }


    public  function getModuleTranslation(
        $module,
        $originalString,
        $source,
        $sprintf = null,
        $js = false,
        $locale = null,
        $fallback = true,
        $escape = true
    ) {
        global $_MODULES, $_MODULE, $_LANGADM;

        static $langCache = [];
        static $name = null;

        // $_MODULES is a cache of translations for all module.
        // $translations_merged is a cache of wether a specific module's translations have already been added to $_MODULES
        static $translationsMerged = [];


        $name = $module->name;

        if (null !== $locale) {
            $iso = Language::getIsoByLocale($locale);
        }

        if (empty($iso)) {
            $iso = Context::getContext()->language->iso_code;
        }

        if (!isset($translationsMerged[$name][$iso])) {
            $filesByPriority = [
                // PrestaShop 1.5 translations
                _PS_MODULE_DIR_ . $name . '/translations/' . $iso . '.php',
                // PrestaShop 1.4 translations
                _PS_MODULE_DIR_ . $name . '/' . $iso . '.php',
                // Translations in theme
                _PS_THEME_DIR_ . 'modules/' . $name . '/translations/' . $iso . '.php',
                _PS_THEME_DIR_ . 'modules/' . $name . '/' . $iso . '.php',
            ];
            foreach ($filesByPriority as $file) {
                if (file_exists($file)) {
                    include_once $file;
                    $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
                }
            }
            $translationsMerged[$name][$iso] = true;
        }


        $string = preg_replace("/\\\*'/", "\'", $originalString);
        $key = md5($string);

        $cacheKey = $name . '|' . $string . '|' . $source . '|' . (int) $js . '|' . $iso;
        if (isset($langCache[$cacheKey])) {
            $ret = $langCache[$cacheKey];
        } else {
            $currentKey = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
            $defaultKey = strtolower('<{' . $name . '}prestashop>' . $source) . '_' . $key;

            if ('controller' == substr($source, -10, 10)) {
                $file = substr($source, 0, -10);
                $currentKeyFile = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
                $defaultKeyFile = strtolower('<{' . $name . '}prestashop>' . $file) . '_' . $key;
            }

            if (isset($currentKeyFile) && !empty($_MODULES[$currentKeyFile])) {
                $ret = stripslashes($_MODULES[$currentKeyFile]);
            } elseif (isset($defaultKeyFile) && !empty($_MODULES[$defaultKeyFile])) {
                $ret = stripslashes($_MODULES[$defaultKeyFile]);
            } elseif (!empty($_MODULES[$currentKey])) {
                $ret = stripslashes($_MODULES[$currentKey]);
            } elseif (!empty($_MODULES[$defaultKey])) {
                $ret = stripslashes($_MODULES[$defaultKey]);
            } elseif (!empty($_LANGADM)) {
                // if translation was not found in module, look for it in AdminController or Helpers
                $ret = stripslashes(Translate::getGenericAdminTranslation($string, $key, $_LANGADM));
            } else {
                $ret = stripslashes($string);
            }

            if (
                $sprintf !== null &&
                (!is_array($sprintf) || !empty($sprintf)) &&
                !(count($sprintf) === 1 && isset($sprintf['legacy']))
            ) {
                $ret = Translate::checkAndReplaceArgs($ret, $sprintf);
            }

            if ($js) {
                $ret = addslashes($ret);
            } elseif ($escape) {
                $ret = htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
            }

            if ($sprintf === null) {
                $langCache[$cacheKey] = $ret;
            }
        }

        if (!is_array($sprintf) && null !== $sprintf) {
            $sprintf_for_trans = [$sprintf];
        } elseif (null === $sprintf) {
            $sprintf_for_trans = [];
        } else {
            $sprintf_for_trans = $sprintf;
        }

        if ($ret === $originalString && $fallback) {
            $ret = Context::getContext()->getTranslator()->trans($originalString, $sprintf_for_trans, null, $locale);
        }

        return $ret;
    }

    /**
     * 🎨 Agregar producto personalizable replicando EXACTAMENTE el flujo de IdxrCustomProduct
     */
    private function addCustomizableProduct($id_product, $id_product_attribute, $customization, $extra, $quantity)
    {
        try {
            // Paso 1: Asegurar que IdxrCustomProduct está disponible
            if (!Module::isEnabled('idxrcustomproduct')) {
                throw new Exception('IdxrCustomProduct module not available');
            }

            $idxrModule = Module::getInstanceByName('idxrcustomproduct');

            if (!$idxrModule) {
                throw new Exception('IdxrCustomProduct module instance not found');
            }

            // Paso 2: Asegurar que existe carrito (equivalente a setCart)
            $this->ensureCartExists($this->context->cart);

            foreach ($customization as &$option) {
                $option = explode('_', $option);
                if (substr_count($option[0], 'x')) {
                    $qty_option = explode('x', $option[0]);
                    $option['qty'] = $qty_option[0];
                    $option['id_component'] = $qty_option[1];
                    unset($qty_option);
                } else {
                    $option['id_component'] = $option[0];
                }
                $option['id_option'] = $option[1];
                unset($option[0]);
                unset($option[1]);
            }

            // Paso 5: Crear producto personalizado REAL (equivalente a createproduct con simulate=false)
            $customProductId = $idxrModule->createProduct(
                $id_product,
                $id_product_attribute,
                $customization,
                $extra,
                $quantity,
                false
            );

            if (!$customProductId) {
                throw new Exception('Failed to create custom product');
            }

            // Paso 6: Simular creación para obtener ID confirmado (equivalente a simulatecreateproduct)
            $simulatedId = $idxrModule->createProduct(
                $id_product,
                $id_product_attribute,
                $customization,
                $extra,
                $quantity,
                true // SÍ simular - solo obtener ID
            );

            // Usar el ID simulado si es diferente (por si hay cambios)
            $finalProductId = $simulatedId ?: $customProductId;

            // Paso 6: Agregar al carrito usando el sistema nativo de PrestaShop
            // (equivalente al $.ajax con prestashop['urls']['pages']['cart'])
            $cart = $this->context->cart;
            $result = $cart->updateQty(
                $quantity,
                $finalProductId,
                0, // Los productos personalizados no usan id_product_attribute
                null,
                'up',
                $cart->id_address_delivery,
                null,
                true,
                true
            );

            if (!$result) {
                throw new Exception('Failed to add custom product to cart');
            }

            // Paso 7: Actualizar estado del carrito
            $logged = $this->context->customer->isLogged();
            if ($logged) {
                $cart->step = 'delivery';
            } else {
                $cart->step = 'login';
            }

            $cart->update();

            return [
                'status' => 'success',
                'message' => $this->l('Custom product added successfully', 'cartcontroller'),
                'custom_product_id' => $finalProductId,
                'original_product_id' => $id_product,
                'cart_result' => $result
            ];
        } catch (Exception $e) {
            // Si falla el flujo personalizado, no intentar fallback
            // porque ya se creó el producto personalizado
            error_log('Custom product creation failed: ' . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parsear string de personalización al formato de IdxrCustomProduct
     */

    private function parseCustomizationString($customization)
    {
        if (empty($customization)) {
            return [];
        }

        // 🔧 VALIDAR TIPO: Si ya es un array, devolverlo directamente
        if (is_array($customization)) {
            error_log('🔧 parseCustomizationString: Received array instead of string: ' . print_r($customization, true));
            return $customization;
        }

        // 🔧 VALIDAR STRING: Asegurar que es string antes de usar explode
        if (!is_string($customization)) {
            error_log('🚨 parseCustomizationString: Invalid type received: ' . gettype($customization));
            return [];
        }

        $customizationArray = [];
        $items = explode(',', $customization);

        foreach ($items as $item) {
            $parts = explode('_', $item);
            if (count($parts) >= 2) {
                // 🔧 FORMATO RECIBIDO: "1_value" -> component=1, option=value
                $customizationArray[] = [
                    'id_component' => (int)$parts[0],
                    'id_option' => $parts[1], // Puede ser texto, no solo número
                    'qty' => isset($parts[2]) ? (int)$parts[2] : 1
                ];
            }
        }

        error_log('🎨 parseCustomizationString result: ' . print_r($customizationArray, true));

        return $customizationArray;
    }

    /**
     * Parsear string de datos extra
     */
    private function parseExtraString($extra)
    {
        if (empty($extra)) {
            return [];
        }

        // 🔧 VALIDAR TIPO: Si ya es un array, devolverlo directamente
        if (is_array($extra)) {
            error_log('🔧 parseExtraString: Received array instead of string: ' . print_r($extra, true));
            return $extra;
        }

        // 🔧 VALIDAR STRING: Asegurar que es string antes de usar explode
        if (!is_string($extra)) {
            error_log('🚨 parseExtraString: Invalid type received: ' . gettype($extra));
            return [];
        }

        $extraArray = [];
        $items = explode('3x7r4', $extra);

        foreach ($items as $item) {
            $parts = explode('_', $item, 2);
            if (count($parts) === 2) {
                // 🔧 FIX: Manejar correctamente json_decode para evitar problemas con valores falsy
                $decoded = json_decode($parts[1], true);
                $data = ($decoded !== null) ? $decoded : $parts[1];

                $extraArray[] = [
                    'id_extra' => (int)$parts[0],
                    'data' => $data
                ];
            }
        }

        return $extraArray;
    }

    /**
     * Genera una expresión SQL para envolver un JSON-array TEXT en comas:
     *   "[1,1324,7]" -> ",1,1324,7,"
     */
    protected function sqlWrapJsonArray($col)
    {
        // CONCAT(',', REPLACE(REPLACE(col,'[',''),']',''), ',')
        return "CONCAT(',', REPLACE(REPLACE($col,'[',''),']',''), ',')";
    }

    /** source_ids contiene exactamente el entero $n */
    protected function jsonArrayContainsInt($col, $n)
    {
        $wrapped = $this->sqlWrapJsonArray($col);
        $n = (int)$n;
        return "$wrapped LIKE '%,{$n},%'";
    }

    /** source_ids contiene CUALQUIERA de los enteros de $ints */
    protected function jsonArrayContainsAny($col, array $ints)
    {
        $ints = array_values(array_unique(array_map('intval', $ints)));
        if (!$ints) return '0'; // false
        $wrapped = $this->sqlWrapJsonArray($col);
        $ors = [];
        foreach ($ints as $n) {
            $ors[] = "$wrapped LIKE '%,{$n},%'";
        }
        return '(' . implode(' OR ', $ors) . ')';
    }

    /** excluded_products NO contiene $n (o está vacío/null) */
    protected function jsonArrayNotContainsInt($col, $n)
    {
        $wrapped = $this->sqlWrapJsonArray("COALESCE($col,'[]')");
        $n = (int)$n;
        return "$wrapped NOT LIKE '%,{$n},%'";
    }

    protected function regexpRefExact($field, $ref)
    {
        $ref = pSQL($ref);
        // Usamos [:space:] para soportar espacios/tab/nuevas líneas
        // NOTA: preg_quote para seguridad en el patrón
        $refQuoted = preg_quote($ref, '/');
        return $field . " REGEXP '(^|[,[:space:]]+)" . $refQuoted . "([,[:space:]]+|$)'";
    }

    protected function splitRefsCsv($csv, $etiqueta = false)
    {
        if ($csv === null) {
            return [];
        }

        $csv = trim((string)$csv);
        if ($csv === '') {
            return [];
        }

        if ($etiqueta) {
            // ✅ Solo separa por coma, preservando espacios dentro de la etiqueta
            $tokens = array_map('trim', explode(',', $csv));
        } else {
            // 🧩 Mantiene compatibilidad: espacios, coma, punto y coma, saltos, tabs, etc.
            $tokens = preg_split('/[\s,;\r\n\t]+/', $csv, -1, PREG_SPLIT_NO_EMPTY);
            $tokens = array_map('trim', $tokens);
        }

        // Filtramos vacíos y duplicados
        $tokens = array_values(array_unique(array_filter($tokens, function ($t) {
            return $t !== '';
        })));

        return $tokens;
    }

    // NUEVO: verifica si alguna etiqueta aplica a este producto/atributo
    protected function labelsApplyToProductAttribute(array $labels, $idProduct, $idProductAttribute)
    {
        $labels = array_values(array_unique(array_filter(array_map('trim', $labels), function ($s) {
            return $s !== '';
        })));
        if (!$labels) return false;

        // Preparar condiciones LIKE seguras
        $likeParts = [];
        foreach ($labels as $lab) {
            // Escapar % y _ en LIKE
            $safe = pSQL(str_replace(['%', '_'], ['\%', '\_'], $lab));
            $likeParts[] = "aci.etiqueta LIKE '%" . $safe . "%'";
        }
        $condLike = '(' . implode(' OR ', $likeParts) . ')';

        $idProduct          = (int)$idProduct;
        $idProductAttribute = (int)$idProductAttribute;


        // 1) Coincidencia en combinaciones (si hay IPA)
        if ($idProductAttribute > 0) {
            $sql1 = '
            SELECT 1
            FROM ' . _DB_PREFIX_ . 'combinaciones_import aci
            LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute apa
              ON apa.id_product_attribute = aci.id_product_attribute
            WHERE ' . $condLike . '
              AND apa.id_product = ' . $idProduct . '
              AND apa.id_product_attribute = ' . $idProductAttribute . '
            LIMIT 1';
            $hit1 = (int)Db::getInstance()->executeS($sql1);
            if ($hit1) return true;
        }

        // 2) Coincidencia en producto “único”
        $sql2 = '
        SELECT 1
        FROM ' . _DB_PREFIX_ . 'combinacionunica_import aci
        WHERE ' . $condLike . '
          AND aci.id_product = ' . $idProduct . '
        LIMIT 1';
        $hit2 = (int)Db::getInstance()->executeS($sql2);

        return $hit2 > 0;
    }
}
