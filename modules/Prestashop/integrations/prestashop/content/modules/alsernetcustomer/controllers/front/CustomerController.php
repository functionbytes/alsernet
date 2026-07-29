<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Crypto\Hashing;

class CustomerController extends Module
{
    public $module;

    public function __construct(){
        $this->bootstrap = true;
        $this->module =  Module::getInstanceByName("alsernetcustomer");
        parent::__construct();
    }

    public function updateinformation()
    {
        $iso = Tools::getValue('iso');

        try {
            $context = Context::getContext();
            $customer = $context->customer;

            if (!$customer || !$customer->isLogged()) {
                return [
                    'status' => 'error',
                    'message' => $this->l('Unauthorized access.', 'authcontroller', $iso),
                    'data' => [],
                ];
            }

            $input = Tools::getAllValues();

            $errors = [];

            // Validaciones básicas
            if (empty($input['firstname'])) {
                $errors[] = $this->l('First name is required.', 'authcontroller', $iso);
            }

            if (empty($input['lastname'])) {
                $errors[] = $this->l('Last name is required.', 'authcontroller', $iso);
            }

            if (!empty($input['new_password']) || !empty($input['current_password'])) {
                if (empty($input['current_password'])) {
                    $errors[] = $this->l('Current password is required to set a new password.', 'authcontroller', $iso);
                } elseif (!Validate::isPasswd($input['current_password']) || !Validate::isPasswd($input['new_password'])) {
                    $errors[] = $this->l('Password format is invalid.', 'authcontroller', $iso);
                } elseif (!password_verify($input['current_password'], $customer->passwd)) {
                    $errors[] = $this->l('Current password is incorrect.', 'authcontroller', $iso);
                }
            }

            if (!empty($errors)) {
                return [
                    'status' => 'warning',
                    'message' => '' ,
                    'operation' => $this->l('Failed operation.',$iso),
                    'data' => [
                        'errors' => $errors,
                        'fields' => $input,
                    ],
                ];
            }

            // Si no hay errores, actualizamos los datos
            $customer->firstname = $input['firstname'];
            $customer->lastname = $input['lastname'];

            if (!empty($input['new_password'])) {
                $customer->passwd = Tools::encrypt($input['new_password']);
            }

            if (isset($input['birthday'])) {
                $customer->birthday = $input['birthday'];
            }

            $customer->update();
            $context->updateCustomer($customer);

            return [
                'status' => 'success',
                'operation' => $this->l('Successful operation.',$iso),
                'message' => $this->l('Information successfully updated.', 'authcontroller', $iso),
                'data' => [],
            ];

        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'operation' => $this->l('Failed operation.',$iso),
                'message' => $this->l('Unexpected error.', 'authcontroller', $iso),
                'data' => [
                    'exception' => $e->getMessage(),
                ],
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



