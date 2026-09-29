<?php

use Illuminate\Support\Facades\Log;
use Modules\Core\Helpers\SiteHelper;
use Modules\Core\Services\HttpClientService;

/**
 * Application Global Helpers (Deprecated - Use Module-Specific Helpers)
 *
 * This file acts as a compatibility layer that routes helper functions to their
 * respective module implementations. New code should import from the specific
 * module helpers instead.
 *
 * Mapping:
 * - System Helpers: modules/System/app/Helpers/
 * - Database Helpers: modules/Database/app/Helpers/
 * - Storage Helpers: modules/Storage/app/Helpers/
 * - Core Helpers: modules/Core/app/Helpers/
 * - Core Services: modules/Core/app/Services/
 *
 * 29-sep-2026 (H3): quitados los envoltorios que llamaban a funciones con
 * namespace inexistentes (\Modules\...\Helpers\xxx). Los de Storage
 * (generatePublicPath, getAppSubdirectory, getAppHost, join_paths) se cargaban
 * antes que PathHelper.php y rompían join_paths(); el resto (setting,
 * updateSettings, getLogo, load_env_from_file, month, number_with_delimiter,
 * get_localization_config, xml_to_array, is_non_web_link,
 * execute_with_limits) ya estaban tapados por la implementación real.
 */

// ============================================================================
// System Configuration Helpers
// ============================================================================
// Location: modules/System/app/Helpers/SettingsHelper.php

if (! function_exists('paginationNumber')) {
    function paginationNumber($value = null)
    {
        return $value != null ? $value : config('app.default_pagination', 20);
    }
}

// ============================================================================
// System Environment Helpers
// ============================================================================
// Location: modules/System/app/Helpers/EnvironmentHelper.php

if (! function_exists('write_env')) {
    function write_env($key, $value, $overwrite = true)
    {
        // 29-sep-2026: el .env no se modifica desde la aplicación (ver
        // modules/System/app/Helpers/EnvironmentHelper.php).
        Log::warning('write_env() ignorado: el .env no se modifica desde la aplicación', ['key' => (string) $key]);

        return false;
    }
}

// ============================================================================
// Database Query Helpers
// ============================================================================
// Location: modules/Database/app/Helpers/QueryHelper.php
// 29-sep-2026: quitados los envoltorios table/quote/db_quote (sin uso), que
// llamaban a \Modules\Database\Helpers\… inexistentes y tapaban QueryHelper.

// ============================================================================
// Core HTTP Service
// ============================================================================
// Location: modules/Core/app/Services/HttpClientService.php

if (! function_exists('url_get_contents_ssl_safe')) {
    function url_get_contents_ssl_safe($url)
    {
        return HttpClientService::getContentSslSafe($url);
    }
}

// ============================================================================
// Core Site Helpers
// ============================================================================
// Location: modules/Core/app/Helpers/SiteHelper.php

if (! function_exists('getSiteName')) {
    function getSiteName(): string
    {
        return SiteHelper::getSiteName();
    }
}

if (! function_exists('getSiteKeyword')) {
    function getSiteKeyword(): string
    {
        return SiteHelper::getSiteKeyword();
    }
}

if (! function_exists('getSiteTitle')) {
    function getSiteTitle(): string
    {
        return SiteHelper::getSiteTitle();
    }
}

// formatBytes() is already defined in modules/Core/app/Helpers/DataFormatHelper.php
// and loaded via composer autoload - no wrapper needed

// ============================================================================
// HTML Sanitization Helpers
// ============================================================================
// Canonical implementation lives in modules/Blog/app/Helpers/BlogHelper.php
// but that file is only loaded when the Blog module is active.
// This fallback ensures clean_html() is always available globally.

if (! function_exists('clean_html')) {
    /**
     * Sanitize untrusted HTML using HTMLPurifier, preserving safe formatting tags.
     */
    function clean_html(?string $dirty): string
    {
        if ($dirty === null || $dirty === '') {
            return '';
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,s,del,ins,a[href|title|target|rel],ul,ol,li,blockquote,pre,code,h1,h2,h3,h4,h5,h6,img[src|alt|title|width|height],table,thead,tbody,tr,th,td,span[class|style],div[class],hr,sub,sup');
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'font-weight', 'font-style', 'text-decoration', 'text-align']);
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('AutoFormat.RemoveEmpty', false);
        $config->set('Cache.DefinitionImpl', null);

        $purifier = new HTMLPurifier($config);

        return $purifier->purify($dirty);
    }
}
