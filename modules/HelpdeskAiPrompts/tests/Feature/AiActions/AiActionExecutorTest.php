<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiActionRun;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionExecutor;

class AiActionExecutorTest extends AiActionsTestCase
{
    private function run_(string $key, array $args, array $ctx, string $source = 'test'): array
    {
        return app(ActionExecutor::class)->run($key, $args, $ctx, $source);
    }

    public function test_order_email_pair_ok_when_order_belongs_to_email(): void
    {
        $this->bridge();
        $ps = $this->ps();
        $ps->shouldReceive('getOrderDetailByReference')->once()->with('ABCDEF', 'ana@example.com', null)
            ->andReturn(['id' => 15, 'reference' => 'ABCDEF', 'customer_email' => 'Ana@Example.com']);
        $ps->shouldReceive('callAllowedAction')->once()
            ->withArgs(fn ($a, $p, $k) => $a === 'order.documents' && $p['order_id'] === 15 && $p['lookup'] === ['email' => 'ana@example.com'])
            ->andReturn(['invoices' => [['number' => 'F-1', 'date' => '2026-01-01']]]);

        $r = $this->run_('documentos_pedido', ['order_ref' => 'ABCDEF', 'email' => 'Ana@example.com'], $this->ctx());

        $this->assertTrue($r['ok']);
        $this->assertSame('{"invoices":[{"number":"F-1"}]}', $r['content']);
    }

    public function test_order_email_pair_mismatch_is_denied_with_neutral_message(): void
    {
        $this->bridge();
        $ps = $this->ps();
        $ps->shouldReceive('getOrderDetailByReference')->andReturn(['id' => 15, 'customer_email' => 'otra@example.com']);
        $ps->shouldNotReceive('callAllowedAction');

        $r = $this->run_('documentos_pedido', ['order_ref' => 'ABCDEF', 'email' => 'ana@example.com'], $this->ctx());

        $this->assertSame('denied', $r['status']);
        $this->assertStringNotContainsString('otra@example.com', $r['content']);
    }

    public function test_order_not_found_for_email_is_denied(): void
    {
        $this->bridge();
        $ps = $this->ps();
        $ps->shouldReceive('getOrderDetailByReference')->andReturn(null);
        $ps->shouldNotReceive('callAllowedAction');

        $this->assertSame('denied', $this->run_('documentos_pedido', ['order_ref' => 'ZZZ', 'email' => 'a@b.com'], $this->ctx())['status']);
    }

    public function test_verified_ownership_uses_ctx_identity_never_model_args(): void
    {
        $this->bridge([
            'key' => 'mis_cupones',
            'config' => ['action' => 'customer.vouchers', 'payload' => []],
            'response' => ['fields' => ['vouchers.*.description']],
            'rules' => ['ownership' => 'verified'],
        ]);
        $ps = $this->ps();
        $ps->shouldReceive('callAllowedAction')->once()
            ->withArgs(fn ($a, $p) => $p['lookup'] === ['email' => 'ana@example.com', 'external_id' => 77])
            ->andReturn(['vouchers' => [['description' => 'Promo']]]);

        $r = $this->run_('mis_cupones', ['email' => 'victima@example.com', 'customer_email' => 'victima@example.com'], $this->verifiedCtx());

        $this->assertTrue($r['ok']);
    }

    public function test_verified_ownership_is_denied_without_verification(): void
    {
        $this->bridge([
            'key' => 'mis_cupones',
            'config' => ['action' => 'customer.vouchers', 'payload' => []],
            'rules' => ['ownership' => 'verified'],
        ]);
        $this->ps()->shouldNotReceive('callAllowedAction');

        $r = $this->run_('mis_cupones', [], $this->ctx(['customer_email' => 'ana@example.com']));

        $this->assertSame('denied', $r['status']);
    }

    public function test_pair_with_verified_ctx_does_not_need_email_and_is_scoped_to_ctx(): void
    {
        $this->bridge();
        $ps = $this->ps();
        $ps->shouldReceive('getOrderDetailByReference')->once()->with('ABCDEF', 'ana@example.com', 77)->andReturn(['id' => 9]);
        $ps->shouldReceive('callAllowedAction')->once()->andReturn(['invoices' => []]);

        $r = $this->run_('documentos_pedido', ['order_ref' => 'ABCDEF', 'email' => 'otro@example.com'], $this->verifiedCtx());

        $this->assertSame('ok', $r['status']);
    }

    public function test_ownership_none_works_for_public_data_without_verification(): void
    {
        $this->bridge([
            'key' => 'buscar_catalogo',
            'config' => ['action' => 'product.search', 'payload' => ['query' => '{{args.q}}']],
            'parameters' => [['name' => 'q', 'type' => 'string', 'description' => 'x', 'required' => true]],
            'response' => ['fields' => ['products.*.title']],
            'rules' => ['ownership' => 'none'],
        ]);
        $ps = $this->ps();
        $ps->shouldReceive('callAllowedAction')->once()
            ->withArgs(fn ($a, $p) => $p === ['query' => 'botas'])
            ->andReturn(['products' => [['title' => 'Bota']]]);

        $this->assertTrue($this->run_('buscar_catalogo', ['q' => 'botas'], $this->ctx())['ok']);
    }

    public function test_customer_variables_are_never_resolved_without_verification(): void
    {
        Http::fake();
        $this->http([
            'config' => ['method' => 'GET', 'url' => 'https://api.example.com/c?e={{customer.email}}'],
            'rules' => ['ownership' => 'none'],
            'parameters' => [],
        ]);

        $r = $this->run_('consulta_externa', [], $this->ctx(['customer_email' => 'ana@example.com']));

        $this->assertSame('denied', $r['status']);
        Http::assertNothingSent();
    }

    public function test_customer_variables_resolve_when_verified(): void
    {
        Http::fake(['api.example.com/*' => Http::response(['items' => [['name' => 'ok']]])]);
        $this->http([
            'config' => ['method' => 'GET', 'url' => 'https://api.example.com/c?e={{customer.email}}&id={{customer.ps_id}}'],
            'parameters' => [],
        ]);

        $this->assertTrue($this->run_('consulta_externa', [], $this->verifiedCtx())['ok']);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'e=ana%40example.com') && str_contains($req->url(), 'id=77'));
    }

    public function test_write_actions_require_customer_confirmed(): void
    {
        $this->bridge([
            'key' => 'enviar',
            'config' => ['action' => 'order.send_email', 'payload' => ['order_id' => '{{order.id}}', 'type' => 'order_conf']],
            'response' => ['fields' => ['sent']],
            'rules' => ['ownership' => 'verified'],
        ]);
        $ps = $this->ps();
        $ps->shouldReceive('getOrderDetailByReference')->andReturn(['id' => 5]);
        $ps->shouldReceive('callAllowedAction')->once()
            ->withArgs(fn ($a, $p, $k) => $p['type'] === 'order_conf' && is_string($k) && $k !== '')
            ->andReturn(['sent' => true]);

        $denied = $this->run_('enviar', ['order_ref' => 'ABC'], $this->verifiedCtx());
        $this->assertSame('denied', $denied['status']);
        $this->assertStringContainsString('Pregunta antes', $denied['content']);

        $falsy = $this->run_('enviar', ['order_ref' => 'ABC', 'customer_confirmed' => 'true'], $this->verifiedCtx());
        $this->assertSame('denied', $falsy['status']);

        $ok = $this->run_('enviar', ['order_ref' => 'ABC', 'customer_confirmed' => true], $this->verifiedCtx());
        $this->assertTrue($ok['ok']);
    }

    public function test_forbidden_bridge_action_is_refused_at_runtime_even_if_inserted_bypassing_the_model(): void
    {
        AiAction::query()->getConnection()->table('helpdesk_ai_actions')->insert([
            'key' => 'cambiar_estado', 'name' => 'x', 'description' => 'x', 'type' => 'bridge', 'is_active' => 1,
            'config' => json_encode(['action' => 'order.change_status', 'payload' => ['order_id' => 1]]),
            'rules' => json_encode(['ownership' => 'verified', 'requires_verified' => true]),
            'parameters' => '[]', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->ps()->shouldNotReceive('callAllowedAction');

        $r = $this->run_('cambiar_estado', [], $this->verifiedCtx());

        $this->assertFalse($r['ok']);
        $this->assertSame('La acción no está disponible.', $r['content']);
    }

    public function test_unknown_or_inactive_actions_are_unavailable_and_logged(): void
    {
        $this->bridge(['is_active' => false]);

        $this->assertSame('error', $this->run_('documentos_pedido', [], $this->ctx())['status']);
        $this->assertSame('error', $this->run_('no_existe', [], $this->ctx())['status']);
        $this->assertSame(2, AiActionRun::query()->count());
    }

    public function test_rate_limit_per_conversation(): void
    {
        $this->bridge([
            'key' => 'mis_cupones',
            'config' => ['action' => 'customer.vouchers', 'payload' => []],
            'response' => ['fields' => ['vouchers.*.description']],
            'rules' => ['ownership' => 'verified', 'max_per_conversation' => 2],
        ]);
        $this->ps()->shouldReceive('callAllowedAction')->times(3)->andReturn(['vouchers' => [['description' => 'a']]]);
        $ctx = $this->verifiedCtx(['conversation_id' => 4242]);

        $this->assertTrue($this->run_('mis_cupones', [], $ctx)['ok']);
        $this->assertTrue($this->run_('mis_cupones', [], $ctx)['ok']);
        $this->assertSame('denied', $this->run_('mis_cupones', [], $ctx)['status']);
        $this->assertTrue($this->run_('mis_cupones', [], $this->verifiedCtx(['conversation_id' => 4243]))['ok'] ?? false);
    }

    public function test_every_execution_is_logged_with_redacted_args(): void
    {
        $this->bridge();
        $ps = $this->ps();
        $ps->shouldReceive('getOrderDetailByReference')->andReturn(null);

        $this->run_('documentos_pedido', ['order_ref' => 'ABCDEF', 'email' => 'ana@example.com'], $this->ctx(['conversation_id' => 11]), 'flow');

        $run = AiActionRun::query()->firstOrFail();
        $this->assertSame('denied', $run->status);
        $this->assertSame('flow', $run->source);
        $this->assertSame(11, $run->conversation_id);
        $this->assertStringNotContainsString('ana@example.com', json_encode($run->args_summary));
        $this->assertSame('ABCDEF', $run->args_summary['order_ref']);
    }

    public function test_secrets_never_reach_logs_runs_or_the_ai(): void
    {
        $secret = 'sk-live-ULTRASECRET123';
        Log::spy();
        Http::fake(['api.example.com/*' => fn () => throw new \RuntimeException("cURL error con Authorization: Bearer {$secret} y clave {$secret}")]);
        $this->http([
            'config' => ['method' => 'GET', 'url' => 'https://api.example.com/x', 'auth' => ['type' => 'bearer', 'secret' => 'token']],
            'secrets' => ['token' => $secret],
            'parameters' => [],
        ]);

        $r = $this->run_('consulta_externa', [], $this->ctx());

        $this->assertSame('error', $r['status']);
        $this->assertStringNotContainsString($secret, $r['content']);
        $this->assertStringNotContainsString($secret, (string) AiActionRun::query()->value('error'));
        $this->assertStringNotContainsString($secret, json_encode(AiActionRun::query()->get()->toArray()));
        Log::shouldHaveReceived('warning')->withArgs(fn ($m, $c = []) => ! str_contains(json_encode($c), $secret))->atLeast()->once();
    }

    public function test_response_is_filtered_redacted_and_truncated(): void
    {
        Http::fake(['api.example.com/*' => Http::response([
            'items' => [
                ['name' => 'Juan juan@example.com 612 345 678 ES9121000418450200051332 | 4111 1111 1111 1111', 'secret_internal' => 'NO', 'note' => 'x'],
                ['name' => str_repeat('largo ', 300)],
            ],
            'internal' => 'oculto',
        ])]);
        $this->http(['response' => ['fields' => ['items.*.name'], 'max_chars' => 400]]);

        $r = $this->run_('consulta_externa', ['q' => 'a b'], $this->ctx());

        $this->assertTrue($r['ok']);
        foreach (['juan@example.com', '612 345 678', 'ES9121', '4111', 'secret_internal', 'oculto'] as $leak) {
            $this->assertStringNotContainsString($leak, $r['content']);
        }
        $this->assertStringContainsString('[email]', $r['content']);
        $this->assertStringContainsString('[iban]', $r['content']);
        $this->assertStringContainsString('[tarjeta]', $r['content']);
        $this->assertLessThanOrEqual(401, mb_strlen($r['content']));
    }

    public function test_allow_pii_keeps_an_explicitly_allowed_field(): void
    {
        Http::fake(['api.example.com/*' => Http::response(['items' => [['name' => 'a@b.com']]])]);
        $this->http(['response' => ['fields' => ['items.*.name'], 'allow_pii' => ['items.*.name']]]);

        $this->assertStringContainsString('a@b.com', $this->run_('consulta_externa', ['q' => 'x'], $this->ctx())['content']);
    }

    public function test_empty_fields_fall_back_to_a_compact_whitelist(): void
    {
        Http::fake(['api.example.com/*' => Http::response(['id' => 3, 'name' => 'Bota', 'internal_cost' => 99, 'owner_email' => 'a@b.com'])]);
        $this->http(['response' => ['fields' => []]]);

        $content = $this->run_('consulta_externa', ['q' => 'x'], $this->ctx())['content'];

        $this->assertSame('{"id":3,"name":"Bota"}', $content);
    }

    public function test_empty_result_returns_the_configured_message(): void
    {
        Http::fake(['api.example.com/*' => Http::response(['items' => []])]);
        $this->http(['response' => ['fields' => ['items.*.name'], 'empty_message' => 'Nada por aquí.']]);

        $this->assertSame('Nada por aquí.', $this->run_('consulta_externa', ['q' => 'x'], $this->ctx())['content']);
    }

    public function test_invalid_arguments_are_rejected_before_calling_anything(): void
    {
        Http::fake();
        $this->http();

        $this->assertSame('error', $this->run_('consulta_externa', [], $this->ctx())['status']);
        Http::assertNothingSent();
    }

    public function test_channel_restriction_is_enforced(): void
    {
        Http::fake();
        $this->http(['channels' => ['email']]);

        $this->assertSame('error', $this->run_('consulta_externa', ['q' => 'x'], $this->ctx(['channel' => 'widget']))['status']);
    }
}
