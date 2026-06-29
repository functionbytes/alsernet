<?php

namespace Modules\Mailrelay\Services;

use Illuminate\Support\Collection;
use Modules\Mailrelay\Entities\MailrelayVariable;

class VariableReplacementService
{
    protected VariableValueService $valueService;

    public function __construct(VariableValueService $valueService)
    {
        $this->valueService = $valueService;
    }

    /**
     * Replace all variables in the content with their actual values.
     */
    public function replaceVariables(string $content, array $context): string
    {
        // Extract all variables from the content
        $variables = $this->extractVariablesFromContent($content);

        if ($variables->isEmpty()) {
            return $content;
        }

        // Get replacement values for each variable
        $replacements = [];
        foreach ($variables as $variableKey) {
            $variable = MailrelayVariable::where('variable_key', $variableKey)
                ->where('status', 'active')
                ->first();

            if ($variable) {
                $value = $this->valueService->getValue($variable, $context);
                $replacements['{'.$variableKey.'}'] = $value;
            }
        }

        // Perform the replacements
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Extract variable keys from content (e.g., {CUSTOMER_NAME}).
     */
    public function extractVariablesFromContent(string $content): Collection
    {
        preg_match_all('/\{([A-Z_]+)\}/', $content, $matches);

        return collect($matches[1] ?? [])->unique();
    }

    /**
     * Get all available variables, optionally filtered by category.
     */
    public function getAvailableVariables(?string $category = null): Collection
    {
        $query = MailrelayVariable::where('status', 'active');

        if ($category) {
            $query->where('category', $category);
        }

        return $query->orderBy('category')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Validate that all variables in the content exist and are active.
     *
     * @return array [bool $valid, array $missingVariables]
     */
    public function validateVariables(string $content): array
    {
        $extractedVars = $this->extractVariablesFromContent($content);

        if ($extractedVars->isEmpty()) {
            return [true, []];
        }

        $existingVars = MailrelayVariable::whereIn('variable_key', $extractedVars->toArray())
            ->where('status', 'active')
            ->pluck('variable_key');

        $missingVars = $extractedVars->diff($existingVars)->toArray();

        return [
            empty($missingVars),
            $missingVars,
        ];
    }

    /**
     * Get a formatted list of variables for display (e.g., in a dropdown).
     */
    public function getVariablesForDisplay(?string $category = null): Collection
    {
        return $this->getAvailableVariables($category)->map(function ($variable) {
            return [
                'key' => $variable->variable_key,
                'placeholder' => $variable->getPlaceholder(),
                'name' => $variable->name,
                'description' => $variable->description,
                'example' => $variable->example_value,
                'category' => $variable->category,
                'module' => $variable->module,
            ];
        });
    }

    /**
     * Get variables grouped by category.
     */
    public function getVariablesGroupedByCategory(): Collection
    {
        return $this->getAvailableVariables()
            ->groupBy('category')
            ->map(function ($variables, $category) {
                return [
                    'category' => $category,
                    'variables' => $variables->map(function ($variable) {
                        return [
                            'key' => $variable->variable_key,
                            'placeholder' => $variable->getPlaceholder(),
                            'name' => $variable->name,
                            'description' => $variable->description,
                            'example' => $variable->example_value,
                        ];
                    }),
                ];
            });
    }

    /**
     * Preview content with example values instead of real data.
     */
    public function previewWithExamples(string $content): string
    {
        $variables = $this->extractVariablesFromContent($content);

        if ($variables->isEmpty()) {
            return $content;
        }

        $replacements = [];
        foreach ($variables as $variableKey) {
            $variable = MailrelayVariable::where('variable_key', $variableKey)
                ->where('status', 'active')
                ->first();

            if ($variable) {
                $replacements['{'.$variableKey.'}'] = $variable->example_value ?? '['.$variableKey.']';
            } else {
                $replacements['{'.$variableKey.'}'] = '[UNDEFINED: '.$variableKey.']';
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
