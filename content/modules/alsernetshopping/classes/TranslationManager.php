<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Gestor centralizado de traducciones para el módulo AlsernetShopping
 * Elimina duplicación de código de traducciones en todos los controladores
 * 
 * @package AlsernetShopping
 * @version 1.0.0
 * @since 2025-08-16
 */
class TranslationManager
{
    private static $instance;
    private static $translationCache = [];
    private static $translationsMerged = [];
    private static $cacheTimeout = 3600; // 1 hora
    
    const MODULE_NAME = 'alsernetshopping';
    
    /**
     * Singleton pattern
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Método principal de traducción - reemplaza el método l() duplicado
     * 
     * @param string $string Texto a traducir
     * @param string $source Fuente/controlador específico
     * @param string|null $locale Idioma específico
     * @param array|null $sprintf Parámetros para sprintf
     * @param bool $js Si es para JavaScript
     * @return string Texto traducido
     */
    public static function trans(
        string $string, 
        string $source = 'general', 
        ?string $locale = null, 
        ?array $sprintf = null,
        bool $js = false
    ): string {
        try {
            $context = Context::getContext();
            $locale = $locale ?: $context->language->locale;
            $iso = Language::getIsoByLocale($locale) ?: $context->language->iso_code;
            
            // Generar clave de cache
            $cacheKey = self::generateCacheKey($string, $source, $iso, $js);
            
            // Verificar cache
            if (self::isCacheValid($cacheKey)) {
                $translation = self::$translationCache[$cacheKey]['translation'];
            } else {
                // Obtener traducción
                $translation = self::getTranslation($string, $source, $iso);
                
                // Guardar en cache si no hay sprintf
                if ($sprintf === null) {
                    self::$translationCache[$cacheKey] = [
                        'translation' => $translation,
                        'timestamp' => time()
                    ];
                }
            }
            
            // Aplicar sprintf si está presente
            if ($sprintf !== null && !empty($sprintf)) {
                $translation = self::applySprintf($translation, $sprintf);
            }
            
            // Procesar para JavaScript
            if ($js) {
                $translation = addslashes($translation);
            }
            
            return $translation;
            
        } catch (\Exception $e) {
            error_log('TranslationManager error: ' . $e->getMessage());
            return $string; // Fallback al texto original
        }
    }

    /**
     * Método de compatibilidad con el método l() anterior
     */
    public static function l(string $string, string $specific = 'general', ?string $locale = null): string
    {
        return self::trans($string, $specific, $locale);
    }

    /**
     * Traducciones para controladores específicos
     */
    public static function controller(string $string, string $controllerName, ?string $locale = null): string
    {
        return self::trans($string, strtolower($controllerName) . 'controller', $locale);
    }

    /**
     * Traducciones para carriers
     */
    public static function carrier(string $string, int $carrierId, ?string $locale = null): string
    {
        return self::trans($string, "carrier_{$carrierId}", $locale);
    }

    /**
     * Traducciones para errores estandarizados
     */
    public static function error(string $errorKey, ?string $locale = null): string
    {
        $errorMessages = [
            'auth_required' => 'You must be logged in.',
            'invalid_carrier' => 'Invalid carrier selected.',
            'missing_address' => 'Address is required.',
            'invalid_data' => 'Invalid data provided.',
            'system_error' => 'A system error occurred.',
            'not_authorized' => 'You are not authorized to perform this action.',
        ];
        
        $defaultMessage = $errorMessages[$errorKey] ?? $errorKey;
        return self::trans($defaultMessage, 'errors', $locale);
    }

    /**
     * Obtiene la traducción del sistema de PrestaShop
     */
    private static function getTranslation(string $string, string $source, string $iso): string
    {
        self::loadTranslations($iso);
        
        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);
        
        // Claves de búsqueda en orden de prioridad
        $searchKeys = [
            strtolower('<{' . self::MODULE_NAME . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key,
            strtolower('<{' . self::MODULE_NAME . '}prestashop>' . $source) . '_' . $key,
        ];
        
        // Si es un controlador, agregar claves adicionales
        if (substr($source, -10) === 'controller') {
            $file = substr($source, 0, -10);
            $searchKeys[] = strtolower('<{' . self::MODULE_NAME . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
            $searchKeys[] = strtolower('<{' . self::MODULE_NAME . '}prestashop>' . $file) . '_' . $key;
        }
        
        // Buscar traducción
        global $_MODULES, $_LANGADM;
        
        foreach ($searchKeys as $searchKey) {
            if (!empty($_MODULES[$searchKey])) {
                return stripslashes($_MODULES[$searchKey]);
            }
        }
        
        // Fallback a traducciones de admin
        if (!empty($_LANGADM)) {
            $adminTranslation = \Translate::getGenericAdminTranslation($string, $key, $_LANGADM);
            if ($adminTranslation !== $string) {
                return stripslashes($adminTranslation);
            }
        }
        
        // Fallback al traductor de contexto de PrestaShop
        try {
            $contextTranslation = Context::getContext()->getTranslator()->trans($string, [], null);
            if ($contextTranslation !== $string) {
                return $contextTranslation;
            }
        } catch (\Exception $e) {
            // Silenciar errores del traductor
        }
        
        return $string; // Último fallback
    }

    /**
     * Carga las traducciones para el idioma especificado
     */
    private static function loadTranslations(string $iso): void
    {
        if (isset(self::$translationsMerged[self::MODULE_NAME][$iso])) {
            return; // Ya cargado
        }
        
        global $_MODULES, $_MODULE;
        
        $filesByPriority = [
            // PrestaShop 1.7+ translations
            _PS_MODULE_DIR_ . self::MODULE_NAME . '/translations/' . $iso . '.php',
            // PrestaShop 1.6 translations
            _PS_MODULE_DIR_ . self::MODULE_NAME . '/' . $iso . '.php',
            // Theme translations
            _PS_THEME_DIR_ . 'modules/' . self::MODULE_NAME . '/translations/' . $iso . '.php',
            _PS_THEME_DIR_ . 'modules/' . self::MODULE_NAME . '/' . $iso . '.php',
        ];
        
        foreach ($filesByPriority as $file) {
            if (file_exists($file)) {
                include_once $file;
                $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
            }
        }
        
        self::$translationsMerged[self::MODULE_NAME][$iso] = true;
    }

    /**
     * Genera clave de cache para traducción
     */
    private static function generateCacheKey(string $string, string $source, string $iso, bool $js): string
    {
        return md5(self::MODULE_NAME . '|' . $string . '|' . $source . '|' . (int)$js . '|' . $iso);
    }

    /**
     * Verifica si el cache es válido
     */
    private static function isCacheValid(string $cacheKey): bool
    {
        if (!isset(self::$translationCache[$cacheKey])) {
            return false;
        }
        
        $cacheData = self::$translationCache[$cacheKey];
        return (time() - $cacheData['timestamp']) < self::$cacheTimeout;
    }

    /**
     * Aplica sprintf a la traducción
     */
    private static function applySprintf(string $translation, array $sprintf): string
    {
        if (empty($sprintf) || (count($sprintf) === 1 && isset($sprintf['legacy']))) {
            return $translation;
        }
        
        return \Translate::checkAndReplaceArgs($translation, $sprintf);
    }

    /**
     * Limpia el cache de traducciones
     */
    public static function clearCache(): void
    {
        self::$translationCache = [];
        self::$translationsMerged = [];
    }

    /**
     * Obtiene estadísticas del cache
     */
    public static function getCacheStats(): array
    {
        return [
            'cached_translations' => count(self::$translationCache),
            'loaded_languages' => count(self::$translationsMerged[self::MODULE_NAME] ?? []),
            'cache_timeout' => self::$cacheTimeout,
            'memory_usage' => memory_get_usage(true)
        ];
    }
}