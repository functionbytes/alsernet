<?php


use PrestaShop\PrestaShop\Core\Localization\CLDR\LocaleRepository;
use PrestaShop\PrestaShop\Core\Localization\Locale;

class NewslettersController extends Module
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
        $condition = empty(Tools::getValue('condition')) ? 1 : 0;
        $services = empty(Tools::getValue('services')) ? 1 : 0;
        $iso = trim(Tools::getValue('iso'));
        $id_lang = Language::getIdByIso($iso);

        $data = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'id_lang' => $id_lang,
            'lang' => $iso,
            'ids_sport' => $sports,
        ];

        $sql = "INSERT INTO `"._DB_PREFIX_."susc_newsletter`
            (`nombre`, `apellidos`, `email`, `ids_alta_baja`, `tipo`, `lopd`, `fecha`, `id_lang`, `cliente_no_info_comercial`, `cliente_no_datos_a_terceros`)
            VALUES (
                '".pSQL($firstname)."',
                '".pSQL($lastname)."',
                '".pSQL($email)."',
                '".pSQL($sports)."',
                '0',
                '0',
                '".pSQL(date('Y-m-d H:i:s'))."',
                ".(int)$id_lang.",
                ".(int)$condition.",
                ".(int)$services."
            )";

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
            $subject = $this->l('Welcome!', 'NewslettersController', $iso);

            if (!Mail::Send($data['id_lang'],$template,$subject,$dataEmail,$data['email'])) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $data['lang']),
                    'data' => [],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created neswletters.', 'formcontroller', $iso),
                'data' => [],
            ];

        }

    }

    function mapCategories(string $cadena): string
    {
        if (strpos($cadena, '1395') !== false) {
            return $cadena;
        }

        $catdefs = explode(',', $cadena);
        $catdefaults = [];

        foreach ($catdefs as $catdef) {
            $catdef = (int)trim($catdef);

            switch ($catdef) {
                case 3: $catdefault = 1; break; // GOLF
                case 4: $catdefault = 5; break; // CAZA
                case 5: $catdefault = 6; break; // PESCA
                case 6: $catdefault = 3; break; // HIPICA
                case 7: $catdefault = 4; break; // BUCEO
                case 8: $catdefault = 2; break; // NAUTICA
                case 9: $catdefault = 9; break; // ESQUI
                case 10: $catdefault = 1395; break; // PADEL
                case 11: $catdefault = 10; break; // AVENTURA
                default: $catdefault = 5; break; // por defecto CAZA
            }

            if (!in_array($catdefault, $catdefaults)) {
                $catdefaults[] = $catdefault;
            }
        }

        return implode(',', $catdefaults);
    }

    //QUITAR TODO

    public function newsletterdischargersnone()
    {
        $context = Context::getContext();
        $email = trim(Tools::getValue('email'));

        $iso = trim(Tools::getValue('iso'));
        $id_lang = Language::getIdByIso($iso);
        $firstname = "";
        $lastname = "";
        $sports = "";
        $fecha = date('Y-m-d H:i:s');
        $fecha_erp = str_replace(' ', 'T', date('Y-m-d H:i:s'));
        $condition = 1;
        $services = 1;

        $newsletter = Db::getInstance()->executeS("SELECT id_susc_newsletter FROM aalv_susc_newsletter WHERE lopd=0 AND email='".$email."' LIMIT 1");
        $sql = 'UPDATE `'._DB_PREFIX_.'susc_newsletter`   SET `baja` = 1,  `cliente_no_info_comercial` = 1,  `cliente_no_datos_a_terceros` = 1   WHERE lopd = 0 AND email = \''.pSQL($email).'\'';
        Db::getInstance()->execute($sql);
        $this->baja_retail_rocker($email);
        AlvarezERP::savelopd($email, $fecha_erp, $condition, $services);

        return [
            'status' => 'success',
            'message' => $this->l('You have successfully created neswletters.', 'formcontroller', $iso),
            'data' => [],
        ];

    }

    //TERCEROS
    public function newsletterdischargersparties()
    {
        $context = Context::getContext();
        $email = trim(Tools::getValue('email'));
        $iso = trim(Tools::getValue('iso'));
        $id_lang = Language::getIdByIso($iso);
        $firstname = "";
        $lastname = "";
        $sports = Tools::getValue('sports');


        $newsletter = Db::getInstance()->executeS("SELECT id_susc_newsletter FROM aalv_susc_newsletter WHERE lopd=0 AND email='".$email."' LIMIT 1");
        $sql = "INSERT INTO `"._DB_PREFIX_."susc_newsletter`
            (`nombre`, `apellidos`, `email`, `ids_alta_baja`, `tipo`, `lopd`, `fecha`, `id_lang`, `cliente_no_info_comercial`, `cliente_no_datos_a_terceros`)
            VALUES (
                '".pSQL($firstname)."',
                '".pSQL($lastname)."',
                '".pSQL($email)."',
                '".pSQL($sports)."',
                '0',
                '0',
                '".pSQL(date('Y-m-d H:i:s'))."',
                ".(int)$id_lang.",
               '0',
               '0'
            )";

            Db::getInstance()->execute($sql);
            AlvarezERP::delsuscribircatalogosporeamilerp($email, $sports);

            return [
                'status' => 'success',
                'message' => $this->l('You have successfully created neswletters.', 'formcontroller', $iso),
                'data' => [],
            ];

    }

    //DEPORTES
    public function newsletterdischargerssports()
    {

        $context = Context::getContext();
        $email = trim(Tools::getValue('email'));
        $iso = trim(Tools::getValue('iso'));
        $id_lang = Language::getIdByIso($iso);
        $firstname = "";
        $lastname = "";
        $sports = Tools::getValue('sports');

        $newsletter = Db::getInstance()->executeS("SELECT id_susc_newsletter FROM aalv_susc_newsletter WHERE lopd=0 AND email='".$email."' LIMIT 1");

        $sql = 'UPDATE `'._DB_PREFIX_.'susc_newsletter`(`baja`) VALUES (1) WHERE lopd=0 AND email=\''.$email.'\'';
        Db::getInstance()->execute($sql);
        AlvarezERP::delsuscribircatalogosporeamilerp($email, $sports);

        return [
            'status' => 'success',
            'message' => $this->l('You have successfully created neswletters.', 'formcontroller', $iso),
            'data' => [],
        ];


    }

    //SUSCRIPCIONES
    public function registersubscribe($data)
    {

        $context = Context::getContext();
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $email = $data['email'];
        $sports = $data['sports'];
        $iso = $data['iso'];
        $birthday = $data['birthday'];
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
            'birthday' => $birthday,
            'condition' => $condition,
            'parties' => $parties
        ];

        $birthdayFormatted = 'NULL';

        if (!empty($birthday)) {
            $birthdayFormatted = "'" . pSQL($birthday) . "'";
        }

        $sql = "INSERT INTO `"._DB_PREFIX_."susc_newsletter`
            (`nombre`, `apellidos`, `email`, `ids_alta_baja`, `tipo`, `lopd`, `fecha`, `fecha_nac`, `id_lang`)
            VALUES (
                '".pSQL($firstname)."',
                '".pSQL($lastname)."',
                '".pSQL($email)."',
                '".pSQL($sports)."',
                0,
                0,
                '".pSQL(date('Y-m-d H:i:s'))."',
                $birthdayFormatted,
                ".(int)$id_lang."
            )";

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
            $subject = $this->l('Welcome!', 'formcontroller', $iso);

            if (!Mail::Send($data['id_lang'],$template,$subject,$dataEmail,$data['email'])) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('An error occurred while sending the notification to the customer.', 'formcontroller', $data['lang']),
                    'data' => [],
                ];
            }

        }
    }

    public function baja_retail_rocker($email)
    {
        if (!Validate::isEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        $emailEncoded = urlencode($email);
        $apiKey = '6202390b97a5281b48e23cd7';
        $partnerId = '6202390b97a5281b48e23cd6';
        $url = "https://api.retailrocket.ru/api/1.0/partner/{$partnerId}/unsubscribe/?email={$emailEncoded}&apiKey={$apiKey}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // prevent hanging

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => 'cURL error: ' . $curlError];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'message' => "HTTP error: $httpCode"];
        }

        return [
            'success' => true,
            'status_code' => $httpCode,
            'response' => $response
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






