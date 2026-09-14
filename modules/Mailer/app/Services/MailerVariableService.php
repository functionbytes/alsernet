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
            ->enabled()
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
            ->enabled()
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
    public static function getVariable(string $key): ?MailerVariable
    {
        return MailerVariable::where('key', $key)
            ->enabled()
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
            ->enabled()
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
            ->enabled()
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

    /**
     * Get the display label for a category
     */
    private static function getCategoryLabel(string $category): string
    {
        $labels = config('mailer-module.category_labels', []);

        return $labels[$category] ?? ucfirst($category);
    }

    /**
     * Get variables for a module (+ core) grouped and formatted for the editor panel.
     * Used by both variables() and variablesByModule() controller actions.
     *
     * @return array<int, array{group: string, items: array<int, array{name: string, description: string}>}>
     */
    public static function getGroupedForModule(string $module): array
    {
        $dbVariables = MailerVariable::query()
            ->enabled()
            ->where(function ($query) use ($module) {
                $query->where('module', $module)
                    ->orWhere('module', 'core');
            })
            ->orderBy('category')
            ->orderBy('key')
            ->get();

        $variables = [];

        foreach ($dbVariables->groupBy('category') as $category => $items) {
            $variables[] = [
                'group' => self::getCategoryLabel($category),
                'items' => $items->map(fn ($v) => [
                    'name' => $v->key,
                    'description' => $v->description ?? $v->name,
                ])->toArray(),
            ];
        }

        return $variables;
    }
}
