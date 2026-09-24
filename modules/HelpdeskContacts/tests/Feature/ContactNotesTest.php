<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Actions\Customers\CustomerMergeAction;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerNote;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Notas internas con autor y fecha de la ficha 360 (bloque "Notas internas").
 */
class ContactNotesTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = [null, 'mysql', 'helpdesk'];

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

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

        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);

        foreach (['contacts.view', 'contacts.update', 'helpdesk.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);
    }

    private function customer(): Customer
    {
        return Customer::factory()->create([
            'email' => 'ct-'.Str::lower(Str::random(12)).'@test.invalid',
            'phone' => null,
        ]);
    }

    public function test_agent_adds_a_note_with_author_and_date(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($this->agent)
            ->postJson(route('contacts.notes.store', $customer), ['body' => '  Entrega en obra, avisar 1 h antes.  ']);

        $response->assertCreated()
            ->assertJsonPath('data.body', 'Entrega en obra, avisar 1 h antes.')
            ->assertJsonPath('data.userId', $this->agent->id);

        $this->assertDatabaseHas('helpdesk_customer_notes', [
            'customer_id' => $customer->id,
            'user_id' => $this->agent->id,
            'body' => 'Entrega en obra, avisar 1 h antes.',
        ], 'helpdesk');
    }

    public function test_note_body_is_required(): void
    {
        $this->actingAs($this->agent)
            ->postJson(route('contacts.notes.store', $this->customer()), ['body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_read_only_agent_cannot_add_notes(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['contacts.view', 'helpdesk.manage']);

        $this->actingAs($viewer)
            ->postJson(route('contacts.notes.store', $this->customer()), ['body' => 'x'])
            ->assertForbidden();
    }

    public function test_only_the_author_can_delete_a_note(): void
    {
        $customer = $this->customer();
        $note = $customer->notes()->create(['user_id' => $this->agent->id, 'body' => 'Mía']);

        $other = User::factory()->create();
        $other->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);

        $this->actingAs($other)
            ->deleteJson(route('contacts.notes.destroy', [$customer, $note]))
            ->assertForbidden();

        $this->actingAs($this->agent)
            ->deleteJson(route('contacts.notes.destroy', [$customer, $note]))
            ->assertOk();

        $this->assertDatabaseMissing('helpdesk_customer_notes', ['id' => $note->id], 'helpdesk');
    }

    public function test_note_of_another_contact_returns_404(): void
    {
        $owner = $this->customer();
        $note = $owner->notes()->create(['user_id' => $this->agent->id, 'body' => 'Ajena']);

        $this->actingAs($this->agent)
            ->deleteJson(route('contacts.notes.destroy', [$this->customer(), $note]))
            ->assertNotFound();

        $this->assertDatabaseHas('helpdesk_customer_notes', ['id' => $note->id], 'helpdesk');
    }

    public function test_show_page_lists_notes_with_author(): void
    {
        $customer = $this->customer();
        $customer->notes()->create(['user_id' => $this->agent->id, 'body' => 'Factura siempre a la empresa.']);

        $this->actingAs($this->agent)
            ->get(route('contacts.show', $customer))
            ->assertOk()
            ->assertSee('Factura siempre a la empresa.')
            ->assertSee('ctf-tabs', false);
    }

    public function test_merge_moves_notes_to_the_kept_contact(): void
    {
        $base = $this->customer();
        $mergee = $this->customer();
        $note = CustomerNote::create(['customer_id' => $mergee->id, 'user_id' => $this->agent->id, 'body' => 'Del duplicado']);

        (new CustomerMergeAction($base, $mergee))->execute();

        $this->assertSame($base->id, (int) $note->fresh()->customer_id);
    }
}
