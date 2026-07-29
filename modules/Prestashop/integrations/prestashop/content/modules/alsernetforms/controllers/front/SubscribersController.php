<?php

include_once(dirname(__FILE__).'/../../classes/ApiManager.php');

class SubscribersController extends Module
{
    public $module;
    public $firstname;
    public $lastname;
    public $email;
    public $id_lang;
    public $iso;
    //const URL_ERP = 'http://127.0.0.1:58002/api-gestion/';
    const URL_ERP = 'http://interges:8080/api-gestion/';
    public function __construct(){
        $this->bootstrap = true;
        $this->module =  Module::getInstanceByName("alsernetforms");
        parent::__construct();
    }


    public function newslettersubscribe()
    {

        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $sports = Tools::getValue('sports');
        $iso = trim(Tools::getValue('iso'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $data = [
            'action' => 'subscribe',
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'lang' => $iso,
            'sports' => $sports,
            'parties' => !empty(Tools::getValue('condition')) ? false : true,
            'commercial' => !empty(Tools::getValue('services')) ? false : true,
        ];

        $apiManager = new ApiManager();
        $response = $apiManager->sendRequest('POST', 'api/v1/subscribers', $data, 'subscription');

        if (isset($response['response']['status']) && $response['response']['status'] === 'success') {

            return [
                'status' => 'success',
                'data' => [
                    'action' => $response['response']['data']['action'],
                    'commercial' => $response['response']['data']['subscriber']['commercial'],
                    'parties' => $response['response']['data']['subscriber']['parties'],
                    'check' => $response['response']['data']['subscriber']['check']!=null ? $response['response']['data']['subscriber']['check'] : null,
                ],
                'message' => $this->l('Your subscription has been successfully updated.', 'formcontroller', $iso),
            ];
        }

        return [
            'status' => 'warning',
            'message' => $this->l('There was an error processing your subscription.', 'formcontroller', $iso),
            'data' => $response['response'] ?? [],
        ];
    }


    public function newsletterdischargersnone()
    {
        $context = Context::getContext();
        $email = trim(Tools::getValue('email'));
        $iso = trim(Tools::getValue('iso'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $data = [
            'action' => 'unsubscribe_none',
            'email' => $email,
        ];

        $apiManager = new ApiManager();
        $response = $apiManager->sendRequest('POST', 'api/v1/subscribers', $data, 'subscription');

        if (isset($response['response']['status']) && $response['response']['status'] === 'success') {
            return [
                'status' => 'success',
                'message' => $this->l('Your subscription has been successfully updated.', 'formcontroller', $iso),
            ];
        }

        return [
            'status' => 'warning',
            'message' => $this->l('There was an error processing your subscription.', 'formcontroller', $iso),
            'data' => $response['response'] ?? [],
        ];

    }

    public function newsletterdischargersparties()
    {
        $context = Context::getContext();
        $email = trim(Tools::getValue('email'));
        $iso = trim(Tools::getValue('iso'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $data = [
            'action' => 'unsubscribe_parties',
            'email' => $email,
        ];

        $apiManager = new ApiManager();
        $response = $apiManager->sendRequest('POST', 'api/v1/subscribers', $data, 'subscription');


        if (isset($response['response']['status']) && $response['response']['status'] === 'success') {

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully unsubscribed from your account.', 'formcontroller', $iso),
            ];

        }

        return [
            'status' => 'warning',
            'message' => $this->l('The email address does not exist in our records.', 'formcontroller', $iso),
            'data' => $response['response'] ?? [],
        ];

    }

    public function newsletterdischargerssports()
    {

        $context = Context::getContext();
        $email = trim(Tools::getValue('email'));
        $iso = trim(Tools::getValue('iso'));
        $sports = trim(Tools::getValue('sports'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $data = [
            'action' => 'unsubscribe_sports',
            'email' => $email,
            'sports' => $sports,
        ];

        $apiManager = new ApiManager();
        $response = $apiManager->sendRequest('POST', 'api/v1/subscribers', $data, 'subscription');

        if (isset($response['response']['status']) && $response['response']['status'] === 'success') {

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully unsubscribed from your account.', 'formcontroller', $iso),
            ];

        }

        return [
            'status' => 'warning',
            'message' => $this->l('The email address does not exist in our records.', 'formcontroller', $iso),
            'data' => $response['response'] ?? [],
        ];


    }


    public function giftvoucher(){

        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $sports = Tools::getValue('sports');
        $commercial = !empty(Tools::getValue('commercial')) ? true : false;
        $parties = !empty(Tools::getValue('parties')) ? true : false;
        $iso = trim(Tools::getValue('iso'));
        $action = trim(Tools::getValue('form'));
        $campaigns = trim(Tools::getValue('campaigns'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $data = [
            'action' => $action,
            'campaigns' => $campaigns,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'lang' => $iso,
            'sports' => $sports,
            'commercial' => $commercial ,
            'parties' => $parties,
        ];

        $apiManager = new ApiManager();
        $response = $apiManager->sendRequest('POST', 'api/subscribers', $data, 'subscription');

        if (isset($response['response']['status']) && $response['response']['status'] === 'success') {

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created neswletters.', 'formcontroller', $iso),
                'data' => [],
            ];

        }

        return [
            'status' => 'warning',
            'message' => $this->l('The email address does not exist in our records.', 'formcontroller', $iso),
            'data' => $response['response'] ?? [],
        ];

    }


    public function customizeyourexperience(){

        $context = Context::getContext();
        $firstname = trim(Tools::getValue('firstname'));
        $lastname = trim(Tools::getValue('lastname'));
        $email = trim(Tools::getValue('email'));
        $sports = Tools::getValue('sports');
        $commercial = !empty(Tools::getValue('commercial')) ? true : false;
        $parties = !empty(Tools::getValue('parties')) ? true : false;
        $iso = trim(Tools::getValue('iso'));
        $action = trim(Tools::getValue('form'));
        $campaigns = trim(Tools::getValue('campaigns'));

        if (!Validate::isEmail($email)) {
            return [
                'status' => 'warning',
                'message' => $this->l('Invalid email address', 'formcontroller', $iso),
                'data' => [],
            ];
        }

        $data = [
            'action' => $action,
            'campaigns' => $campaigns,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'lang' => $iso,
            'sports' => $sports,
            'commercial' => $commercial ,
            'parties' => $parties,
        ];

        $apiManager = new ApiManager();
        $response = $apiManager->sendRequest('POST', 'api/subscribers', $data, 'subscription');

        if (isset($response['response']['status']) && $response['response']['status'] === 'success') {

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created neswletters.', 'formcontroller', $iso),
                'data' => [],
            ];

        }

        return [
            'status' => 'warning',
            'message' => $this->l('The email address does not exist in our records.', 'formcontroller', $iso),
            'data' => $response['response'] ?? [],
        ];

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


    public function registersubscribe($data)
    {

        $context = Context::getContext();
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $email = $data['email'];
        $sports = $data['sports'];
        $iso = $data['iso'];
        $parties = $data['parties'];
        $condition = $data['condition'];
        $id_lang = Language::getIdByIso($iso);

        $data = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'id_lang' => $id_lang,
            'lang' => $iso,
            'ids_sport' => $sports,
            'condition' => $condition,
            'parties' => $parties
        ];


        $sql = "INSERT INTO `"._DB_PREFIX_."susc_newsletter`(`nombre`, `apellidos`, `email`, `ids_alta_baja`, `tipo`, `lopd`, `fecha`, `id_lang`) VALUES ('".$firstname."','".$lastname."','".$email."','".$sports."','0','0','".date('Y-m-d H:i:s')."', ".$id_lang.") ";

        if (Db::getInstance()->execute($sql)) {

            if (!Mail::Send(
                1,
                'newslettersubscribe',
                $this->l('Subscription request', 'formcontroller', 'es'),
                [
                    '{firstname}' => $data['firstname'],
                    '{lastname}' => $data['lastname'],
                    '{email}' => $data['email'],
                    '{sports}' => implode(', ', $this->processSportsTranslate($data['ids_sport']))
                ],
                'formulariosprestashop@a-alvarez.com'
            )) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to Alvarez.', 'formcontroller', $data['lang']),
                    'data' => [],
                ];
            }

            $dataEmail = [
                '{token}' => md5($data['email'].Configuration::get('NW_SALT')),
                '{url}' =>  Tools::getShopDomainSsl(true) . __PS_BASE_URI__,
            ];

            $template = 'newslettersubscribecheck';
            $subject = $this->l('Welcome!', 'SubscribersController', $iso);

            if (!Mail::Send($data['id_lang'],$template,$subject,$dataEmail,$data['email'])) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $data['lang']),
                    'data' => [],
                ];
            }

        }
    }


    function processSportsTranslate($sports) {

        $sports_map = [
            1 => 'GOLF',
            5 => 'HUNTING',
            6 => 'FISHING',
            3 => 'HORSE',
            4 => 'DIVING',
            2 => 'BOATING',
            9 => 'SKIING',
            1395 => 'PADEL',
            10 => 'ADVENTURE',
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

        $sportsArray = explode(',', $sports);

        $sports_in_language = array_map(function($id) use ($sports_map, $sports_translation_map) {
            $sport_name = $sports_map[$id];
            return $sports_translation_map[1][$sport_name] ?? $sport_name;
        }, $sportsArray);

        return $sports_in_language;
    }

}


