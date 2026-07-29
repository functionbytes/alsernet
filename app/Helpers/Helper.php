<?php

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
 */

// ============================================================================
// System Configuration Helpers
// ============================================================================
// Location: modules/System/app/Helpers/SettingsHelper.php

if (! function_exists('updateSettings')) {
    function updateSettings($data)
    {
        return \Modules\System\Helpers\updateSettings($data);
    }
}

if (! function_exists('setting')) {
    function setting($key)
    {
        return \Modules\System\Helpers\setting($key);
    }
}

if (! function_exists('getLogo')) {
    function getLogo()
    {
        return \Modules\System\Helpers\getLogo();
    }
}

if (! function_exists('paginationNumber')) {
    function paginationNumber($value = null)
    {
        return $value != null ? $value : env('DEFAULT_PAGINATION');
    }
}

// ============================================================================
// System Environment Helpers
// ============================================================================
// Location: modules/System/app/Helpers/EnvironmentHelper.php

if (! function_exists('write_env')) {
    function write_env($key, $value, $overwrite = true)
    {
        return \Modules\System\Helpers\write_env($key, $value, $overwrite);
    }
}

if (! function_exists('load_env_from_file')) {
    function load_env_from_file($path)
    {
        return \Modules\System\Helpers\load_env_from_file($path);
    }
}

// ============================================================================
// Database Query Helpers
// ============================================================================
// Location: modules/Database/app/Helpers/QueryHelper.php

if (! function_exists('table')) {
    function table($name)
    {
        return \Modules\Database\Helpers\table($name);
    }
}

if (! function_exists('quote')) {
    function quote($value)
    {
        return \Modules\Database\Helpers\quote($value);
    }
}

if (! function_exists('db_quote')) {
    function db_quote($value)
    {
        return \Modules\Database\Helpers\db_quote($value);
    }
}

// ============================================================================
// Storage Path Helpers
// ============================================================================
// Location: modules/Storage/app/Helpers/PathHelper.php

if (! function_exists('generatePublicPath')) {
    function generatePublicPath($absPath, $withHost = false)
    {
        return \Modules\Storage\Helpers\generatePublicPath($absPath, $withHost);
    }
}

if (! function_exists('getAppSubdirectory')) {
    function getAppSubdirectory()
    {
        return \Modules\Storage\Helpers\getAppSubdirectory();
    }
}

if (! function_exists('getAppHost')) {
    function getAppHost()
    {
        return \Modules\Storage\Helpers\getAppHost();
    }
}

if (! function_exists('join_paths')) {
    function join_paths()
    {
        return call_user_func_array('\Modules\Storage\Helpers\join_paths', func_get_args());
    }
}

// ============================================================================
// Core Date Helpers
// ============================================================================
// Location: modules/Core/app/Helpers/DateHelper.php

if (! function_exists('month')) {
    function month($dates): string
    {
        return \Modules\Core\Helpers\month($dates);
    }
}

// ============================================================================
// Core Localization Helpers
// ============================================================================
// Location: modules/Core/app/Helpers/LocalizationHelper.php

if (! function_exists('number_with_delimiter')) {
    function number_with_delimiter($number, $precision = null, $seperator = null, $locale = null)
    {
        return \Modules\Core\Helpers\number_with_delimiter($number, $precision, $seperator, $locale);
    }
}

if (! function_exists('get_localization_config')) {
    function get_localization_config($name, $locale)
    {
        return \Modules\Core\Helpers\get_localization_config($name, $locale);
    }
}

// ============================================================================
// Core Data Format Helpers
// ============================================================================
// Location: modules/Core/app/Helpers/DataFormatHelper.php

if (! function_exists('xml_to_array')) {
    function xml_to_array($xml)
    {
        return \Modules\Core\Helpers\xml_to_array($xml);
    }
}

// ============================================================================
// Core URL Validation Helpers
// ============================================================================
// Location: modules/Core/app/Helpers/UrlValidationHelper.php

if (! function_exists('is_non_web_link')) {
    function is_non_web_link($url)
    {
        return \Modules\Core\Helpers\is_non_web_link($url);
    }
}

// ============================================================================
// Core HTTP Service
// ============================================================================
// Location: modules/Core/app/Services/HttpClientService.php

if (! function_exists('url_get_contents_ssl_safe')) {
    function url_get_contents_ssl_safe($url)
    {
        return \Modules\Core\Services\HttpClientService::getContentSslSafe($url);
    }
}

// ============================================================================
// Core Rate Limiting Helpers
// ============================================================================
// Location: modules/Core/app/Helpers/RateLimitHelper.php

if (! function_exists('execute_with_limits')) {
    function execute_with_limits(array $rateTrackers, array $creditTrackers, ?\Closure $task = null)
    {
        return \Modules\Core\Helpers\execute_with_limits($rateTrackers, $creditTrackers, $task);
    }
}

// ============================================================================
// Core Site Helpers
// ============================================================================
// Location: modules/Core/app/Helpers/SiteHelper.php

if (! function_exists('getSiteName')) {
    function getSiteName(): string
    {
        return \Modules\Core\Helpers\SiteHelper::getSiteName();
    }
}

if (! function_exists('getSiteKeyword')) {
    function getSiteKeyword(): string
    {
        return \Modules\Core\Helpers\SiteHelper::getSiteKeyword();
    }
}

if (! function_exists('getSiteTitle')) {
    function getSiteTitle(): string
    {
        return \Modules\Core\Helpers\SiteHelper::getSiteTitle();
    }
}

// formatBytes() is already defined in modules/Core/app/Helpers/DataFormatHelper.php
// and loaded via composer autoload - no wrapper needed
