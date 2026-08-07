<?php

namespace Modules\HelpdeskTranslate\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTranslate\Http\Concerns\EnforcesTranslationQuota;
use Modules\HelpdeskTranslate\Models\TranslationCache;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

/**
 * El cargo real del cupo diario vive en CachedTranslator (solo se descuenta
 * en un cache-miss real contra el proveedor); EnforcesTranslationQuota es
 * ahora solo un "peek" de lectura para fallar rápido con 429. Ver notas en
 * ambas clases.
 */
class TranslationQuotaTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        TranslationCache::query()->delete();

        // El bucket 'auto' es GLOBAL y vive en Redis, no en la BD — a
        // diferencia de TranslationCache, DatabaseTransactions no lo revierte
        // entre tests. Sin este forget, otro test de la misma tanda que ya
        // haya usado feature != 'manual' antes deja residuo y rompe las
        // aserciones de valor exacto de este archivo.
        Cache::forget('helpdesktranslate:chars:auto:'.now()->format('Ymd'));

        Setting::set('helpdesktranslate.provider', 'deepl', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.key', 'fake-key', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.url', 'https://api-free.deepl.com', 'helpdesktranslate');
    }

    private function enforcer(): object
    {
        return new class
        {
            use EnforcesTranslationQuota;

            public function run(CachedTranslator $translator): void
            {
                $this->enforceDailyCharacterQuota($translator);
            }
        };
    }

    private function fakeDeeplSuccess(string $translated = 'Hola mundo'): void
    {
        Http::fake([
            'api-free.deepl.com/v2/translate' => Http::response([
                'translations' => [['text' => $translated, 'detected_source_language' => 'EN']],
            ], 200),
        ]);
    }

    // ── enforceDailyCharacterQuota() (peek, sin descontar) ──────────────────

    public function test_it_allows_when_quota_not_yet_exhausted(): void
    {
        config()->set('helpdesktranslate.daily_char_limit', 1000);
        $this->actingAs(User::factory()->create());

        $this->enforcer()->run(app(CachedTranslator::class));
        $this->addToAssertionCount(1); // no exception = pass
    }

    public function test_it_blocks_with_429_when_quota_already_exhausted(): void
    {
        config()->set('helpdesktranslate.daily_char_limit', 1000);
        $user = User::factory()->create();
        $this->actingAs($user);

        Cache::put('helpdesktranslate:chars:'.$user->id.':'.now()->format('Ymd'), 1000, now()->endOfDay());

        try {
            $this->enforcer()->run(app(CachedTranslator::class));
            $this->fail('Expected the quota to be exceeded.');
        } catch (HttpResponseException $e) {
            $this->assertSame(429, $e->getResponse()->getStatusCode());
        }
    }

    public function test_a_zero_limit_disables_the_cap(): void
    {
        config()->set('helpdesktranslate.daily_char_limit', 0);
        $user = User::factory()->create();
        $this->actingAs($user);

        Cache::put('helpdesktranslate:chars:'.$user->id.':'.now()->format('Ymd'), 5_000_000, now()->endOfDay());

        $this->enforcer()->run(app(CachedTranslator::class));
        $this->addToAssertionCount(1);
    }

    // ── CachedTranslator: el cargo real solo ocurre en cache-miss ──────────

    public function test_cache_hit_does_not_charge_the_quota(): void
    {
        config()->set('helpdesktranslate.daily_char_limit', 1000);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->fakeDeeplSuccess();

        $translator = app(CachedTranslator::class);
        $key = 'helpdesktranslate:chars:'.$user->id.':'.now()->format('Ymd');

        // Primera llamada: cache-miss real, sí descuenta cupo.
        $translator->translate('Hello world', 'es', 'en', feature: 'manual');
        $this->assertSame(mb_strlen('Hello world'), (int) Cache::get($key, 0));

        // Segunda llamada, mismo texto/idiomas: acierto de caché, NO debe
        // descontar cupo ni volver a pegarle al proveedor (Http::fake ya
        // solo tiene una respuesta registrada; preventStrayRequests() en
        // setUp() haría fallar el test si se repitiera la llamada HTTP).
        $translator->translate('Hello world', 'es', 'en', feature: 'manual');
        $this->assertSame(mb_strlen('Hello world'), (int) Cache::get($key, 0));
    }

    public function test_translate_returns_null_without_calling_provider_when_quota_already_exhausted(): void
    {
        config()->set('helpdesktranslate.daily_char_limit', 5);
        $user = User::factory()->create();
        $this->actingAs($user);

        $key = 'helpdesktranslate:chars:'.$user->id.':'.now()->format('Ymd');
        Cache::put($key, 5, now()->endOfDay());

        // Sin fakes registrados: si CachedTranslator intentara llamar al
        // proveedor pese al cupo agotado, preventStrayRequests() tumbaría
        // el test con una excepción de red no permitida.
        Http::fake([]);

        $translator = app(CachedTranslator::class);
        $translated = $translator->translate('Hello world again', 'es', 'en', feature: 'manual');

        $this->assertNull($translated);
    }

    public function test_manual_and_automatic_quotas_are_isolated_buckets(): void
    {
        config()->set('helpdesktranslate.daily_char_limit', 1000);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->fakeDeeplSuccess();

        $translator = app(CachedTranslator::class);
        $translator->translate('Hello world', 'es', 'en', feature: 'manual');
        $translator->translate('Good morning team', 'es', 'en', feature: 'auto_incoming');

        $manualKey = 'helpdesktranslate:chars:'.$user->id.':'.now()->format('Ymd');
        $autoKey = 'helpdesktranslate:chars:auto:'.now()->format('Ymd');

        $this->assertSame(mb_strlen('Hello world'), (int) Cache::get($manualKey, 0));
        $this->assertSame(mb_strlen('Good morning team'), (int) Cache::get($autoKey, 0));
    }
}
