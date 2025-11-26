<?php

namespace Checkout;

require_once(dirname(__FILE__) . '/../../../classes/CheckoutValidationService.php');
require_once dirname(__FILE__) . '/../BaseController.php';

use Address;
use AddressFormat;
use Cart;
use Configuration;
use Context;
use Country;
use Db;
use Exception;
use Language;
use Module;
use Product;
use State;
use Tools;
use Translate;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CheckoutAddressController extends \BaseController
{
    public $module;
    protected $controllerName;
    protected $customer;
    protected $cart;
    protected $iso;
    protected $lang;
    protected $language;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->module =  Module::getInstanceByName("alsernetshopping");
        $this->context = Context::getContext();
        $this->customer = $this->context->customer;
        $this->cart = $this->context->cart;
        $this->language = $this->context->language;
        $this->iso = $this->context->language->iso_code;
        $this->lang = (int)$this->context->language->id;
        $this->autoAssignSingleAddress();
        $this->validateInvoiceAddressConsistency();
        parent::__construct();
    }

    public function init()
    {
        $this->autoAssignSingleAddress();
        $this->validateInvoiceAddressConsistency();

        $translations = [
            'delivery_address' => $this->l('Delivery address', 'checkoutaddresscontroller'),
            'invoice_address' => $this->l('Invoice address', 'checkoutaddresscontroller'),
            'add_new_address' => $this->l('Add new address', 'checkoutaddresscontroller'),
            'edit_address' => $this->l('Edit address', 'checkoutaddresscontroller'),
            'delete_address' => $this->l('Delete address', 'checkoutaddresscontroller'),
            'use_same_address' => $this->l('Use the same address for billing', 'checkoutaddresscontroller'),
            'different_invoice_address' => $this->l('Use a different address for billing', 'checkoutaddresscontroller'),
            'select_address' => $this->l('Select address', 'checkoutaddresscontroller'),
            'continue' => $this->l('Continue', 'checkoutaddresscontroller'),
            'update' => $this->l('Update', 'checkoutaddresscontroller'),
            'delete' => $this->l('Delete', 'checkoutaddresscontroller'),
            'postalcode' => $this->l('Postal code', 'checkoutaddresscontroller'),
            'city' => $this->l('City', 'checkoutaddresscontroller'),
            'country' => $this->l('Country', 'checkoutaddresscontroller'),
            'dnivat' => $this->l('Dni-Vat', 'checkoutaddresscontroller'),
            'phone' => $this->l('Phone', 'checkoutaddresscontroller'),
            'address' => $this->l('Address', 'checkoutaddresscontroller'),
            'default' => $this->l('Default', 'checkoutaddresscontroller'),
        ];

        $addresses = $this->customer->isLogged()  ? array_map([$this, 'formatAddressData'], $this->customer->getAddresses($this->lang))  : [];

        $addresses_count = count($addresses);

        // Acceso defensivo a propiedades del carrito
        $delivery_address_id = isset($this->cart->id_address_delivery) ? (int)$this->cart->id_address_delivery : 0;
        $invoice_address_id = isset($this->cart->id_address_invoice) ? (int)$this->cart->id_address_invoice : 0;

        $use_same_address = $delivery_address_id === $invoice_address_id;
        $show_delivery_address_form = $addresses_count === 0;
        $show_invoice_address_form = !$use_same_address && $addresses_count < 2;

        $configuration = [
            'invoice_address_required' => (bool)Configuration::get('PS_INVOICE_TAXES_ADDRESS'),
            'vat_number_required' => (bool)Configuration::get('PS_B2B_ENABLE'),
            'delivery_to_invoice' => $this->cart->isVirtualCart(),
        ];

        $this->context->smarty->assign([
            'addresses' => $addresses,
            'configuration' => $configuration,
            'translations' => $translations,
            'current_delivery_address' => $delivery_address_id,
            'current_invoice_address' => $invoice_address_id,
            'show_delivery_address_form' => $show_delivery_address_form,
            'show_invoice_address_form' => $show_invoice_address_form,
            'use_same_address' => $use_same_address,
            'need_invoice' => $this->isInvoiceMandatory(),
            'current_step' => 'addresses',
        ]);

        return $this->context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/view/addresses.tpl');

    }

    public function stepaddress()
    {
        $context  = Context::getContext();
        $cart     = $context->cart;
        $customer = $context->customer;

        if (!$customer || !$customer->isLogged()) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Unauthorized access.'),
                'data'    => [],
            ];
        }

        // Valores venidos del formulario (si no llegan, caen al valor actual del carrito)
        $idAddressDelivery = (int) Tools::getValue('id_address_delivery', (int)$cart->id_address_delivery);
        $needInvoice       = Tools::getValue('need_invoice') === '1' || Tools::getValue('need_invoice') === 'on';

        // Si el usuario marcó "dirección de factura distinta", tomamos la que venga; si no, igual a delivery
        // Asegura que si no viene, caiga a delivery
        $idAddressInvoiceForm = (int) Tools::getValue('id_address_invoice', 0);
        $idAddressInvoice     = $needInvoice ? ($idAddressInvoiceForm ?: $idAddressDelivery) : $idAddressDelivery;

        // Validaciones mínimas
        if (!$idAddressDelivery) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Delivery address is required.'),
                'data'    => [],
            ];
        }

        // Verificar propiedad y existencia
        $addrDeliveryObj = new Address($idAddressDelivery);
        if (!Validate::isLoadedObject($addrDeliveryObj) || (int)$addrDeliveryObj->id_customer !== (int)$customer->id || (int)$addrDeliveryObj->deleted === 1) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Invalid delivery address.'),
                'data'    => [],
            ];
        }

        $addrInvoiceObj = new Address($idAddressInvoice);
        if (!Validate::isLoadedObject($addrInvoiceObj) || (int)$addrInvoiceObj->id_customer !== (int)$customer->id || (int)$addrInvoiceObj->deleted === 1) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Invalid invoice address.'),
                'data'    => [],
            ];
        }

        // Asignar SIEMPRE explícitamente al carrito (forzar enteros)
        $cart->id_address_delivery = (int)$idAddressDelivery;
        $cart->id_address_invoice  = (int)$idAddressInvoice;
        $cart->need_invoice        = $needInvoice ? 1 : 0;
        $cart->step                = 'delivery';

        // Mantener consistencia en cart_product cuando cambia la de entrega
        $this->forceSingleDeliveryAddressForCart($cart);

        if (!$cart->update()) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Failed to update cart with address data.'),
                'data'    => [],
            ];
        }

        // Por si otras reglas pudieran ajustar need_invoice ↔ id_address_invoice
        $this->validateInvoiceAddressConsistency();

        return [
            'status'    => 'success',
            'message'   => $this->l('Addresses saved successfully.'),
            'operation' => $this->l('Step completed'),
            'data'      => [
                'id_address_delivery' => (int)$cart->id_address_delivery,
                'id_address_invoice'  => (int)$cart->id_address_invoice,
                'need_invoice'        => (bool)$cart->need_invoice,
            ],
        ];
    }

    public function addaddress()
    {

        $context  = Context::getContext();
        $customer = $context->customer;
        $cart     = $context->cart;
        $type     = Tools::getValue('type') ?: 'delivery';

        if (!$customer || !$customer->isLogged()) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Unauthorized access.'),
                'data'    => [],
            ];
        }

        $address = new Address();
        $address->id_customer  = (int)$customer->id;
        $address->firstname    = Tools::getValue('firstname');
        $address->lastname     = Tools::getValue('lastname');
        $address->address1     = Tools::getValue('address1');
        $address->address2     = Tools::getValue('address2');
        $address->postcode     = Tools::getValue('postcode');
        $address->city         = Tools::getValue('city');
        $address->vat_number   = Tools::getValue('vat_number');
        $address->id_country   = (int)Tools::getValue('id_country');
        $address->id_state     = (int)Tools::getValue('id_state');
        $address->phone        = Tools::getValue('phone');
        $address->phone_mobile = Tools::getValue('phone_mobile');
        $address->active = 1;
        $address->default      = (int)Tools::getValue('default', 0);

        $errors = $address->validateFieldsRequiredDatabase();

        if (!empty($errors)) {

            return [
                'status'  => 'warning',
                'message' => $this->l('Please fill in all required fields.'),
                'data'    => $errors,
            ];
        }

        if (!$address->add()) {
            return [
                'status'    => 'warning',
                'operation' => $this->l('Error creating address.'),
                'message'   => $this->l('Error creating address.'),
                'data'      => [],
            ];
        }

        $this->enforceUniqueDefaultAddress((int)$customer->id, (int)$address->id, (int)$address->default);

        if ($type === 'invoice') {
            $cart->id_address_invoice = (string)$address->id;
        } else {
            $cart->id_address_delivery = (string)$address->id;
            // IMPORTANTE: Auto-asignar dirección de facturación si no hay una asignada
            // O si need_invoice es 0 (usa la misma dirección)
            if (!$cart->id_address_invoice || !$cart->need_invoice) {
                $cart->id_address_invoice = (string)$address->id;
                // error_log("✅ Auto-assigned billing address (new): delivery={$address->id}, billing={$address->id}");
            }
        }

        if (!$cart->update()) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Error updating cart with new address.'),
                'data'    => [],
            ];
        }

        $this->autoAssignSingleAddress($cart);

        return [
            'status'  => 'success',
            'message' => $this->l('Address created successfully.'),
            'data'    => [
                'id_address' => (int)$address->id,
                'cart'       => $cart,
                'default'    => (int)$address->default,
                'type'       => $type,
            ],
        ];

    }

    public function deleteaddress()
    {
        $id_address = Tools::getValue('id_address');
        $this->iso = Tools::getValue('iso');

        if (!$id_address || !Validate::isUnsignedId($id_address)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid address ID'),
                'data' => [],
            ];
        }

        $address = new Address((int)$id_address);

        if (!Validate::isLoadedObject($address)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Address not found'),
                'data' => [],
            ];
        }

        $context = Context::getContext();
        if ($context->customer->id != $address->id_customer) {
            return [
                'status' => 'warning',
                'message' => $this->l('Unauthorized access'),
                'data' => [],
            ];
        }
        if ($address->delete()) {

            return [
                'status' => 'success',
                'message' => $this->l('Address deleted successfully.'),
                'data' => [],
            ];
        }

        return [
            'status' => 'warning',
            'message' => $this->l('Error deleting address'),
            'data' => [],
        ];
    }

    public function setaddress()
    {
        $context  = Context::getContext();
        $cart     = $context->cart;
        $customer = $context->customer;

        $id_address = (int)Tools::getValue('id_address');
        $type       = Tools::getValue('type') ?: 'delivery'; // 'delivery' | 'invoice'

        // NO tocar default aquí: ignoramos flags si llegan
        // (no modificamos $_GET/$_POST, simplemente no los usamos)
        // Tools::getValue('default'); Tools::getValue('default'); // <- ignorados

        if (!$customer || !$customer->isLogged()) {
            return [
                'status'  => 'error',
                'message' => $this->l('You must be logged in.', 'checkoutaddresscontroller'),
                'data'    => []
            ];
        }

        $address = new Address($id_address);
        if (!Validate::isLoadedObject($address) || (int)$address->id_customer !== (int)$customer->id || (int)$address->deleted === 1) {
            return [
                'status'  => 'error',
                'message' => $this->l('Invalid address.', 'checkoutaddresscontroller'),
                'data'    => []
            ];
        }

        // Solo setear el carrito
        if ($type === 'delivery') {
            $cart->id_address_delivery = $id_address;

            // IMPORTANTE: Auto-asignar dirección de facturación si no hay una asignada
            // O si need_invoice es 0 (usa la misma dirección)
            if (!$cart->id_address_invoice || !$cart->need_invoice) {
                $cart->id_address_invoice = $id_address;
                // error_log("✅ Auto-assigned billing address: delivery={$id_address}, billing={$id_address}");
            }

        } elseif ($type === 'invoice') {
            $cart->id_address_invoice = $id_address;
        } else {
            return [
                'status'  => 'error',
                'message' => $this->l('Invalid address type.', 'checkoutaddresscontroller'),
                'data'    => []
            ];
        }

        if (!$cart->update()) {
            return [
                'status'  => 'error',
                'message' => $this->l('Failed to update cart.', 'checkoutaddresscontroller'),
                'data'    => []
            ];
        }

        // Mantener consistencia en cart_product SOLO cuando cambia delivery
        if ($type === 'delivery') {
            $this->forceSingleDeliveryAddressForCart($cart);
        }

        return [
            'status'  => 'success',
            'message' => $this->l('Address updated successfully.', 'checkoutaddresscontroller'),
            'data'    => [
                'id_cart'    => (int)$cart->id,
                'id_address' => $id_address,
                'type'       => $type
            ]
        ];
    }

    public function editaddress()
    {
        try {

            $id_address = Tools::getValue('id_address');
            $this->iso        = Tools::getValue('iso');

            if (!$id_address || !Validate::isUnsignedId($id_address)) {
                return [
                    'status'  => 'warning',
                    'message' => $this->l('Invalid address ID.', 'checkoutaddresscontroller'),
                    'data'    => [],
                ];
            }

            $address = new Address((int)$id_address);
            if (!Validate::isLoadedObject($address)) {
                return [
                    'status'  => 'warning',
                    'message' => $this->l('Address not found.', 'checkoutaddresscontroller'),
                    'data'    => [],
                ];
            }

            $context  = Context::getContext();
            $customer = $context->customer;
            if (!$customer || $customer->id != $address->id_customer) {
                return [
                    'status'  => 'warning',
                    'message' => $this->l('Unauthorized access.', 'checkoutaddresscontroller'),
                    'data'    => [],
                ];
            }

            $address->firstname    = Tools::getValue('firstname');
            $address->vat_number   = Tools::getValue('vat_number');
            $address->lastname     = Tools::getValue('lastname');
            $address->address1     = Tools::getValue('address1');
            $address->address2     = Tools::getValue('address2');
            $address->postcode     = Tools::getValue('postcode');
            $address->city         = Tools::getValue('city');
            $address->id_country   = (int)Tools::getValue('id_country');
            $address->id_state     = (int)Tools::getValue('id_state');
            $address->phone        = Tools::getValue('phone');
            $address->phone_mobile = Tools::getValue('phone_mobile');
            $address->active = 1;
            $address->default      = (int)Tools::getValue('default', (int)$address->default); // <- viene del form

            if (!$address->update()) {
                return [
                    'status'  => 'warning',
                    'message' => $this->l('Error updating address.', 'checkoutaddresscontroller'),
                    'data'    => [],
                ];
            }

            $this->enforceUniqueDefaultAddress($customer->id, (int)$address->id, (int)$address->default);

            $type = Tools::getValue('type');

            if ($type && (int)$address->default === 1) {

                $cart = $context->cart;

                if ($type === 'invoice') {
                    $cart->id_address_invoice = (int)$address->id;
                } else {
                    $cart->id_address_delivery = (int)$address->id;
                }

                $cart->update();

                if ($type !== 'invoice') {
                    $this->forceSingleDeliveryAddressForCart($cart);
                }
            }

            return [
                'status'  => 'success',
                'message' => $this->l('Address updated successfully.', 'checkoutaddresscontroller'),
                'data'    => [],
            ];

        } catch (Exception $e) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Unauthorized access.', 'checkoutaddresscontroller'),
                'data'    => [],
            ];
        }
    }

    public function getaddaddressfields()
    {
        $context   = Context::getContext();
        $iso = Tools::getValue('iso');
        $lenguage =  $context->language;
        $cart      = $context->cart;
        $customer  = $context->customer;
        $type      = Tools::getValue('type') ?: 'delivery'; // 'delivery' | 'invoice'
        $id_address = (int)Tools::getValue('id_address');
        $address   = null;

        $hasAddresses = false;
        if ($customer && $customer->isLogged()) {
            $existingAddresses = $customer->getSimpleAddresses((int)$context->language->id);
            $hasAddresses = !empty($existingAddresses);
        }

        if ($id_address && Validate::isUnsignedId($id_address)) {

            $address = new Address($id_address);

            if (!Validate::isLoadedObject($address)) {
                return [
                    'status'  => 'warning',
                    'message' => $this->l('Address not found', 'checkoutaddresscontroller'),
                    'data'    => [],
                ];
            }

            if (!$customer || $customer->id != $address->id_customer) {
                return [
                    'status'  => 'warning',
                    'message' => $this->l('Unauthorized access', 'checkoutaddresscontroller'),
                    'data'    => [],
                ];
            }

        } else {

            $address = new Address();
            $address->id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');

            if ($customer && $customer->isLogged()) {
                if (!$hasAddresses) {
                    $address->firstname = $customer->firstname;
                    $address->lastname  = $customer->lastname;
                    $address->default = $hasAddresses ? 0 : 1;
                }
            }

        }

        $requiredFields = AddressFormat::getFieldsRequired();


        $formFields = AddressFormat::getOrderedAddressFields($address->id_country, true, true);


        $fieldsData = [];

        foreach ($formFields as $field) {
            if ($field === 'company') {
                continue;
            }
            if ($field === 'Country:name') {
                $label = $this->l('Country', 'checkoutaddresscontroller');
            }elseif ($field === 'Firstname') {
                $label = $this->l('Firstname', 'checkoutaddresscontroller');
            }elseif ($field === 'Lastname') {
                $label = $this->l('Lastname', 'checkoutaddresscontroller');
            }elseif ($field === 'Postcode') {
                $label = $this->l('Postcode', 'checkoutaddresscontroller');
            }elseif ($field === 'State:name') {
                $label = $this->l('State', 'checkoutaddresscontroller');
            }elseif ($field === 'Vat number') {
                $label = $this->l('Vat number', 'checkoutaddresscontroller');
            }elseif ($field === 'Address1') {
                $label = $this->l('Address1', 'checkoutaddresscontroller');
            }else {
                $label = ucfirst(str_replace(['_', ':name'], [' ', ''], $field));
            }

            $fieldData = [
                'name'     => $field,
                'label'    => $this->l($label, 'checkoutaddresscontroller'),
                'required' => in_array($field, $requiredFields),
                'type'     => 'text',
            ];

            if ($field === 'Country:name') {

                $fieldData['type'] = 'select';
                $fieldData['name'] = 'id_country';
                $fieldData['value'] = ((int)$address->id === 0) ? (int)$context->country->id : (int)$address->id_country;
                $fieldData['options'] = array_values(array_map(function ($country) {
                    return [
                        'value' => (int)$country['id_country'],
                        'label' => $country['name'],
                    ];
                }, Country::getCountries($context->language->id, true)));


            } elseif ($field === 'State:name') {
                $countryIdToCheck = ((int)$address->id === 0) ? (int)$context->country->id : (int)$address->id_country;
                $states = State::getStatesByIdCountry($countryIdToCheck, $context->language->id);
                if (!empty($states)) {
                    $fieldData['type'] = 'select';
                    $fieldData['name'] = 'id_state';
                    $fieldData['value'] = (int)$address->id_state;
                    $fieldData['options'] = array_map(function ($state) {
                        return [
                            'value' => (int)$state['id_state'],
                            'label' => $state['name'],
                        ];
                    }, $states);
                } else {
                    continue;
                }

            }elseif ($field === 'postcode') {
                $fieldData['required'] = true;
            } else {
                $prop = str_replace(':name', '', $field);
                $fieldData['value'] = isset($address->{$prop}) ? $address->{$prop} : '';
            }

            $fieldsData[] = $fieldData;
        }

        $isDefaultSelected = 0;

        if ((int)$address->id) {
            $isDefaultSelected = (int)$address->default;
        } else {
            $existingAddresses = [];
            if ($customer && $customer->isLogged()) {
                $existingAddresses = $customer->getAddresses($this->lang);
            }
            $isDefaultSelected = empty($existingAddresses) ? 1 : 0;
        }

        $labelDefault = $this->l('Use as default address', 'checkoutaddresscontroller');

        $defaultField = [
            'name'     => 'default',
            'label'    => $labelDefault,
            'required' => false,
            'type'     => 'select',
            'value'    => $isDefaultSelected,
            'options'  => [
                ['value' => 1, 'label' => $this->l('Yes', 'checkoutaddresscontroller')],
                ['value' => 0, 'label' => $this->l('No', 'checkoutaddresscontroller')],
            ],
        ];

        $fieldsData[] = $defaultField;

        return [
            'status'  => 'success',
            'message' => $this->l('Address loaded successfully', 'checkoutaddresscontroller'),
            'data'    => [
                'type' => $type,
                'default' => $isDefaultSelected === 1,
                'country' => $context->country->id,

            ],
            'fields'  => $fieldsData,
        ];
    }

    public function getaddressfields()
    {
        $context    = Context::getContext();
        $cart       = $context->cart;
        $customer   = $context->customer;
        $type       = Tools::getValue('type') ?: 'delivery';
        $id_address = Tools::getValue('id_address');

        if (!$id_address || !Validate::isUnsignedId($id_address)) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Invalid address ID', 'checkoutaddresscontroller'),
                'data'    => [],
            ];
        }

        $address = new Address((int)$id_address);

        if (!Validate::isLoadedObject($address)) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Address not found', 'checkoutaddresscontroller'),
                'data'    => [],
            ];
        }

        if (!$customer || $customer->id != $address->id_customer) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Unauthorized access', 'checkoutaddresscontroller'),
                'data'    => [],
            ];
        }

        $requiredFields = AddressFormat::getFieldsRequired();
        $formFields = AddressFormat::getOrderedAddressFields($address->id_country, true, true);

        $fieldsData = [];

        foreach ($formFields as $field) {
            if ($field === 'company') {
                continue;
            }
            if ($field === 'Country:name') {
                $label = $this->l('Country', 'checkoutaddresscontroller');
            }elseif ($field === 'Firstname') {
                $label = $this->l('Firstname', 'checkoutaddresscontroller');
            }elseif ($field === 'Lastname') {
                $label = $this->l('Lastname', 'checkoutaddresscontroller');
            }elseif ($field === 'Postcode') {
                $label = $this->l('Postcode', 'checkoutaddresscontroller');
            }elseif ($field === 'State:name') {
                $label = $this->l('State', 'checkoutaddresscontroller');
            }elseif ($field === 'Vat number') {
                $label = $this->l('Vat number', 'checkoutaddresscontroller');
            }elseif ($field === 'Address1') {
                $label = $this->l('Address1', 'checkoutaddresscontroller');
            }else {
                $label = ucfirst(str_replace(['_', ':name'], [' ', ''], $field));
            }

            $fieldData = [
                'name'     => $field,
                'label'    => $this->l($label, 'checkoutaddresscontroller'),
                'required' => in_array($field, $requiredFields),
                'type'     => 'text',
            ];

            if ($field === 'Country:name') {
                $countries = Country::getCountries($context->language->id, true);
                $options = [];
                foreach ($countries as $country) {
                    $options[] = [
                        'value' => (int)$country['id_country'],
                        'label' => $country['name'],
                    ];
                }
                $fieldData['type']    = 'select';
                $fieldData['name']    = 'id_country';
                $fieldData['options'] = $options;
                $fieldData['value']   = (int)$address->id_country;

            } elseif ($field === 'State:name') {
                $states  = State::getStatesByIdCountry((int)$address->id_country, $context->language->id);
                $options = [];
                foreach ($states as $state) {
                    $options[] = [
                        'value' => (int)$state['id_state'],
                        'label' => $state['name'],
                    ];
                }
                $fieldData['type']    = 'select';
                $fieldData['name']    = 'id_state';
                $fieldData['options'] = $options;
                $fieldData['value']   = (int)$address->id_state;

            } else {
                // mapear propiedad real (ej: 'firstname', 'address1', etc.)
                $prop = str_replace(':name', '', $field);
                $fieldData['value'] = isset($address->{$prop}) ? $address->{$prop} : '';
            }

            $fieldsData[] = $fieldData;
        }

        $isDefaultSelected = (int)$address->default;

        $labelDefault = ($type === 'invoice') ? $this->l('Use as default invoice address', 'checkoutaddresscontroller') : $this->l('Use as default delivery address', 'checkoutaddresscontroller');

        $defaultField = [
            'name'     => 'default',
            'label'    => $labelDefault,
            'required' => true,
            'type'     => 'select',
            'value'    => $isDefaultSelected,
            'options'  => [
                ['value' => 1, 'label' => $this->l('Yes', 'checkoutaddresscontroller')],
                ['value' => 0, 'label' => $this->l('No', 'checkoutaddresscontroller')],
            ],
            'meta'     => ['applies_to' => $type, 'address_id' => (int)$address->id],
        ];

        $fieldsData[] = $defaultField;

        return [
            'status'  => 'success',
            'message' => $this->l('Address loaded successfully', 'checkoutaddresscontroller'),
            'data'    => [
                'type'       => $type,
                'id_address' => (int)$address->id,
            ],
            'fields'  => $fieldsData,
        ];
    }

    public function getaddress()
    {
        $context = $this->context;
        $cart = $this->cart;
        $customer = $this->customer;

        $configuration = Configuration::getMultiple([
            'PS_TAX_ADDRESS_TYPE', 'PS_INVOICE', 'VATNUMBER_MANAGEMENT'
        ]);

        if (!$customer || !$customer->isLogged()) {
            return [
                'status' => 'warning',
                'message' => $this->l('You must be logged in to see your addresses.', 'checkoutaddresscontroller'),
                'html_delivery' => '',
                'html_invoice' => '',
            ];
        }

        $addresses = $customer->isLogged()
            ? array_map([$this, 'formatAddressData'], $customer->getAddresses($this->lang))
            : [];

        if (empty($addresses)) {
            return [
                'status' => 'warning',
                'message' => $this->l('No addresses found.', 'checkoutaddresscontroller'),
                'html_delivery' => '',
                'html_invoice' => '',
            ];
        }

        $translations = [
            'delivery_address' => $this->l('Delivery address', 'checkoutaddresscontroller'),
            'invoice_address' => $this->l('Invoice address', 'checkoutaddresscontroller'),
            'add_new_address' => $this->l('Add new address', 'checkoutaddresscontroller'),
            'edit_address' => $this->l('Edit address', 'checkoutaddresscontroller'),
            'delete_address' => $this->l('Delete address', 'checkoutaddresscontroller'),
            'use_same_address' => $this->l('Use the same address for billing', 'checkoutaddresscontroller'),
            'different_invoice_address' => $this->l('Use a different address for billing', 'checkoutaddresscontroller'),
            'select_address' => $this->l('Select address', 'checkoutaddresscontroller'),
            'continue' => $this->l('Continue', 'checkoutaddresscontroller'),
            'update' => $this->l('Update', 'checkoutaddresscontroller'),
            'delete' => $this->l('Delete', 'checkoutaddresscontroller'),
            'postalcode' => $this->l('Postal code', 'checkoutaddresscontroller'),
            'city' => $this->l('City', 'checkoutaddresscontroller'),
            'country' => $this->l('Country', 'checkoutaddresscontroller'),
            'dnivat' => $this->l('Dni-Vat', 'checkoutaddresscontroller'),
            'phone' => $this->l('Phone', 'checkoutaddresscontroller'),
            'address' => $this->l('Address', 'checkoutaddresscontroller'),
            'default' => $this->l('Default', 'checkoutaddresscontroller'),
        ];

        $context->smarty->assign([
            'addresses' => $addresses,
            'name' => 'id_address_delivery',
            'selected' => (int)$cart->id_address_delivery,
            'type' => 'delivery',
            'configuration' => $configuration,
            'translations' => $translations,
        ]);

        $html_delivery = $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/partials/addresses/address-selector.tpl');

        $context->smarty->assign([
            'addresses' => $addresses,
            'name' => 'id_address_invoice',
            'selected' => (int)$cart->id_address_invoice,
            'type' => 'invoice',
            'configuration' => $configuration,
            'translations' => $translations,
        ]);

        $html_invoice = $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/partials/addresses/address-selector.tpl');

        return [
            'status' => 'success',
            'message' => $this->l('Addresses loaded successfully.', 'checkoutaddresscontroller'),
            'html_delivery' => $html_delivery,
            'html_invoice' => $html_invoice,
        ];
    }

    public function setneeinvoice()
    {
        $context = $this->context;
        $cart = $this->cart;
        $customer = $this->customer;

        if (!$customer || !$customer->isLogged()) {
            return [
                'status' => 'error',
                'message' => $this->l(
                    'You must be logged in.', 'checkoutaddresscontroller',
                    [],
                    'Shop.Notifications.Error',
                    $context->language->locale
                ),
                'data' => []
            ];
        }

        $needInvoice = (int)Tools::getValue('need_invoice');

        // Validate need_invoice value (0 or 1)
        if (!in_array($needInvoice, [0, 1])) {
            return [
                'status' => 'error',
                'message' => $this->l('Invalid need_invoice value.', 'checkoutaddresscontroller'),
                'data' => []
            ];
        }

        // Update cart need_invoice
        $cart->need_invoice = $needInvoice;

        // Validate invoice address consistency
        $this->validateInvoiceAddressConsistency();

        if ($cart->update()) {
            return [
                'status' => 'success',
                'message' => $this->l('Invoice setting updated successfully.', 'checkoutaddresscontroller'),
                'data' => [
                    'need_invoice' => $needInvoice,
                    'invoice_mandatory' => $this->isInvoiceMandatory()
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => $this->l('Error updating invoice setting.', 'checkoutaddresscontroller'),
                'data' => []
            ];
        }
    }

    public function getaddressdelivery()
    {
        $context = Context::getContext();
        $customer = $context->customer;

        if (!$customer || !$customer->isLogged()) {
            return [
                'status' => 'warning',
                'message' => $this->l('You must be logged in to see your addresses.', 'checkoutaddresscontroller'),
                'addresses' => [],
            ];
        }

        $addresses = $customer->getSimpleAddresses($context->language->id);

        if (empty($addresses)) {
            return [
                'status' => 'warning',
                'message' => $this->l('No addresses found.', 'checkoutaddresscontroller'),
                'addresses' => [],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->l('Addresses loaded successfully.', 'checkoutaddresscontroller'),
            'addresses' => $addresses,
        ];
    }

    public function getaddressinvoice()
    {
        $context = Context::getContext();
        $customer = $context->customer;

        if (!$customer || !$customer->isLogged()) {
            return [
                'status' => 'warning',
                'message' => $this->l('You must be logged in to see your addresses.', 'checkoutaddresscontroller'),
                'addresses' => [],
            ];
        }

        $addresses = $customer->getSimpleAddresses($context->language->id);

        if (empty($addresses)) {
            return [
                'status' => 'warning',
                'message' => $this->l('No addresses found.', 'checkoutaddresscontroller'),
                'addresses' => [],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->l('Addresses loaded successfully.', 'checkoutaddresscontroller'),
            'addresses' => $addresses,
        ];
    }

    public function getstates()
    {
        $context = Context::getContext();
        $id_country = (int)Tools::getValue('id_country');
        $id_lang = (int)$context->language->id;

        if (!$id_country || !Validate::isUnsignedId($id_country)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid country ID', 'checkoutaddresscontroller'),
                'label' => '',
                'options' => [],
            ];
        }

        $states = State::getStatesByIdCountry($id_country, $id_lang);
        $options = [];

        foreach ($states as $state) {
            $options[] = [
                'value' => (int)$state['id_state'],
                'label' => $state['name'], // Ya traducido con $id_lang
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->l('States loaded successfully', 'checkoutaddresscontroller'),
            'label' => $this->l('State', 'checkoutaddresscontroller'),
            'options' => $options,
        ];
    }

    /**
     * Validates postcode in real-time using PrestaShop's native zip_code_format
     * This provides generic validation for all countries based on their configured format
     */
    public function validatepostcode()
    {
        $context = Context::getContext();
        $postcode = trim(Tools::getValue('postcode'));
        $id_country = (int)Tools::getValue('id_country');

        // Basic validation
        if (empty($postcode)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Postcode is required', 'checkoutaddresscontroller'),
                'valid' => false,
                'data' => []
            ];
        }

        if (!$id_country || !Validate::isUnsignedId($id_country)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Country is required to validate postcode', 'checkoutaddresscontroller'),
                'valid' => false,
                'data' => []
            ];
        }

        try {
            // Load country object
            $country = new Country($id_country);
            if (!Validate::isLoadedObject($country)) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Invalid country', 'checkoutaddresscontroller'),
                    'valid' => false,
                    'data' => []
                ];
            }

            $countryName = is_array($country->name)
                ? ($country->name[$context->language->id] ?? reset($country->name))
                : $country->name;

            // Check if country requires postal code
            if (!$country->need_zip_code) {
                return [
                    'status' => 'success',
                    'message' => $this->l('Postcode not required for this country', 'checkoutaddresscontroller'),
                    'valid' => true,
                    'data' => [
                        'postcode' => $postcode,
                        'country_id' => $id_country,
                        'country_name' => $countryName,
                        'postcode_required' => false
                    ]
                ];
            }

            // Use PrestaShop's native zip code validation
            $isValidFormat = $country->checkZipCode($postcode);

            if (!$isValidFormat) {
                // Get expected format for user feedback
                $expectedFormat = $this->getHumanReadableZipFormat($country->zip_code_format, $country->iso_code);

                return [
                    'status' => 'warning',
                    'message' => $this->l('Invalid postcode format for', 'checkoutaddresscontroller'),
                    'valid' => false,
                    'data' => [
                        'country' => $countryName,
                        'country_iso' => $country->iso_code,
                        'postcode' => $postcode,
                        'expected_format' => $expectedFormat,
                        'zip_code_format' => $country->zip_code_format
                    ]
                ];
            }

            // Additional validation using PrestaShop's general Validate class
            if (!Validate::isPostCode($postcode)) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Invalid postcode format', 'checkoutaddresscontroller'),
                    'valid' => false,
                    'data' => [
                        'country' => $countryName,
                        'postcode' => $postcode,
                        'validation_type' => 'general_format'
                    ]
                ];
            }

            // Valid postcode
            return [
                'status' => 'success',
                'message' => $this->l('Valid postcode', 'checkoutaddresscontroller'),
                'valid' => true,
                'data' => [
                    'postcode' => $postcode,
                    'country_id' => $id_country,
                    'country_name' => $countryName,
                    'country_iso' => $country->iso_code
                ]
            ];

        } catch (Exception $e) {
            // Log error for debugging
            error_log('Postcode validation error: ' . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $this->l('Error validating postcode', 'checkoutaddresscontroller'),
                'valid' => false,
                'data' => []
            ];
        }
    }

    /**
     * Converts PrestaShop zip_code_format to human-readable format
     * N = Number, L = Letter, C = Country ISO code
     */
    private function getHumanReadableZipFormat($zipFormat, $isoCode)
    {
        if (empty($zipFormat)) {
            return $this->l('Format not specified', 'checkoutaddresscontroller');
        }

        // Convert PrestaShop format to human readable
        $humanFormat = $zipFormat;
        $humanFormat = str_replace('N', '9', $humanFormat); // Numbers
        $humanFormat = str_replace('L', 'A', $humanFormat); // Letters
        $humanFormat = str_replace('C', $isoCode, $humanFormat); // Country code

        // Add common format examples by country
        $examples = [
            'PT' => '1000-004 (Continental) / 9000-004 (Azores)',
            'ES' => '28001',
            'FR' => '75001',
            'DE' => '10117',
            'IT' => '00118',
            'US' => '90210 / 90210-1234',
            'GB' => 'SW1A 1AA',
            'CA' => 'K1A 0A6'
        ];

        if (isset($examples[$isoCode])) {
            return $examples[$isoCode];
        }

        return $humanFormat;
    }
    private function checkRequirements()
    {
        $cart = $this->cart;

        return [
            'need_invoice' => (
                \CheckoutValidationService::checkNeedInvoiceByProductType($cart) ||
                \CheckoutValidationService::checkNeedInvoiceByOrderTotal($cart)
            ),
            'need_dni' => (
                \CheckoutValidationService::checkNeedDNIByProductType($cart) ||
                \CheckoutValidationService::checkNeedDNIByCategory($cart) ||
                \CheckoutValidationService::checkNeedDNIByCountry($cart)
            )
        ];
    }

    private function autoAssignSingleAddress()
    {
        if (!$this->customer || !$this->customer->isLogged()) {
            return false;
        }

        $cart = $this->cart;


        if ((int)$cart->id_address_delivery !== 0) {
            return false;
        }

        $addresses = $this->customer->getSimpleAddresses($this->language->id);

        if (empty($addresses)) {
            return false;
        }

        $assignedAddressId = null;

        if (count($addresses) === 1) {
            $singleAddress = reset($addresses);
            $addressId = isset($singleAddress['id_address']) ? (int)$singleAddress['id_address'] :
                (isset($singleAddress['id']) ? (int)$singleAddress['id'] : 0);

            if ($addressId > 0) {
                $assignedAddressId = $addressId;
            }

        } else {
            foreach ($addresses as $addr) {

                $addressId = isset($addr['id_address']) ? (int)$addr['id_address'] :
                    (isset($addr['id']) ? (int)$addr['id'] : 0);

                if ($addressId > 0) {
                    $addressObj = new Address($addressId);
                    if ((int)$addressObj->default === 1 &&
                        (int)$addressObj->deleted === 0 &&
                        (int)$addressObj->id_customer === (int)$this->customer->id) {
                        $assignedAddressId = (int)$addressObj->id;
                        break;
                    }
                }
            }
        }

        if ($assignedAddressId) {

            $address = new Address($assignedAddressId);

            if (Validate::isLoadedObject($address)) {

                $cart->id_address_delivery = $assignedAddressId;

                if (!$cart->id_address_invoice) {
                    $cart->id_address_invoice = $assignedAddressId;
                }

                if (count($addresses) === 1 && (int)$address->default !== 1) {
                    $address->default = 1;
                    $address->update();
                }

                $cart->update();
                $this->forceSingleDeliveryAddressForCart($cart);

                return true;
            }
        }

        return false;
    }

    private function formatAddressData(array $addr)
    {
        $addressObj = new Address($addr['id_address']);
        $country = new Country($addressObj->id_country);
        $state = $addressObj->id_state ? new State($addressObj->id_state) : null;

        $countryName = is_array($country->name) ? ($country->name[$this->lang] ?? reset($country->name)) : $country->name;

        $stateName = '';

        if ($state) {
            $stateName = is_array($state->name)
                ? ($state->name[$this->lang] ?? reset($state->name))
                : $state->name;
        }

        return [
            'id' => (int)$addressObj->id,
            'firstname' => (string)($addressObj->firstname ?? ''),
            'lastname' => (string)($addressObj->lastname ?? ''),
            'company' => (string)($addressObj->company ?? ''),
            'address1' => (string)($addressObj->address1 ?? ''),
            'address2' => (string)($addressObj->address2 ?? ''),
            'postcode' => (string)($addressObj->postcode ?? ''),
            'default' => isset($addressObj->default) ? (int)$addressObj->default : 0,
            'city' => (string)($addressObj->city ?? ''),
            'country' => (string)$countryName,
            'vat_number' => (string)($addressObj->vat_number ?? ''),
            'country_iso' => (string)($country->iso_code ?? ''),
            'state' => (string)$stateName,
            'phone' => (string)($addressObj->phone ?? ''),
            'phone_mobile' => (string)($addressObj->phone_mobile ?? ''),
            'formatted' => (string)AddressFormat::generateAddress($addressObj, [], '<br>'),
            'is_delivery' => (bool)((int)Context::getContext()->cart->id_address_delivery === (int)$addressObj->id),
            'is_invoice' => (bool)((int)Context::getContext()->cart->id_address_invoice === (int)$addressObj->id),
        ];
    }

    private function isInvoiceMandatory(): bool
    {
        $cart = $this->cart;

        $mandatoryByRules =
            \CheckoutValidationService::checkNeedInvoiceByProductType($cart) ||
            \CheckoutValidationService::checkNeedInvoiceByOrderTotal($cart)  ||
            \CheckoutValidationService::checkNeedDNIByProductType($cart)     ||
            \CheckoutValidationService::checkNeedDNIByCountry($cart)         ||
            \CheckoutValidationService::checkNeedDNIByCategory($cart);

        return $mandatoryByRules ? true : !empty($cart->need_invoice);
    }

    public function forceSingleDeliveryAddressForCart($cart)
    {
        if (!$cart->id || !$cart->id_address_delivery) {
            return false;
        }

        $db = Db::getInstance();
        $table = 'cart_product';

        $success = $db->update(
            $table,
            ['id_address_delivery' => (int) $cart->id_address_delivery],
            'id_cart = ' . (int) $cart->id
        );

        return true;
    }

    private function enforceUniqueDefaultAddress(int $idCustomer, int $idAddress, int $isDefault): void
    {

        $customer = new \Customer($idCustomer);
        $allAddresses = $customer->getAddresses($this->lang);
        $validAddresses = array_filter($allAddresses, function($row) {
            return (int)$row['deleted'] === 0;
        });

        if ($isDefault === 1) {

            foreach ($validAddresses as $row) {
                $aid = (int)$row['id_address'];
                $addr = new Address($aid);

                if (!Validate::isLoadedObject($addr)) continue;

                $addr->default = ($aid === $idAddress) ? 1 : 0;
                $addr->update();
            }
        } else {

            $hasOtherDefault = false;

            foreach ($validAddresses as $row) {
                $aid = (int)$row['id_address'];
                if ($aid !== $idAddress) { // No contar la dirección actual
                    $addr = new Address($aid);
                    if (Validate::isLoadedObject($addr) && (int)$addr->default === 1) {
                        $hasOtherDefault = true;
                        break;
                    }
                }
            }

            if (!$hasOtherDefault) {
                $currentAddr = new Address($idAddress);
                if (Validate::isLoadedObject($currentAddr)) {
                    $currentAddr->default = 1;
                    $currentAddr->update();
                }
            }
        }
    }

    /**
     * Validates invoice address consistency based on business rules and need_invoice setting
     * Uses isInvoiceMandatory() to determine the correct need_invoice value
     * Then ensures id_address_invoice consistency
     */
    private function validateInvoiceAddressConsistency()
    {
        if (!$this->cart || !(int)$this->cart->id || !(int)$this->cart->id_address_delivery) {
            return false;
        }

        $cart = $this->cart;
        $cart->id_address_delivery = (int)$cart->id_address_delivery;
        $cart->id_address_invoice  = (int)$cart->id_address_invoice;
        $cart->need_invoice        = (int)!empty($cart->need_invoice);

        $shouldNeedInvoice   = $this->isInvoiceMandatory();
        $currentNeedInvoice  = (bool)$cart->need_invoice;
        $updated             = false;

        if ($shouldNeedInvoice !== $currentNeedInvoice) {
            $cart->need_invoice = $shouldNeedInvoice ? 1 : 0;
            $updated = true;
        }

        if (!$cart->need_invoice) {
            // SIN factura distinta → SIEMPRE igualar a delivery
            if ($cart->id_address_invoice !== $cart->id_address_delivery) {
                $cart->id_address_invoice = $cart->id_address_delivery;
                $updated = true;
            }
        } else {
            // Con factura → asegurar una dirección válida (si no hay, hereda delivery)
            if (!(int)$cart->id_address_invoice) {
                $cart->id_address_invoice = $cart->id_address_delivery;
                $updated = true;
            }
        }

        if ($updated) {
            $cart->update();
            return true;
        }

        return false;
    }



    public function l($string, $specific = false, $locale = null){

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

}
