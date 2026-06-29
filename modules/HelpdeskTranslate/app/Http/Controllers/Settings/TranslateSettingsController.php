<?php

namespace Modules\HelpdeskTranslate\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTranslate\Http\Requests\UpdateTranslateSettingsRequest;
use Modules\HelpdeskTranslate\Models\TranslationCache;

class TranslateSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk-translate.settings.view')->only('index');
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
            ? round($charsSaved / 1_000_000 * 4.99, 4)
            : 0.0;

        return [
            'cached' => $cached,
            'hits' => $hits,
            'chars_saved' => $charsSaved,
            'estimated_saving_eur' => $estimatedSavingEur,
        ];
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
