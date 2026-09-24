<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Database\Seeders\HelpdeskErpPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Rutas manager.helpdesk.erp.chat.*: permisos por sección, alcance por
 * bandeja, cliente sin vínculo y resumen con alertas.
 */
class ErpChatEndpointsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const ERP = '101544116';

    private const BASE = 'http://manager.test/api/erp/customer/101544116';

    private const GRANT = ['success' => false, 'error' => 'Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.'];

    private const ALL_CHAT = [
        'helpdeskerp.view',
        'helpdeskerp.orders.view',
        'helpdeskerp.addresses.view',
        'helpdeskerp.finance.view',
        'helpdeskerp.loyalty.view',
    ];

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
    }

    /* ── Permisos ─────────────────────────────────────────────────────── */

    public function test_without_view_permission_everything_is_forbidden(): void
    {
        Http::fake();
        $customer = $this->linkedCustomer();
        $user = $this->agent(['helpdeskerp.orders.view']);

        $this->actingAs($user)->getJson($this->url($customer, 'overview'))->assertForbidden();
        $this->actingAs($user)->getJson($this->url($customer, 'sections/orders'))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_each_section_requires_its_own_permission(): void
    {
        $this->fakeManager();
        $customer = $this->linkedCustomer();
        $user = $this->agent(['helpdeskerp.view', 'helpdeskerp.orders.view']);

        $this->actingAs($user)->getJson($this->url($customer, 'sections/balance'))->assertForbidden();
        $this->actingAs($user)->getJson($this->url($customer, 'sections/vouchers'))->assertForbidden();
        $this->actingAs($user)->getJson($this->url($customer, 'sections/addresses'))->assertForbidden();
        $this->actingAs($user)->getJson($this->url($customer, 'invoices/55'))->assertForbidden();

        $this->actingAs($user)->getJson($this->url($customer, 'sections/orders'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('section', 'orders')
            ->assertJsonPath('state', 'ok')
            ->assertJsonPath('data.0.number', '47427');

        // Albaranes: vale con pedidos O con finanzas.
        $this->actingAs($user)->getJson($this->url($customer, 'sections/delivery-notes'))->assertOk();
    }

    public function test_cards_and_accounts_require_sensitive_permission(): void
    {
        $this->fakeManager();
        $customer = $this->linkedCustomer();

        $agent = $this->agent(self::ALL_CHAT);
        $this->actingAs($agent)->getJson($this->url($customer, 'sections/cards'))->assertForbidden();
        $this->actingAs($agent)->getJson($this->url($customer, 'sections/accounts'))->assertForbidden();

        $manager = $this->agent([...self::ALL_CHAT, 'helpdeskerp.sensitive.view']);
        $this->actingAs($manager)->getJson($this->url($customer, 'sections/cards'))
            ->assertOk()
            ->assertJsonPath('state', 'blocked')
            ->assertJsonPath('message', 'Pendiente de permiso en Oracle');
    }

    public function test_seeder_gives_chat_permissions_to_helpdesk_roles(): void
    {
        $agentRole = Role::where('name', 'helpdesk-agent')->first();
        $managerRole = Role::where('name', 'helpdesk-manager')->first();

        if (! $agentRole || ! $managerRole) {
            $this->markTestSkipped('Roles del helpdesk no sembrados en esta base.');
        }

        $this->assertTrue($agentRole->hasPermissionTo('helpdeskerp.finance.view'));
        $this->assertFalse($agentRole->hasPermissionTo('helpdeskerp.sensitive.view'));
        $this->assertTrue($managerRole->hasPermissionTo('helpdeskerp.sensitive.view'));
    }

    public function test_unknown_section_is_not_routed(): void
    {
        $customer = $this->linkedCustomer();

        $this->actingAs($this->agent(self::ALL_CHAT))
            ->getJson($this->url($customer, 'sections/clearCache'))
            ->assertNotFound();
    }

    public function test_invalid_filters_are_rejected(): void
    {
        Http::fake();
        $customer = $this->linkedCustomer();
        $user = $this->agent(self::ALL_CHAT);

        $this->actingAs($user)->getJson($this->url($customer, 'sections/orders?limit=500'))->assertUnprocessable();
        $this->actingAs($user)->getJson($this->url($customer, 'sections/orders?from=01-01-2025'))->assertUnprocessable();
        $this->actingAs($user)->getJson($this->url($customer, 'sections/orders?status=1%27%20OR'))->assertUnprocessable();
        Http::assertNothingSent();
    }

    /* ── Alcance del cliente ──────────────────────────────────────────── */

    public function test_customer_outside_agent_inboxes_is_forbidden(): void
    {
        Http::fake();
        $customer = $this->linkedCustomer();
        $agent = $this->agent(self::ALL_CHAT, scoped: false);

        $this->actingAs($agent)->getJson($this->url($customer, 'overview'))->assertForbidden();
        $this->actingAs($agent)->getJson($this->url($customer, 'orders/10102138690'))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_unknown_customer_is_not_found(): void
    {
        $this->actingAs($this->agent(self::ALL_CHAT))
            ->getJson(route('manager.helpdesk.erp.chat.overview', ['customer' => 99999999]))
            ->assertNotFound();
    }

    public function test_customer_without_erp_link_is_unlinked(): void
    {
        Http::fake();
        $customer = $this->customer();
        $user = $this->agent(self::ALL_CHAT);

        $this->actingAs($user)->getJson($this->url($customer, 'overview'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('state', 'unlinked')
            ->assertJsonPath('data', null);

        // Un id ERP no numérico (resto de importaciones) tampoco vale.
        $customer->linkExternalId('erp', 'CTEST'.Str::upper(Str::random(8)));
        $this->actingAs($user)->getJson($this->url($customer, 'sections/orders'))
            ->assertOk()
            ->assertJsonPath('state', 'unlinked');

        Http::assertNothingSent();
    }

    /* ── Resumen ──────────────────────────────────────────────────────── */

    public function test_overview_aggregates_sections_and_computes_alerts(): void
    {
        $this->fakeManager();
        $customer = $this->linkedCustomer();

        $response = $this->actingAs($this->agent(self::ALL_CHAT))
            ->getJson($this->url($customer, 'overview'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('state', 'ok')
            ->assertJsonPath('data.erp_id', 101544116)
            ->assertJsonPath('data.links.prestashop_customer_id', '911230')
            ->assertJsonPath('data.sections.summary.state', 'ok')
            ->assertJsonPath('data.sections.orders.state', 'ok')
            ->assertJsonPath('data.sections.balance.state', 'blocked')
            ->assertJsonPath('data.sections.bonuses.state', 'blocked')
            ->assertJsonPath('data.sections.loyalty_points.data.balance', 53)
            ->assertJsonPath('data.realtime', null);

        $codes = collect($response->json('data.alerts'))->pluck('code')->all();

        foreach (['risk_exceeded', 'pending_debt', 'no_commercial_consent', 'voucher_expiring', 'order_served'] as $code) {
            $this->assertContains($code, $codes);
        }
        $this->assertNotContains('inactive', $codes);

        $served = collect($response->json('data.alerts'))->firstWhere('code', 'order_served');
        $this->assertSame(['open' => 'order', 'order_id' => '10102142050'], $served['action']);

        $debt = collect($response->json('data.alerts'))->firstWhere('code', 'pending_debt');
        $this->assertSame('Deuda pendiente: 1.250,50 € (2 albaranes).', $debt['text']);

        // El resumen se sirve en una sola ida al manager, sin pedir secciones sueltas de más.
        Http::assertSentCount(8);
    }

    public function test_overview_marks_sections_without_permission_as_forbidden(): void
    {
        $this->fakeManager();
        $customer = $this->linkedCustomer();

        $this->actingAs($this->agent(['helpdeskerp.view', 'helpdeskerp.orders.view']))
            ->getJson($this->url($customer, 'overview'))
            ->assertOk()
            ->assertJsonPath('data.sections.orders.state', 'ok')
            ->assertJsonPath('data.sections.balance.state', 'forbidden')
            ->assertJsonPath('data.sections.debts.data', null)
            ->assertJsonPath('data.sections.vouchers.state', 'forbidden');

        Http::assertNotSent(fn (Request $r): bool => str_contains($r->url(), '/debts') || str_contains($r->url(), '/vouchers'));
        Http::assertSentCount(2);
    }

    public function test_overview_while_orders_load_exposes_realtime_channel(): void
    {
        $this->fakeManager(ordersLoading: true);
        $customer = $this->linkedCustomer();

        $this->actingAs($this->agent(self::ALL_CHAT))
            ->getJson($this->url($customer, 'overview'))
            ->assertOk()
            ->assertJsonPath('data.sections.orders.state', 'loading')
            ->assertJsonPath('data.sections.orders.retry_after', 35)
            ->assertJsonPath('data.realtime.channel', 'erp-orders-ready.'.md5(strtolower($customer->email)))
            ->assertJsonPath('data.realtime.event', '.erp.orders.ready');
    }

    public function test_deregistered_customer_raises_inactive_alert(): void
    {
        Http::fake(fn (Request $r) => Http::response(['success' => false, 'error' => 'Customer not found'], 404));
        $customer = $this->linkedCustomer();

        $response = $this->actingAs($this->agent(self::ALL_CHAT))
            ->getJson($this->url($customer, 'overview'))
            ->assertOk()
            ->assertJsonPath('state', 'unavailable');

        $this->assertContains('inactive', collect($response->json('data.alerts'))->pluck('code')->all());
    }

    /* ── Detalles ─────────────────────────────────────────────────────── */

    public function test_order_detail_bundles_history_and_shipping_states(): void
    {
        $this->fakeManager();
        $customer = $this->linkedCustomer();

        $this->actingAs($this->agent(self::ALL_CHAT))
            ->getJson($this->url($customer, 'orders/10102138690'))
            ->assertOk()
            ->assertJsonPath('state', 'ok')
            ->assertJsonPath('data.order.number', '44398')
            ->assertJsonPath('data.order.lines.0.article.code', 'C307232-42')
            ->assertJsonPath('data.history.state', 'unavailable')
            ->assertJsonPath('data.shipping.state', 'ok')
            ->assertJsonPath('data.delivery_notes.0.number', '43696');
    }

    public function test_order_of_another_customer_is_not_found(): void
    {
        $this->fakeManager();
        $customer = $this->linkedCustomer();

        $this->actingAs($this->agent(self::ALL_CHAT))
            ->getJson($this->url($customer, 'orders/777'))
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('state', 'unavailable');
    }

    public function test_delivery_note_and_blocked_invoice(): void
    {
        $this->fakeManager();
        $customer = $this->linkedCustomer();
        $user = $this->agent(self::ALL_CHAT);

        $this->actingAs($user)->getJson($this->url($customer, 'delivery-notes/10101961890'))
            ->assertOk()
            ->assertJsonPath('state', 'ok')
            ->assertJsonPath('data.totals.lines_total_with_taxes', 39.99);

        $this->actingAs($user)->getJson($this->url($customer, 'invoices/101183768'))
            ->assertOk()
            ->assertJsonPath('state', 'blocked');
    }

    /* ── Datos sensibles ──────────────────────────────────────────────── */

    public function test_summary_never_exposes_cards_or_addresses_without_permission(): void
    {
        $this->fakeManager();
        $customer = $this->linkedCustomer();

        $response = $this->actingAs($this->agent(['helpdeskerp.view', 'helpdeskerp.orders.view']))
            ->getJson($this->url($customer, 'overview'))
            ->assertOk()
            ->assertJsonPath('data.sections.summary.data.cards', [])
            ->assertJsonPath('data.sections.summary.data.addresses', [])
            ->assertJsonPath('data.sections.summary.data.statistics.cards.total', null);
        $this->assertStringNotContainsString('4111', $response->getContent());

        // La sección suelta también se recorta, y con la caché ya caliente.
        $this->actingAs($this->agent(self::ALL_CHAT))
            ->getJson($this->url($customer, 'sections/summary'))
            ->assertOk()
            ->assertJsonPath('data.cards', [])
            ->assertJsonPath('data.addresses.0.street', 'QUIJADAS 2');
    }

    public function test_card_numbers_and_ibans_are_masked_even_with_sensitive_permission(): void
    {
        $this->fakeManager();
        $customer = $this->linkedCustomer();
        $manager = $this->agent([...self::ALL_CHAT, 'helpdeskerp.sensitive.view']);

        $this->actingAs($manager)->getJson($this->url($customer, 'sections/summary'))
            ->assertOk()
            ->assertJsonPath('data.cards.0.number', '************1234')
            ->assertJsonPath('data.cards.0.holder', 'ALBERTO MARCOS');

        $this->actingAs($manager)->getJson($this->url($customer, 'sections/accounts'))
            ->assertOk()
            ->assertJsonPath('data.accounts.0.iban', 'ES91****************1332')
            ->assertJsonPath('data.accounts.0.legacy.number', '******1332');

        // Tampoco queda el PAN en la caché compartida.
        $cached = Cache::get('helpdeskerp:chat:'.self::ERP.':v0:summary:'.md5('[]'));
        $this->assertIsArray($cached);
        $this->assertSame('************1234', $cached['data']['cards'][0]['number']);
    }

    /* ── Endpoints nuevos del manager ─────────────────────────────────── */

    public function test_returns_section_passes_real_shape_and_pagination(): void
    {
        $this->fakeManager();
        $customer = $this->linkedCustomer();

        $this->actingAs($this->agent(self::ALL_CHAT))
            ->getJson($this->url($customer, 'sections/returns?limit=20'))
            ->assertOk()
            ->assertJsonPath('state', 'ok')
            ->assertJsonPath('data.0.kind', 'devolucion')
            ->assertJsonPath('data.0.order_id', 10102138690)
            ->assertJsonPath('pagination.has_more', false);
    }

    public function test_returns_endpoint_missing_or_failing_is_unavailable_not_error(): void
    {
        $customer = $this->linkedCustomer();
        $user = $this->agent(self::ALL_CHAT);

        Http::fake(['*' => Http::response('<!DOCTYPE html><title>Not Found</title>', 404)]);
        $this->actingAs($user)->getJson($this->url($customer, 'sections/returns'))
            ->assertOk()->assertJsonPath('state', 'unavailable');

        Cache::flush();
        Http::fake(['*' => Http::response(['success' => false, 'error' => 'ORA-00904: invalid identifier'], 500)]);
        $this->actingAs($user)->getJson($this->url($customer, 'sections/returns?force=1'))
            ->assertOk()->assertJsonPath('state', 'unavailable');
    }

    public function test_history_not_available_answer_is_unavailable_with_manager_message(): void
    {
        $customer = $this->linkedCustomer();

        Http::fake(function (Request $r) {
            $path = (string) parse_url($r->url(), PHP_URL_PATH);

            return match (true) {
                str_ends_with($path, '/history') => Http::response(['success' => false, 'error' => 'not_available',
                    'message' => 'El ERP no expone un histórico de estados del pedido al usuario de lectura.']),
                str_ends_with($path, '/shipping') => Http::response(['success' => true, 'data' => ['carrier' => null, 'delivery_notes' => []]]),
                default => Http::response(['success' => true, 'data' => ['id' => 10102138690, 'number' => '44398', 'lines' => []]]),
            };
        });

        $this->actingAs($this->agent(self::ALL_CHAT))
            ->getJson($this->url($customer, 'orders/10102138690'))
            ->assertOk()
            ->assertJsonPath('data.history.state', 'unavailable')
            ->assertJsonPath('data.history.message', 'El ERP no expone un histórico de estados del pedido al usuario de lectura.')
            ->assertJsonPath('data.delivery_notes', []);
    }

    public function test_legacy_order_route_requires_order_detail_permission(): void
    {
        Http::fake();
        $customer = $this->linkedCustomer();

        $this->actingAs($this->agent(['helpdeskerp.view']))
            ->getJson('/panel/helpdesk/erp/orders/'.self::ERP.'/10102138690')
            ->assertForbidden();
        Http::assertNothingSent();
    }

    /* ── Fixtures ─────────────────────────────────────────────────────── */

    private function url(Customer $customer, string $path): string
    {
        return '/panel/helpdesk/customers/'.$customer->id.'/erp/'.$path;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function agent(array $permissions, bool $scoped = true): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        if ($scoped) {
            // Atajo de CustomerPolicy::sharesInboxWith para perfiles que ven
            // todas las bandejas (igual que ErpRelinkEndpointTest).
            $user->givePermissionTo('helpdesk.customers.manage');
        }

        return $user;
    }

    private function customer(): Customer
    {
        return Customer::factory()->create([
            'email' => Str::lower(Str::replace('-', '', (string) Str::uuid())).'@test.example',
        ]);
    }

    private function linkedCustomer(): Customer
    {
        $customer = $this->customer();
        $customer->linkExternalId('erp', self::ERP);

        return $customer;
    }

    /**
     * Manager falso con las respuestas reales del cliente 101544116 (fechas
     * relativas a hoy para las alertas).
     */
    private function fakeManager(bool $ordersLoading = false): void
    {
        $today = now()->format('Y-m-d');
        $soon = now()->addDays(3)->format('Y-m-d');

        Http::fake(function (Request $request) use ($today, $soon, $ordersLoading) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $sub = trim(substr($path, strlen('/api/erp/customer/'.self::ERP)), '/');

            return match (true) {
                $sub === '' => Http::response(['success' => true, 'data' => [
                    'id' => 101544116, 'label' => 'ALBERTO', 'surnames' => 'MARCOS CAMARZANA', 'cif' => '45688302K',
                    'email' => 'albertomarcoscamarzana@gmail.com', 'code_internet' => '911230', 'available' => true,
                    'phones' => [['id' => 100705411, 'number' => '620429277']],
                    'addresses' => [['id' => 100586990, 'street' => 'QUIJADAS 2', 'city' => 'CASTROVERDE DE CAMPOS', 'province' => 'ZAMORA']],
                    'cards' => [['id' => 7, 'card_id' => 3, 'number' => '4111 1111 1111 1234', 'holder' => 'ALBERTO MARCOS', 'expires' => '2027-01-31']],
                    'statistics' => ['cards' => ['total' => 1], 'addresses' => ['total' => 1]],
                    'lopd' => ['accepted' => true, 'accepted_at' => '2025-09-16', 'no_commercial_info' => true],
                ]]),
                $sub === 'addresses' => Http::response(['success' => true, 'data' => ['id' => 101544116, 'addresses' => []]]),
                $sub === 'orders' => $ordersLoading
                    ? Http::response(['success' => true, 'data' => [], 'pagination' => ['limit' => 10, 'offset' => 0, 'count' => 0, 'hasMore' => false], 'meta' => ['loading' => true, 'retry_after' => 35]])
                    : Http::response(['success' => true, 'data' => [
                        ['id' => '10102142050', 'number' => '47427', 'status' => '7', 'date' => '2025-09-23 11:37:08', 'served_date' => $today.' 00:00:00'],
                        ['id' => '10102138690', 'number' => '44398', 'status' => '7', 'date' => '2025-09-16 20:06:39', 'served_date' => '2025-09-18 00:00:00'],
                    ], 'pagination' => ['limit' => 10, 'offset' => 0, 'count' => 2, 'hasMore' => false]]),
                $sub === 'orders/10102138690' => Http::response(['success' => true, 'data' => [
                    'id' => 10102138690, 'number' => '44398', 'status' => true, 'status_description' => 'Creacion',
                    'lines' => [['id' => 1, 'article' => ['code' => 'C307232-42', 'description' => 'PANTALON HART'], 'units' => 1, 'price' => 41.314, 'subtotal' => 41.31]],
                    'payments' => [], 'totals' => ['lines_total' => 99.98, 'payments_total' => 0],
                ]]),
                $sub === 'orders/777' => Http::response(['success' => false, 'error' => 'Order not found for this customer'], 404),
                $sub === 'orders/10102138690/history' => Http::response('<!DOCTYPE html><title>Not Found</title>', 404),
                $sub === 'orders/10102138690/shipping' => Http::response(['success' => true, 'data' => [
                    'carrier' => null, 'tracking_number' => null, 'tracking_url' => null, 'shipped_at' => null,
                    'delivery_notes' => [['id' => 10101952248, 'number' => '43696', 'date' => '2025-09-30']],
                ]]),
                $sub === 'delivery-notes' => Http::response(['success' => true, 'data' => [], 'pagination' => ['limit' => 10, 'offset' => 0, 'count' => 0, 'hasMore' => false]]),
                $sub === 'delivery-notes/10101961890' => Http::response(['success' => true, 'data' => [
                    'id' => 10101961890, 'number' => '51498', 'lines' => [],
                    'totals' => ['lines_total_bi' => 33.05, 'lines_total_with_taxes' => 39.99, 'lines_total_net' => 47.98],
                ]]),
                $sub === 'debts' => Http::response(['success' => true, 'data' => [
                    'id' => 101544116, 'risk' => ['current' => 1500.0, 'max_allowed' => 1000.0],
                    'debts' => [], 'statistics' => ['debts' => ['total' => 2, 'amount_total' => 1250.5]],
                ]]),
                $sub === 'vouchers' => Http::response(['success' => true, 'data' => [
                    'id' => 101544116,
                    'vouchers' => [
                        ['id' => 1, 'amount' => 10.0, 'valid_until' => $soon, 'cancelled_at' => null, 'available' => true],
                        ['id' => 2, 'amount' => 5.0, 'valid_until' => '2020-01-01', 'cancelled_at' => null, 'available' => true],
                    ],
                ]]),
                $sub === 'accounts' => Http::response(['success' => true, 'data' => [
                    'id' => 101544116, 'accounts' => [['id' => 9, 'iban' => 'ES9121000418450200051332', 'legacy' => ['number' => '0200051332']]],
                ]]),
                $sub === 'returns' => Http::response(['success' => true, 'data' => [
                    ['id' => 10101952174, 'kind' => 'devolucion', 'number' => '2817', 'date' => '2025-09-30 15:43:47', 'amount' => -49.99,
                        'delivery_note_id' => 10101949389, 'invoice_id' => null, 'order_id' => 10102138690],
                ], 'pagination' => ['limit' => 20, 'offset' => 0, 'count' => 1, 'hasMore' => false]]),
                $sub === 'loyalty-points' => Http::response(['success' => true, 'data' => ['id' => 101544116, 'balance' => 53, 'movements' => []]]),
                default => Http::response(self::GRANT, 200), // balance, bonuses, cards, invoices…
            };
        });
    }
}
