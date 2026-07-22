<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskContacts\Database\Seeders\HelpdeskContactsPermissionsSeeder;
use Tests\Concerns\SeedsCorePermissions;
use Tests\TestCase;

/**
 * Tests for the Contacts CRM 360 list/detail pages.
 *
 * Routes (prefix panel/contacts, middleware ['web', 'auth']):
 *   GET panel/contacts                 contacts.index  — can:contacts.view
 *   GET panel/contacts/{customer}      contacts.show   — can:contacts.view
 *
 * The {customer} parameter resolves Modules\Helpdesk\Models\Customer on the
 * 'helpdesk' connection, so both connections must be wrapped in a transaction.
 */
class ContactsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsCorePermissions;

    /** Roll back writes on both the default and helpdesk connections. */
    protected $connectionsToTransact = [null, 'helpdesk'];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Pulse is used inside CustomerInsightsService; stub it so the suite
        // never touches the real Pulse storage.
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

        $this->seedCorePermissions();
        $this->seed(HelpdeskContactsPermissionsSeeder::class);

        // Usuario happy-path: manager (helpdesk.manage) → ve todos los contactos.
        // El aislamiento por inbox de agentes restringidos se cubre en sus
        // propios tests más abajo.
        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['contacts.view', 'helpdesk.manage']);
    }

    // ── Authorization ──────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_from_index(): void
    {
        $this->get('/panel/contacts')
            ->assertRedirect();
    }

    public function test_unauthenticated_user_is_redirected_from_show(): void
    {
        $customer = Customer::factory()->create();

        $this->get('/panel/contacts/'.$customer->id)
            ->assertRedirect();
    }

    public function test_authenticated_user_without_view_permission_is_forbidden_on_index(): void
    {
        $noPerm = User::factory()->create();

        $this->actingAs($noPerm)
            ->get('/panel/contacts')
            ->assertForbidden();
    }

    public function test_authenticated_user_without_view_permission_is_forbidden_on_show(): void
    {
        $noPerm = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($noPerm)
            ->get('/panel/contacts/'.$customer->id)
            ->assertForbidden();
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_index_renders_and_lists_a_seeded_customer(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Contacto Visible '.uniqid(),
        ]);

        $this->actingAs($this->user)
            ->get('/panel/contacts')
            ->assertOk()
            ->assertViewIs('contacts::contacts.index')
            ->assertViewHas('customers')
            ->assertSee($customer->name);
    }

    public function test_index_supports_search_query(): void
    {
        $needle = 'Unico'.uniqid();
        $customer = Customer::factory()->create(['name' => $needle]);
        Customer::factory()->create(['name' => 'Otro Contacto']);

        $this->actingAs($this->user)
            ->get('/panel/contacts?q='.$needle)
            ->assertOk()
            ->assertViewHas('q', $needle)
            ->assertSee($customer->name);
    }

    // ── Show ───────────────────────────────────────────────────────────────

    public function test_show_returns_200_for_existing_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->user)
            ->get('/panel/contacts/'.$customer->id)
            ->assertOk()
            ->assertViewIs('contacts::contacts.show')
            ->assertViewHas('customer');
    }

    public function test_show_returns_404_for_missing_customer(): void
    {
        $this->actingAs($this->user)
            ->get('/panel/contacts/999999999')
            ->assertNotFound();
    }

    public function test_show_falls_back_to_whatsapp_phone_when_phone_is_missing(): void
    {
        $customer = Customer::factory()->create([
            'phone' => null,
            'whatsapp_phone' => '34600111222',
        ]);

        $this->actingAs($this->user)
            ->get('/panel/contacts/'.$customer->id)
            ->assertOk()
            ->assertSee('34600111222');
    }

    // ── bulkAction (validación movida a BulkContactActionRequest) ────────────

    public function test_bulk_action_forbidden_without_update_permission(): void
    {
        $customer = Customer::factory()->create();

        // $this->user solo tiene contacts.view → authorize() del Form Request deniega.
        $this->actingAs($this->user)
            ->postJson(route('contacts.bulk-action'), ['action' => 'ban', 'ids' => [$customer->id]])
            ->assertForbidden();
    }

    public function test_bulk_action_validates_action_and_ids(): void
    {
        $editor = User::factory()->create();
        $editor->givePermissionTo(['contacts.view', 'contacts.update']);

        $this->actingAs($editor)
            ->postJson(route('contacts.bulk-action'), ['action' => 'explode', 'ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action', 'ids']);
    }

    public function test_bulk_action_bans_selected_contacts(): void
    {
        $editor = User::factory()->create();
        $editor->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);
        $customer = Customer::factory()->create(['banned_at' => null]);

        $this->actingAs($editor)
            ->postJson(route('contacts.bulk-action'), ['action' => 'ban', 'ids' => [$customer->id]])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($customer->fresh()->banned_at, 'La acción ban debe marcar banned_at.');
    }

    public function test_bulk_action_unban_clears_ban_reason(): void
    {
        $editor = User::factory()->create();
        $editor->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);
        $customer = Customer::factory()->create([
            'banned_at' => now(),
            'ban_reason' => 'spam',
        ]);

        $this->actingAs($editor)
            ->postJson(route('contacts.bulk-action'), ['action' => 'unban', 'ids' => [$customer->id]])
            ->assertOk()
            ->assertJsonPath('success', true);

        $fresh = $customer->fresh();
        $this->assertNull($fresh->banned_at, 'La acción unban debe limpiar banned_at.');
        $this->assertNull($fresh->ban_reason, 'La acción unban debe limpiar también ban_reason.');
    }

    // ── Aislamiento por inbox (regresión de seguridad) ───────────────────────

    public function test_restricted_agent_only_sees_contacts_in_assigned_inboxes(): void
    {
        $inboxA = Inbox::create(['name' => 'Inbox A', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        $inboxB = Inbox::create(['name' => 'Inbox B', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);

        $mine = Customer::factory()->create(['name' => 'ClienteMio'.uniqid()]);
        $theirs = Customer::factory()->create(['name' => 'ClienteAjeno'.uniqid()]);
        Conversation::factory()->create(['customer_id' => $mine->id, 'inbox_id' => $inboxA->id]);
        Conversation::factory()->create(['customer_id' => $theirs->id, 'inbox_id' => $inboxB->id]);

        $agent = User::factory()->create();
        $agent->givePermissionTo('contacts.view');
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $inboxA->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        // La lista solo muestra el cliente de su inbox.
        $this->actingAs($agent)
            ->get('/panel/contacts')
            ->assertOk()
            ->assertSee($mine->name)
            ->assertDontSee($theirs->name);

        // El detalle de un cliente de otro inbox → 403; el propio → 200.
        $this->actingAs($agent)->get('/panel/contacts/'.$theirs->id)->assertForbidden();
        $this->actingAs($agent)->get('/panel/contacts/'.$mine->id)->assertOk();
    }

    public function test_manager_sees_contacts_of_any_inbox(): void
    {
        $inboxB = Inbox::create(['name' => 'Inbox B', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        $theirs = Customer::factory()->create(['name' => 'ClienteAjeno'.uniqid()]);
        Conversation::factory()->create(['customer_id' => $theirs->id, 'inbox_id' => $inboxB->id]);

        // $this->user tiene helpdesk.manage → sin restricción de inbox.
        $this->actingAs($this->user)
            ->get('/panel/contacts/'.$theirs->id)
            ->assertOk();
    }

    public function test_export_neutralizes_csv_formula_injection(): void
    {
        Customer::factory()->create(['name' => '=cmd|calc', 'email' => 'evil@example.test']);

        $response = $this->actingAs($this->user)->get(route('contacts.export'));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=cmd|calc", $csv, 'El valor con = debe prefijarse con comilla.');
    }
}
