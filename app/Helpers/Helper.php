<?php

use Modules\Core\Helpers\SiteHelper;

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
 * 29-sep-2026: eliminados los envoltorios sin ningún uso que llamaban a
 * funciones con namespace inexistentes (getLogo, load_env_from_file, table,
 * quote, db_quote, month, number_with_delimiter, xml_to_array,
 * is_non_web_link, url_get_contents_ssl_safe, execute_with_limits). Al
 * cargarse este fichero primero, tapaban la implementación real del módulo,
 * que ahora es la que queda definida.
 *
 * 29-sep-2026 (H3): igual con setting(), updateSettings(), write_env(),
 * generatePublicPath(), getAppSubdirectory(), getAppHost(), join_paths() y
 * get_localization_config(): delegaban en \Modules\...\Helpers\xxx(), que no
 * existen, y tapaban las implementaciones globales reales de
 * modules/System|Storage|Core/app/Helpers (cargadas por composer "files").
 */

// ============================================================================
// System Configuration Helpers
// ============================================================================
// Location: modules/System/app/Helpers/SettingsHelper.php

if (! function_exists('paginationNumber')) {
    function paginationNumber($value = null)
    {
        return $value != null ? $value : env('DEFAULT_PAGINATION');
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
