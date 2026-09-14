<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTranslate\Models\TranslationCache;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

/**
 * Exercises the cross-provider fallback in CachedTranslator: when the primary
 * provider returns null the orchestrator tries the secondary one and, on
 * success, persists the cache row under the provider that actually answered.
 */
class CachedTranslatorFallbackTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        // Reset cache so previous runs don't short-circuit the test
        TranslationCache::query()->delete();
    }

    public function test_deepl_failure_falls_back_to_libretranslate_and_persists(): void
    {
        Setting::set('helpdesktranslate.provider', 'deepl', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.key', '', 'helpdesktranslate'); // DeepL will short-circuit to null
        Setting::set('helpdesktranslate.libretranslate.endpoint', 'http://fake-libretranslate.test/translate', 'helpdesktranslate');
        // Make config services.deepl.key empty so resolveApiKey returns null
        config(['helpdesktranslate.deepl.key' => '', 'services.deepl.key' => '']);

        Http::fake([
            'fake-libretranslate.test/translate' => Http::response([
                'translatedText' => 'Hola mundo',
                'detectedLanguage' => ['language' => 'en', 'confidence' => 0.99],
            ], 200),
        ]);

        $translator = app(CachedTranslator::class);
        $translated = $translator->translate('Hello world', 'es', 'en');

        $this->assertSame('Hola mundo', $translated);

        $row = TranslationCache::query()->where('text_source', 'Hello world')->first();
        $this->assertNotNull($row, 'Cache row should be persisted after a successful fallback');
        $this->assertSame('libretranslate', $row->provider, 'Provider on the cache row must reflect the fallback that succeeded, not the configured one');
        $this->assertSame('en', $row->source_lang);
        $this->assertSame('es', $row->target_lang);
    }

    public function test_libretranslate_failure_falls_back_to_deepl(): void
    {
        Setting::set('helpdesktranslate.provider', 'libretranslate', 'helpdesktranslate');
        // No libretranslate endpoint → mock mode → marked failed/mocked in CachedTranslator
        Setting::set('helpdesktranslate.libretranslate.endpoint', '', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.key', 'fake-key', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.url', 'https://api-free.deepl.com', 'helpdesktranslate');

        Http::fake([
            'api-free.deepl.com/v2/translate' => Http::response([
                'translations' => [[
                    'text' => 'Hola mundo',
                    'detected_source_language' => 'EN',
                ]],
            ], 200),
        ]);

        $translator = app(CachedTranslator::class);
        $translated = $translator->translate('Hello world', 'es', 'en');

        $this->assertSame('Hola mundo', $translated);

        $row = TranslationCache::query()->where('text_source', 'Hello world')->first();
        $this->assertNotNull($row);
        $this->assertSame('deepl', $row->provider);
    }

    public function test_returns_null_when_both_providers_fail(): void
    {
        Setting::set('helpdesktranslate.provider', 'deepl', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.key', '', 'helpdesktranslate');
        Setting::set('helpdesktranslate.libretranslate.endpoint', '', 'helpdesktranslate');
        config(['helpdesktranslate.deepl.key' => '', 'services.deepl.key' => '']);

        Http::fake([]);

        $translator = app(CachedTranslator::class);
        $translated = $translator->translate('Hello world', 'es', 'en');

        $this->assertNull($translated);
        $this->assertSame(0, TranslationCache::query()->count());
    }
}
