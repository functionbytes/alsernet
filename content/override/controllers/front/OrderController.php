<?php

use PrestaShop\PrestaShop\Adapter\Cart\CartPresenter;
use PrestaShop\PrestaShop\Core\Foundation\Templating\RenderableProxy;

class OrderController extends OrderControllerCore
{
    public function initContent()
    {

        // $cartLink = $this->context->link->getPageLink('cart', null, null, ['action' => 'show']);
        // if (Configuration::isCatalogMode()) {
        //     Tools::redirect('index.php');
        // }
        // $this->restorePersistedData($this->checkoutProcess);
        // $this->checkoutProcess->handleRequest(
        //     Tools::getAllValues()
        // );
        // $presentedCart = $this->cart_presenter->present($this->context->cart);
        // if (count($presentedCart['products']) <= 0 || $presentedCart['minimalPurchaseRequired']) {
        //     $cartLink = $this->context->link->getPageLink('cart');
        //     Tools::redirect($cartLink);
        // }
        // $product = $this->context->cart->checkQuantities(true);
        // if (Module::isEnabled('wkbundleproduct')) {
        //     $context = Context::getContext();
        //     require_once _PS_MODULE_DIR_ . 'wkbundleproduct/wkbundleproduct.php';
        //     if ($cartProducts = $context->cart->getProducts()) {
        //         if (!empty($cartProducts)) {
        //             $objBundle = new WkBundle();
        //             $objTempData = new WkBundleCartDataFinal();
        //             $objSubproduct = new WkBundleSubProduct();
        //             foreach ($cartProducts as $productList) {
        //                 if ($objBundle->isBundleProduct($productList['id_product'])) {
        //                     $productIdArray = [];
        //                     $bundleProductInformation = $objTempData->getSelectedBundleProduct(
        //                         $productList['id_product'],
        //                         $this->context->cart->id,
        //                         $this->context->shop->id
        //                     );
        //                     if (!empty($bundleProductInformation)) {
        //                         foreach ($bundleProductInformation as $bundleInfo) {
        //                             $availQty = $objSubproduct->checkProductQuantity(
        //                                 $bundleInfo['id_wk_bundle_section'],
        //                                 $bundleInfo['id_product'],
        //                                 $bundleInfo['id_product_attribute']
        //                             );
        //                             $availableStock = StockAvailable::getQuantityAvailableByProduct(
        //                                 $bundleInfo['id_product'],
        //                                 $bundleInfo['id_product_attribute'],
        //                                 $this->context->shop->id
        //                             );
        //                             if ($availableStock
        //                                         >= ($productList['cart_quantity'] * $bundleInfo['product_qty'])
        //                             ) {
        //                                 if ($availQty) {
        //                                     if ($availQty['quantity']
        //                                                 < ($productList['cart_quantity'] * $bundleInfo['product_qty'])
        //                                     ) {
        //                                         $productIdArray[] = $bundleInfo['id_product'];
        //                                     }
        //                                 }
        //                             } else {
        //                                 $productIdArray[] = $bundleInfo['id_product'];
        //                             }
        //                         }
        //                     } else {
        //                         $this->context->cart->deleteProduct($productList['id_product']);
        //                     }
        //                     if (!empty($productIdArray)) {
        //                         $nameArray = [];
        //                         foreach ($productIdArray as $product) {
        //                             $nameArray[] = Product::getProductName(
        //                                 $product,
        //                                 0,
        //                                 $this->context->language->id
        //                             );
        //                         }
        //                         if ($nameArray) {
        //                             $nameArray = implode(',', $nameArray);
        //                         }
        //                         Tools::redirect($cartLink);
        //                     }
        //                 }
        //                 if (Configuration::get('WK_BUNDLE_PRODUCT_RESERVED_QTY')) {
        //                     if ($objSubproduct->getAllAvailableProduct(0)) {
        //                         if (in_array(
        //                             $productList['id_product'],
        //                             $objSubproduct->getAllAvailableProduct(0)
        //                         )) {
        //                             $qty = $objSubproduct->getProductMaximumQuantity(
        //                                 $productList['id_product'],
        //                                 $productList['id_product_attribute']
        //                             );
        //                             if ($qty) {
        //                                 if ($productList['cart_quantity'] > $qty) {
        //                                     Tools::redirect($cartLink);
        //                                 }
        //                             } else {
        //                                 Tools::redirect($cartLink);
        //                             }
        //                         }
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }
        // if (is_array($product)) {
        //     Tools::redirect($cartLink);
        // }
        // $this->checkoutProcess
        //     ->setNextStepReachable()
        //     ->markCurrentStep()
        //     ->invalidateAllStepsAfterCurrent();
        // $this->saveDataToPersist($this->checkoutProcess);
        // if (!$this->checkoutProcess->hasErrors()) {
        //     if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !$this->ajax) {
        //         return $this->redirectWithNotifications(
        //             $this->checkoutProcess->getCheckoutSession()->getCheckoutURL()
        //         );
        //     }
        // }
        // $this->context->smarty->assign([
        //     'checkout_process' => new RenderableProxy($this->checkoutProcess),
        //     'cart' => $presentedCart,
        // ]);
        // $this->context->smarty->assign([
        //     'display_transaction_updated_info' => Tools::getIsset('updatedTransaction'),
        // ]);
        parent::initContent();
        $this->setTemplate('checkout/checkout');

        // $sports = $this->getSportsByIdsAndTranslate();

        // $this->context->smarty->assign([
        //     'sports' => $sports,
        // ]);

    }


    function getSportsByIdsAndTranslate()
    {

        $lang = $this->context->language->id;

        $sports_map = [
            1 => 'GOLF',
            2 => 'HUNTING',
            3 => 'FISHING',
            4 => 'HORSE',
            5 => 'DIVING',
            6 => 'BOATING',
            7 => 'SKIING',
            8 => 'PADEL',
            9 => 'ADVENTURE',
        ];

        $sports_translation_map = [
            1 => [
                'GOLF' => 'GOLF',
                'HUNTING' => 'CAZA',
                'FISHING' => 'PESCA',
                'HORSE' => 'HÍPICA',
                'DIVING' => 'BUCEO',
                'BOATING' => 'NAUTICA',
                'SKIING' => 'ESQUÍ',
                'PADEL' => 'PÁDEL',
                'ADVENTURE' => 'AVENTURA',
            ],
            2 => [
                'GOLF' => 'GOLF',
                'HUNTING' => 'HUNTING',
                'FISHING' => 'FISHING',
                'HORSE' => 'HORSE RIDING',
                'DIVING' => 'DIVING',
                'BOATING' => 'BOATING',
                'SKIING' => 'SKIING',
                'PADEL' => 'PADEL',
                'ADVENTURE' => 'ADVENTURE',
            ],
            3 => [
                'GOLF' => 'GOLF',
                'HUNTING' => 'CHÂSSE',
                'FISHING' => 'PÊCHE',
                'HORSE' => 'ÉQUITATION',
                'DIVING' => 'PLONGÉE',
                'BOATING' => 'NAUTIQUE',
                'SKIING' => 'SKI',
                'PADEL' => 'PADEL',
                'ADVENTURE' => 'OUTDOOR',
            ],
            4 => [
                'GOLF' => 'GOLFE',
                'HUNTING' => 'CAÇA',
                'FISHING' => 'PESCA',
                'HORSE' => 'EQUITAÇÃO',
                'DIVING' => 'MERGULHO',
                'BOATING' => 'VELA',
                'SKIING' => 'ESQUI',
                'PADEL' => 'PADEL',
                'ADVENTURE' => 'AVENTURA',
            ],
            5 => [
                'GOLF' => 'GOLF',
                'HUNTING' => 'JAGD',
                'FISHING' => 'ANGELN',
                'HORSE' => 'REITEN',
                'DIVING' => 'TAUCHEN',
                'BOATING' => 'NAUTIK',
                'SKIING' => 'SKI',
                'PADEL' => 'PADEL',
                'ADVENTURE' => 'OUTDOOR',
            ],
            6 => [
                'GOLF' => 'GOLF',
                'HUNTING' => 'CACCIA',
                'FISHING' => 'PESCA',
                'HORSE' => 'EQUITAZIONE',
                'DIVING' => 'SUBACQUEA',
                'BOATING' => 'NAUTICA',
                'SKIING' => 'SCI',
                'PADEL' => 'PADEL',
                'ADVENTURE' => 'OUTDOOR',
            ],
        ];

        $ids = [1, 2, 3, 4, 5, 6, 7, 8, 9];

        $sports_in_language = array_map(function ($id) use ($sports_map, $sports_translation_map, $lang) {
            $sport_name = $sports_map[$id];
            return [
                'id' => $id,
                'name' => $sports_translation_map[$lang][$sport_name] ?? $sport_name,
            ];
        }, $ids);


        return $sports_in_language;
    }
    //public function initContent() {
    //     parent::initContent();

    // $checkMinimumQtyMunition = $this->checkMinimumQtyMunition(null);
    // if ($checkMinimumQtyMunition) {
    //     $this->errors[] = $checkMinimumQtyMunition;
    //     Tools::redirect('cart');
    // }

    //     $presenter = new CartPresenter();
    //     $presented_cart = $presenter->present($this->context->cart, $shouldSeparateGifts = true,$this->context->language->id);

    //     //Añadido addis para control de descuentos de productos en el carrito
    //     $suma_descuentos = 0;
    //     if(isset($presentedCart)){
    //         foreach ($presentedCart['products'] as $producto) {
    //            $suma_descuentos += $producto['quantity_wanted']*(float)$producto['reduction'];
    //         }
    //     }
    //     $this->context->smarty->assign([
    //         'suma_descuentos' => $suma_descuentos,
    //     ]);
    //     //Hasta aqui

    //     $this->context->smarty->assign([
    //         'id_lang' => $this->context->language->id,
    //         'cart' => $presented_cart,
    //     ]);

    // }
    // public function displayAjaxselectTarifaPlana()
    // {
    //     $cart = $this->cart_presenter->present(
    //         $this->context->cart,
    //         true
    //     );
    //     ob_end_clean();
    //     header('Content-Type: application/json');
    //     $this->ajaxRender(Tools::jsonEncode([
    //         'preview' => $this->render('checkout/_partials/cart-summary', [
    //             'cart' => $cart,
    //             'static_token' => Tools::getToken(false),
    //         ]),
    //     ]));
    // }
    // public function postProcess()
    // {
    //     if (Tools::getValue('ajax') && Tools::getValue('action') == 'selectTarifaPlana') {
    //         $id_product = Tools::getValue('product_tarifa_plana');
    //         $id_product_attribute = Tools::getValue('product_attribute_tarifa_plana');
    //         $customization_id = 0;
    //         $qty = 1;
    //         $add_tariaplana = Tools::getvalue('add_tarifaplana');
    //         if ($id_product) {
    //             if ($add_tariaplana) {
    //                 if (!$this->context->cart->containsProduct((int) $id_product, (int) $id_product_attribute, (int) $customization_id)) {

    //                     $this->context->cart->updateQty($qty, (int) $id_product, (int) $id_product_attribute, (int) $customization_id, $operator = 'up', $this->context->cart->id_address_delivery, $shop = null, $auto_add_cart_rule = true);
    //                 }
    //             } else {
    //                 if ($this->context->cart->containsProduct((int) $id_product, (int) $id_product_attribute, (int) $customization_id)) {

    //                     $this->context->cart->deleteProduct((int) $id_product, (int) $id_product_attribute, (int) $customization_id, $this->context->cart->id_address_delivery);
    //                 }
    //             }
    //         }
    //     }
    //     parent::postProcess();
    // }
    /*
    * module: quantitydiscountpro
    * date: 2022-04-04 17:10:59
    * version: 2.1.37
    */
    protected function bootstrap()
    {
        parent::bootstrap();
        if (Module::isEnabled('quantitydiscountpro') && Tools::getValue('action') == 'updateCarrier') {
            include_once(_PS_MODULE_DIR_ . 'quantitydiscountpro/quantitydiscountpro.php');
            $quantityDiscount = new QuantityDiscountRule();
            $quantityDiscount->createAndRemoveRules();
        }
    }

    /**
     * la compra mínima de los balines son 4uds (da igual el producto mientras sean balines).
     * la compra mínima de los cartuchos son 10uds (da igual el producto mientras sean cartuchos) a excepción de la marca imperator que la compra mínima son 10uds.
     *
     * balines -> si el producto se encuentra en armas_de_balines-municion_para_armas_de_balines y municion-municion_para_armas_de_balines (id_category = 577 y 3368)
     * cartuchos -> si el producto está asociado a la categoría cartuchos (id_category = 193)
     * marca imperator: 133
     *
     * JLP - 29/12/2022 - Por petición de Alvarez se modifica la forma de comprobar si un producto es cartucho/balin
     * cartucho: Subfamilia 100001670
     * balines: Grupo 100005302
     *
     */
    // protected function checkMinimumQtyMunition($products = null) {
    //     if (!$products) {
    //         $products = $this->context->cart->getProducts();
    //     }

    //     $qty_balines = 0;
    //     $qty_cartuchos = 0;
    //     $qty_cartuchos_imperator = 0;

    //     foreach ($products as $product) {
    //         $es_balin = false;
    //         $es_cartucho = false;
    //         $es_cartucho_imperator = false;

    //         //$category_balines = Configuration::get('BAN_CATEGORY_BALINES');
    //         //$category_cartuchos = Configuration::get('BAN_CATEGORY_CARTUCHOS');
    //         $subfamily_cartuchos = Configuration::get('BAN_FAMILY_CARTUCHOS');
    //         $group_balines = Configuration::get('BAN_GROUP_BALINES');
    //         $marca_imperator = Configuration::get('BAN_MARCA_IMPERATOR');

    //         //$categories_product = Product::getProductCategories((int) $product['id_product']);

    //         // comprobamos si es un producto del tipo balines
    //         /*if ($category_balines && !empty($category_balines)) {
    //             foreach (explode(',', $category_balines) as $category_balines_item) {
    //                 if (is_numeric($category_balines_item)) {
    //                     if (in_array($category_balines_item, $categories_product)) {
    //                         $es_balin = true;
    //                         break;
    //                     }
    //                 }
    //             }
    //         }*/
    //         if ($group_balines && !empty($group_balines)) {
    //             foreach (explode(',', $group_balines) as $group_balines_item) {
    //                 if (Product::getGrupoAlvarez((int) $product['id_product'], (int) $product['id_product_attribute'], null) == $group_balines_item) {
    //                     $es_balin = true;
    //                     break;
    //                 }
    //             }
    //         }

    //         // comprobamos si es un producto del tipo cartucho y si es de la marca imperator
    //         /*if ($category_cartuchos && !empty($category_cartuchos) && is_numeric($category_cartuchos)) {
    //             if (in_array($category_cartuchos, $categories_product)) {
    //                 $es_cartucho = true;

    //                 if ($marca_imperator && !empty($marca_imperator) && is_numeric($marca_imperator)) {
    //                     if ($product['id_manufacturer'] == $marca_imperator) {
    //                         $es_cartucho_imperator = true;
    //                     }
    //                 }
    //             }
    //         }*/
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
}
