<?php
/**
 * Quantity Discount Pro
 *
 * NOTICE OF LICENSE
 *
 * This product is licensed for one customer to use on one installation (test stores and multishop included).
 * Site developer has the right to modify this module to suit their needs, but can not redistribute the module in
 * whole or in part. Any other use of this module constitues a violation of the user agreement.
 *
 * DISCLAIMER
 *
 * NO WARRANTIES OF DATA SAFETY OR MODULE SECURITY
 * ARE EXPRESSED OR IMPLIED. USE THIS MODULE IN ACCORDANCE
 * WITH YOUR MERCHANT AGREEMENT, KNOWING THAT VIOLATIONS OF
 * PCI COMPLIANCY OR A DATA BREACH CAN COST THOUSANDS OF DOLLARS
 * IN FINES AND DAMAGE A STORES REPUTATION. USE AT YOUR OWN RISK.
 *
 *  @author    idnovate.com <info@idnovate.com>
 *  @copyright 2020 idnovate.com
 *  @license   See above
 */
use Symfony\Component\Translation\TranslatorInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
class CheckoutAddressesStep extends CheckoutAddressesStepCore
{

    private $addressForm;
    private $use_same_address = true;
    private $need_invoice_option = false;
    private $need_invoice = false;
    private $show_delivery_address_form = false;
    private $show_invoice_address_form = false;
    private $form_has_continue_button = false;

    /* pedro bloqueo */
    private $productsblocked = [];

    private $need_dni = false;


    public function __construct(
        Context $context,
        TranslatorInterface $translator,
        CustomerAddressForm $addressForm
    ) {
        parent::__construct($context, $translator, $addressForm);
        $this->addressForm = $addressForm;
    }

    public function getDataToPersist()
    {
        if ($this->checkNeedInvoiceByProductTypeInCart()){
            $this->need_invoice = true;
        }

        if ($this->checkNeedDNIByProductTypeInCart()){
            $this->need_dni = true;
        }

        if ($this->checkNeedDNIByProductGunCart()){
            $this->need_dni = true;
        }

        if ($this->checkNeedDNIByCategoryInCart()){
            $this->need_dni = true;
        }


        if ($this->checkNeedDNIByCountry()){
            $this->need_dni = true;
        }

        if ($this->checkNeedInvoiceByOrderTotal()){
            $this->need_invoice = true;
        }

        return [
            'use_same_address' => $this->use_same_address,
            'need_invoice' => $this->need_invoice,
        ];
    }

    public function restorePersistedData(array $data)
    {
        if (array_key_exists('use_same_address', $data)) {
            $this->use_same_address = $data['use_same_address'];
        }
        if (array_key_exists('need_invoice', $data)) {
            $this->need_invoice = $data['need_invoice'];
        }

        if ($this->checkNeedInvoiceByProductTypeInCart()){
            $this->need_invoice = true;
        }

        if ($this->checkNeedDNIByProductTypeInCart()){
            $this->need_dni = true;
        }

        if ($this->checkNeedDNIByProductGunCart()){
            $this->need_dni = true;
        }

        if ($this->checkNeedDNIByCategoryInCart()){
            $this->need_dni = true;
        }

        if ($this->checkNeedDNIByCountry()){
            $this->need_dni = true;
        }

        if ($this->checkNeedInvoiceByOrderTotal()){
            $this->need_invoice = true;
        }

        return $this;
    }

    public function handleRequest(array $requestParams = [])
    {
        $this->addressForm->setAction($this->getCheckoutSession()->getCheckoutURL());

        if (array_key_exists('use_same_address', $requestParams)) {
            $this->use_same_address = (bool) $requestParams['use_same_address'];
            if (!$this->use_same_address) {
                $this->setCurrent(true);
            }
        }

        if (array_key_exists('need_invoice', $requestParams)) {
            $this->need_invoice = (bool) $requestParams['need_invoice'];

            if ($this->checkNeedInvoiceByProductTypeInCart()){
                $this->need_invoice = true;
            }

            if ($this->checkNeedDNIByProductTypeInCart()){
                $this->need_invoice = true;
            }


            if ($this->checkNeedDNIByProductGunCart()){
                $this->need_dni = true;
            }

            if ($this->checkNeedDNIByCategoryInCart()){
                $this->need_invoice = true;
            }

            if ($this->checkNeedDNIByCountry()){
                $this->need_invoice = true;
            }

            if ($this->checkNeedInvoiceByOrderTotal()){
                $this->need_invoice = true;
            }

            if (!$this->need_invoice) {
                $this->setCurrent(true);
            }
        }


        if (isset($requestParams['cancelAddress'])) {
            if ($requestParams['cancelAddress'] === 'invoice') {
                if ($this->getCheckoutSession()->getCustomerAddressesCount() < 2) {
                    $this->use_same_address = true;
                }
            }
            $this->setCurrent(true);
        }


        if (isset($requestParams['need-invoice']) || $this->need_invoice) {
            $this->use_same_address = true;
        }

        // Can't really hurt to set the firstname and lastname.
        $this->addressForm->fillWith([
            'firstname' => $this->getCheckoutSession()->getCustomer()->firstname,
            'lastname' => $this->getCheckoutSession()->getCustomer()->lastname,
        ]);

        if (isset($requestParams['saveAddress'])) {
            $saved = $this->addressForm->fillWith($requestParams)->submit();
            if (!$saved) {
                $this->setCurrent(true);
                $this->getCheckoutProcess()->setHasErrors(true);
                if ($requestParams['saveAddress'] === 'delivery') {
                    $this->show_delivery_address_form = true;
                } else {
                    $this->show_invoice_address_form = true;
                }
            } else {
                if ($requestParams['saveAddress'] === 'delivery' || isset($requestParams['newAddress'])) {
                    $this->use_same_address = isset($requestParams['use_same_address']) || (isset($requestParams['need-invoice']) && $requestParams['need-invoice']);
                }
                $id_address = $this->addressForm->getAddress()->id;
                if ($requestParams['saveAddress'] === 'delivery' || isset($requestParams['newAddress'])) {
                    $this->getCheckoutSession()->setIdAddressDelivery($id_address);
                    $idAddressInvoice = $this->use_same_address ? $id_address : null;
                    $this->getCheckoutSession()->setIdAddressInvoice($idAddressInvoice);
                } else {
                    $this->getCheckoutSession()->setIdAddressInvoice($id_address);
                }
            }
        } elseif (isset($requestParams['newAddress'])) {
            // while a form is open, do not go to next step
            $this->setCurrent(true);
            if ($requestParams['newAddress'] === 'delivery') {
                $this->show_delivery_address_form = true;
            } else {
                $this->show_invoice_address_form = true;
            }
            $this->addressForm->fillWith($requestParams);
            $this->form_has_continue_button = $this->use_same_address;
        } elseif (isset($requestParams['editAddress'])) {
            // while a form is open, do not go to next step
            $this->setCurrent(true);
            if ($requestParams['editAddress'] === 'delivery') {
                $this->show_delivery_address_form = true;
            } else {
                $this->show_invoice_address_form = true;
            }
            $this->addressForm->loadAddressById($requestParams['id_address']);
        } elseif (isset($requestParams['deleteAddress'])) {
            $addressPersister = new CustomerAddressPersister(
                $this->context->customer,
                $this->context->cart,
                Tools::getToken(true, $this->context)
            );

            $deletionResult = (bool) $addressPersister->delete(
                new Address((int) Tools::getValue('id_address'), $this->context->language->id),
                Tools::getValue('token')
            );
            if ($deletionResult) {
                $this->context->controller->success[] = $this->getTranslator()->trans(
                    'Address successfully deleted!',
                    [],
                    'Shop.Notifications.Success'
                );
                $this->context->controller->redirectWithNotifications(
                    $this->getCheckoutSession()->getCheckoutURL()
                );
            } else {
                $this->getCheckoutProcess()->setHasErrors(true);
                $this->context->controller->errors[] = $this->getTranslator()->trans(
                    'Could not delete address.',
                    [],
                    'Shop.Notifications.Error'
                );
            }
        }

        if (isset($requestParams['confirm-addresses'])) {
            if ((isset($requestParams['need-invoice']) && $requestParams['need-invoice'] == '1')) {
                $this->need_invoice = true;
            } else {
                $this->need_invoice = false;
            }

            if ($this->checkNeedInvoiceByProductTypeInCart()){
                $this->need_invoice = true;
            }

            if ($this->checkNeedDNIByProductTypeInCart()){
                $this->need_dni = true;
            }


            if ($this->checkNeedDNIByProductGunCart()){
                $this->need_dni = true;
            }


            if ($this->checkNeedDNIByCategoryInCart()){
                $this->need_dni = true;
            }
            if ($this->checkNeedDNIByCountry()){
                $this->need_dni = true;
            }

            if ($this->checkNeedInvoiceByOrderTotal()){
                $this->need_invoice = true;
            }

            /* JLP - 27/04/2022 - Guardar en pedido si genera factura */
            $this->context->cart->need_invoice = $this->need_invoice;
            $this->context->need_invoice = $this->need_invoice;

            if (isset($requestParams['id_address_delivery'])) {
                $id_address = $requestParams['id_address_delivery'];

                if (!Customer::customerHasAddress($this->getCheckoutSession()->getCustomer()->id, $id_address)) {
                    $this->getCheckoutProcess()->setHasErrors(true);
                } else {
                    if ($this->getCheckoutSession()->getIdAddressDelivery() != $id_address) {
                        $this->setCurrent(true);
                        $this->getCheckoutProcess()->invalidateAllStepsAfterCurrent();
                    }
                    $this->getCheckoutSession()->setIdAddressDelivery($id_address);
                    //if ($this->use_same_address) {
                    $this->getCheckoutSession()->setIdAddressInvoice($id_address);
                    //}
                }
            }

            if ((isset($requestParams['need-invoice']) && $requestParams['need-invoice'] == '1') || $this->need_invoice) {
                if (isset($requestParams['id_address_invoice'])) {
                    $id_address = $requestParams['id_address_invoice'];
                    if (!Customer::customerHasAddress($this->getCheckoutSession()->getCustomer()->id, $id_address)) {
                        $this->getCheckoutProcess()->setHasErrors(true);
                    } else {
                        $this->getCheckoutSession()->setIdAddressInvoice($id_address);
                    }
                }
            }


            /*Añadido Addis para control de factura*/
            if ($this->checkNeedInvoiceByProductTypeInCart()){
                $this->need_invoice = true;
            }

            if ($this->checkNeedDNIByProductTypeInCart()){
                $this->need_invoice = true;
            }

            if ($this->checkNeedDNIByCategoryInCart()){
                $this->need_invoice = true;
            }
            if ($this->checkNeedDNIByCountry()){
                $this->need_invoice = true;
            }

            if ($this->checkNeedInvoiceByOrderTotal()){
                $this->need_invoice = true;
            }

            /*if (isset($requestParams['need-invoice']) || $this->need_invoice) {
                $this->need_invoice = $requestParams['need-invoice'];
                $this->context->need_invoice = $requestParams['need-invoice'];

                if ($this->checkNeedInvoiceByProductTypeInCart()){
                    $this->need_invoice = 1;
                    $this->context->need_invoice = 1;
                }

                if ($this->checkNeedInvoiceByOrderTotal()){
                    $this->need_invoice = 1;
                    $this->context->need_invoice = 1;
                }

                if (!$this->getCheckoutProcess()->hasErrors()) {
                    $this->setNextStepAsCurrent();
                    $this->setComplete(
                        $this->getCheckoutSession()->getIdAddressInvoice() &&
                        $this->getCheckoutSession()->getIdAddressDelivery()
                    );
                    if ($this->getCheckoutSession()->getIdAddressInvoice() == $this->getCheckoutSession()->getIdAddressDelivery()) {
                        $this->use_same_address = true;
                    }else{
                        $this->use_same_address = false;
                    }
                }
            }else{
                $this->need_invoice = '';
                $this->context->need_invoice = '';
                if (!$this->getCheckoutProcess()->hasErrors()) {
                    $this->setNextStepAsCurrent();
                    $this->setComplete(
                        $this->getCheckoutSession()->getIdAddressInvoice() &&
                        $this->getCheckoutSession()->getIdAddressDelivery()
                    );
                    $this->use_same_address = true;
                    $id_address = $requestParams['id_address_delivery'];
                    $this->getCheckoutSession()->setIdAddressDelivery($id_address);
                    $this->getCheckoutSession()->setIdAddressInvoice($id_address);
                }
            }*/

            if (isset($requestParams['need-invoice']) || $this->need_invoice) {
                $this->need_invoice = $requestParams['need-invoice'];
                $this->context->need_invoice = $requestParams['need-invoice'];
            } else {
                $this->need_invoice = '';
                $this->context->need_invoice = '';
            }

            if ($this->checkNeedInvoiceByProductTypeInCart()){
                $this->need_invoice = 1;
                $this->context->need_invoice = 1;
            }

            if ($this->checkNeedDNIByProductTypeInCart()){
                $this->need_invoice = 1;
                $this->context->need_invoice = 1;
            }

            if ($this->checkNeedDNIByCategoryInCart()){
                $this->need_invoice = 1;
                $this->context->need_invoice = 1;
            }
            if ($this->checkNeedDNIByCountry()){
                $this->need_invoice = 1;
                $this->context->need_invoice = 1;
            }

            if ($this->checkNeedInvoiceByOrderTotal()){
                $this->need_invoice = 1;
                $this->context->need_invoice = 1;
            }

            if (!$this->getCheckoutProcess()->hasErrors()) {
                $this->setNextStepAsCurrent();
                $this->setComplete(
                    $this->getCheckoutSession()->getIdAddressInvoice() &&
                    $this->getCheckoutSession()->getIdAddressDelivery()
                );
                if ($this->getCheckoutSession()->getIdAddressInvoice() == $this->getCheckoutSession()->getIdAddressDelivery()) {
                    $this->use_same_address = true;
                }else{
                    $this->use_same_address = false;
                }
            }
            /*HASTA AQUI*/

            /*if (!$this->getCheckoutProcess()->hasErrors()) {
                $this->setNextStepAsCurrent();
                $this->setComplete(
                    $this->getCheckoutSession()->getIdAddressInvoice() &&
                    $this->getCheckoutSession()->getIdAddressDelivery()
                );

                // if we just pushed the invoice address form, we are using another address for invoice
                // (param 'id_address_delivery' is only pushed in invoice address form)
                if (isset($requestParams['saveAddress'], $requestParams['id_address_delivery'])) {
                    $this->use_same_address = false;
                }
                if ($this->need_invoice == '' || !$this->need_invoice) {
                    $this->use_same_address = true;
                }
            }*/

        }

        $addresses_count = $this->getCheckoutSession()->getCustomerAddressesCount();

        /* JLP - 21/06/2022 - si sólo tengo 1 direccion, marco la bandera de usar la misma direccion a true */
        if ($addresses_count == 1) {
            $this->use_same_address = true;
        }
        /* FIN */

        if ($addresses_count === 0) {
            $this->show_delivery_address_form = true;
        } elseif ($addresses_count < 2 && !$this->use_same_address) {
            $this->show_invoice_address_form = true;
            $this->setComplete(false);
        }

        if ($this->show_invoice_address_form) {
            // show continue button because form is at the end of the step
            $this->form_has_continue_button = true;
        } elseif ($this->show_delivery_address_form) {
            // only show continue button if we're sure
            // our form is at the bottom of the step
            if ($this->use_same_address || $addresses_count < 2) {
                $this->form_has_continue_button = true;
            }
        }

        /* pedro bloqueos */


        $this->setComplete($this->getCheckoutSession()->getIdAddressInvoice() &&
            $this->getCheckoutSession()->getIdAddressDelivery() &&
            $this->comprobarbloqueos() &&
            $this->checkVatNumberIfNeedInvoice()

        );
        /* fin */

        $this->setTitle($this->getTranslator()->trans('Addresses', [], 'Shop.Theme.Checkout'));

        if (Module::isEnabled('quantitydiscountpro')) {
            include_once(_PS_MODULE_DIR_.'quantitydiscountpro/quantitydiscountpro.php');
            $quantityDiscount = new QuantityDiscountRule();
            $quantityDiscount->createAndRemoveRules();
        }

        return $this;
    }

    /* pedro bloqueos */
    public function comprobarbloqueos(){

        $id_cart=(int)Context::getContext()->cookie->id_cart;
        $cart = new Cart($id_cart);
        $products = $cart->getProducts();

        $this->productsblocked = [];

        foreach ($products  as $product) {//2
            $p = new Product($product['id_product']);
            if ($p->isBlocked($product['id_product'])){
                $this->productsblocked[]=$product['id_product'];
            }
        }
        return (count($this->productsblocked)==0);
    }
    /* fin */



    /* jordi si en carrito hay productos tipo arma o armero es obligatorio la factura */
    protected function checkNeedDNIByProductGunCart()
    {
        $need_invoice = false;
        $giun_categories = [587, 588];

        $id_cart = (int) Context::getContext()->cookie->id_cart;
        $cart = new Cart($id_cart);

        if ($cart) {
            $products = $cart->getProducts();
            if ($products) {
                foreach ($products as $product) {
                    $product_categories = Product::getProductCategories($product['id_product']);
                    if (array_intersect($giun_categories, $product_categories)) {
                        $need_invoice = true;
                        break;
                    }
                }
            }
        }

        return $need_invoice;
    }
    /* jordi si en carrito hay productos tipo arma o armero es obligatorio la factura */
    protected function checkNeedInvoiceByProductTypeInCart() {
        $need_invoice = false;

        $id_feature_product_type = 0;
        $id_feature_value_product_type_need_invoice = '';

        if (Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE'))) {
            $id_feature_product_type = (int) Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE');
        }

        if (Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_NEED_INVOICE')) {
            $id_feature_value_product_type_need_invoice = Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_NEED_INVOICE');
        }

        $id_cart = (int) Context::getContext()->cookie->id_cart;
        $cart = new Cart($id_cart);
        if ($cart) {
            $products = $cart->getProducts();
            if ($products) {
                foreach ($products as $key => $product) {
                    if ($product['features']) {
                        foreach ($product['features'] as $feature) {
                            if ((int) $feature['id_feature'] == $id_feature_product_type) {
                                if (strpos(','.$id_feature_value_product_type_need_invoice.',', ','.$feature['id_feature_value'].',') !== false) {
                                    $need_invoice = true;
                                    break 2;
                                }
                            }
                        }
                    }

                    /* JLP - 19/06/2022 - si pertenece al grupo armero */
                    $familia = Product::getGrupoAlvarez($product['id_product'], $product['id_product_attribute'], 0);
                    if ($familia == '100005311' || $familia == '100005312') {
                        $need_invoice = true;
                        break;
                    }
                }
            }
        }

        return $need_invoice;
    }
    /* fin */

    /* jordi si el total del carrito es >= 3000e es obligatorio la factura */
    protected function checkNeedInvoiceByOrderTotal() {
        $need_invoice = false;

        $id_cart = (int) Context::getContext()->cookie->id_cart;
        $cart = new Cart($id_cart);

        if ($cart->getOrderTotal() >= 3000) {
            $need_invoice = true;
        }

        return $need_invoice;
    }
    /* fin */

    /************* ALSERNET ***********/
    protected function checkNeedDNIByProductTypeInCart() {
        $need_dni = false;
        $id_feature_value_product_type_need_dni = '';

        if (Configuration::get('DNI_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_NEED_DNI')) {
            $id_feature_value_product_type_need_dni = Configuration::get('DNI_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_NEED_DNI');
        }

        $id_cart = (int) Context::getContext()->cookie->id_cart;
        $cart = new Cart($id_cart);

        if ($cart) {
            $products = $cart->getProducts();

            if ($products) {
                foreach ($products as $product) {
                    if ($product['features']) {
                        foreach ($product['features'] as $feature) {

                            if (strpos(',' . $id_feature_value_product_type_need_dni . ',', ',' . $feature['id_feature_value'] . ',') !== false) {
                                $need_dni = true;
                                break 2;
                            }

                        }
                    }
                }
            }
        }

        return $need_dni;
        }

    //DNI Y FACTURA NECESARIO PARA CLIENTES DE CANARIAS, CEUTA Y MELILLA

    protected function checkNeedDNIByCountry() {
        $need_dni = false;
        $ue_countries = [1, 2, 3, 233, 16, 76, 74, 20, 37, 191, 6, 243, 244, 242, 86, 7, 8, 9, 142, 26, 10, 124, 130, 12, 138, 13, 14, 245, 15, 36, 18];

        $id_address_delivery = Context::getContext()->cart->id_address_delivery;
        $address = new Address($id_address_delivery);
        $country = new Country ($address->id_country);
        $country_id = $country->id;


        if($country_id === 242 || $country_id === 243){
            $need_dni = true;

        } elseif(!in_array($country_id, $ue_countries)){
            $need_dni = true;

        }
        return $need_dni;


    }

    /* DNI POR CATEGORÍA*/

    protected function checkNeedDNIByCategoryInCart() {
        $need_dni = false;
        $category_ids_need_dni = array();

        // Obtener las categorías que requieren DNI
        if (Configuration::get('DNI_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_CATEGORY_NEED_DNI')) {
            $category_ids_need_dni = array_map('intval', explode(',', Configuration::get('DNI_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_CATEGORY_NEED_DNI')));
        }

        $id_cart = (int) Context::getContext()->cookie->id_cart;
        $cart = new Cart($id_cart);

        if ($cart) {
            $products = $cart->getProducts();

            if ($products) {
                foreach ($products as $key => $product) {
                    $categories = Product::getProductCategories($product['id_product']);

                    if ($categories) {
                        $has_required_category = false;
                        $has_additional_category = false;

                        foreach ($categories as $category_id){
                            if(in_array((int)$category_id, $category_ids_need_dni)){
                                $has_required_category = true;
                            }

                            $has_additional_categories = [586, 718, 2564, 585, 2565, 180];
                            if(in_array((int)$category_id, $has_additional_categories)){
                                $has_additional_category = true;
                            }
                        }

                        if ($has_required_category && !$has_additional_category) {
                            $need_dni = true;
                        }
                    }
                }
            }
        }

        return $need_dni;
    }






    /*fin*/




    public function getTemplateParameters()
    {


        if ($this->need_invoice && in_array($this->context->language->iso_code, ['es','en','fr', 'pt', 'de', 'it'])) {
            $this->need_invoice_option = true;
        }

        if ($this->checkNeedInvoiceByProductTypeInCart()){
            $this->need_invoice = true;
        }

        if ($this->checkNeedDNIByProductTypeInCart()){
            $this->need_dni = true;
        }

        if ($this->checkNeedDNIByProductGunCart()){
            $this->need_dni = true;
        }

        if ($this->checkNeedDNIByCategoryInCart()){
            $this->need_dni = true;
        }

        if ($this->checkNeedDNIByCountry()){
            $this->need_dni = true;
        }

        if ($this->checkNeedInvoiceByOrderTotal()){
            $this->need_invoice = true;
        }

        $idAddressDelivery = (int) $this->getCheckoutSession()->getIdAddressDelivery();
        $idAddressInvoice = (int) $this->getCheckoutSession()->getIdAddressInvoice();
        //dump($idAddressDelivery);
        //dump($idAddressInvoice);
        $params = [
            'need_invoice_option' => $this->need_invoice_option,
            'address_form' => $this->addressForm->getProxy(),
            'use_same_address' => $this->use_same_address,
            'need_invoice' => $this->need_invoice,
            'use_different_address_url' => $this->context->link->getPageLink(
                'order',
                true,
                null,
                ['use_same_address' => 0]
            ),
            'new_address_delivery_url' => $this->context->link->getPageLink(
                'order',
                true,
                null,
                ['newAddress' => 'delivery']
            ),
            'new_address_invoice_url' => $this->context->link->getPageLink(
                'order',
                true,
                null,
                ['newAddress' => 'invoice']
            ),
            'id_address' => (int) Tools::getValue('id_address'),
            'id_address_delivery' => $idAddressDelivery,
            'id_address_invoice' => $idAddressInvoice,
            'show_delivery_address_form' => $this->show_delivery_address_form,
            'show_invoice_address_form' => $this->show_invoice_address_form,
            'form_has_continue_button' => $this->form_has_continue_button,
        ];

        /** @var OrderControllerCore $controller */
        $controller = $this->context->controller;
        if (isset($controller)) {
            $warnings = $controller->checkoutWarning;
            $addressWarning = isset($warnings['address'])
                ? $warnings['address']
                : false;
            $invalidAddresses = isset($warnings['invalid_addresses'])
                ? $warnings['invalid_addresses']
                : [];

            $errors = [];
            if (in_array($idAddressDelivery, $invalidAddresses)) {
                $errors['delivery_address_error'] = $addressWarning;
            }

            if (in_array($idAddressInvoice, $invalidAddresses)) {
                $errors['invoice_address_error'] = $addressWarning;
            }

            if ($this->show_invoice_address_form
                || $idAddressInvoice != $idAddressDelivery
                || !empty($errors['invoice_address_error'])
            ) {
                $this->use_same_address = false;
            }

            if (!empty($this->need_invoice) && is_array($this->need_invoice)) {
                if ($this->context->need_invoice == 1) { //condicion de need_invoice
                    $this->need_invoice = true;
                }
            }

            if ($this->checkNeedInvoiceByProductTypeInCart()){
                $this->need_invoice = true;
            }

            if ($this->checkNeedDNIByProductTypeInCart()){
                $this->need_invoice = true;
            }
            if ($this->checkNeedDNIByCategoryInCart()){
                $this->need_invoice = true;
            }

            if ($this->checkNeedDNIByCountry()){
                $this->need_invoice = true;
            }

            if ($this->checkNeedInvoiceByOrderTotal()){
                $this->need_invoice = true;
            }

            $need_invoice_mandatory = false;
            if ($this->checkNeedInvoiceByProductTypeInCart() || $this->checkNeedInvoiceByOrderTotal()|| $this->checkNeedDNIByProductTypeInCart()|| $this->checkNeedDNIByCountry()||$this->checkNeedDNIByCategoryInCart()) {
                $need_invoice_mandatory = true;
            }

            // Add specific parameters
            $params = array_replace(
                $params,
                [
                    'not_valid_addresses' => implode(',', $invalidAddresses),
                    'use_same_address' => $this->use_same_address,
                    'need_invoice_option' => $this->need_invoice_option,
                    'need_invoice' => $this->need_invoice,
                    'productsblocked' => $this->getProductsBlocked(),  //pedro bloqueos
                    'modal_need_invoice' => !$this->checkVatNumberIfNeedInvoice() ,  /* Jordi - DNI obligatorio en dir_facturacion si solicita factura */
                    'need_invoice_mandatory' => $need_invoice_mandatory,  /* Jordi - necesita factura obligatorio si en carrito hay productos tipo arma o armero // o si el pedido es superior a 3000e */
                    'need_dni' => $this->checkNeedDNIByProductTypeInCart() || $this->checkNeedDNIByCategoryInCart() ||  $this->checkNeedDNIByProductGunCart(),
                ],
                $errors
            );
        }

        return $params;
    }

    private function  getProductsBlocked(){

        if (count($this->productsblocked)>0){

            $sql = 'SELECT * FROM `'._DB_PREFIX_.'product` WHERE id_product in ('.implode(',', $this->productsblocked).')';

            $products = Db::getInstance()->executeS($sql);
            //var_dump($products);
            $assembler = new ProductAssembler($this->context);

            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = new ProductListingPresenter(
                new ImageRetriever(
                    $this->context->link
                ),
                $this->context->link,
                new PriceFormatter(),
                new ProductColorsRetriever(),
                $this->context->getTranslator()
            );

            $products_for_template = [];

            foreach ($products as $rawProduct) {
                $products_for_template[] = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );
            }

            return $products_for_template;
        }
        else{
            $products_for_template = [];
            return $products_for_template;
        }
    }


    protected function checkVatNumberIfNeedInvoice() {
        if (!Tools::getIsset('editAddress')) {
            if ($this->need_invoice || $this->checkNeedInvoiceByProductTypeInCart() || $this->checkNeedInvoiceByOrderTotal()|| $this->checkNeedDNIByCountry() || $this->checkNeedDNIByProductTypeInCart() || $this->checkNeedDNIByCategoryInCart()) {
                $this->need_invoice = true;

                $id_invoice_address = (int) $this->getCheckoutSession()->getIdAddressInvoice();
                if ($id_invoice_address) {
                    $invoice_address = new Address($id_invoice_address);
                    if (!$invoice_address->vat_number || empty(trim($invoice_address->vat_number)) || trim($invoice_address->vat_number) == '') {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function render(array $extraParams = [])
    {
        return $this->renderTemplate(
            $this->getTemplate(),
            $extraParams,
            $this->getTemplateParameters()
        );
    }

}