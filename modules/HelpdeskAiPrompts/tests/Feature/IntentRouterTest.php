<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Services\IntentRouter;

class IntentRouterTest extends HelpdeskAiPromptsTestCase
{
    private IntentRouter $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = app(IntentRouter::class);
        config(['services.openai.key' => null]);
    }

    private function makeCase(array $overrides = []): AiPromptCase
    {
        return AiPromptCase::query()->create(array_merge([
            'key' => 'x',
            'name' => 'X',
            'description' => 'desc',
            'priority' => 0,
            'is_active' => true,
            'instructions' => 'inst',
            'keywords' => [],
        ], $overrides));
    }

    public function test_keyword_match_picks_the_highest_priority_case(): void
    {
        $this->makeCase(['key' => 'envio', 'priority' => 10, 'keywords' => ['envío', 'envio']]);
        $this->makeCase(['key' => 'pedido', 'priority' => 50, 'keywords' => ['pedido']]);

        $result = $this->router->route('¿Dónde está mi pedido?', ['channel' => 'web', 'locale' => 'es']);

        $this->assertSame('pedido', $result['case']->key);
        $this->assertSame('keyword', $result['routed_by']);
    }

    public function test_keyword_match_is_accent_and_case_insensitive(): void
    {
        $this->makeCase(['key' => 'envio', 'keywords' => ['envío']]);

        $result = $this->router->route('Quiero saber del ENVIO de mi compra', []);

        $this->assertSame('envio', $result['case']->key);
    }

    public function test_keyword_does_not_match_as_a_substring(): void
    {
        $this->makeCase(['key' => 'pedido', 'keywords' => ['pedido']]);
        $this->makeCase(['key' => 'general', 'priority' => -100, 'keywords' => []]);

        $result = $this->router->route('Fui a un despedido de soltero', []);

        $this->assertSame('general', $result['case']->key);
        $this->assertSame('default', $result['routed_by']);
    }

    public function test_llm_classification_is_used_when_no_keyword_matches(): void
    {
        config(['services.openai.key' => 'test-key']);
        $this->makeCase(['key' => 'devoluciones', 'keywords' => []]);
        $this->makeCase(['key' => 'pedido', 'keywords' => []]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"case": "devoluciones"}']]],
            ], 200),
        ]);

        $result = $this->router->route('Quiero cambiar un producto que no me vale', ['channel' => 'web']);

        $this->assertSame('devoluciones', $result['case']->key);
        $this->assertSame('llm', $result['routed_by']);
    }

    public function test_llm_classification_result_is_cached_for_the_same_question(): void
    {
        config(['services.openai.key' => 'test-key']);
        $this->makeCase(['key' => 'devoluciones', 'keywords' => []]);

        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '{"case": "devoluciones"}']]]], 200),
        ]);

        $this->router->route('Quiero cambiar un producto', ['channel' => 'web']);
        $this->router->route('Quiero cambiar un producto', ['channel' => 'web']);

        Http::assertSentCount(1);
    }

    public function test_without_an_api_key_the_llm_step_is_skipped(): void
    {
        config(['services.openai.key' => null]);
        $this->makeCase(['key' => 'devoluciones', 'keywords' => []]);
        $this->makeCase(['key' => 'general', 'priority' => -100, 'keywords' => []]);

        $result = $this->router->route('Algo que no coincide con ningún keyword', []);

        $this->assertSame('general', $result['case']->key);
        $this->assertSame('default', $result['routed_by']);
    }

    public function test_llm_error_is_not_cached_and_falls_back_to_default(): void
    {
        config(['services.openai.key' => 'test-key']);
        $this->makeCase(['key' => 'devoluciones', 'keywords' => []]);
        $this->makeCase(['key' => 'general', 'priority' => -100, 'keywords' => []]);

        Http::fake(['api.openai.com/*' => Http::response([], 500)]);

        $result = $this->router->route('Algo raro que no matchea', []);

        $this->assertSame('general', $result['case']->key);
        $this->assertSame('default', $result['routed_by']);
    }

    public function test_filters_channels_restricts_the_case_to_that_channel(): void
    {
        $this->makeCase(['key' => 'solo_web', 'keywords' => ['hola'], 'filters' => ['channels' => ['web']]]);

        $onWeb = $this->router->route('hola', ['channel' => 'web']);
        $this->assertSame('solo_web', $onWeb['case']->key);

        $onWhatsapp = $this->router->route('hola', ['channel' => 'whatsapp']);
        $this->assertSame('none', $onWhatsapp['routed_by']);
    }

    public function test_filters_logged_in_yes_requires_an_identified_customer(): void
    {
        $this->makeCase(['key' => 'mi_cuenta', 'keywords' => ['mi cuenta'], 'filters' => ['logged_in' => 'yes']]);

        $anonymous = $this->router->route('mi cuenta', ['logged_in' => false]);
        $this->assertSame('none', $anonymous['routed_by']);

        $identified = $this->router->route('mi cuenta', ['logged_in' => true]);
        $this->assertSame('mi_cuenta', $identified['case']->key);
    }

    public function test_filters_url_contains_matches_the_page_url(): void
    {
        $this->makeCase(['key' => 'checkout_help', 'keywords' => ['ayuda'], 'filters' => ['url_contains' => ['/checkout']]]);

        $onCheckout = $this->router->route('ayuda', ['page_url' => 'https://tienda.com/checkout/pago']);
        $this->assertSame('checkout_help', $onCheckout['case']->key);

        $elsewhere = $this->router->route('ayuda', ['page_url' => 'https://tienda.com/producto/1']);
        $this->assertSame('none', $elsewhere['routed_by']);
    }

    public function test_filters_hours_range(): void
    {
        $this->makeCase(['key' => 'horario_tienda', 'keywords' => ['abierto'], 'filters' => ['hours' => '09-21']]);

        $inHours = $this->router->route('abierto', ['now' => Carbon::parse('2026-09-28 10:00:00')]);
        $this->assertSame('horario_tienda', $inHours['case']->key);

        $outOfHours = $this->router->route('abierto', ['now' => Carbon::parse('2026-09-28 23:00:00')]);
        $this->assertSame('none', $outOfHours['routed_by']);
    }

    public function test_no_applicable_cases_returns_none(): void
    {
        $result = $this->router->route('cualquier cosa', []);

        $this->assertNull($result['case']);
        $this->assertSame('none', $result['routed_by']);
    }
}
