<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../classes/TranslationManager.php';
require_once dirname(__FILE__) . '/Services/ResponseHelper.php';
require_once dirname(__FILE__) . '/Services/ControllerHelper.php';

/**
 * Controlador base para todos los controladores del módulo AlsernetShopping
 * Proporciona funcionalidad común y elimina duplicación
 *
 * @package AlsernetShopping
 * @version 1.0.0
 * @since 2025-08-16
 */
abstract class BaseController extends Module
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
        $this->module = Module::getInstanceByName("alsernetshopping");
        $this->context = Context::getContext();
        $this->customer = $this->context->customer;
        $this->cart = $this->context->cart;
        $this->language = $this->context->language;
        $this->iso = $this->context->language->iso_code;
        $this->lang = Language::getIdByIso($this->iso);
        $this->controllerName = $this->getControllerName();
        parent::__construct();
    }


    /**
     * Traducciones para errores comunes
     */
    protected function error(string $errorKey, ?string $locale = null): string
    {
        return TranslationManager::error($errorKey, $locale);
    }

    /**
     * Traducciones generales del módulo
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        // Compatibilidad con ModuleCore::trans()
        $source = $domain ?: 'general';
        return TranslationManager::trans((string)$id, $source, $locale, $parameters);
    }

    /**
     * Valida autenticación del usuario
     */
    protected function validateAuth(): ?array
    {
        return ControllerHelper::validateAuthentication($this->context);
    }

    /**
     * Obtiene el nombre del controlador actual
     */
    private function getControllerName(): string
    {
        $className = get_class($this);

        // Remover 'Controller' del final si existe
        if (substr($className, -10) === 'Controller') {
            $className = substr($className, 0, -10);
        }

        return strtolower($className);
    }

    /**
     * Respuesta de éxito estandarizada
     */
    protected function success(array $data = [], string $message = ''): array
    {
        return ResponseHelper::success($data, $message);
    }

    /**
     * Respuesta de error estandarizada
     */
    protected function errorResponse(string $message, array $data = []): array
    {
        return ResponseHelper::error($message, $data);
    }

    /**
     * Respuesta de advertencia estandarizada
     */
    protected function warning(string $message, array $data = []): array
    {
        return ResponseHelper::warning($message, $data);
    }

    /**
     * Respuesta específica para carriers
     */
    protected function carrierResponse(string $status, string $html, int $carrierId, int $addressId, string $message = ''): array
    {
        return ResponseHelper::carrierResponse($status, $html, $carrierId, $addressId, $message);
    }

    protected function isLoggedIn(): bool
    {
        return $this->context->customer && $this->context->customer->isLogged();
    }

    /**
     * Obtiene el ID del idioma actual
     */
    protected function getLanguageId(): int
    {
        return (int)$this->context->language->id;
    }

    /**
     * Obtiene el ISO del idioma actual
     */
    protected function getLanguageIso(): string
    {
        return $this->context->language->iso_code;
    }

    /**
     * Log de debug para el módulo
     */
    protected function debug(string $message, array $context = []): void
    {
        if (Configuration::get('ALSERNET_DEBUG_MODE')) {
            $logMessage = "[{$this->controllerName}] {$message}";
            if (!empty($context)) {
                $logMessage .= ' - Context: ' . json_encode($context);
            }
            error_log($logMessage);
        }
    }

    /**
     * Asigna variables comunes a Smarty
     */
    protected function assignCommonVars(array $additionalVars = []): void
    {
        $commonVars = [
            'module_name' => 'alsernetshopping',
            'controller_name' => $this->controllerName,
            'is_logged' => $this->isLoggedIn(),
            'current_language' => $this->getLanguageIso(),
            'debug_mode' => (bool)Configuration::get('ALSERNET_DEBUG_MODE'),
        ];

        $this->context->smarty->assign(array_merge($commonVars, $additionalVars));
    }

    /**
     * Método abstracto que deben implementar los controladores hijos
     */
    abstract public function init();



    public function getModuleTranslation($module,$originalString,$source,$sprintf = null,$js = false,$locale = null,$fallback = true,$escape = true)
    {
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
            $iso = $this->context->language->iso_code;
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

        $cacheKey = $name . '|' . $string . '|' . $source . '|' . (int)$js . '|' . $iso;
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
            $ret = $this->context->getTranslator()->trans($originalString, $sprintf_for_trans, null, $locale);
        }

        return $ret;
    }

    public function l($string, $specific = false, $locale = null)
    {

        return $this->getModuleTranslation(
            $this->module,
            $string,
            ($specific) ? $specific : $this->name,
            null,
            false,
            $locale
        );
    }

}