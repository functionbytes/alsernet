<?php

namespace Modules\Mailer\Services;

use Modules\Mailer\Models\MailerVariable;

class MailerVariableService
{
    /**
     * Get all available variables for a specific module
     */
    public static function getVariablesByModule(string $module): array
    {
        $variables = MailerVariable::where('module', $module)
            ->where('is_enabled', true)
            ->orderBy('category')
            ->orderBy('key')
            ->get();

        $result = [];
        foreach ($variables as $variable) {
            $result[$variable->key] = [
                'name' => $variable->name,
                'description' => $variable->description,
                'category' => $variable->category,
            ];
        }

        return $result;
    }

    /**
     * Get all available variables across all modules
     */
    public static function getAllVariables(): array
    {
        $variables = MailerVariable::where('is_enabled', true)
            ->orderBy('module')
            ->orderBy('category')
            ->orderBy('key')
            ->get();

        $result = [];
        foreach ($variables as $variable) {
            $result[$variable->key] = [
                'name' => $variable->name,
                'description' => $variable->description,
                'category' => $variable->category,
                'module' => $variable->module,
            ];
        }

        return $result;
    }

    /**
     * Get variables by category
     */
    public static function getVariablesByCategory(string $module, string $category): array
    {
        $variables = MailerVariable::where('module', $module)
            ->where('category', $category)
            ->where('is_enabled', true)
            ->orderBy('key')
            ->get();

        $result = [];
        foreach ($variables as $variable) {
            $result[$variable->key] = [
                'name' => $variable->name,
                'description' => $variable->description,
            ];
        }

        return $result;
    }

    /**
     * Get variable by key
     */
    public static function getVariable(string $key): ?MailVariable
    {
        return MailerVariable::where('key', $key)
            ->where('is_enabled', true)
            ->first();
    }

    /**
     * Get translated variable name and description
     */
    public static function getTranslatedVariable(string $key, int $langId): ?array
    {
        $variable = self::getVariable($key);

        if (! $variable) {
            return null;
        }

        $translation = $variable->translate($langId);

        return [
            'key' => $variable->key,
            'name' => $translation?->name ?? $variable->name,
            'description' => $translation?->description ?? $variable->description,
            'category' => $variable->category,
            'module' => $variable->module,
        ];
    }

    /**
     * Validate if a variable exists in database
     */
    public static function variableExists(string $key): bool
    {
        return MailerVariable::where('key', $key)
            ->where('is_enabled', true)
            ->exists();
    }

    /**
     * Get all variable keys for validation
     */
    public static function getAllVariableKeys(): array
    {
        return MailerVariable::where('is_enabled', true)
            ->pluck('key')
            ->toArray();
    }

    /**
     * Get variables grouped by category
     */
    public static function getVariablesGroupedByCategory(string $module): array
    {
        $variables = MailerVariable::where('module', $module)
            ->where('is_enabled', true)
            ->orderBy('category')
            ->orderBy('key')
            ->get();

        $grouped = [];
        foreach ($variables as $variable) {
            if (! isset($grouped[$variable->category])) {
                $grouped[$variable->category] = [];
            }

            $grouped[$variable->category][] = [
                'key' => $variable->key,
                'name' => $variable->name,
                'description' => $variable->description,
            ];
        }

        return $grouped;
    }
}
