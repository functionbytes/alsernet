<?php

namespace Modules\HelpdeskTranslate\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\Exports\CsvStreamExporter;
use Modules\HelpdeskTranslate\Http\Requests\UpdateTranslateSettingsRequest;
use Modules\HelpdeskTranslate\Http\Requests\UsageReportRequest;
use Modules\HelpdeskTranslate\Models\TranslateUsage;
use Modules\HelpdeskTranslate\Models\TranslationCache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslateSettingsController extends Controller
{
    /**
     * DeepL Pro rate (Free tier no cobra hasta agotar los 500K/mes). Se usa
     * también en extractStats() para el ahorro estimado de caché — mismo
     * criterio de "peor caso" para que ambas cifras sean comparables.
     */
    private const DEEPL_EUR_PER_MILLION_CHARS = 4.99;

    public function __construct()
    {
        $this->middleware('can:helpdesk-translate.settings.view')->only(['index', 'usage', 'usageExport']);
        $this->middleware('can:helpdesk-translate.settings.update')->only(['update', 'test', 'clearCache']);
    }

    public function index(): View
    {
        return view('helpdesktranslate::settings.index', [
            'backups' => $this->extractSettings(),
            'stats' => $this->extractStats(),
        ]);
    }

    public function update(UpdateTranslateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Setting::set('helpdesktranslate.provider', $data['provider'], 'helpdesktranslate');
        Setting::set('helpdesktranslate.default_target', strtolower($data['default_target']), 'helpdesktranslate');
        Setting::set('helpdesktranslate.auto_translate_incoming', $request->boolean('auto_translate_incoming'), 'helpdesktranslate');
        Setting::set('helpdesktranslate.auto_translate_outgoing', $request->boolean('auto_translate_outgoing'), 'helpdesktranslate');

        if (! empty($data['deepl_key'])) {
            Setting::set('helpdesktranslate.deepl.key', $data['deepl_key'], 'helpdesktranslate');
        }

        if (! empty($data['deepl_url'])) {
            Setting::set('helpdesktranslate.deepl.url', $data['deepl_url'], 'helpdesktranslate');
        }

        if (array_key_exists('libretranslate_endpoint', $data)) {
            Setting::set('helpdesktranslate.libretranslate.endpoint', $data['libretranslate_endpoint'] ?? '', 'helpdesktranslate');
        }

        if (! empty($data['libretranslate_api_key'])) {
            Setting::set('helpdesktranslate.libretranslate.api_key', $data['libretranslate_api_key'], 'helpdesktranslate');
        }

        return back()->with('success', __('helpdesktranslate::messages.success.settings_saved'));
    }

    /**
     * Quick connectivity check against DeepL with the saved credentials.
     */
    public function test(): JsonResponse
    {
        $apiKey = Setting::get('helpdesktranslate.deepl.key')
            ?: config('helpdesktranslate.deepl.key')
            ?: config('services.deepl.key');

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => __('helpdesktranslate::messages.errors.no_api_key'),
            ], 422);
        }

        $baseUrl = Setting::get('helpdesktranslate.deepl.url')
            ?: config('helpdesktranslate.deepl.url')
            ?: 'https://api-free.deepl.com';

        try {
            $response = Http::withHeaders(['Authorization' => "DeepL-Auth-Key {$apiKey}"])
                ->timeout(10)
                ->get("{$baseUrl}/v2/usage");

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => __('helpdesktranslate::messages.errors.deepl_status', ['status' => $response->status()]),
                ], 422);
            }

            $usage = $response->json();

            return response()->json([
                'success' => true,
                'message' => __('helpdesktranslate::messages.success.deepl_ok'),
                'usage' => [
                    'character_count' => $usage['character_count'] ?? null,
                    'character_limit' => $usage['character_limit'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('helpdesktranslate::messages.errors.deepl_connect', ['message' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Consumption report from the `helpdesk_translate_usage` ledger, filtered
     * by date range, plus the live DeepL quota. Default range is the last 30
     * days when no `from`/`to` is given.
     */
    public function usage(UsageReportRequest $request): JsonResponse
    {
        [$from, $to] = $this->resolveRange($request->validated());

        $totalsRow = TranslateUsage::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as calls, COALESCE(SUM(characters), 0) as characters, COALESCE(SUM(success), 0) as success_calls')
            ->first();

        $calls = (int) ($totalsRow->calls ?? 0);
        $successCalls = (int) ($totalsRow->success_calls ?? 0);

        return response()->json([
            'success' => true,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'totals' => [
                'characters' => (int) ($totalsRow->characters ?? 0),
                'calls' => $calls,
                'success_calls' => $successCalls,
                'failed_calls' => $calls - $successCalls,
                'estimated_cost_eur' => $this->estimateDeeplCost($from, $to),
            ],
            'by_feature' => $this->aggregateUsageBy('feature', $from, $to),
            'by_operation' => $this->aggregateUsageBy('operation', $from, $to),
            'by_provider' => $this->aggregateUsageBy('provider', $from, $to),
            'daily' => $this->dailyUsage($from, $to),
            'quota' => $this->fetchLiveDeeplQuota(),
        ]);
    }

    /**
     * Exporta a CSV el desglose diario del rango filtrado (mismo cálculo que
     * alimenta el gráfico de tendencia), para llevar el consumo a contabilidad.
     */
    public function usageExport(UsageReportRequest $request, CsvStreamExporter $exporter): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request->validated());

        $rows = $this->dailyUsage($from, $to);

        return $exporter->stream(
            'deepl-consumo-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv',
            ['Fecha', 'Caracteres', 'Llamadas', 'Exitosas', 'Fallidas'],
            (function () use ($rows) {
                foreach ($rows as $row) {
                    yield [$row['date'], $row['characters'], $row['calls'], $row['success'], $row['failed']];
                }
            })()
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(array $data): array
    {
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfDay();
        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        return [$from, $to];
    }

    /**
     * Wipe the local translation cache. Returns deleted row count.
     */
    public function clearCache(): JsonResponse
    {
        $deleted = TranslationCache::query()->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => __('helpdesktranslate::messages.success.cache_cleared', ['count' => $deleted]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractStats(): array
    {
        $row = TranslationCache::query()
            ->selectRaw('COUNT(*) as cached, COALESCE(SUM(hits), 0) as total_hits, COALESCE(SUM(chars_saved), 0) as chars_saved')
            ->first();

        $cached = (int) ($row->cached ?? 0);
        $hits = (int) ($row->total_hits ?? 0);
        $charsSaved = (int) ($row->chars_saved ?? 0);

        // DeepL Free is 500K chars/month (€0). Pro is €4.99/M chars after that.
        // We estimate worst case as Pro pricing for transparency.
        $estimatedSavingEur = $charsSaved > 0
            ? round($charsSaved / 1_000_000 * self::DEEPL_EUR_PER_MILLION_CHARS, 4)
            : 0.0;

        return [
            'cached' => $cached,
            'hits' => $hits,
            'chars_saved' => $charsSaved,
            'estimated_saving_eur' => $estimatedSavingEur,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aggregateUsageBy(string $column, Carbon $from, Carbon $to): array
    {
        $column = match ($column) {
            'feature', 'operation', 'provider' => $column,
            default => throw new \InvalidArgumentException("Unsupported groupBy column: {$column}"),
        };

        return TranslateUsage::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("{$column}, COUNT(*) as calls, COALESCE(SUM(characters), 0) as characters")
            ->groupBy($column)
            ->orderByDesc('characters')
            ->get()
            ->map(fn ($row) => [
                $column => $row->{$column},
                'calls' => (int) $row->calls,
                'characters' => (int) $row->characters,
            ])
            ->all();
    }

    /**
     * Estima el gasto real en € solo de las llamadas exitosas al proveedor
     * DeepL (LibreTranslate es self-hosted y gratis, y una llamada fallida no
     * llega a facturarse). Usa tarifa Pro como peor caso, igual que el ahorro
     * estimado de caché en extractStats().
     */
    private function estimateDeeplCost(Carbon $from, Carbon $to): float
    {
        $characters = (int) TranslateUsage::query()
            ->where('provider', 'deepl')
            ->where('success', true)
            ->whereBetween('created_at', [$from, $to])
            ->sum('characters');

        return $characters > 0
            ? round($characters / 1_000_000 * self::DEEPL_EUR_PER_MILLION_CHARS, 4)
            : 0.0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dailyUsage(Carbon $from, Carbon $to): array
    {
        return TranslateUsage::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as calls, COALESCE(SUM(characters), 0) as characters, COALESCE(SUM(success), 0) as success_calls')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'calls' => (int) $row->calls,
                'characters' => (int) $row->characters,
                'success' => (int) $row->success_calls,
                'failed' => (int) $row->calls - (int) $row->success_calls,
            ])
            ->all();
    }

    /**
     * @return array{character_count: int|null, character_limit: int|null}|null
     */
    private function fetchLiveDeeplQuota(): ?array
    {
        $apiKey = Setting::get('helpdesktranslate.deepl.key')
            ?: config('helpdesktranslate.deepl.key')
            ?: config('services.deepl.key');

        if (empty($apiKey)) {
            return null;
        }

        $baseUrl = Setting::get('helpdesktranslate.deepl.url')
            ?: config('helpdesktranslate.deepl.url')
            ?: 'https://api-free.deepl.com';

        try {
            $response = Http::withHeaders(['Authorization' => "DeepL-Auth-Key {$apiKey}"])
                ->timeout(10)
                ->get("{$baseUrl}/v2/usage");

            if ($response->failed()) {
                return null;
            }

            $usage = $response->json();

            return [
                'character_count' => $usage['character_count'] ?? null,
                'character_limit' => $usage['character_limit'] ?? null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractSettings(): array
    {
        // `?:` (not `??`) is intentional: operators "clear" a setting by
        // saving the empty string, and `??` would skip the config fallback.
        return [
            'provider' => (string) (Setting::get('helpdesktranslate.provider') ?: config('helpdesktranslate.provider', 'deepl')),
            'default_target' => (string) (Setting::get('helpdesktranslate.default_target') ?: config('helpdesktranslate.default_target', 'es')),
            'auto_translate_incoming' => (bool) (Setting::get('helpdesktranslate.auto_translate_incoming') ?? config('helpdesktranslate.auto_translate_incoming', false)),
            'auto_translate_outgoing' => (bool) (Setting::get('helpdesktranslate.auto_translate_outgoing') ?? config('helpdesktranslate.auto_translate_outgoing', false)),
            'deepl_url' => (string) (Setting::get('helpdesktranslate.deepl.url') ?: config('helpdesktranslate.deepl.url', 'https://api-free.deepl.com')),
            'has_env_key' => ! empty(config('services.deepl.key')) || ! empty(config('helpdesktranslate.deepl.key')),
            'has_deepl_key' => ! empty(Setting::get('helpdesktranslate.deepl.key')),
            'libretranslate_endpoint' => (string) (Setting::get('helpdesktranslate.libretranslate.endpoint') ?: config('helpdesktranslate.libretranslate.endpoint', '')),
            'has_libretranslate_key' => ! empty(Setting::get('helpdesktranslate.libretranslate.api_key')),
        ];
    }
}
