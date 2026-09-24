<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\AI\AiClient;
use Modules\Helpdesk\Services\AI\SuggestReplyService;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Modules\HelpdeskErp\Listeners\ErpAssistAiReplyContext;
use Modules\HelpdeskErp\Services\ErpAssist\ErpAssistCachedData;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatService;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Contexto de Gestión (ERP) para las sugerencias de IA: solo caché, con
 * permisos y sin datos sensibles.
 */
class ErpAssistAiContextTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    // Id ERP ficticio: el real de pruebas (101544116) ya está vinculado a un
    // cliente de la base de desarrollo y el índice único (platform,
    // external_id) impide vincularlo a los clientes del test.
    private const ERP = '990000017';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'helpdeskErp.manager_url' => 'http://manager.test',
            'helpdeskErp.bridge_token' => '',
        ]);
        Cache::flush();
        Http::preventStrayRequests();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskErpPermissionsSeeder::class);

        if (! function_exists('helpdesk_erp_enabled') || ! helpdesk_erp_enabled()) {
            $this->markTestSkipped('La integración ERP no está activa en esta base.');
        }
    }

    public function test_peek_reads_exactly_what_erp_chat_service_cached(): void
    {
        $this->fakeManager();
        $peek = app(ErpAssistCachedData::class);

        $this->assertNull($peek->peek((int) self::ERP, 'orders', $peek->overviewOrdersParams()));

        app(ErpChatService::class)->many((int) self::ERP, [
            'orders' => ['orders', $peek->overviewOrdersParams()],
            'summary' => ['summary', []],
        ]);

        $orders = $peek->peek((int) self::ERP, 'orders', $peek->overviewOrdersParams());
        $this->assertSame('ok', $orders['state'] ?? null);
        $numbers = array_map(fn ($o) => (string) ($o['number'] ?? ''), (array) ($orders['data'] ?? []));
        sort($numbers);
        $this->assertSame(['44398', '47427'], $numbers);
        $this->assertSame('ok', $peek->peek((int) self::ERP, 'summary')['state'] ?? null);
    }

    public function test_empty_cache_adds_nothing_and_never_calls_the_manager(): void
    {
        Http::fake();
        [$conversation] = $this->conversation();
        $agent = $this->agent(['helpdeskerp.view', 'helpdeskerp.orders.view', 'helpdeskerp.loyalty.view']);

        $this->assertNull(app(ErpAssistAiReplyContext::class)->handle($conversation, $agent));
        Http::assertNothingSent();
    }

    public function test_requires_orders_permission(): void
    {
        $this->warmCache();
        [$conversation] = $this->conversation();

        $viewOnly = $this->agent(['helpdeskerp.view']);
        $this->assertNull(app(ErpAssistAiReplyContext::class)->handle($conversation, $viewOnly));

        $noView = $this->agent(['helpdeskerp.orders.view']);
        $this->assertNull(app(ErpAssistAiReplyContext::class)->handle($conversation, $noView));

        $this->assertNull(app(ErpAssistAiReplyContext::class)->handle($conversation, null));
    }

    public function test_summary_has_orders_and_points_without_sensitive_data(): void
    {
        $this->warmCache();
        [$conversation] = $this->conversation();
        $agent = $this->agent(['helpdeskerp.view', 'helpdeskerp.orders.view', 'helpdeskerp.loyalty.view']);

        $sent = count(Http::recorded());
        $text = (string) app(ErpAssistAiReplyContext::class)->handle($conversation, $agent);

        // Solo caché: ninguna llamada nueva al manager.
        $this->assertCount($sent, Http::recorded());
        $this->assertStringContainsString('Alberto Marcos Camarzana', $text);
        $this->assertStringContainsString('Pedidos en Gestión: 2', $text);
        $this->assertStringContainsString('Último pedido: nº 47427 del 23/09/2025, estado Servido, servido el', $text);
        $this->assertStringContainsString('Puntos de fidelización: 53', $text);
        $this->assertStringContainsString('No acepta información comercial', $text);

        foreach (['45688302K', '4111', '1234', '100404753', '620429277', 'albertomarcoscamarzana@gmail.com'] as $secret) {
            $this->assertStringNotContainsString($secret, $text);
        }
    }

    public function test_points_need_loyalty_permission(): void
    {
        $this->warmCache();
        [$conversation] = $this->conversation();
        $agent = $this->agent(['helpdeskerp.view', 'helpdeskerp.orders.view']);

        $text = (string) app(ErpAssistAiReplyContext::class)->handle($conversation, $agent);

        $this->assertStringContainsString('Último pedido', $text);
        $this->assertStringNotContainsString('Puntos', $text);
    }

    public function test_suggest_reply_prompt_receives_the_erp_context(): void
    {
        $this->warmCache();
        [$conversation] = $this->conversation();
        $agent = $this->agent(['helpdeskerp.view', 'helpdeskerp.orders.view']);
        $this->actingAs($agent);

        $raw = Event::getRawListeners()[SuggestReplyService::CONTEXT_EVENT] ?? [];
        if (! in_array(ErpAssistAiReplyContext::class, $raw, true)) {
            Event::listen(SuggestReplyService::CONTEXT_EVENT, ErpAssistAiReplyContext::class);
        }

        $prompt = null;
        $client = Mockery::mock(AiClient::class);
        $client->shouldReceive('isEnabled')->andReturn(true);
        $client->shouldReceive('chat')->once()->andReturnUsing(function (array $messages) use (&$prompt) {
            $prompt = $messages[1]['content'] ?? null;

            return '["Hola"]';
        });
        $this->app->instance(AiClient::class, $client);

        $this->assertSame(['Hola'], app(SuggestReplyService::class)->suggest($conversation));
        $this->assertIsString($prompt);
        $this->assertStringContainsString('Datos de Gestión (ERP)', $prompt);
        $this->assertStringContainsString('nº 47427', $prompt);
    }

    /* ── Utilidades ───────────────────────────────────────────────── */

    private function warmCache(): void
    {
        $this->fakeManager();
        $peek = app(ErpAssistCachedData::class);

        app(ErpChatService::class)->many((int) self::ERP, [
            'summary' => ['summary', []],
            'orders' => ['orders', $peek->overviewOrdersParams()],
            'loyalty_points' => ['loyalty-points', []],
        ]);
    }

    /**
     * @return array{0: Conversation, 1: Customer}
     */
    private function conversation(): array
    {
        $customer = Customer::factory()->create([
            'email' => Str::lower(Str::replace('-', '', (string) Str::uuid())).'@test.example',
        ]);
        $customer->linkExternalId('erp', self::ERP);

        $conversation = Conversation::factory()->create(['channel' => 'web', 'customer_id' => $customer->id]);

        return [$conversation->fresh(), $customer];
    }

    private function agent(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function fakeManager(): void
    {
        $base = '/api/erp/customer/'.self::ERP;

        Http::fake(function (Request $request) use ($base) {
            $sub = trim(substr((string) parse_url($request->url(), PHP_URL_PATH), strlen($base)), '/');

            return match ($sub) {
                '' => Http::response(['success' => true, 'data' => [
                    'id' => (int) self::ERP, 'label' => 'ALBERTO', 'surnames' => 'MARCOS CAMARZANA', 'cif' => '45688302K',
                    'email' => 'albertomarcoscamarzana@gmail.com', 'code_internet' => '911230', 'available' => true,
                    'card' => '100404753',
                    'phones' => [['id' => 100705411, 'number' => '620429277']],
                    'cards' => [['id' => 7, 'number' => '4111 1111 1111 1234']],
                    'lopd' => ['accepted' => true, 'no_commercial_info' => true],
                ]]),
                'orders' => Http::response(['success' => true, 'data' => [
                    ['id' => '10102138690', 'number' => '44398', 'status' => '7', 'date' => '2025-09-16 20:06:39', 'served_date' => '2025-09-18 00:00:00'],
                    ['id' => '10102142050', 'number' => '47427', 'status' => '7', 'date' => '2025-09-23 11:37:08', 'served_date' => '2025-09-30 00:00:00'],
                ], 'pagination' => ['limit' => 10, 'offset' => 0, 'count' => 2, 'hasMore' => false]]),
                'loyalty-points' => Http::response(['success' => true, 'data' => [
                    'main_card' => '100404753', 'balance' => 53, 'movements' => [],
                ]]),
                default => Http::response(['success' => false], 404),
            };
        });
    }
}
