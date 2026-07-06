<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskContacts\Database\Seeders\HelpdeskContactsPermissionsSeeder;
use Tests\Concerns\SeedsCorePermissions;
use Tests\TestCase;

/**
 * Tests for the Contacts CRM 360 tab JSON endpoints.
 *
 * Routes (prefix panel/contacts, middleware ['web', 'auth']):
 *   GET  panel/contacts/{customer}/tab/{resumen|conversaciones|chats|erp|
 *        prestashop|tienda|actividad}   — can:contacts.view
 *   POST panel/contacts/{customer}/sync — can:contacts.view + can:contacts.update
 *
 * Every tab is wrapped by ContactTabsController::tab(), which ALWAYS returns
 * { success: true, data: ... } and degrades a failing aggregation to
 * { success: true, data: { available: false } } instead of a 500. These tests
 * assert that resilient contract regardless of which optional modules (ERP,
 * PrestaShop, Remarketing, ...) are enabled in the test environment.
 */
class ContactTabsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsCorePermissions;

    /** Roll back writes on both the default and helpdesk connections. */
    protected $connectionsToTransact = [null, 'helpdesk'];

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Pulse is used inside CustomerInsightsService; stub it.
        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        // Prevent any optional integration from making a real outbound call.
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);

        config(['helpdeskErp.manager_url' => 'http://manager.test']);

        $this->seedCorePermissions();
        $this->seed(HelpdeskContactsPermissionsSeeder::class);

        // Happy-path: manager (helpdesk.manage) → forAgent() ve todos los contactos.
        // El aislamiento por bandeja de agentes restringidos se cubre en el test
        // de regresión de abajo (test_restricted_agent_cannot_read_foreign_customer_tab).
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('contacts.view');
        $this->user->givePermissionTo('contacts.update');
        $this->user->givePermissionTo('helpdesk.manage');

        $this->customer = Customer::factory()->create();
    }

    private function tabUrl(string $tab): string
    {
        return '/panel/contacts/'.$this->customer->id.'/tab/'.$tab;
    }

    // ── Authorization ──────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_from_a_tab(): void
    {
        $this->get($this->tabUrl('resumen'))
            ->assertRedirect();
    }

    public function test_user_without_view_permission_is_forbidden_on_tabs(): void
    {
        $noPerm = User::factory()->create();

        foreach (['resumen', 'conversaciones', 'chats', 'erp', 'prestashop', 'tienda', 'actividad'] as $tab) {
            $this->actingAs($noPerm)
                ->getJson($this->tabUrl($tab))
                ->assertForbidden();
        }
    }

    /**
     * Regresión IDOR: tener contacts.view NO basta: un agente restringido a sus
     * bandejas no puede leer los tabs (ERP/PS/tickets/actividad) de un cliente
     * que no está en ninguna de sus bandejas iterando el id de la ruta.
     */
    public function test_restricted_agent_cannot_read_foreign_customer_tab(): void
    {
        $inboxMine = Inbox::create(['name' => 'Inbox propia', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);

        $agent = User::factory()->create();
        $agent->givePermissionTo('contacts.view');
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $inboxMine->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        // Cliente de OTRA bandeja, sin relación con el agente.
        $foreign = Customer::factory()->create();
        Conversation::factory()->create(['customer_id' => $foreign->id, 'inbox_id' => Inbox::create([
            'name' => 'Inbox ajena', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true,
        ])->id]);

        foreach (['resumen', 'erp', 'prestashop', 'tienda', 'actividad', 'tickets'] as $tab) {
            $this->actingAs($agent)
                ->getJson('/panel/contacts/'.$foreign->id.'/tab/'.$tab)
                ->assertForbidden();
        }
    }

    /**
     * Regresión IDOR: el carrito asistido (mostrar/generar pedido) también aísla
     * por bandeja — assertVisible() corta antes de tocar el servicio PrestaShop,
     * así que un agente restringido no puede operar el carrito de un cliente ajeno
     * ni con el mismo dato que le daría un 422 de "no disponible".
     */
    public function test_restricted_agent_cannot_access_foreign_customer_cart(): void
    {
        $inboxMine = Inbox::create(['name' => 'Inbox propia', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);

        $agent = User::factory()->create();
        $agent->givePermissionTo('contacts.view');
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $inboxMine->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        $foreign = Customer::factory()->create();
        Conversation::factory()->create(['customer_id' => $foreign->id, 'inbox_id' => Inbox::create([
            'name' => 'Inbox ajena', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true,
        ])->id]);

        $this->actingAs($agent)
            ->getJson('/panel/contacts/'.$foreign->id.'/cart')
            ->assertForbidden();
    }

    // ── Resumen ────────────────────────────────────────────────────────────

    public function test_resumen_tab_returns_success_envelope_with_contract_keys(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->tabUrl('resumen'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);

        $data = $response->json('data');

        // The resumen aggregation can degrade to { available: false } if an
        // optional dependency or table is missing; otherwise it must expose
        // the full identity/stats/integrations contract. Either is valid, but
        // it must never be a 500 and must always be the success envelope.
        if (($data['available'] ?? null) === false) {
            $this->assertSame(['available' => false], $data);

            return;
        }

        $response->assertJsonStructure([
            'data' => [
                'name',
                'email',
                'isVerified',
                'isBanned',
                'location' => ['country', 'state', 'city', 'postalCode'],
                'stats' => ['totalConversations', 'totalPageVisits', 'healthScore'],
                'integrations',
            ],
        ])->assertJsonPath('data.email', $this->customer->email);
    }

    // ── Conversaciones ─────────────────────────────────────────────────────

    public function test_conversaciones_tab_returns_success_with_conversations_array(): void
    {
        $this->actingAs($this->user)
            ->getJson($this->tabUrl('conversaciones'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['conversations']]);
    }

    // ── Chats ──────────────────────────────────────────────────────────────

    public function test_chats_tab_returns_success_with_availability_flag(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->tabUrl('chats'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['available', 'chats']]);

        // available is a boolean regardless of whether livechat/social are on.
        $this->assertIsBool($response->json('data.available'));
        $this->assertIsArray($response->json('data.chats'));
    }

    // ── ERP (optional module / external service resilience) ─────────────────

    public function test_erp_tab_never_returns_500_and_keeps_success_envelope(): void
    {
        $this->actingAs($this->user)
            ->getJson($this->tabUrl('erp'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_erp_tab_reports_unavailable_when_customer_has_no_email(): void
    {
        $customer = Customer::factory()->create(['email' => null]);

        $this->actingAs($this->user)
            ->getJson('/panel/contacts/'.$customer->id.'/tab/erp')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.available', false);
    }

    // ── PrestaShop (optional module / external service resilience) ──────────

    public function test_prestashop_tab_never_returns_500_and_keeps_success_envelope(): void
    {
        $this->actingAs($this->user)
            ->getJson($this->tabUrl('prestashop'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_prestashop_tab_reports_unavailable_when_customer_has_no_email(): void
    {
        $customer = Customer::factory()->create(['email' => null]);

        $this->actingAs($this->user)
            ->getJson('/panel/contacts/'.$customer->id.'/tab/prestashop')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.available', false);
    }

    // ── Tienda (Remarketing mirror) ─────────────────────────────────────────

    public function test_tienda_tab_returns_success_with_availability_flag(): void
    {
        $this->actingAs($this->user)
            ->getJson($this->tabUrl('tienda'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['available']]);
    }

    public function test_tienda_tab_reports_unavailable_when_customer_has_no_email(): void
    {
        $customer = Customer::factory()->create(['email' => null]);

        $this->actingAs($this->user)
            ->getJson('/panel/contacts/'.$customer->id.'/tab/tienda')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.available', false);
    }

    // ── Actividad ──────────────────────────────────────────────────────────

    public function test_actividad_tab_returns_success_with_contract_sections(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->tabUrl('actividad'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);

        $data = $response->json('data');

        // Like resumen, actividad degrades to { available: false } when an
        // underlying section throws; otherwise it exposes its timeline contract.
        if (($data['available'] ?? null) === false) {
            $this->assertSame(['available' => false], $data);

            return;
        }

        $response->assertJsonStructure([
            'data' => ['timeline', 'csat', 'pageVisits', 'emails', 'tickets'],
        ]);
    }

    // ── Sync ───────────────────────────────────────────────────────────────

    public function test_sync_requires_authentication(): void
    {
        $this->postJson('/panel/contacts/'.$this->customer->id.'/sync')
            ->assertUnauthorized();
    }

    public function test_sync_is_forbidden_without_update_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('contacts.view');

        $this->actingAs($viewer)
            ->postJson('/panel/contacts/'.$this->customer->id.'/sync')
            ->assertForbidden();
    }

    public function test_sync_succeeds_with_update_permission(): void
    {
        $this->actingAs($this->user)
            ->postJson('/panel/contacts/'.$this->customer->id.'/sync')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['integrations']]);
    }
}
