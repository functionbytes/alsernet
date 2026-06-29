<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Database\Seeders\HelpdeskContactsPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskContactsPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('contacts.view');
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
}
