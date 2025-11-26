<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AddressController extends Module
{
    public $module;
    protected $controllerName;
    protected $customer;
    protected $cart;
    protected $iso;
    protected $lang;
    protected $language;

    public function __construct(){
        $this->bootstrap = true;
        $iso = Tools::getValue('iso');
        $this->module =  Module::getInstanceByName("alsernetcustomer");
        $this->context = Context::getContext();
        $this->customer = $this->context->customer;
        $this->cart = $this->context->cart;
        $this->language = new Language(Language::getIdByIso($iso));
        $this->iso = $iso;
        $this->lang = (int)$this->context->language->id;
        parent::__construct();
    }

    public function addaddress()
    {
        try {

            $context = Context::getContext();
            $customer = $context->customer;

            if (!$context->customer || !$context->customer->isLogged()) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Unauthorized access.', 'addresscontroller'),
                    'data' => [],
                ];
            }

            $address = new Address();
            $address->id_customer = (int) $context->customer->id;
            $address->alias = Tools::getValue('alias');
            $address->firstname = Tools::getValue('firstname');
            $address->lastname = Tools::getValue('lastname');
            $address->address1 = Tools::getValue('address1');
            $address->address2 = Tools::getValue('address2');
            $address->postcode = Tools::getValue('postcode');
            $address->city = Tools::getValue('city');
            $address->id_country = (int) Tools::getValue('id_country');
            $address->id_state = (int) Tools::getValue('id_state');
            $address->phone = Tools::getValue('phone');
            $address->phone_mobile = Tools::getValue('phone_mobile');
            $address->active = 1;
            $address->default      = (int)Tools::getValue('default', 0);

            $this->enforceUniqueDefaultAddress((int)$customer->id, (int)$address->id, (int)$address->default);
            // Validar dirección antes de guardar
            $errors = $address->validateFieldsRequiredDatabase();
            if (count($errors)) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Please fill in all required fields.', 'addresscontroller'),
                    'data' => $errors,
                ];
            }

            if (!$address->add()) {
                return [
                    'status' => 'warning',
                    'operation' => $this->l('Error creating address.', 'addresscontroller'),
                    'message' => $this->l('Error creating address.', 'addresscontroller'),
                    'data' => [],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->l('Address created successfully.', 'addresscontroller'),
                'data' => [
                    'id_address' => $address->id,
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $this->l('Unexpected error occurred while adding address.', 'addresscontroller'),
                'data' => ['exception' => $e->getMessage()],
            ];
        }
    }


    public function editaddress(){

        try {

            $context = Context::getContext();
            $customer = $context->customer;
            $id_address = Tools::getValue('id_address');
            $iso = Tools::getValue('iso');

            if (!$id_address || !Validate::isUnsignedId($id_address)) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Invalid address ID.', 'addresscontroller'),
                    'data' => [],
                ];
            }

            $address = new Address((int)$id_address);
            if (!Validate::isLoadedObject($address)) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Address not found.', 'addresscontroller'),
                    'data' => [],
                ];
            }

            $context = Context::getContext();
            if (!$context->customer || $context->customer->id != $address->id_customer) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Unauthorized access.', 'addresscontroller'),
                    'data' => [],
                ];
            }

            $address->alias = Tools::getValue('alias');
            $address->firstname = Tools::getValue('firstname');
            $address->vat_number = Tools::getValue('vat_number');
            $address->lastname = Tools::getValue('lastname');
            $address->address1 = Tools::getValue('address1');
            $address->address2 = Tools::getValue('address2');
            $address->postcode = Tools::getValue('postcode');
            // $address->default = Tools::getValue('default');
            $address->city = Tools::getValue('city');
            $address->id_country = (int) Tools::getValue('id_country');
            $address->id_state = (int) Tools::getValue('id_state');
            $address->phone = Tools::getValue('phone');
            $address->phone_mobile = Tools::getValue('phone_mobile');
            $address->active = 1;
            $address->default      = (int)Tools::getValue('default', (int)$address->default); // <- viene del form

            $this->enforceUniqueDefaultAddress((int)$customer->id, (int)$address->id, (int)$address->default);

            if (!$address->update()) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Error updating address.', 'addresscontroller'),
                    'data' => [],
                ];
            }


            return [
                'status' => 'success',
                'message' => $this->l('Address updated successfully.', 'addresscontroller'),
                'data' => [],
            ];


        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'message' => $this->l('Unauthorized access.', 'addresscontroller'),
                'data' => [],
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
                    'message' => $this->l('Address not found', 'addresscontroller'),
                    'data'    => [],
                ];
            }

            if (!$customer || $customer->id != $address->id_customer) {
                return [
                    'status'  => 'warning',
                    'message' => $this->l('Unauthorized access', 'addresscontroller'),
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
                $label = $this->l('Country', 'addresscontroller');
            }elseif ($field === 'Firstname') {
                $label = $this->l('Firstname', 'addresscontroller');
            }elseif ($field === 'Lastname') {
                $label = $this->l('Lastname', 'addresscontroller');
            }elseif ($field === 'Postcode') {
                $label = $this->l('Postcode', 'addresscontroller');
            }elseif ($field === 'State:name') {
                $label = $this->l('State', 'addresscontroller');
            }elseif ($field === 'Vat number') {
                $label = $this->l('Vat number', 'addresscontroller');
            }elseif ($field === 'Address1') {
                $label = $this->l('Address1', 'addresscontroller');
            }else {
                $label = ucfirst(str_replace(['_', ':name'], [' ', ''], $field));
            }

            $fieldData = [
                'name'     => $field,
                'label'    => $this->l($label, 'addresscontroller'),
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

        $labelDefault = $this->l('Use as default address', 'addresscontroller');

        $defaultField = [
            'name'     => 'default',
            'label'    => $labelDefault,
            'required' => false,
            'type'     => 'select',
            'value'    => $isDefaultSelected,
            'options'  => [
                ['value' => 1, 'label' => $this->l('Yes', 'addresscontroller')],
                ['value' => 0, 'label' => $this->l('No', 'addresscontroller')],
            ],
        ];

        $fieldsData[] = $defaultField;

        return [
            'status'  => 'success',
            'message' => $this->l('Address loaded successfully', 'addresscontroller'),
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
                'message' => $this->l('Invalid address ID', 'addresscontroller'),
                'data'    => [],
            ];
        }

        $address = new Address((int)$id_address);

        if (!Validate::isLoadedObject($address)) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Address not found', 'addresscontroller'),
                'data'    => [],
            ];
        }

        if (!$customer || $customer->id != $address->id_customer) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Unauthorized access', 'addresscontroller'),
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
                $label = $this->l('Country', 'addresscontroller');
            }elseif ($field === 'Firstname') {
                $label = $this->l('Firstname', 'addresscontroller');
            }elseif ($field === 'Lastname') {
                $label = $this->l('Lastname', 'addresscontroller');
            }elseif ($field === 'Postcode') {
                $label = $this->l('Postcode', 'addresscontroller');
            }elseif ($field === 'State:name') {
                $label = $this->l('State', 'addresscontroller');
            }elseif ($field === 'Vat number') {
                $label = $this->l('Vat number', 'addresscontroller');
            }elseif ($field === 'Address1') {
                $label = $this->l('Address1', 'addresscontroller');
            }else {
                $label = ucfirst(str_replace(['_', ':name'], [' ', ''], $field));
            }

            $fieldData = [
                'name'     => $field,
                'label'    => $this->l($label, 'addresscontroller'),
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

        $labelDefault = ($type === 'invoice') ? $this->l('Use as default invoice address', 'addresscontroller') : $this->l('Use as default delivery address', 'addresscontroller');

        $defaultField = [
            'name'     => 'default',
            'label'    => $labelDefault,
            'required' => true,
            'type'     => 'select',
            'value'    => $isDefaultSelected,
            'options'  => [
                ['value' => 1, 'label' => $this->l('Yes', 'addresscontroller')],
                ['value' => 0, 'label' => $this->l('No', 'addresscontroller')],
            ],
            'meta'     => ['applies_to' => $type, 'address_id' => (int)$address->id],
        ];

        $fieldsData[] = $defaultField;

        return [
            'status'  => 'success',
            'message' => $this->l('Address loaded successfully', 'addresscontroller'),
            'data'    => [
                'type'       => $type,
                'id_address' => (int)$address->id,
            ],
            'fields'  => $fieldsData,
        ];
    }

    public function getaddresses()
    {
        $context = Context::getContext();
        $customer = $context->customer;
        $customer = $context->customer;

        $translations = [
            'postalcode' => $this->l('Postal code', 'addresscontroller'),
            'city' => $this->l('City', 'addresscontroller'),
            'country' => $this->l('Country', 'addresscontroller'),
            'dnivat' => $this->l('Vat number', 'addresscontroller'),
            'phone' => $this->l('Phone', 'addresscontroller'),
            'address' => $this->l('Address', 'addresscontroller'),
            'default' => $this->l('Default', 'addresscontroller'),
            'delete' => $this->l('Delete', 'addresscontroller'),
            'update' => $this->l('Update', 'addresscontroller'),
        ];

        if (!$customer || !$customer->isLogged()) {
            return [
                'status' => 'warning',
                'message' => $this->l('You must be logged in to see your addresses.', 'addresscontroller'),
                'addresses' => [],
                'translations' => $translations,
            ];
        }

        $addresses = $customer->getAddresses($context->language->id);

        if (empty($addresses)) {
            return [
                'status' => 'warning',
                'message' => $this->l('No addresses found.', 'addresscontroller'),
                'addresses' => [],
                'translations' => $translations,
            ];
        }

        $context->smarty->assign([
            'addresses' => $addresses,
            'translations' => $translations,
        ]);


        $html = $context->smarty->fetch('module:alsernetcustomer/views/templates/_partials/address.tpl');

        return [
            'status' => 'success',
            'message' => $this->l('Addresses loaded successfully.', 'addresscontroller'),
            'html' => $html,
        ];
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


    public function getstates()
    {
        $context = Context::getContext();
        $id_country = (int) Tools::getValue('id_country');
        $id_lang = (int) $context->language->id;
        $iso = Tools::getValue('iso');

        if (!$id_country || !Validate::isUnsignedId($id_country)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid country ID', 'addresscontroller'),
                'label' => '',
                'options' => [],
            ];
        }

        $states = State::getStatesByIdCountry($id_country, $id_lang);
        $options = [];

        foreach ($states as $state) {
            $options[] = [
                'value' => (int) $state['id_state'],
                'label' => $state['name'], // Ya traducido con $id_lang
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->l('States loaded successfully', 'addresscontroller'),
            'label' => $this->l('State', 'addresscontroller'),
            'options' => $options,
        ];
    }


    public function deleteaddress()
    {
        $id_address = Tools::getValue('id_address');
        $iso = Tools::getValue('iso');

        if (!$id_address || !Validate::isUnsignedId($id_address)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid address ID', 'addresscontroller'),
                'data' => [],
            ];
        }

        $address = new Address((int)$id_address);

        if (!Validate::isLoadedObject($address)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Address not found', 'addresscontroller'),
                'data' => [],
            ];
        }

        $context = Context::getContext();
        if ($context->customer->id != $address->id_customer) {
            return [
                'status' => 'warning',
                'message' => $this->l('Unauthorized access', 'addresscontroller'),
                'data' => [],
            ];
        }
        if ($address->delete()) {

            return [
                'status' => 'success',
                'message' => $this->l('Address deleted successfully.', 'addresscontroller'),
                'data' => [],
            ];
        }

        return [
            'status' => 'warning',
            'message' => $this->l('Error deleting address', 'addresscontroller'),
            'data' => [],
        ];
    }


    public function getaddress(){

        try {

            $id_address = Tools::getValue('id_address');
            $iso = Tools::getValue('iso');

            if (!$id_address || !Validate::isUnsignedId($id_address)) {
                http_response_code(400);
                throw new Exception('Invalid address ID', 'addresscontroller');
            }

            $address = new Address((int)$id_address);
            if (!Validate::isLoadedObject($address)) {
                http_response_code(404);
                throw new Exception('Address not found', 'addresscontroller');
            }

            $context = Context::getContext();
            if (!$context->customer || $context->customer->id != $address->id_customer) {
                http_response_code(403);
                throw new Exception('Unauthorized access', 'addresscontroller');
            }

            $addressData = [
                'alias' => $address->alias,
                'firstname' => $address->firstname,
                'lastname' => $address->lastname,
                'company' => $address->company,
                'address1' => $address->address1,
                'address2' => $address->address2,
                'postcode' => $address->postcode,
                'city' => $address->city,
                'id_country' => $address->id_country,
                'id_state' => $address->id_state,
                'phone' => $address->phone,
                'phone_mobile' => $address->phone_mobile,
            ];

            return [
                'status' => 'success',
                'message' => $this->l('Address deleted successfully.', 'addresscontroller'),
                'data' => $addressData,
            ];

        } catch (Exception $e) {

            return [
                'status' => 'warning',
                'message' => $this->l('Error deleting address', 'addresscontroller'),
                'data' => [],
            ];
        }


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
        $iso = $this->language->iso_code;

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



