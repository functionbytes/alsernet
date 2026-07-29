<?php

namespace Modules\Supplier\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Prompt\Prompt;

/**
 * PromptSelectionService
 *
 * Service responsible for selecting the most appropriate prompt based on:
 * - Supplier ID
 * - Category ID
 * - Content Type
 * - Scope priority (supplier_category > supplier > category > global)
 * - Priority field value
 * - Active status
 */
class PromptSelectionService
{
    /**
     * Priority order for scope matching (highest to lowest)
     */
    protected const SCOPE_PRIORITY = [
        'supplier_category' => 4,
        'supplier' => 3,
        'category' => 2,
        'global' => 1,
    ];

    // Bonus de prioridad cuando el prompt tiene subfamily_id que coincide
    protected const SUBFAMILY_BONUS = 10;

    /**
     * Find the best matching prompt for given criteria
     *
     * Selection algorithm:
     * 1. Try supplier_category match (most specific)
     * 2. Try supplier match
     * 3. Try category match
     * 4. Try global match (fallback)
     * 5. Within each scope level, select by highest priority
     *
     * @param  int|null  $supplierId  Supplier ID
     * @param  int|null  $categoryId  Category ID
     * @param  string  $contentType  Content type (description, seo_title, etc.)
     * @param  int|null  $sourceId  Optional source ID for additional filtering
     * @return Prompt|null The best matching prompt or null if none found
     */
    public function selectPrompt(
        ?int $supplierId = null,
        ?int $categoryId = null,
        string $contentType = 'description',
        ?int $sourceId = null,
        ?int $subfamilyId = null,
    ): ?Prompt {
        $cacheKey = $this->getCacheKey($supplierId, $categoryId, $contentType, $sourceId, $subfamilyId);

        return Cache::tags(['prompt_selection'])->remember($cacheKey, 300, function () use ($supplierId, $categoryId, $contentType, $sourceId, $subfamilyId) {
            return $this->findBestPrompt($supplierId, $categoryId, $contentType, $sourceId, $subfamilyId);
        });
    }

    /**
     * Find the best prompt without caching (for real-time selection)
     */
    public function selectPromptNow(
        ?int $supplierId = null,
        ?int $categoryId = null,
        string $contentType = 'description',
        ?int $sourceId = null,
        ?int $subfamilyId = null,
    ): ?Prompt {
        return $this->findBestPrompt($supplierId, $categoryId, $contentType, $sourceId, $subfamilyId);
    }

    /**
     * Get all applicable prompts ordered by priority
     *
     * @return \Illuminate\Database\Eloquent\Collection<Prompt>
     */
    public function getApplicablePrompts(
        ?int $supplierId = null,
        ?int $categoryId = null,
        string $contentType = 'description',
        ?int $sourceId = null,
        ?int $subfamilyId = null,
    ) {
        $query = Prompt::where('is_active', true)
            ->where('content_type', $contentType);

        $query->where(function ($q) use ($supplierId, $categoryId, $sourceId, $subfamilyId) {
            // Más específico: proveedor + categoría
            if ($supplierId && $categoryId) {
                $q->orWhere(function ($subQ) use ($supplierId, $categoryId) {
                    $subQ->where('scope', 'supplier_category')
                        ->where('supplier_id', $supplierId)
                        ->where('category_id', $categoryId);
                });
            }

            // Proveedor solo
            if ($supplierId) {
                $q->orWhere(function ($subQ) use ($supplierId) {
                    $subQ->where('scope', 'supplier')
                        ->where('supplier_id', $supplierId);
                });
            }

            // Subfamilia: coincidencia en subfamily_id (legacy) O en subfamily_ids JSON
            // Buscamos tanto int como string porque MySQL es type-sensitive en JSON_CONTAINS
            if ($subfamilyId) {
                $q->orWhere(function ($subQ) use ($subfamilyId) {
                    $subQ->where('scope', 'category')
                        ->where(function ($inner) use ($subfamilyId) {
                            $inner->where('subfamily_id', $subfamilyId)
                                ->orWhereJsonContains('subfamily_ids', (int) $subfamilyId)
                                ->orWhereJsonContains('subfamily_ids', (string) $subfamilyId);
                        });
                });
            }

            // Categoría sola (sin subfamilia o como fallback)
            if ($categoryId) {
                $q->orWhere(function ($subQ) use ($categoryId) {
                    $subQ->where('scope', 'category')
                        ->where('category_id', $categoryId)
                        ->whereNull('subfamily_id')
                        ->whereNull('subfamily_ids');
                });
            }

            // Source específico
            if ($sourceId) {
                $q->orWhere(function ($subQ) use ($sourceId) {
                    $subQ->where('scope', 'source')
                        ->where('source_id', $sourceId);
                });
            }

            // Global fallback
            $q->orWhere('scope', 'global');
        });

        // Ordenar: scope más específico primero, luego subfamily match, luego priority
        return $query->orderByRaw("
            CASE scope
                WHEN 'supplier_category' THEN 4
                WHEN 'supplier' THEN 3
                WHEN 'category' THEN 2
                WHEN 'source' THEN 2
                WHEN 'global' THEN 1
                ELSE 0
            END DESC
        ")
            ->orderByRaw('(subfamily_id IS NOT NULL OR subfamily_ids IS NOT NULL) DESC')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Internal method to find the best prompt
     */
    protected function findBestPrompt(
        ?int $supplierId,
        ?int $categoryId,
        string $contentType,
        ?int $sourceId,
        ?int $subfamilyId = null,
    ): ?Prompt {
        $prompts = $this->getApplicablePrompts($supplierId, $categoryId, $contentType, $sourceId, $subfamilyId);

        return $prompts->first();
    }

    /**
     * Replace variables in prompt template with actual data
     *
     * @param  Prompt  $prompt  The prompt to use
     * @param  array  $productData  Product data with keys like product_name, category, price, etc.
     * @return string The processed prompt with variables replaced
     */
    public function renderPrompt(Prompt $prompt, array $productData): string
    {
        $template = $prompt->prompt_template;

        // Available variables for replacement
        $variables = [
            'product_name' => $productData['product_name'] ?? '',
            'category' => $productData['category'] ?? '',
            'supplier_name' => $productData['supplier_name'] ?? '',
            'sku' => $productData['sku'] ?? '',
            'reference' => $productData['reference'] ?? '',
            'price' => $productData['price'] ?? '',
            'description' => $productData['description'] ?? '',
            'features' => $productData['features'] ?? '',
            'brand' => $productData['brand'] ?? '',
            'ean' => $productData['ean'] ?? '',
        ];

        // Replace @{{ variable }} with actual values
        foreach ($variables as $key => $value) {
            $template = str_replace("@{{ {$key} }}", $value, $template);
            // Also support {{ variable }} format
            $template = str_replace("{{ {$key} }}", $value, $template);
        }

        return $template;
    }

    /**
     * Get selection explanation (useful for debugging)
     *
     * @return array Selection details with reasoning
     */
    public function explainSelection(
        ?int $supplierId = null,
        ?int $categoryId = null,
        string $contentType = 'description',
        ?int $sourceId = null
    ): array {
        $selectedPrompt = $this->selectPromptNow($supplierId, $categoryId, $contentType, $sourceId);
        $allApplicable = $this->getApplicablePrompts($supplierId, $categoryId, $contentType, $sourceId);

        return [
            'selected_prompt' => $selectedPrompt ? [
                'id' => $selectedPrompt->id,
                'uid' => $selectedPrompt->uid,
                'label' => $selectedPrompt->label,
                'scope' => $selectedPrompt->scope,
                'priority' => $selectedPrompt->priority,
                'content_type' => $selectedPrompt->content_type,
            ] : null,
            'applicable_prompts_count' => $allApplicable->count(),
            'all_applicable' => $allApplicable->map(function ($prompt) {
                return [
                    'id' => $prompt->id,
                    'label' => $prompt->label,
                    'scope' => $prompt->scope,
                    'priority' => $prompt->priority,
                ];
            })->toArray(),
            'selection_criteria' => [
                'supplier_id' => $supplierId,
                'category_id' => $categoryId,
                'content_type' => $contentType,
                'source_id' => $sourceId,
            ],
            'selection_reason' => $selectedPrompt
                ? "Selected based on scope '{$selectedPrompt->scope}' with priority {$selectedPrompt->priority}"
                : 'No matching prompt found',
        ];
    }

    /**
     * Clear selection cache for specific criteria or all
     */
    public function clearCache(?int $supplierId = null, ?int $categoryId = null, ?string $contentType = null): void
    {
        if ($supplierId === null && $categoryId === null && $contentType === null) {
            // Clear all prompt selection cache
            Cache::tags(['prompt_selection'])->flush();
        } else {
            // Clear specific cache key
            $cacheKey = $this->getCacheKey($supplierId, $categoryId, $contentType ?? 'description');
            Cache::forget($cacheKey);
        }
    }

    /**
     * Generate cache key for selection
     */
    protected function getCacheKey(?int $supplierId, ?int $categoryId, string $contentType, ?int $sourceId = null, ?int $subfamilyId = null): string
    {
        return sprintf(
            'prompt_selection:%s:%s:%s:%s:%s',
            $supplierId ?? 'null',
            $categoryId ?? 'null',
            $subfamilyId ?? 'null',
            $contentType,
            $sourceId ?? 'null',
        );
    }

    /**
     * Prepare data for n8n integration
     *
     * Return a structured response ready for n8n consumption
     *
     * @return array|null Array with prompt and metadata, or null if no prompt found
     */
    public function prepareForN8n(
        ?int $supplierId = null,
        ?int $categoryId = null,
        string $contentType = 'description',
        array $productData = []
    ): ?array {
        $prompt = $this->selectPrompt($supplierId, $categoryId, $contentType);

        if (! $prompt) {
            Log::warning('No prompt found for n8n request', [
                'supplier_id' => $supplierId,
                'category_id' => $categoryId,
                'content_type' => $contentType,
            ]);

            return null;
        }

        // Render prompt if product data provided
        $renderedPrompt = ! empty($productData)
            ? $this->renderPrompt($prompt, $productData)
            : $prompt->prompt_template;

        return [
            'prompt_id' => $prompt->id,
            'prompt_uid' => $prompt->uid,
            'prompt_label' => $prompt->label,
            'prompt_text' => $renderedPrompt,
            'output_language' => $prompt->output_language,
            'tone' => $prompt->tone,
            'seo_focus' => $prompt->seo_focus,
            'content_type' => $prompt->content_type,
            'scope' => $prompt->scope,
            'priority' => $prompt->priority,
            'version' => $prompt->version,
            'metadata' => [
                'supplier_id' => $supplierId,
                'category_id' => $categoryId,
                'selection_reason' => "Matched scope: {$prompt->scope}",
            ],
        ];
    }
}
