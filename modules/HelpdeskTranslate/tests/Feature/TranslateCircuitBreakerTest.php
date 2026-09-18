<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTranslate\Models\TranslationCache;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

/**
 * Per-provider circuit breaker in CachedTranslator. The manual synchronous path
 * chains DeepL (15s) + LibreTranslate (10s) fallbacks — ~25s per request when
 * both are down. After N consecutive failures a provider is skipped for a
 * cooldown so the fallback fires instantly (or null is returned without hanging).
 */
class TranslateCircuitBreakerTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    private const LT_KEY = 'helpdesktranslate:circuit:libretranslate';

    private const DEEPL_KEY = 'helpdesktranslate:circuit:deepl';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        TranslationCache::query()->delete();

        config(['helpdesktranslate.circuit_failure_threshold' => 3]);
        config(['helpdesktranslate.circuit_open_seconds' => 60]);

        Cache::forget(self::LT_KEY);
        Cache::forget(self::DEEPL_KEY);

        // DeepL fallback fails fast (no key, no HTTP) so we can loop failures and
        // observe LibreTranslate HTTP call counts in isolation.
        Setting::set('helpdesktranslate.provider', 'libretranslate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.libretranslate.endpoint', 'http://fake-lt.test/translate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.key', '', 'helpdesktranslate');
        config(['helpdesktranslate.deepl.key' => '', 'services.deepl.key' => '']);
    }

    public function test_open_circuit_skips_failing_provider_without_more_http(): void
    {
        Http::fake([
            'fake-lt.test/translate' => Http::response(['error' => 'down'], 500),
        ]);

        $translator = app(CachedTranslator::class);

        // Three failures (1 HTTP call each) open the LibreTranslate circuit.
        foreach (['uno', 'dos', 'tres'] as $text) {
            $this->assertNull($translator->translate($text, 'es', 'en'));
        }

        $this->assertGreaterThanOrEqual(3, (int) Cache::get(self::LT_KEY));

        // Fourth call: circuit open → provider skipped → no 4th HTTP request.
        $this->assertNull($translator->translate('cuatro', 'es', 'en'));

        Http::assertSentCount(3);
    }

    public function test_circuit_resets_after_a_successful_translation(): void
    {
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;
            if ($calls <= 2) {
                return Http::response(['error' => 'down'], 500);
            }

            return Http::response([
                'translatedText' => 'ok',
                'detectedLanguage' => ['language' => 'en'],
            ], 200);
        });

        $translator = app(CachedTranslator::class);

        $this->assertNull($translator->translate('uno', 'es', 'en'));   // fail 1
        $this->assertNull($translator->translate('dos', 'es', 'en'));   // fail 2
        $this->assertSame(2, (int) Cache::get(self::LT_KEY));

        // A successful translation clears the counter (circuit stays closed).
        $this->assertSame('ok', $translator->translate('tres', 'es', 'en'));
        $this->assertNull(Cache::get(self::LT_KEY));
    }
}
