<?php

namespace Modules\Supplier\Services;

use DeepL\Translator;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Contracts\WebhookDispatcherInterface;
use Modules\Supplier\Helpers\HtmlSanitizer;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Ai\AiCost;
use Modules\Supplier\Models\Content\ContentValidation;
use Modules\Supplier\Models\Extraction\ExtractionResult;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Services\Ai\AiApiClient;
use Modules\Supplier\Services\PromptSelectionService;

class ContentGenerationService
{
    public const STATS_CACHE_KEY = 'supplier:ai_content:stats';

    protected const QUALITY_EXCELLENT = 85;
    protected const QUALITY_GOOD = 70;
    protected const QUALITY_ACCEPTABLE = 50;
    protected const READABILITY_EASY = 60;
    protected const READABILITY_MODERATE = 50;

    protected float $dailyBudgetLimit;
    protected float $monthlyBudgetLimit;

    public function __construct(
        protected AiApiClient $apiClient,
        protected PromptSelectionService $promptSelectionService,
    ) {
        $this->dailyBudgetLimit = config('supplier.ai.daily_budget_limit', 100.00);
        $this->monthlyBudgetLimit = config('supplier.ai.monthly_budget_limit', 2000.00);
    }

    public function resolvePrompt(int $supplierId, ?int $categoryId = null, ?int $sourceId = null, ?int $subfamilyId = null): Prompt
    {
        $prompt = Prompt::resolvePrompt($supplierId, $categoryId, $sourceId, $subfamilyId);

        if (! $prompt) {
            Log::error('Failed to resolve prompt', [
                'supplier_id' => $supplierId,
                'category_id' => $categoryId,
                'subfamily_id' => $subfamilyId,
                'source_id' => $sourceId,
            ]);

            throw new Exception('No active prompt found for the given parameters. Please configure a global default prompt.');
        }

        Log::info('Prompt resolved', [
            'prompt_id'    => $prompt->id,
            'prompt_scope' => $prompt->scope,
            'supplier_id'  => $supplierId,
            'category_id'  => $categoryId,
            'subfamily_id' => $subfamilyId,
            'source_id'    => $sourceId,
        ]);

        return $prompt;
    }

    public function generateContent(ExtractionResult $result, ?Prompt $prompt = null): AiContent
    {
        if (! $prompt) {
            $categoryId  = $result->extracted_data['category_id'] ?? null;
            $subfamilyId = $result->extracted_data['subfamily_id'] ?? null;

            if (! $categoryId && ! empty($result->extracted_data['categorie'])) {
                $erpCatId = is_array($result->extracted_data['categorie'])
                    ? ($result->extracted_data['categorie']['id'] ?? null)
                    : $result->extracted_data['categorie'];
                if ($erpCatId) {
                    $categoryId = \Modules\Supplier\Models\Category\Category::where('erp_id', $erpCatId)->value('id');
                }
            }

            if (! $subfamilyId && ! empty($result->extracted_data['product_attributes'])) {
                $firstAttr   = $result->extracted_data['product_attributes'][0] ?? [];
                $erpSubfamId = $firstAttr['subfamily_id'] ?? null;
                if ($erpSubfamId) {
                    $subfamilyId = \Modules\Supplier\Models\Category\Subfamily::where('erp_id', $erpSubfamId)->value('id');
                }
            }

            $prompt = $this->resolvePrompt(
                $result->supplier_id,
                $categoryId,
                $result->source_id,
                $subfamilyId
            );
        }

        $this->checkBudgetLimits();

        $content = AiContent::create([
            'supplier_id'       => $result->supplier_id,
            'erp_reference'     => $result->reference,
            'ean'               => $result->ean,
            'status'            => AiContent::STATUS_GENERATING,
            'prompt_id'         => $prompt->id,
            'source_attributes' => $result->extracted_data,
        ]);

        try {
            $renderedPrompt = $this->renderPrompt($prompt, $this->prepareVariables($result));

            $aiResponse = $this->callAiApi($renderedPrompt, [
                'model'             => $prompt->ai_model ?: (\Modules\Core\Models\Setting::get('supplier.ai_default_model') ?: config('supplier.ai.default_model', 'gemini-2.5-flash')),
                'max_tokens'        => 4000,
                'enable_web_search' => (bool) $prompt->enable_web_search,
            ]);

            $parsedContent = $this->parseAiResponse($aiResponse['content']);

            $content->update([
                'generated_name'      => $parsedContent['name'] ?? null,
                'short_description'   => $parsedContent['short_description'] ?? null,
                'long_description'    => $parsedContent['long_description'] ?? null,
                'bullet_points'       => $parsedContent['bullet_points'] ?? null,
                'seo_title'           => $parsedContent['seo_title'] ?? null,
                'seo_description'     => $parsedContent['seo_description'] ?? null,
                'seo_keywords'        => $parsedContent['seo_keywords'] ?? null,
                'sources_used'        => $aiResponse['sources_used'] ?? [],
                'generation_metadata' => [
                    'model'            => $aiResponse['model'],
                    'tokens'           => $aiResponse['tokens'],
                    'cost'             => $aiResponse['cost'],
                    'latency_ms'       => $aiResponse['latency_ms'],
                    'generated_at'     => now()->toIso8601String(),
                    'web_search_query' => $aiResponse['web_search_query'] ?? null,
                ],
            ]);

            $this->updateContentHashAndWarnDuplicates($content);

            $this->trackAiCost(
                model: $aiResponse['model'],
                inputTokens: $aiResponse['tokens']['input'],
                outputTokens: $aiResponse['tokens']['output'],
                cost: $aiResponse['cost'],
                contentId: $content->id,
                supplierId: $result->supplier_id,
                batchId: $result->batch_id,
                latencyMs: $aiResponse['latency_ms'] ?? null,
                webSearchCost: $aiResponse['web_search_cost'] ?? 0,
                webSearchCalls: $aiResponse['web_search_calls'] ?? 0,
            );

            $content->markAsGenerated();
            $content->log(AiContent::ACTION_GENERATION_COMPLETED);

            Log::info('Content generated successfully', [
                'content_id'  => $content->id,
                'supplier_id' => $result->supplier_id,
                'model'       => $aiResponse['model'],
            ]);

            return $content->fresh();
        } catch (Exception $e) {
            $content->markAsFailed($e->getMessage());
            $content->log(AiContent::ACTION_GENERATION_FAILED, null, null, ['error' => $e->getMessage()]);

            Log::error('Content generation failed', [
                'content_id' => $content->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function regenerateContent(AiContent $content, ?Prompt $newPrompt = null, ?int $requestedByUserId = null): AiContent
    {
        if (! $newPrompt) {
            // Resolver por categoría + subfamilia del producto vinculado (respeta ai_model del prompt)
            $newPrompt = $content->prompt
                ?? $this->promptSelectionService->selectPrompt(
                    supplierId: $content->supplier_id,
                    categoryId: $content->supplierProduct?->category_id,
                    subfamilyId: $content->supplierProduct?->subfamily_id,
                );

            if (! $newPrompt) {
                throw new Exception('No active prompt found. Please configure a prompt for this subfamily or a global default.');
            }
        }

        $this->checkBudgetLimits();

        $content->update([
            'status'           => AiContent::STATUS_GENERATING,
            'prompt_id'        => $newPrompt->id,
            'rejection_reason' => null,
        ]);

        $userId = $requestedByUserId ?? \Illuminate\Support\Facades\Auth::id();
        if ($userId) {
            $content->log('regeneration_requested', null, AiContent::STATUS_GENERATING, [
                'source'    => 'manual',
                'prompt_id' => $newPrompt->id,
            ], $userId);
        }

        try {
            $previousSourcesUsed = $content->sources_used ?? [];
            $previousWebQuery    = $content->generation_metadata['web_search_query'] ?? null;
            $previousGeneratedAt = $content->generation_metadata['regenerated_at']
                ?? $content->generation_metadata['generated_at']
                ?? null;

            $variables      = $this->prepareVariablesFromContent($content);
            $renderedPrompt = $this->renderPrompt($newPrompt, $variables);

            $aiResponse = $this->callAiApi($renderedPrompt, [
                'model'             => $newPrompt->ai_model ?: (\Modules\Core\Models\Setting::get('supplier.ai_default_model') ?: config('supplier.ai.default_model', 'gemini-2.5-flash')),
                'max_tokens'        => (int) \Modules\Core\Models\Setting::get('supplier.ai_max_tokens') ?: 4000,
                'enable_web_search' => (bool) $newPrompt->enable_web_search,
            ]);

            $parsedContent = $this->parseAiResponse($aiResponse['content']);

            $sourcesHistory = $content->sources_history ?? [];
            if (! empty($previousSourcesUsed) || $previousWebQuery) {
                $sourcesHistory[] = [
                    'sources_used'     => $previousSourcesUsed,
                    'web_search_query' => $previousWebQuery,
                    'generated_at'     => $previousGeneratedAt,
                    'archived_at'      => now()->toIso8601String(),
                ];
                if (count($sourcesHistory) > 10) {
                    $sourcesHistory = array_slice($sourcesHistory, -10);
                }
            }

            $content->update([
                'sources_history'     => $sourcesHistory,
                'generated_name'      => $parsedContent['name'] ?? null,
                'short_description'   => $parsedContent['short_description'] ?? null,
                'long_description'    => $parsedContent['long_description'] ?? null,
                'bullet_points'       => $parsedContent['bullet_points'] ?? null,
                'seo_title'           => $parsedContent['seo_title'] ?? null,
                'seo_description'     => $parsedContent['seo_description'] ?? null,
                'seo_keywords'        => $parsedContent['seo_keywords'] ?? null,
                'sources_used'        => $aiResponse['sources_used'] ?? [],
                'generation_metadata' => [
                    'model'            => $aiResponse['model'],
                    'tokens'           => $aiResponse['tokens'],
                    'cost'             => $aiResponse['cost'],
                    'latency_ms'       => $aiResponse['latency_ms'],
                    'regenerated_at'   => now()->toIso8601String(),
                    'web_search_query' => $aiResponse['web_search_query'] ?? null,
                ],
            ]);

            $this->updateContentHashAndWarnDuplicates($content);

            $this->trackAiCost(
                model: $aiResponse['model'],
                inputTokens: $aiResponse['tokens']['input'],
                outputTokens: $aiResponse['tokens']['output'],
                cost: $aiResponse['cost'],
                contentId: $content->id,
                supplierId: $content->supplier_id,
                operationType: 'regeneration',
                latencyMs: $aiResponse['latency_ms'] ?? null,
                webSearchCost: $aiResponse['web_search_cost'] ?? 0,
                webSearchCalls: $aiResponse['web_search_calls'] ?? 0,
            );

            $content->markAsGenerated();
            $content->log(AiContent::ACTION_GENERATION_COMPLETED, null, null, ['regenerated' => true]);

            $this->forgetStatsCache();

            Log::info('Content regenerated successfully', [
                'content_id' => $content->id,
                'model'      => $aiResponse['model'],
            ]);

            return $content->fresh();
        } catch (Exception $e) {
            $content->markAsFailed($e->getMessage());
            $content->log(AiContent::ACTION_GENERATION_FAILED, null, null, [
                'error'                => $e->getMessage(),
                'regeneration_attempt' => true,
            ]);

            $this->forgetStatsCache();

            Log::error('Content regeneration failed', [
                'content_id' => $content->id,
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Genera contenido AI directamente desde datos ERP (filter endpoint).
     * Usa el ai_model del prompt resuelto por categoría; si no tiene, cae al global.
     */
    public function generateFromErpModel(AiContent $content, Prompt $prompt, array $erpData): void
    {
        $this->checkBudgetLimits();

        $variables      = $this->prepareVariablesFromErpData($erpData);
        $renderedPrompt = $this->renderPrompt($prompt, $variables);

        $aiResponse = $this->callAiApi($renderedPrompt, [
            'model'             => $prompt->ai_model ?: (\Modules\Core\Models\Setting::get('supplier.ai_default_model') ?: config('supplier.ai.default_model', 'gemini-2.5-flash')),
            'max_tokens'        => (int) \Modules\Core\Models\Setting::get('supplier.ai_max_tokens') ?: 4000,
            'enable_web_search' => (bool) $prompt->enable_web_search,
        ]);

        $parsedContent = $this->parseAiResponse($aiResponse['content']);

        $content->update([
            'generated_name'      => $parsedContent['name'] ?? null,
            'short_description'   => $parsedContent['short_description'] ?? null,
            'long_description'    => $parsedContent['long_description'] ?? null,
            'bullet_points'       => $parsedContent['bullet_points'] ?? null,
            'seo_title'           => $parsedContent['seo_title'] ?? null,
            'seo_description'     => $parsedContent['seo_description'] ?? null,
            'seo_keywords'        => $parsedContent['seo_keywords'] ?? null,
            'source_attributes'   => $erpData,
            'sources_used'        => $aiResponse['sources_used'] ?? [],
            'generation_metadata' => [
                'model'            => $aiResponse['model'],
                'tokens'           => $aiResponse['tokens'],
                'cost'             => $aiResponse['cost'],
                'latency_ms'       => $aiResponse['latency_ms'],
                'generated_at'     => now()->toIso8601String(),
                'source'           => 'erp_sync',
                'web_search_query' => $aiResponse['web_search_query'] ?? null,
            ],
        ]);

        $this->updateContentHashAndWarnDuplicates($content);

        $this->trackAiCost(
            model: $aiResponse['model'],
            inputTokens: $aiResponse['tokens']['input'],
            outputTokens: $aiResponse['tokens']['output'],
            cost: $aiResponse['cost'],
            contentId: $content->id,
            supplierId: $content->supplier_id,
            latencyMs: $aiResponse['latency_ms'] ?? null,
            webSearchCost: $aiResponse['web_search_cost'] ?? 0,
            webSearchCalls: $aiResponse['web_search_calls'] ?? 0
        );

        $content->markAsGenerated();
        $content->log(AiContent::ACTION_GENERATION_COMPLETED);
    }

    protected function prepareVariablesFromErpData(array $erpData): array
    {
        $attrs = collect($erpData['product_attributes'] ?? []);

        $features = $attrs->pluck('name')->filter()->values()->implode(', ');

        $ean = $erpData['ean13'] ?? $erpData['ean'] ?? '';
        if (empty($ean)) {
            $eanAttr = $attrs->first(fn ($a) => str_contains(strtolower($a['name'] ?? ''), 'ean'));
            $ean = $eanAttr['value'] ?? '';
        }

        $brand = $erpData['brand'] ?? $erpData['marca'] ?? '';
        if (empty($brand)) {
            $brandAttr = $attrs->first(fn ($a) => in_array(strtolower($a['name'] ?? ''), ['marca', 'brand']));
            $brand = $brandAttr['value'] ?? '';
        }

        $category = $erpData['categorie']['description'] ?? $erpData['category']['description'] ?? $erpData['category'] ?? '';
        if (is_array($category)) {
            $category = $category['description'] ?? '';
        }

        $supplier = $erpData['supplier']['name'] ?? $erpData['supplier']['label'] ?? '';

        return [
            'product_name'     => $erpData['name'] ?? 'Unknown Product',
            'reference'        => $erpData['code'] ?? '',
            'ean'              => (string) $ean,
            'short_description'=> '',
            'long_description' => '',
            'specifications'   => '',
            'features'         => $features,
            'brand'            => (string) $brand,
            'category'         => (string) $category,
            'supplier'         => (string) $supplier,
        ];
    }

    public function generateBatch(array $resultIds, ?int $promptId = null): array
    {
        $results = [
            'total'      => count($resultIds),
            'successful' => 0,
            'failed'     => 0,
            'details'    => [],
        ];

        $prompt = $promptId ? Prompt::find($promptId) : null;

        foreach ($resultIds as $resultId) {
            try {
                $extractionResult = ExtractionResult::findOrFail($resultId);
                $content = $this->generateContent($extractionResult, $prompt);

                $results['successful']++;
                $results['details'][] = [
                    'result_id'  => $resultId,
                    'content_id' => $content->id,
                    'status'     => 'success',
                ];
            } catch (Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'result_id' => $resultId,
                    'status'    => 'failed',
                    'error'     => $e->getMessage(),
                ];

                Log::error('Batch generation failed for result', [
                    'result_id' => $resultId,
                    'error'     => $e->getMessage(),
                ]);
            }

            if ($resultId !== end($resultIds)) {
                usleep(500000);
            }
        }

        Log::info('Batch generation completed', $results);

        return $results;
    }

    public function callAiApi(string $prompt, array $options = []): array
    {
        return $this->apiClient->generate($prompt, $options);
    }

    public function trackAiCost(
        string $model,
        int $inputTokens,
        int $outputTokens,
        float $cost,
        ?int $contentId = null,
        ?int $supplierId = null,
        ?string $batchId = null,
        string $operationType = 'generation',
        ?int $latencyMs = null,
        float $webSearchCost = 0,
        int $webSearchCalls = 0,
    ): AiCost {
        $config = AiApiClient::MODEL_CONFIG[$model] ?? [];

        $inputCost  = ($inputTokens / 1_000_000) * ($config['input_cost_per_1m'] ?? 0);
        $outputCost = ($outputTokens / 1_000_000) * ($config['output_cost_per_1m'] ?? 0);

        return AiCost::create([
            'supplier_id'      => $supplierId,
            'content_id'       => $contentId,
            'batch_id'         => $batchId,
            'model'            => $model,
            'operation_type'   => $operationType,
            'input_tokens'     => $inputTokens,
            'output_tokens'    => $outputTokens,
            'input_cost'       => $inputCost,
            'output_cost'      => $outputCost,
            'web_search_cost'  => $webSearchCost,
            'web_search_calls' => $webSearchCalls,
            'latency_ms'       => $latencyMs,
        ]);
    }

    public function validateContent(AiContent $content): ContentValidation
    {
        $qualityScore     = $this->calculateQualityScore($content->long_description ?? '');
        $readabilityScore = $this->checkReadability($content->long_description ?? '');

        $combinedText = implode(' ', array_filter([
            $content->generated_name,
            $content->short_description,
            $content->long_description,
        ]));

        $issues      = [];
        $suggestions = [];

        if (empty($content->generated_name)) {
            $issues[] = 'Missing product name';
        }

        if (empty($content->long_description)) {
            $issues[] = 'Missing long description';
        }

        if ($content->prompt && $content->prompt->required_sections) {
            foreach ($content->prompt->required_sections as $section) {
                if (! str_contains(strtolower($combinedText), strtolower($section))) {
                    $issues[] = "Missing required section: {$section}";
                }
            }
        }

        if (str_word_count($content->long_description ?? '') < 100) {
            $suggestions[] = 'Description should be at least 100 words';
        }

        if ($qualityScore < self::QUALITY_ACCEPTABLE) {
            $suggestions[] = 'Quality score is below acceptable threshold';
        }

        if ($readabilityScore < self::READABILITY_MODERATE) {
            $suggestions[] = 'Content readability could be improved';
        }

        $validationStatus = empty($issues) && $qualityScore >= self::QUALITY_GOOD
            ? 'passed'
            : (empty($issues) ? 'needs_review' : 'failed');

        return ContentValidation::create([
            'content_id'            => $content->id,
            'quality_score'         => $qualityScore,
            'readability_score'     => $readabilityScore,
            'has_required_sections' => count($issues) === 0,
            'validation_status'     => $validationStatus,
            'issues'                => $issues,
            'suggestions'           => $suggestions,
            'validated_at'          => now(),
        ]);
    }

    public function calculateQualityScore(string $content): float
    {
        if (empty($content)) { return 0.0; }

        $score     = 50.0;
        $wordCount = str_word_count($content);
        if ($wordCount >= 100) { $score += 10; }
        if ($wordCount >= 200) { $score += 10; }

        $sentenceCount = count(preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY));
        if ($sentenceCount >= 5) { $score += 10; }

        $words       = str_word_count(strtolower($content), 1);
        $uniqueRatio = count(array_unique($words)) / max(count($words), 1);
        if ($uniqueRatio >= 0.7) { $score += 10; }

        if (preg_match('/\b(specifications|features|benefits|warranty|components)\b/i', $content)) {
            $score += 10;
        }

        return min(100.0, max(0.0, $score));
    }

    public function checkReadability(string $content): float
    {
        if (empty($content)) { return 0.0; }

        $sentenceCount = max(count(preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY)), 1);
        $words         = str_word_count($content, 1);
        $wordCount     = max(count($words), 1);

        $syllableCount = 0;
        foreach ($words as $word) {
            $syllableCount += $this->countSyllables($word);
        }

        $fleschScore = 206.835
            - (1.015 * ($wordCount / $sentenceCount))
            - (84.6 * (max($syllableCount, 1) / $wordCount));

        return max(0.0, min(100.0, $fleschScore));
    }

    public function submitForReview(AiContent $content): void
    {
        $content->transitionTo(AiContent::STATUS_IN_REVIEW);
        Log::info('Content submitted for review', ['content_id' => $content->id, 'supplier_id' => $content->supplier_id]);
    }

    public function approveContent(AiContent $content, int $userId, bool $syncToErp = true): void
    {
        if ($content->status === AiContent::STATUS_VALIDATED) {
            throw new \RuntimeException('Este contenido ya ha sido aprobado.');
        }

        $content->validate($userId);

        // Solo disparar el job de sincronización con publicar=0 cuando el caller NO va a
        // sincronizar el estado de publicación manualmente. Los modales (validar/aprobar)
        // llaman a syncToErp() con el valor de "publicar" elegido; si además se dispara el
        // job, su publicar=0 asíncrono sobrescribiría la elección del revisor en el ERP.
        if ($syncToErp) {
            \Modules\Supplier\Jobs\SyncContentToErpJob::dispatch($content->uid);
        }

        $this->forgetStatsCache();

        Log::info('Content approved', ['content_id' => $content->id, 'validated_by' => $userId]);

        $this->dispatchWebhooks('content.approved', [
            'uid'      => $content->uid,
            'product'  => $content->erp_reference,
            'supplier' => $content->supplier_id,
        ]);
    }

    public function rejectContent(AiContent $content, int $userId, string $reason): void
    {
        if ($content->status === AiContent::STATUS_REJECTED) {
            throw new \RuntimeException('Este contenido ya ha sido rechazado.');
        }

        $content->reject($reason, $userId);
        $this->forgetStatsCache();

        Log::info('Content rejected', ['content_id' => $content->id, 'rejected_by' => $userId, 'reason' => $reason]);
    }

    private function dispatchWebhooks(string $event, array $payload): void
    {
        if (! interface_exists(WebhookDispatcherInterface::class) || ! app()->bound(WebhookDispatcherInterface::class)) {
            Log::debug('Webhook dispatch skipped: no dispatcher bound', ['event' => $event]);
            return;
        }

        try {
            app(WebhookDispatcherInterface::class)->dispatch($event, $payload);
        } catch (\Throwable $e) {
            Log::warning('Webhook dispatch failed', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    private function forgetStatsCache(): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }

    public function renderPrompt(Prompt $prompt, array $variables): string
    {
        return $prompt->render($variables);
    }

    protected function checkBudgetLimits(): void
    {
        if (AiCost::isDailyBudgetExceeded($this->dailyBudgetLimit)) {
            throw new Exception("Daily AI budget limit of \${$this->dailyBudgetLimit} has been exceeded.");
        }

        if (AiCost::isMonthlyBudgetExceeded($this->monthlyBudgetLimit)) {
            throw new Exception("Monthly AI budget limit of \${$this->monthlyBudgetLimit} has been exceeded.");
        }
    }

    protected function prepareVariables(ExtractionResult $result): array
    {
        $data = $result->extracted_data;

        return [
            'product_name'     => $data['product_name'] ?? 'Unknown Product',
            'reference'        => $result->reference ?? '',
            'ean'              => $result->ean ?? '',
            'short_description'=> $data['short_description'] ?? '',
            'long_description' => $data['long_description'] ?? '',
            'specifications'   => $data['specifications'] ?? [],
            'features'         => $data['features'] ?? [],
            'price'            => $data['price'] ?? '',
            'brand'            => $data['brand'] ?? '',
            'category'         => $data['category'] ?? '',
            'supplier'         => $result->supplier->label ?? '',
        ];
    }

    protected function prepareVariablesFromContent(AiContent $content): array
    {
        $data = $content->source_attributes ?? [];

        $features = $data['features'] ?? null;
        if (is_array($features)) {
            $features = implode(', ', $features);
        }
        if (empty($features)) {
            $features = collect($data['product_attributes'] ?? [])
                ->pluck('name')->filter()->implode(', ');
        }

        $specifications = $data['specifications'] ?? null;
        if (is_array($specifications)) {
            $specifications = implode(', ', $specifications);
        }

        $category = $data['category'] ?? $data['categorie'] ?? '';
        if (is_array($category)) {
            $category = $category['description'] ?? '';
        }

        $supplier = $data['supplier'] ?? '';
        if (is_array($supplier)) {
            $supplier = $supplier['name'] ?? $supplier['label'] ?? '';
        }
        if (empty($supplier)) {
            $supplier = $content->supplier?->label ?? '';
        }

        $str = function (mixed $val, string $fallback = ''): string {
            if (is_array($val)) return implode(', ', array_filter(array_map('strval', $val)));
            if (is_null($val))  return $fallback;
            return (string) $val;
        };

        return [
            'product_name'     => $str($data['name'] ?? $data['product_name'] ?? $content->generated_name, 'Unknown Product'),
            'reference'        => $str($content->erp_reference ?? $data['code'] ?? ''),
            'ean'              => $str($content->ean ?? $data['ean13'] ?? $data['ean'] ?? ''),
            'short_description'=> $str($data['short_description'] ?? $content->short_description ?? ''),
            'long_description' => $str($data['long_description'] ?? $content->long_description ?? ''),
            'specifications'   => $str($specifications ?? ''),
            'features'         => $str($features ?? ''),
            'price'            => $str($data['price'] ?? ''),
            'brand'            => $str($data['brand'] ?? $data['marca'] ?? ''),
            'category'         => $str($category),
            'supplier'         => $str($supplier),
        ];
    }

    protected function parseAiResponse(string $response): array
    {
        $lines  = explode("\n", $response);
        $parsed = [
            'name'              => null,
            'short_description' => null,
            'long_description'  => null,
            'bullet_points'     => [],
            'seo_title'         => null,
            'seo_description'   => null,
            'seo_keywords'      => null,
        ];

        $currentSection = null;
        $buffer         = [];

        $flushBuffer = function () use (&$buffer, &$parsed, &$currentSection) {
            if (empty($buffer) || ! $currentSection) { $buffer = []; return; }
            if ($currentSection === 'short_description') {
                $parsed['short_description'] = implode(' ', $buffer);
            } elseif ($currentSection === 'long_description') {
                $parsed['long_description'] = implode("\n", $buffer);
            } elseif ($currentSection === 'seo_description') {
                $parsed['seo_description'] = implode(' ', $buffer);
            }
            $buffer = [];
        };

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^(name|title|product name):/i', $line)) {
                $flushBuffer();
                $currentSection = 'name';
                $parsed['name'] = trim(preg_replace('/^(name|title|product name):\s*/i', '', $line));
                continue;
            }
            if (preg_match('/^short description:/i', $line)) {
                $flushBuffer(); $currentSection = 'short_description'; continue;
            }
            if (preg_match('/^(long description|description):/i', $line)) {
                $flushBuffer(); $currentSection = 'long_description'; continue;
            }
            if (preg_match('/^(bullet points|features|key features):/i', $line)) {
                $flushBuffer(); $currentSection = 'bullet_points'; continue;
            }
            if (preg_match('/^seo title:/i', $line)) {
                $flushBuffer();
                $currentSection      = 'seo_title';
                $parsed['seo_title'] = trim(preg_replace('/^seo title:\s*/i', '', $line));
                continue;
            }
            if (preg_match('/^seo description:/i', $line)) {
                $flushBuffer(); $currentSection = 'seo_description'; continue;
            }
            if (preg_match('/^(seo keywords|keywords):/i', $line)) {
                $flushBuffer();
                $currentSection         = 'seo_keywords';
                $parsed['seo_keywords'] = trim(preg_replace('/^(seo keywords|keywords):\s*/i', '', $line));
                continue;
            }

            if ($currentSection === 'bullet_points' && preg_match('/^[-*•]\s*(.+)/', $line, $matches)) {
                $parsed['bullet_points'][] = trim($matches[1]);
            } elseif ($currentSection && ! empty($line)) {
                if (in_array($currentSection, ['short_description', 'long_description', 'seo_description'])) {
                    $buffer[] = $line;
                }
            }
        }

        $flushBuffer();

        if (empty($parsed['long_description']) && ! empty($response)) {
            $parsed['long_description'] = $response;
        }

        if (! empty($parsed['long_description'])) {
            $parsed['long_description'] = HtmlSanitizer::clean($parsed['long_description']);
        }
        if (! empty($parsed['short_description'])) {
            $parsed['short_description'] = strip_tags($parsed['short_description']);
        }
        if (! empty($parsed['name'])) {
            $parsed['name'] = strip_tags($parsed['name']);
        }
        if (! empty($parsed['seo_title'])) {
            $parsed['seo_title'] = strip_tags($parsed['seo_title']);
        }
        if (! empty($parsed['seo_description'])) {
            $parsed['seo_description'] = strip_tags($parsed['seo_description']);
        }

        return $parsed;
    }

    private function updateContentHashAndWarnDuplicates(AiContent $content): void
    {
        if (! $content->long_description) { return; }

        $hash = md5(trim($content->long_description));
        $content->update(['content_hash' => $hash]);

        $duplicate = AiContent::where('content_hash', $hash)
            ->where('id', '!=', $content->id)
            ->whereNotNull('content_hash')
            ->first();

        if ($duplicate) {
            Log::warning('Duplicate content hash detected after generation', [
                'content_id'    => $content->id,
                'duplicate_id'  => $duplicate->id,
                'duplicate_uid' => $duplicate->uid,
                'erp_reference' => $content->erp_reference,
                'content_hash'  => $hash,
            ]);
        }
    }

    protected function countSyllables(string $word): int
    {
        $word = strtolower(preg_replace('/[^a-z]/', '', $word));
        if (strlen($word) <= 3) { return 1; }
        $word = preg_replace('/(?:[^laeiouy]es|ed|[^laeiouy]e)$/', '', $word);
        $word = preg_replace('/^y/', '', $word);
        return max(1, preg_match_all('/[aeiouy]{1,2}/', $word));
    }

    public function translateContent(AiContent $content, string $targetLang): array
    {
        $apiKey = config('services.deepl.key');
        if (! $apiKey) {
            return ['success' => false, 'message' => 'DeepL API key not configured'];
        }

        try {
            $translator = new Translator($apiKey);

            $fields = array_filter([
                'generated_name'    => $content->generated_name,
                'short_description' => $content->short_description,
                'long_description'  => $content->long_description,
                'seo_title'         => $content->seo_title,
                'seo_description'   => $content->seo_description,
            ]);

            if (empty($fields)) {
                return ['success' => false, 'message' => 'No content to translate'];
            }

            $texts   = array_values($fields);
            $results = $translator->translateText($texts, null, $targetLang);

            $translated = array_combine(
                array_keys($fields),
                array_map(fn ($r) => $r->text, $results)
            );

            $translations              = $content->translations ?? [];
            $translations[$targetLang] = $translated;
            $content->update(['translations' => $translations]);

            return ['success' => true, 'message' => "Translated to {$targetLang}"];
        } catch (Exception $e) {
            Log::error('DeepL translation failed', ['lang' => $targetLang, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
