<?php

namespace App\Helpers;

class ModuleStatusHelper
{
    /**
     * Check if a module is enabled in modules_statuses.json
     */
    public static function isModuleEnabled(string $moduleName): bool
    {
        $statusFile = base_path('modules_statuses.json');

        if (! file_exists($statusFile)) {
            return false;
        }

        $statuses = json_decode(file_get_contents($statusFile), true);

        return ($statuses[$moduleName] ?? false) === true;
    }

    /**
     * Get all enabled modules
     */
    public static function getEnabledModules(): array
    {
        $statusFile = base_path('modules_statuses.json');

        if (! file_exists($statusFile)) {
            return [];
        }

        $statuses = json_decode(file_get_contents($statusFile), true);

        return array_keys(array_filter($statuses, fn ($status) => $status === true));
    }

    /**
     * Get all disabled modules
     */
    public static function getDisabledModules(): array
    {
        $statusFile = base_path('modules_statuses.json');

        if (! file_exists($statusFile)) {
            return [];
        }

        $statuses = json_decode(file_get_contents($statusFile), true);

        return array_keys(array_filter($statuses, fn ($status) => $status === false));
    }
}
