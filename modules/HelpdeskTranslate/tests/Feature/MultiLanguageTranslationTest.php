<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTranslate\Models\TranslationCache;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

/**
 * Integration tests against the live LibreTranslate container running on
 * http://localhost:5050. Skip when the container isn't reachable so CI
 * environments without docker don't fail.
 */
class MultiLanguageTranslationTest extends TestCase
{
    // DatabaseTransactions is preferred over RefreshDatabase here because the
    // `helpdesk` connection points to an existing database whose migrations are
    // not run as part of the test suite. DatabaseTransactions wraps every test
    // in a transaction and rolls it back automatically, so Setting::set() and
    // TranslationCache::query()->delete() never persist to the real database.
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    private CachedTranslator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip immediately when the container is unavailable so no database
        // writes happen in environments without LibreTranslate (CI, etc.).
        if (! $this->libretranslateReachable()) {
            $this->markTestSkipped('LibreTranslate container not reachable on localhost:5050');
        }

        // Force LibreTranslate provider for these tests, regardless of the
        // operator's saved settings.
        Setting::set('helpdesktranslate.provider', 'libretranslate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.libretranslate.endpoint', 'http://localhost:5050/translate', 'helpdesktranslate');

        // Wipe cache so we observe actual provider hits, not stale rows.
        TranslationCache::query()->delete();

        $this->translator = app(CachedTranslator::class);
    }

    /** @return array<string, array{string, string, string}> [text, source, target] */
    public static function intoSpanishProvider(): array
    {
        return [
            'EN → ES' => ['Hello, I need help with my order', 'en', 'es'],
            'FR → ES' => ["Bonjour, j'ai besoin d'aide", 'fr', 'es'],
            'DE → ES' => ['Guten Morgen, ich brauche Hilfe', 'de', 'es'],
            'IT → ES' => ['Buongiorno, ho bisogno di aiuto', 'it', 'es'],
            'PT → ES' => ['Bom dia, preciso de ajuda', 'pt', 'es'],
        ];
    }

    /** @return array<string, array{string, string, string}> */
    public static function fromSpanishProvider(): array
    {
        return [
            'ES → EN' => ['Hola, necesito ayuda con mi pedido', 'es', 'en'],
            'ES → FR' => ['Hola, necesito ayuda', 'es', 'fr'],
            'ES → DE' => ['Buenos días, necesito ayuda', 'es', 'de'],
            'ES → IT' => ['Buenos días, necesito ayuda', 'es', 'it'],
            'ES → PT' => ['Buenos días, necesito ayuda', 'es', 'pt'],
        ];
    }

    /**
     * @dataProvider intoSpanishProvider
     */
    public function test_translates_into_spanish(string $text, string $source, string $target): void
    {
        $translated = $this->translator->translate($text, $target, $source);

        $this->assertNotNull($translated, "[$source → $target] should return a translation");
        $this->assertNotSame($text, $translated, "[$source → $target] result should differ from source");
        $this->assertNotEmpty(trim($translated), "[$source → $target] should not be empty");
    }

    /**
     * @dataProvider fromSpanishProvider
     */
    public function test_translates_from_spanish(string $text, string $source, string $target): void
    {
        $translated = $this->translator->translate($text, $target, $source);

        $this->assertNotNull($translated, "[$source → $target] should return a translation");
        $this->assertNotSame($text, $translated, "[$source → $target] result should differ from source");
        $this->assertNotEmpty(trim($translated), "[$source → $target] should not be empty");
    }

    public function test_cache_hit_avoids_provider_call(): void
    {
        $text = 'Thank you for your patience';
        $source = 'en';
        $target = 'es';

        // First call — populates cache
        $first = $this->translator->translate($text, $target, $source);
        $this->assertNotNull($first);

        $rowAfterFirst = TranslationCache::query()->where('source_lang', $source)->where('target_lang', $target)->first();
        $this->assertSame(1, (int) $rowAfterFirst->hits);

        // Second call — must be served from cache
        $start = microtime(true);
        $second = $this->translator->translate($text, $target, $source);
        $elapsedMs = (microtime(true) - $start) * 1000;

        $this->assertSame($first, $second, 'Cached result should match first call');
        $this->assertLessThan(50, $elapsedMs, "Cached call should be < 50ms (was {$elapsedMs}ms)");

        $rowAfterSecond = TranslationCache::query()->where('source_lang', $source)->where('target_lang', $target)->first();
        $this->assertSame(2, (int) $rowAfterSecond->hits, 'Hits counter should increment');
        $this->assertGreaterThan(0, $rowAfterSecond->chars_saved, 'chars_saved should accumulate on each hit');
    }

    private function libretranslateReachable(): bool
    {
        $ch = curl_init('http://localhost:5050/languages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code === 200;
    }
}
