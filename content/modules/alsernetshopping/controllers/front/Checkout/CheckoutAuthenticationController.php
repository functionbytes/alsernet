<?php

namespace Checkout;

require_once(dirname(__FILE__) . '/../../../classes/CheckoutValidationService.php');
require_once _PS_MODULE_DIR_ . 'alsernetforms/controllers/front/NewslettersController.php';
require_once dirname(__FILE__) . '/../BaseController.php';

use NewslettersController;
use Configuration;
use Customer;
use Context;
use Language;
use Currency;
use Module;
use Tools;
use Validate;
use Hook;
use Mail;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CheckoutAuthenticationController extends \BaseController
{


    public function __construct()
    {
        parent::__construct();
    }

    public function init()
    {
        $lang = $this->lang;
        $cart = $this->cart;
        $context = $this->context;
        $customer = $this->customer;
        $iso = $this->language->iso_code;
        $isLogged = $customer->isLogged();
        $showLoginForm = !$isLogged;
        $customerArray = [];

        if ($isLogged) {
            $customerArray = $customer->getFields();
            $customerArray['is_logged'] = true;
            $customerArray['is_guest'] = $customer->is_guest;
            $customerArray['firstname'] = $customer->firstname;
            $customerArray['lastname'] = $customer->lastname;
            $customerArray['email'] = $customer->email;
            $customerArray['birthday'] = $customer->birthday;
            $customerArray['newsletter'] = $customer->newsletter;
            $customerArray['optin'] = $customer->optin;
        }


        $checkoutUrls = [
            'pages' => [
                'identity' => $context->link->getPageLink('identity', null, $lang),
                'order' => $context->link->getPageLink('order', null, $lang),
                'authentication' => $context->link->getPageLink('authentication', null, $lang),
                'register' => $context->link->getPageLink('authentication', null, $lang, 'create_account=1'),
                'my_account' => $context->link->getPageLink('my-account', null, $lang),
            ],
            'actions' => [
                'logout' => $context->link->getPageLink('index', true, $lang, 'mylogout'),
                'login' => '/modules/alsernetshopping/controllers/routes.php?modalitie=checkout&action=authlogin&iso=' . $iso,
                'register' => '/modules/alsernetshopping/controllers/routes.php?modalitie=checkout&action=authregister&iso=' . $iso,
                'password' =>  $this->context->link->getPageLink('password', true, $lang)
            ],
        ];


        $configuration = [
            'guest_allowed' => (bool) Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
            'empty_cart_on_logout' => !Configuration::get('PS_CART_FOLLOWING'),
            'account_creation_required' => !Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
            'password_policy' => [
                'length' => Configuration::get('PS_PASSWD_MIN_LENGTH') ?: 8,
                'score' => Configuration::get('PS_PASSWD_SCORE') ?: 2,
            ]
        ];

        $translations = [
            'email' => $this->l('Email'),
            'password' => $this->l('Password'),
            'forgot_password' => $this->l('Forgot your password?','checkoutauthenticationcontroller'),
            'firstname' => $this->l('First name','checkoutauthenticationcontroller'),
            'lastname' => $this->l('Last name','checkoutauthenticationcontroller'),
            'birthday' => $this->l('Birthdate','checkoutauthenticationcontroller'),
            'newsletter' => $this->l('Sign up for our newsletter','checkoutauthenticationcontroller'),
            'privacy_policy' => $this->l('I agree to the privacy policy','checkoutauthenticationcontroller'),
            'terms_conditions' => $this->l('I agree to the terms and conditions','checkoutauthenticationcontroller'),
        ];

        $cartInfo = [
            'id' => $cart->id,
            'total' => $cart->getOrderTotal(),
        ];

        $errors = [];
        if (Tools::getValue('login_error')) {
            $errors[] = $this->l('Authentication failed.');
        }
        if (Tools::getValue('create_account_error')) {
            $errors[] = $this->l('An error occurred while creating your account.','checkoutauthenticationcontroller');
        }

        $context->smarty->assign([
            'authenticated' => $isLogged,
            'show_login_form' => $showLoginForm,
            'customer' => $customerArray,
            'urls' => $checkoutUrls,
            'configuration' => $configuration,
            'translations' => $translations,
            'cart_info' => $cartInfo,
            'errors' => $errors,
            'iso' => $iso,
            'current_step' => 'login',
            'checkout_session' => [
                'token' => Tools::getToken(false),
                'time' => time(),
            ],
        ]);

        return $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/view/login.tpl');

    }

    public function steplogin()
    {
        $context = $this->context;
        $customer = $this->customer;
        $cart = $this->cart;

        if (!$customer|| !$customer->isLogged()) {
            return [
                'status' => 'warning',
                'message' => $this->l('Unauthorized access.','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        }

        $need_invoice = Tools::getValue('need_invoice') === '1' || Tools::getValue('need_invoice') === 'on';
        $address_invoide = (int) Tools::getValue('address_invoide');
        $this->forceSingleDeliveryAddressForCart($cart);

        if (!$cart->id_address_delivery) {
            return [
                'status' => 'warning',
                'message' => $this->l('Delivery address is required.','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        }

        if ($need_invoice) {
            $cart->id_address_invoice = $cart->id_address_invoice;
        } else {
            $cart->id_address_invoice = $cart->id_address_delivery;
        }

        $cart->need_invoice = $need_invoice;
        $cart->step = 'address';

        if (!$cart->update()) {
            return [
                'status' => 'warning',
                'message' => $this->l('Failed to update cart with address data.','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->l('Addresses saved successfully.','checkoutauthenticationcontroller'),
            'operation' => $this->l('Step completed','checkoutauthenticationcontroller'),
            'data' => [
                'id_address_delivery' => $cart->id_address_delivery,
                'id_address_invoice' => $cart->id_address_invoice,
                'need_invoice' => $cart->need_invoice,
            ],
        ];


    }

    public function login()
    {
        $context = $this->context;
        $email = trim(Tools::getValue('email'));
        $password = trim(Tools::getValue('password'));
        $iso = trim(Tools::getValue('iso'));
        $id_lang = Language::getIdByIso($iso);

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } elseif (!Validate::isPasswd($password)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid password','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } elseif (!Tools::getValue('remember')) {
            $context->cookie->customer_last_activity = time();
        }

        $customer = new Customer();
        $authentication = $customer->getByEmail($email, $password);

        if (isset($authentication->active) && !$authentication->active) {
            return [
                'status' => 'warning',
                'message' => $this->l('Your account isn\'t available at this time, please contact us','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } elseif (!$authentication || !$customer->id || $customer->is_guest) {
            return [
                'status' => 'warning',
                'message' => $this->l('Authentication failed.','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } else {

            $context->updateCustomer($customer);

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully logged in','checkoutauthenticationcontroller'),
                'data' => [],
                'url' => $context->link->getPageLink('my-account', true, $id_lang),
            ];
        }
    }

    public function register()
    {

        $guestCheckoutEnabled = true;
        $context = $this->context;
        $email = trim(Tools::getValue('email'));
        $password = trim(Tools::getValue('password'));
        $firstname = trim(Tools::getValue('firstname'));
        $birthday = trim(Tools::getValue('date'));
        $lastname = trim(Tools::getValue('lastname'));
        $iso = trim(Tools::getValue('iso'));
        $id_lang = Language::getIdByIso($iso);
        $sports = Tools::getValue('sports');
        $condition = Tools::getValue('condition');
        $services = Tools::getValue('services');

        if (!is_array($sports)) {
            $sports = [];
        }

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } elseif (!$guestCheckoutEnabled && !Validate::isPasswd($password)) {
            // Only validate password if guest checkout is disabled
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid password','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } elseif (!Validate::isName($firstname)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid first name','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } elseif (!Validate::isName($lastname)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid last name','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } elseif (empty($sports) || count($sports) === 0) {
            return [
                'status' => 'warning',
                'message' => $this->l('Please select at least one sport','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } elseif (empty($condition) || $condition !== 'on') {
            return [
                'status' => 'warning',
                'message' => $this->l('You must accept the terms and conditions','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        }

        if (Customer::customerExists($email, true, true)) {
            return [
                'status' => 'warning',
                'message' => $this->l('This email is already used, please choose another one or sign in','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } else {

            $customer = new Customer();
            $customer->firstname = $firstname;
            $customer->lastname = $lastname;
            $customer->email = $email;
            $customer->is_guest = 0;

            if ($guestCheckoutEnabled && empty($password)) {
                $generatedPassword = Tools::passwdGen(8);
                $customer->passwd = $this->get('hashing')->hash($generatedPassword, _COOKIE_KEY_);
                $password = $generatedPassword;
            } else {
                $customer->passwd = $this->get('hashing')->hash($password, _COOKIE_KEY_);
            }

            if ($customer->save()) {

                $context->updateCustomer($customer);
                $context->cart->id_customer = (int)$customer->id;
                $context->cart->update();

                $subject = $this->l('Welcome!');

                $mailParams = array(
                    '{email}' => $customer->email,
                    '{lastname}' => $customer->lastname,
                    '{firstname}' => $customer->firstname,
                );

                Mail::Send(
                    $this->context->language->id,
                    'account',
                    $subject,
                    $mailParams,
                    $customer->email,
                    $customer->firstname . ' ' . $customer->lastname
                );

                Hook::exec('actionCustomerAccountAdd', array(
                    'newCustomer' => $customer,
                ));

                if (class_exists('NewslettersController')) {

                    $sportsRaw = is_array($sports ?? null)
                        ? $sports
                        : preg_split('/[,\s;]+/', (string)($sports ?? Tools::getValue('sports', '')), -1, PREG_SPLIT_NO_EMPTY);

                    $sportsIds = array_values(array_unique(array_filter(array_map('intval', $sportsRaw), function ($v) { return $v > 0; })));

                    $sportsCsv = implode(',', $sportsIds);

                    $controller = new NewslettersController();
                    $controller->registersubscribe([
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'email' => $email,
                        'sports' => $sportsCsv,
                        'iso' => $iso,
                        'birthday' => $birthday,
                        'parties' => $services,
                        'condition' => $condition,
                    ]);
                }

                return [
                    'status' => 'success',
                    'message' => $this->l('You have successfully created a new account.','checkoutauthenticationcontroller'),
                    'url' => $this->context->link->getPageLink('my-account', true, $id_lang),
                    'data' => [],
                ];

            } else {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while creating the new account.','checkoutauthenticationcontroller'),
                    'url' => $this->context->link->getPageLink('my-account', true, $id_lang),
                    'data' => [],
                ];
            }
        }
    }

    public function validateemail()
    {
        $email = trim(Tools::getValue('email'));

        if (!Validate::isEmail($email)) {
            return [
                'success' => 'warning',
                'message' => $this->l('Invalid email address','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        }

        $customer = new Customer();
        if ($customer->getByEmail($email)) {
            return [
                'status' => 'success',
                'message' => $this->l('Your email is already registered in our system','checkoutauthenticationcontroller'),
                'data' => [],
            ];
        } else {
            return [
                'status' => 'warning',
                'message' => '',
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



