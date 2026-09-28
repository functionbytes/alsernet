<?php

namespace Modules\HelpdeskAiPrompts\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Helpdesk\Services\AI\AiClient;
use Modules\Helpdesk\Services\AI\PromptSanitizer;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;

/**
 * Picks the AiPromptCase that best matches a customer question:
 * 1) keyword match (case-/accent-insensitive, whole word), highest priority wins;
 * 2) otherwise, LLM classification among the still-applicable cases;
 * 3) otherwise, the 'general' case if present;
 * 4) otherwise, none.
 */
class IntentRouter
{
    private const LLM_CACHE_TTL = 600; // 10 min

    public function __construct(
        private readonly PromptLibrary $library,
        private readonly AiClient $aiClient,
        private readonly PromptSanitizer $sanitizer,
    ) {}

    /**
     * @param  array{channel?: ?string, locale?: ?string, page_url?: ?string, logged_in?: bool, now?: ?Carbon}  $ctx
     * @return array{case: ?AiPromptCase, routed_by: string}
     */
    public function route(string $question, array $ctx): array
    {
        $candidates = $this->applicableCases($ctx);

        if ($candidates->isEmpty()) {
            return ['case' => null, 'routed_by' => 'none'];
        }

        if (($keywordMatch = $this->matchByKeyword($question, $candidates)) !== null) {
            return ['case' => $keywordMatch, 'routed_by' => 'keyword'];
        }

        if (($llmMatch = $this->matchByLlm($question, $candidates)) !== null) {
            return ['case' => $llmMatch, 'routed_by' => 'llm'];
        }

        $default = $candidates->firstWhere('key', 'general');

        return $default !== null
            ? ['case' => $default, 'routed_by' => 'default']
            : ['case' => null, 'routed_by' => 'none'];
    }

    /**
     * @return Collection<int, AiPromptCase>
     */
    private function applicableCases(array $ctx): Collection
    {
        return $this->library->cases($ctx['channel'] ?? null)
            ->filter(fn (AiPromptCase $case) => $this->filtersMatch((array) ($case->filters ?? []), $ctx))
            ->values();
    }

    private function filtersMatch(array $filters, array $ctx): bool
    {
        if ($filters === []) {
            return true;
        }

        if (! empty($filters['channels']) && ! in_array($ctx['channel'] ?? null, $filters['channels'], true)) {
            return false;
        }

        if (! empty($filters['locales']) && ! in_array($ctx['locale'] ?? null, $filters['locales'], true)) {
            return false;
        }

        if (! empty($filters['url_contains'])) {
            $url = (string) ($ctx['page_url'] ?? '');
            $matches = collect($filters['url_contains'])
                ->contains(fn ($needle) => $needle !== '' && str_contains($url, (string) $needle));

            if (! $matches) {
                return false;
            }
        }

        $loggedIn = $filters['logged_in'] ?? 'any';
        if ($loggedIn !== 'any' && (bool) ($ctx['logged_in'] ?? false) !== ($loggedIn === 'yes')) {
            return false;
        }

        if (! empty($filters['hours']) && ! $this->withinHours((string) $filters['hours'], $ctx['now'] ?? null)) {
            return false;
        }

        return true;
    }

    private function withinHours(string $range, ?Carbon $now): bool
    {
        if (! str_contains($range, '-')) {
            return true;
        }

        [$start, $end] = array_map('intval', explode('-', $range, 2));
        $hour = ($now ?? Carbon::now())->hour;

        return $start <= $end
            ? ($hour >= $start && $hour < $end)
            : ($hour >= $start || $hour < $end);
    }

    /**
     * @param  Collection<int, AiPromptCase>  $candidates
     */
    private function matchByKeyword(string $question, Collection $candidates): ?AiPromptCase
    {
        $normalized = $this->normalize($question);

        return $candidates
            ->sortByDesc('priority')
            ->first(function (AiPromptCase $case) use ($normalized) {
                foreach ((array) ($case->keywords ?? []) as $keyword) {
                    $needle = $this->normalize((string) $keyword);

                    if ($needle !== '' && preg_match('/\b'.preg_quote($needle, '/').'\b/', $normalized) === 1) {
                        return true;
                    }
                }

                return false;
            });
    }

    private function normalize(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }

    /**
     * @param  Collection<int, AiPromptCase>  $candidates
     */
    private function matchByLlm(string $question, Collection $candidates): ?AiPromptCase
    {
        if (empty(config('services.openai.key'))) {
            return null;
        }

        $cacheKey = 'helpdesk_ai_prompts:router:'.md5($this->normalize($question).'|'.$candidates->pluck('key')->implode(','));

        if (Cache::has($cacheKey)) {
            $cachedKey = Cache::get($cacheKey);

            return $cachedKey !== null ? $candidates->firstWhere('key', $cachedKey) : null;
        }

        ['ok' => $ok, 'case' => $caseKey] = $this->classify($question, $candidates);

        if (! $ok) {
            // Transient failure: don't cache it, so the next question can retry.
            return null;
        }

        Cache::put($cacheKey, $caseKey, self::LLM_CACHE_TTL);

        return $caseKey !== null ? $candidates->firstWhere('key', $caseKey) : null;
    }

    /**
     * @param  Collection<int, AiPromptCase>  $candidates
     * @return array{ok: bool, case: ?string}
     */
    private function classify(string $question, Collection $candidates): array
    {
        $catalog = $candidates
            ->map(fn (AiPromptCase $case) => sprintf('- %s: %s', $case->key, $case->description))
            ->implode("\n");

        $system = "Clasifica la pregunta del cliente en uno de estos casos:\n{$catalog}\n\n"
            .'Responde SOLO JSON, sin explicaciones: {"case": "<key>"} o {"case": null} si ninguno encaja bien.';

        $message = $this->aiClient->chatCompletion([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $this->sanitizer->wrap($question, 'PREGUNTA_CLIENTE')],
        ], [
            'model' => config('helpdeskchatflow.ai.model', 'gpt-4o-mini'),
            'temperature' => 0,
            'timeout' => 20,
        ]);

        if ($message === null) {
            return ['ok' => false, 'case' => null];
        }

        $decoded = json_decode(trim((string) ($message['content'] ?? '')), true);
        $case = is_array($decoded) ? ($decoded['case'] ?? null) : null;
        $case = is_string($case) && $candidates->contains('key', $case) ? $case : null;

        return ['ok' => true, 'case' => $case];
    }
}
