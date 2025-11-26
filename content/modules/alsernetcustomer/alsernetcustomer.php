<?php

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
require_once _PS_MODULE_DIR_ . 'alsernetcustomer/classes/Wishlist/Wishlist.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class Alsernetcustomer extends Module implements WidgetInterface
{
    public function __construct(){
        $this->name = 'alsernetcustomer';
        $this->author = 'Alsernet';
        $this->version = '1.0.0';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = "Alsernet - Customer ";
        $this->description = $this->getTranslator()->trans('Make your customers feel at home on your store, invite them to sign in!', [], 'Modules.Customersignin.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

    }

    public function install(){

        return parent::install() && $this->registerHook('displayAuthLogin')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('displayCustomerGdpr')
            && $this->registerHook('displayCustomerCookies')
            && $this->registerHook('displayCustomerOrderHistory')
            && $this->registerHook('displayCustomerCoupons')
            && $this->registerHook('displayCustomerAddress')
            && $this->registerHook('displayCustomerInformation')
            && $this->registerHook('displayTop')
            && $this->registerHook('displayHeader')
            && $this->registerHook('header');
    }

    public function hookDisplayBeforeBodyClosingTag($params)
    {
        if (!$this->context->customer->isLogged()) {
            return $this->fetch('module:alsernetcustomer/views/templates/hook/wishlist/modal-button.tpl');
        }
    }

    public function getWidgetVariables($hookName, array $configuration){
    }

    public function renderWidget($hookName, array $configuration){

        if ($hookName == 'displayCustomerGdpr') {
            return $this->fetch('module:alsernetcustomer/views/templates/pages/gdpr.tpl');
        }elseif ($hookName == 'displayCustomerCookies') {
            return $this->fetch('module:alsernetcustomer/views/templates/pages/cookies.tpl');
        }elseif ($hookName == 'displayCustomerOrderHistory') {
            return $this->fetch('module:alsernetcustomer/views/templates/pages/orders.tpl');
        }elseif ($hookName == 'displayCustomerCoupons') {
            return $this->fetch('module:alsernetcustomer/views/templates/pages/cupons.tpl');
        }elseif ($hookName == 'displayCustomerAddress') {
            return $this->fetch('module:alsernetcustomer/views/templates/pages/address.tpl');
        }elseif ($hookName == 'displayCustomerInformation') {
            return $this->fetch('module:alsernetcustomer/views/templates/pages/information.tpl');
        }elseif($hookName == 'displayNav2' &&  $configuration['action'] == 'wishlist') {

            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
            $logged = $this->context->customer->isLogged();
            $iso =  $this->context->language->iso_code;


            if ($logged) {

                $wishlist_product = 0;

                $wishlist = WishList::existsLang($this->context->customer->id, 1);

                if ($wishlist) {

                    $wishlist_product = WishList::getProductByIdWishlist((int)$wishlist['id_wishlist'], $this->context->customer->id, 1);

                }

                $this->smarty->assign(array(
                    'iso' => $iso,
                    'isloggedwishlist' => true,
                    'wishlist_product' => count($wishlist_product),
                ));

                return $this->fetch('module:alsernetcustomer/views/templates/hook/wishlist/wishlist.tpl');
            }

        }elseif(isset($configuration['type'])) {

            if($configuration['type'] == 'wishlist') {

                $wishlist = null;
                $id_wishlist = false;
                $is_product_in_wishlist = false;
                $wishlist_product_info = null;
                $id_product = $configuration['product']['id_product'];
                $id_product_attribute = $configuration['product']['id_product_attribute'];

                if ($this->context->customer->isLogged()) {

                    $wishlist = Wishlist::existsLang($this->context->customer->id, 1);

                    if ($wishlist) {
                        $id_wishlist = (int) $wishlist['id_wishlist'];

                        $products_in_wishlist = Wishlist::getProductByIdCustomer(
                            $id_wishlist,
                            $this->context->customer->id,
                            $this->context->language->id,
                            $id_product
                        );

                        $is_product_in_wishlist = false;
                        $wishlist_product_info = null;

                        if (!empty($products_in_wishlist)) {
                            foreach ($products_in_wishlist as $product) {
                                // Verificar si coincide el producto y el atributo
                                if ((int)$product['id_product'] === (int)$id_product) {
                                    // Si no hay atributo específico o coincide el atributo
                                    if ($id_product_attribute == 0 || (int)$product['id_product_attribute'] === (int)$id_product_attribute) {
                                        $is_product_in_wishlist = true;
                                        $wishlist_product_info = array(
                                            'id_wishlist' => $id_wishlist,
                                            'id_product' => $product['id_product'],
                                            'id_product_attribute' => $product['id_product_attribute'],
                                            'quantity' => $product['quantity'],
                                            'wishlist_name' => $wishlist['name']
                                        );
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    $this->smarty->assign(array(
                        'wishlists' => $wishlist,
                        'wishlist_id' => $id_wishlist,
                        'wishlist_id_product' => $id_product,
                        'wishlist_id_product_attribute' => $id_product_attribute,
                        'is_product_in_wishlist' => $is_product_in_wishlist,
                        'added_wishlist' => $is_product_in_wishlist,
                        'has_wishlists' => count($wishlist) > 0,
                        'current_wishlist_info' => $wishlist,
                    ));

                    return $this->fetch('module:alsernetcustomer/views/templates/hook/wishlist/button.tpl');
                } else {
                    return $this->fetch('module:alsernetcustomer/views/templates/hook/wishlist/not-button.tpl');
                }

            }elseif($configuration['type'] == 'stick-wishlist') {

                $added_wishlist = false;
                $id_product = $configuration['product']['id_product'];
                $id_product_attribute = $configuration['product']['id_product_attribute'];
                $id_wishlist = false;

                if ($this->context->customer->isLogged()) {

                    $wishlist = Wishlist::existsLang($this->context->customer->id, 1);

                    if ($wishlist) {
                        $id_wishlist = (int) $wishlist['id_wishlist'];

                        // Método 1: Verificar usando getProductByIdCustomer
                        $products_in_wishlist = Wishlist::getProductByIdCustomer(
                            $id_wishlist,
                            $this->context->customer->id,
                            1,
                            $id_product
                        );

                        $added_wishlist = false;
                        if (!empty($products_in_wishlist)) {
                            foreach ($products_in_wishlist as $product) {
                                if ((int)$product['id_product'] === (int)$id_product) {
                                    if ($id_product_attribute == 0 || (int)$product['id_product_attribute'] === (int)$id_product_attribute) {
                                        $added_wishlist = true;
                                        break;
                                    }
                                }
                            }
                        }

                        // Método 2: Fallback usando getSimpleProductByIdValidate (como en la lógica original)
                        if (!$added_wishlist) {
                            $added_wishlist = Wishlist::getSimpleProductByIdValidate(
                                $this->context->customer->id,
                                $id_product,
                                $id_product_attribute,
                                1,
                                $this->context->shop->id
                            );
                        }
                    } else {
                        $added_wishlist = false;
                        $id_wishlist = false;
                    }

                    $this->smarty->assign(array(
                        'added_wishlist' => $added_wishlist,
                        'id_wishlist' => $id_wishlist,
                        'id_product' => $id_product,
                        'id_product_attribute' => $id_product_attribute,
                    ));

                    return $this->fetch('module:alsernetcustomer/views/templates/hook/wishlist/stick.tpl');

                }


            }

        }
    }
    public function hookDisplayCustomerGdpr($params){
        return $this->renderWidget('displayCustomerGdpr', $params);
    }
    public function hookDisplayCustomerCookies($params){
        return $this->renderWidget('displayCustomerCookies', $params);
    }
    public function hookDisplayCustomerOrderHistory($params){
        return $this->renderWidget('displayCustomerOrderHistory', $params);
    }
    public function hookDisplayCustomerCoupons($params){
        return $this->renderWidget('displayCustomerCoupons', $params);
    }

    public function hookDisplayCustomerAddress($params){
        return $this->renderWidget('displayCustomerAddress', $params);
    }

    public function hookDisplayCustomerInformation($params){
        return $this->renderWidget('displayCustomerInformation', $params);
    }

    public function hookHeader($params){

        $this->context->controller->addCSS($this->_path.'views/css/front/customer.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/wishlist.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/order.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/refund.css','all');


        $this->context->controller->registerJavascript(
            'module-toastr',
            $this->_path . 'views/js/vendor/toastr/toastr.min.js',
            ['position' => 'bottom', 'priority' => 10]
        );


        $this->context->controller->addJS($this->_path . 'views/js/vendor/validate/validate.js');
        $this->context->controller->addJS($this->_path . 'views/js/vendor/validate/messages.js');
        $this->context->controller->addJS($this->_path.'views/js/front/customer.js');
        $this->context->controller->addJS($this->_path.'views/js/front/address.js');
        $this->context->controller->addJS($this->_path.'views/js/front/orders.js');
        $this->context->controller->addJS($this->_path.'views/js/front/information.js');
        $this->context->controller->addJS($this->_path.'views/js/front/refund.js');
        $this->context->controller->addJS($this->_path.'views/js/front/scripts.js');

        $this->context->controller->registerJavascript(
            'alsernet-customer-wishlist-manager',
            $this->_path.'views/js/front/wishlist-manager.js',
            ['position' => 'bottom', 'priority' => 61]
        );
    }

}


