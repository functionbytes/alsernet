<?php

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Alsernetshopping extends Module implements WidgetInterface
{
    public function __construct(){

        $this->name = 'alsernetshopping';
        $this->author = 'Alsernet';
        $this->version = '2.0.4';
        $this->need_instance = 0;
        $this->controllers = array('productscompare', 'mywishlist', 'viewwishlist');

        parent::__construct();

        // Load carrier system
        require_once dirname(__FILE__) . '/controllers/front/Carriers/load_carriers.php';

        //$this->secure_key = Tools::encrypt($this->name);
        $this->displayName = "Alsernet shopping";
        $this->description = $this->getTranslator()->trans('Make your customers feel at home on your store, invite them to sign in!', [], 'Modules.Customersignin.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

    }
    public function install(){

        return parent::install() &&
            $this->registerHook('header')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayFooterProduct')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('actionUpdateLangAfter');

    }


    public function getWidgetVariables($hookName, array $configuration){

        $url = $this->getCartSummaryURL();

        return [
            'cart' => (new CartPresenter())->present(isset($params['cart']) ? $params['cart'] : $this->context->cart),
            'refresh' => $this->context->link->getModuleLink('alsernetshopping', 'ajax', [], null, null, null, true),
            'url' => $url,
        ];

    }

    private function getCartSummaryURL(){
        return $this->context->link->getPageLink('cart', null, $this->context->language->id,['action' => 'show',],false,null,true);
    }

    public function renderWidget($hookName, array $configuration){

        if ($hookName == 'displayFooterProduct') {

            $this->smarty->assign(array(
                'product' => $configuration['product'],
                'category' => $configuration['category'],
            ));

            //return $this->fetch('module:alsernetshopping/views/templates/hook/partial/product-sticky.tpl');

        }elseif($hookName == 'displayNav2' && $configuration['action'] == 'shopping') {
            return $this->fetch('module:alsernetshopping/views/templates/hook/shopping/shopping.tpl');
        }elseif(isset($configuration['type'])) {

            if($configuration['type'] == 'reassurance') {
                return $this->fetch('module:alsernetshopping/views/templates/hook/view/reassurance.tpl');
            }

        }

    }

    public function hookHeader($params){

        $this->context->controller->addCSS($this->_path.'views/css/front/style.css','all');

        $this->context->controller->addCSS($this->_path.'views/css/front/orders/confirmation.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/checkout/checkout.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/checkout/cart.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/orders/order.css','all');

        $this->context->controller->addCSS($this->_path.'views/css/front/components/cart.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/components/minipupup.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/components/modal.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/components/sticky.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/components/header.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/components/dropdown.css','all');

        $this->context->controller->addCSS($this->_path.'views/css/front/checkout/carriers/guard-pickup.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/checkout/carriers/store-pickup.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/checkout/carriers/delivery-address.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/checkout/carriers/correosexpress-carrier.css','all');
        $this->context->controller->addCSS($this->_path.'views/css/front/checkout/carriers/mondialrelay.css','all');


        // ========================================
        // ✅ ORDEN CORRECTO DE CARGA - CRÍTICO
        // ========================================

        // 1. LIBRERÍAS BASE (TOP - primera carga)
        $this->context->controller->registerJavascript(
            'alsernet-toastrs',
            $this->_path . 'views/vendor/toastr/toastr.min.js',
            ['position' => 'top', 'priority' => 1]
        );

        // 2. GTM MANAGER (CORE GTM - DEBE CARGAR PRIMERO)
        $this->context->controller->registerJavascript(
            'alsernet-gtm-manager',
            $this->_path.'views/js/front/gtm/gtm-manager.js',
            ['position' => 'bottom', 'priority' => 5]
        );

        // 3. GTM CONFIGS (dependen de GTM Manager)
        $this->context->controller->registerJavascript(
            'alsernet-gtm-checkout-config',
            $this->_path.'views/js/front/checkout/checkout-gtm-config.js',
            ['position' => 'bottom', 'priority' => 10]
        );

        $this->context->controller->registerJavascript(
            'alsernet-search-gtm-config',
            $this->_path.'views/js/front/search/search-gtm-config.js',
            ['position' => 'bottom', 'priority' => 1]
        );

        $this->context->controller->registerJavascript(
            'alsernet-gtm-cart-config',
            $this->_path.'views/js/front/cart/cart-gtm-config.js',
            ['position' => 'bottom', 'priority' => 1]
        );

        // 4. CHECKOUT CORE (FUNDAMENTAL - antes que todo lo demás)
        $this->context->controller->registerJavascript(
            'alsernet-checkout-manager',
            $this->_path.'views/js/front/checkout/checkout-manager.js',
            ['position' => 'bottom', 'priority' => 15]
        );

        // 5. CHECKOUT AUXILIARES (dependen de CheckoutManager)
        $this->context->controller->registerJavascript(
            'alsernet-modal-strategies',
            $this->_path.'views/js/front/checkout/checkout-modal-strategies.js',
            ['position' => 'bottom', 'priority' => 20]
        );

        $this->context->controller->registerJavascript(
            'alsernet-checkout-block',
            $this->_path.'views/js/front/checkout/checkout-block.js',
            ['position' => 'bottom', 'priority' => 25]
        );

        $this->context->controller->registerJavascript(
            'alsernet-checkout-navigation',
            $this->_path.'views/js/front/checkout/checkout-navigation.js',
            ['position' => 'bottom', 'priority' => 30]
        );

        // 6. STEP HANDLERS (dependen de CheckoutManager + Navigation)
        $this->context->controller->registerJavascript(
            'alsernet-login-step',
            $this->_path.'views/js/front/checkout/steps/login/login-step.js',
            ['position' => 'bottom', 'priority' => 35]
        );

        $this->context->controller->registerJavascript(
            'alsernet-address-step',
            $this->_path.'views/js/front/checkout/steps/address/address-step.js',
            ['position' => 'bottom', 'priority' => 40]
        );

        $this->context->controller->registerJavascript(
            'alsernet-delivery-step',
            $this->_path.'views/js/front/checkout/steps/delivery/delivery-step.js',
            ['position' => 'bottom', 'priority' => 45]
        );

        $this->context->controller->registerJavascript(
            'alsernet-payment-step',
            $this->_path.'views/js/front/checkout/steps/payment/payment-step.js',
            ['position' => 'bottom', 'priority' => 50]
        );

        // 7. CARRIERS (dependen de delivery-step)
        $this->context->controller->registerJavascript(
            'alsernet-guard-pickup',
            $this->_path . 'views/js/front/checkout/steps/delivery/carriers/guard-pickup.js',
            ['position' => 'bottom', 'priority' => 55]
        );

        $this->context->controller->registerJavascript(
            'alsernet-correosexpress-carrier',
            $this->_path . 'views/js/front/checkout/steps/delivery/carriers/correosexpress-carrier.js',
            ['position' => 'bottom', 'priority' => 56]
        );

        $this->context->controller->registerJavascript(
            'alsernet-delivery-address-carrier',
            $this->_path . 'views/js/front/checkout/steps/delivery/carriers/delivery-address.js',
            ['position' => 'bottom', 'priority' => 57]
        );

        // 8. HIPAY SDK & PAYMENT RESOURCES (para checkout personalizado)
        $this->loadHipayResources();

        // 9. CART (independiente pero usa GTM)
        $this->context->controller->registerJavascript(
            'alsernet-cart-manager',
            $this->_path.'views/js/front/cart/cart-manager.js',
            ['position' => 'bottom', 'priority' => 60]
        );


    }

    public function isTokenValid(){
        if (!Configuration::get('PS_TOKEN_ENABLE')) {
            return true;
        }
        return strcasecmp(Tools::getToken(false), Tools::getValue('token')) == 0;
    }

    protected function createTables(){
        $res = 1;
        include_once(dirname(__FILE__) . '/install/install.php');
        return $res;
    }

    public function deleteTables(){
        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS
            `' . _DB_PREFIX_ . 'alsernetshopping_product_review`,
			`' . _DB_PREFIX_ . 'alsernetshopping_product_review_criterion`,
			`' . _DB_PREFIX_ . 'alsernetshopping_product_review_criterion_product`,
			`' . _DB_PREFIX_ . 'alsernetshopping_product_review_criterion_lang`,
			`' . _DB_PREFIX_ . 'alsernetshopping_product_review_criterion_category`,
			`' . _DB_PREFIX_ . 'alsernetshopping_product_review_grade`,
			`' . _DB_PREFIX_ . 'alsernetshopping_product_review_usefulness`,
			`' . _DB_PREFIX_ . 'alsernetshopping_product_review_report`,
			`' . _DB_PREFIX_ . 'alsernetshopping_compare`,
			`' . _DB_PREFIX_ . 'alsernetshopping_compare_product`,
			`' . _DB_PREFIX_ . 'alsernetshopping_wishlist`,
			`' . _DB_PREFIX_ . 'alsernetshopping_wishlist_product`
		');

    }

    /**
     * Carga los recursos de HiPay SDK cuando el módulo está activo
     * Se integra en el hookHeader para mantener consistencia con la estructura del módulo
     */
    private function loadHipayResources()
    {
        // Solo cargar si estamos en páginas donde puede haber checkout
        $relevantPages = ['order', 'cart', 'module-alsernetshopping'];
        $currentPage = $this->context->controller->php_self ?? '';

        if (!in_array($currentPage, $relevantPages) &&
            strpos($this->context->controller->page_name ?? '', 'checkout') === false) {
            return; // No cargar en páginas irrelevantes
        }

        $hipayModule = Module::getInstanceByName('hipay_enterprise');

        if (!$hipayModule || !$hipayModule->active) {
            return; // HiPay no está disponible
        }

        try {
            $hipayConfig = $hipayModule->hipayConfigTool->getConfigHipay();
            $sdkUrl = $hipayConfig['payment']['global']['sdk_js_url'] ?? '';

            if (!$sdkUrl) {
                return; // No hay URL del SDK configurada
            }

            // ===== CSS DE HIPAY =====
            $this->context->controller->addCSS('modules/hipay_enterprise/views/css/card-js.min.css');
            $this->context->controller->addCSS('modules/hipay_enterprise/views/css/hipay-enterprise.css');

            // ===== SDK PRINCIPAL DE HIPAY =====
            $this->context->controller->registerJavascript(
                'hipay-sdk-js',
                $sdkUrl,
                ['server' => 'remote', 'position' => 'bottom', 'priority' => 58]
            );

            // ===== JS BÁSICOS SIEMPRE NECESARIOS =====
            $this->context->controller->registerJavascript(
                'hipay-strings',
                'modules/hipay_enterprise/views/js/strings.js',
                ['position' => 'bottom', 'priority' => 59]
            );

            $this->context->controller->registerJavascript(
                'hipay-form-control',
                'modules/hipay_enterprise/views/js/form-input-control.js',
                ['position' => 'head', 'priority' => 59]
            );

            $this->context->controller->registerJavascript(
                'hipay-device-fingerprint',
                'modules/hipay_enterprise/views/js/devicefingerprint.js',
                ['position' => 'bottom', 'priority' => 59]
            );

            $this->context->controller->registerJavascript(
                'hipay-cc-functions',
                'modules/hipay_enterprise/views/js/cc.functions.js',
                ['position' => 'bottom', 'priority' => 59]
            );

            // ===== JS ESPECÍFICOS SEGÚN UX MODE =====
            $uxMode = $hipayConfig['payment']['global']['operating_mode']['UXMode'] ?? '';

            switch ($uxMode) {
                case 'direct_post':
                    $this->context->controller->registerJavascript(
                        'hipay-card-js',
                        'modules/hipay_enterprise/views/js/card-js.min.js',
                        ['position' => 'bottom', 'priority' => 60]
                    );
                    $this->context->controller->registerJavascript(
                        'hipay-card-tokenize',
                        'modules/hipay_enterprise/views/js/card-tokenize.js',
                        ['position' => 'bottom', 'priority' => 61]
                    );
                    break;

                case 'hosted_fields':
                    $this->context->controller->registerJavascript(
                        'hipay-hosted-fields',
                        'modules/hipay_enterprise/views/js/hosted-fields.js',
                        ['position' => 'bottom', 'priority' => 60]
                    );
                    break;
            }

            // Log para debug
            error_log('✅ HiPay SDK loaded via alsernetshopping module - UX Mode: ' . $uxMode);

        } catch (Exception $e) {
            error_log('❌ Error loading HiPay SDK via alsernetshopping: ' . $e->getMessage());
        }
    }

}