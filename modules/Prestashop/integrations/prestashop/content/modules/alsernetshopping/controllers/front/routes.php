<?php

use Checkout\CheckoutAddressController;
use Checkout\CheckoutDeliveryController;
use Checkout\CheckoutAuthenticationController;
use Checkout\CheckoutPaymentController;

include_once(dirname(__FILE__) . '/../front/CheckoutController.php');
include_once(dirname(__FILE__) . '/../front/GtmController.php');
include_once(dirname(__FILE__) . '/../front/Checkout/CheckoutAuthenticationController.php');
include_once(dirname(__FILE__) . '/../front/Checkout/CheckoutDeliveryController.php');
include_once(dirname(__FILE__) . '/../front/Checkout/CheckoutPaymentController.php');
include_once(dirname(__FILE__) . '/../front/Checkout/CheckoutAddressController.php');
include_once(dirname(__FILE__) . '/../front/CartController.php');

class AlsernetshoppingRoutesModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function init()
    {

        parent::init();

        $response = $this->handleAction();

        $this->sendJsonResponse($response);

    }

    private function syncLanguageFromCountry()
    {
        $context = Context::getContext();
        if (
            !isset($context->country, $context->language, $context->customer)
            || $context->customer->isLogged(true)
        ) {
            return;
        }

        $map = [
            6 => 1,  // España    → Español
            17 => 2,  // USA       → Inglés
            8 => 3,  // Francia   → Francés
            15 => 4,  // Italia    → Italiano
            1 => 5,  // Portugal  → Portugués
            10 => 6,  // Alemania  → Alemán
        ];

        $countryId = (int)$context->country->id;
        $desiredLangId = $map[$countryId] ?? (int)Configuration::get('PS_LANG_DEFAULT');

        if ((int)$context->language->id !== $desiredLangId) {
            $context->language = new Language($desiredLangId);
            $context->cookie->id_lang = $desiredLangId;
            $context->cookie->write();

            if ($context->cart && $context->cart->id) {
                $context->cart->id_lang = $desiredLangId;
                $context->cart->save();
            }
        }
    }

    public function display()
    {
        if (Tools::getValue('ajax') == 1) {
            $this->ajax = true;
            $this->sendJsonResponse($this->handleAction());
        } else {
            parent::initContent();
            $this->sendJsonResponse($this->handleAction());
        }
    }

    private function syncLanguageFromCountryIfNeeded()
    {
        $context = Context::getContext();

        // No hacer nada si no hay contexto válido
        if (!isset($context->country) || !isset($context->language)) {
            return false;
        }

        // No hacer nada si el usuario está logueado
        if (isset($context->customer) && $context->customer->isLogged(true)) {
            return false;
        }

        // Primero: Verificar si hay prefijo de idioma en URL pero PrestaShop no lo procesó
        $urlLanguageIso = $this->getLanguageFromUrl();
        if ($urlLanguageIso) {
            // Si hay prefijo en URL pero el contexto no coincide, forzar el cambio
            if ($context->language->iso_code !== $urlLanguageIso) {
                return $this->forceLanguageByIso($urlLanguageIso);
            }
            // Si ya coincide, no hacer nada más
            return false;
        }

        // Solo sincronizar por país si NO hay prefijo de idioma en la URL
        $countryId = (int)$context->country->id;

        // Mapa país → idioma
        $mapCountryToLang = [
            6 => 1, // España    → Español
            17 => 2, // USA       → Inglés
            8 => 3, // Francia   → Francés
            15 => 4, // Italia    → Italiano
            1 => 5, // Portugal  → Portugués
            10 => 6, // Alemania  → Alemán
        ];

        $desiredLangId = isset($mapCountryToLang[$countryId])
            ? $mapCountryToLang[$countryId]
            : (int)Configuration::get('PS_LANG_DEFAULT');

        // Solo cambiar si el idioma actual es diferente
        if ((int)$context->language->id !== $desiredLangId) {

            // Verificar que el idioma existe y está activo
            $language = new Language($desiredLangId);
            if (!Validate::isLoadedObject($language) || !$language->active) {
                return false;
            }

            // Actualizar contexto
            $context->language = $language;
            $context->cookie->id_lang = $desiredLangId;
            $context->cookie->write();

            // Actualizar carrito si existe
            if (isset($context->cart) && $context->cart->id) {
                $context->cart->id_lang = $desiredLangId;
                $context->cart->save();
            }

            return true;
        }

        return false;
    }

    /**
     * Extrae el código de idioma de la URL si existe
     * Ejemplo: /fr/module/... → "fr"
     */
    private function getLanguageFromUrl()
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // Obtener todos los idiomas activos
        $languages = Language::getLanguages(true);

        foreach ($languages as $language) {
            $isoCode = $language['iso_code'];
            // Verificar si la URL contiene el prefijo del idioma
            if (preg_match('#^/?' . preg_quote($isoCode) . '/#', $requestUri)) {
                return $isoCode;
            }
        }

        return null;
    }


    /**
     * Fuerza el cambio de idioma por código ISO
     */
    private function forceLanguageByIso($isoCode)
    {
        $context = Context::getContext();

        // Buscar el idioma por ISO
        $languages = Language::getLanguages(true);
        $targetLanguage = null;

        foreach ($languages as $lang) {
            if ($lang['iso_code'] === $isoCode) {
                $targetLanguage = new Language($lang['id_lang']);
                break;
            }
        }

        if (!$targetLanguage || !Validate::isLoadedObject($targetLanguage)) {
            return false;
        }

        // Actualizar contexto
        $context->language = $targetLanguage;
        $context->cookie->id_lang = $targetLanguage->id;
        $context->cookie->write();

        // Actualizar carrito si existe
        if (isset($context->cart) && $context->cart->id) {
            $context->cart->id_lang = $targetLanguage->id;
            $context->cart->save();
        }

        error_log("Idioma forzado a: {$isoCode} (ID: {$targetLanguage->id})");

        return true;
    }


    /**
     * Verifica si la URL actual contiene un prefijo de idioma
     * Ejemplo: /es/, /fr/, /en/, etc.
     */
    private function hasLanguagePrefixInUrl()
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // Obtener todos los idiomas activos
        $languages = Language::getLanguages(true);

        foreach ($languages as $language) {
            $isoCode = $language['iso_code'];
            // Verificar si la URL contiene el prefijo del idioma
            if (preg_match('#^/?' . preg_quote($isoCode) . '/#', $requestUri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Método alternativo para detectar prefijo de idioma usando el contexto de PrestaShop
     */
    private function hasLanguagePrefixInUrlAlternative()
    {
        $context = Context::getContext();

        // Si PrestaShop detectó un idioma por URL, respetarlo
        if (isset($context->language) && isset($_GET['isolang'])) {
            return true;
        }

        // Verificar si la URL contiene el iso_code del idioma actual
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $currentLangIso = $context->language->iso_code;

        return (strpos($requestUri, '/' . $currentLangIso . '/') !== false);
    }

    private function handleAction()
    {
        $modalitie = Tools::getValue('modalitie');
        $action = Tools::getValue('action');
        $iso = Tools::getValue('iso');


        switch ($modalitie) {
            case 'shopping':
            case 'cart':
                $controller = new CartController();
                $response = null;

                switch ($action) {
                    case 'summary':
                        $response = $controller->summary();
                        break;
                    case 'add':
                        $response = $controller->add();
                        break;
                    case 'count':
                        $response = $controller->count();
                        break;
                    case 'init':
                        $response = $controller->init();
                        break;
                    case 'delete':
                        $response = $controller->delete();
                        break;
                    case 'update':
                        $response = $controller->update();
                        break;
                    case 'get':
                        $response = $controller->gets();
                        break;
                    case 'modal':
                        $response = $controller->modal();
                        break;
                    case 'modalcomplementary':
                        $response = $controller->modalcomplementary();
                        break;
                    case 'coupon':
                        $response = $controller->coupon();
                        break;
                    case 'deletecoupon':
                        $response = $controller->deletecoupon();
                        break;
                    default:
                        $response = array(
                            'status' => 'error',
                            'message' => 'Invalid action',
                        );
                        break;
                }
                return $response;
            case 'checkout':

                $controllerCheckout = new CheckoutController();
                $controllerAddress = new CheckoutAddressController();
                $controllerDelivery = new CheckoutDeliveryController();
                $controllerPayment = new CheckoutPaymentController();
                $controllerAuthentication = new CheckoutAuthenticationController();

                $response = null;

                switch ($action) {
                    case 'validations':
                        $response = $controllerCheckout->validations();
                        break;
                    case 'load':
                        $response = $controllerCheckout->load();
                        break;
                    case 'step':
                        $response = $controllerCheckout->step();
                        break;
                    case 'steps':
                        $response = $controllerCheckout->steps();
                        break;


                    case 'coupon':
                        $response = $controllerCheckout->coupon();
                        break;
                    case 'deletecoupon':
                        $response = $controllerCheckout->deletecoupon();
                        break;
                    case 'summary':
                        $response = $controllerCheckout->stepsummary();
                        break;

                    // Address actions
                    case 'address':
                        $response = $controllerAddress->init();
                        break;
                    case 'addaddress':
                        $response = $controllerAddress->addaddress();
                        break;
                    case 'editaddress':
                        $response = $controllerAddress->editaddress();
                        break;
                    case 'deleteaddress':
                        $response = $controllerAddress->deleteaddress();
                        break;
                    case 'setaddress':
                        $response = $controllerAddress->setaddress();
                        break;
                    case 'getaddress':
                        $response = $controllerAddress->getaddress();
                        break;
                    case 'setneeinvoice':
                        $response = $controllerAddress->setneeinvoice();
                        break;
                    case 'getaddaddressfields':
                        $response = $controllerAddress->getaddaddressfields();
                        break;
                    case 'getaddressfields':
                        $response = $controllerAddress->getaddressfields();
                        break;
                    case 'getstates':
                        $response = $controllerAddress->getstates();
                        break;
                    case 'validatepostcode':
                        $response = $controllerAddress->validatepostcode();
                        break;
                    case 'getaddressdelivery':
                        $response = $controllerAddress->getaddressdelivery();
                        break;
                    case 'getaddressinvoice':
                        $response = $controllerAddress->getaddressinvoice();
                        break;
                    case 'stepaddress':
                        $response = $controllerAddress->stepaddress();
                        break;

                    // Delivery actions
                    case 'delivery':
                        $response = $controllerDelivery->init();
                        break;
                    case 'getdeliverys':
                        $response = $controllerDelivery->getdelivery();
                        break;
                    case 'setdelivery':
                        $response = $controllerDelivery->setdelivery();
                        break;
                    case 'selectdelivery':
                        $response = $controllerDelivery->selectdelivery();
                        break;
                    case 'stepdelivery':
                        $response = $controllerDelivery->stepdelivery();
                        break;
                    case 'steppayment':
                        $response = $controllerPayment->steppayment();
                        break;


                    // Auth actions
                    case 'steplogin':
                        $response = $controllerAuthentication->steplogin();
                        break;
                    case 'auth':
                        $response = $controllerAuthentication->init();
                        break;
                    case 'authlogin':
                        $response = $controllerAuthentication->login();
                        break;
                    case 'authregister':
                        $response = $controllerAuthentication->register();
                        break;
                    case 'resetpassword':
                        $response = $controllerAuthentication->resetpassword();
                        break;
                    case 'validateemail':
                        $response = $controllerAuthentication->validateemail();
                        break;
                    case 'changepassword':
                        $response = $controllerAuthentication->changepassword();
                        break;


                    // Payment actions
                    case 'steppayment':
                        $response = $controllerPayment->steppayment();
                        break;


                    case 'stepsummary':
                        $response = $controllerCheckout->stepsummary();
                        break;

                    default:
                        $response = array(
                            'status' => 'error',
                            'message' => 'Invalid action',
                        );
                        break;
                }
                return $response;
            case 'gtp':

                $controllerGtm = new GtmController();

                $response = null;

                switch ($action) {
                    case 'init':
                        $response = $controllerGtm->init();
                        break;
                    default:
                        $response = array(
                            'status' => 'error',
                            'message' => 'Invalid action',
                        );
                        break;
                }
                return $response;

            default:
                return array(
                    'status' => 'error',
                    'message' => 'Invalid modality',
                );
        }
    }

    private function sendJsonResponse($response)
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}