<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Database\Seeders\HelpdeskContactsPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for ContactsMergeController (search/preview/execute) — la
 * auditoria encontro que esta capa HTTP (permisos, validacion de
 * autofusion, 404 de duplicado inexistente) no tenia ningun test, pese a
 * ser una operacion destructiva e irreversible.
 */
class ContactsMergeControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskContactsPermissionsSeeder::class);

        // assertVisible()/scopeForAgent() consultan estos permisos del core con
        // hasPermissionTo(), que LANZA si no existen; el seeder de Contactos solo
        // crea contacts.* — hay que asegurarlos aunque el usuario no los tenga.
        Permission::findOrCreate('helpdesk.manage', 'web');
        Permission::findOrCreate('helpdesk.customers.manage', 'web');

        // helpdesk.manage → scopeForAgent trata al usuario como manager (ve todos
        // los contactos); sin él, assertVisible lo restringe a los de su bandeja y
        // el merge de contactos de prueba daría 403 en vez de la respuesta real.
        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['contacts.view', 'contacts.merge', 'helpdesk.manage']);
    }

    // ─── autorizacion ───────────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_from_execute(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();

        $this->postJson("/panel/contacts/{$winner->id}/merge", ['loser_id' => $loser->id])
            ->assertUnauthorized();
    }

    public function test_user_without_merge_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('contacts.view');

        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();

        $this->actingAs($user)
            ->postJson("/panel/contacts/{$winner->id}/merge", ['loser_id' => $loser->id])
            ->assertForbidden();
    }

    // ─── validaciones ───────────────────────────────────────────────────────────

    public function test_cannot_merge_a_customer_with_itself(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->user)
            ->postJson("/panel/contacts/{$customer->id}/merge", ['loser_id' => $customer->id])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    /**
     * ExecuteMergeRequest ya valida 'exists:helpdesk.helpdesk_customers,id'
     * sobre loser_id, asi que un id inexistente nunca llega al 404 propio
     * del controller — se bloquea antes con 422.
     */
    public function test_rejects_a_nonexistent_loser_id_with_validation_error(): void
    {
        $winner = Customer::factory()->create();

        $this->actingAs($this->user)
            ->postJson("/panel/contacts/{$winner->id}/merge", ['loser_id' => 999999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('loser_id');
    }

    /**
     * 'exists:' no filtra soft-deleted (consulta la tabla cruda), pero
     * Customer::find() si — este es el caso real que el 404 del controller
     * cubre: un loser_id que existe en la tabla pero ya fue borrado.
     */
    public function test_returns_404_when_loser_is_soft_deleted(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();
        $loser->delete();

        $this->actingAs($this->user)
            ->postJson("/panel/contacts/{$winner->id}/merge", ['loser_id' => $loser->id])
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_preview_requires_loser_id(): void
    {
        $winner = Customer::factory()->create();

        $this->actingAs($this->user)
            ->getJson("/panel/contacts/{$winner->id}/merge/preview")
            ->assertUnprocessable();
    }

    public function test_preview_returns_404_when_loser_does_not_exist(): void
    {
        $winner = Customer::factory()->create();

        $this->actingAs($this->user)
            ->getJson("/panel/contacts/{$winner->id}/merge/preview?loser_id=999999999")
            ->assertNotFound();
    }

    // ─── caso feliz ────────────────────────────────────────────────────────────

    public function test_execute_merges_and_returns_success(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();

        $this->actingAs($this->user)
            ->postJson("/panel/contacts/{$winner->id}/merge", ['loser_id' => $loser->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('helpdesk_customers', ['id' => $loser->id], connection: 'helpdesk');
    }

    public function test_preview_returns_both_contacts_summary(): void
    {
        $winner = Customer::factory()->create(['name' => 'Ganador']);
        $loser = Customer::factory()->create(['name' => 'Perdedor']);

        $this->actingAs($this->user)
            ->getJson("/panel/contacts/{$winner->id}/merge/preview?loser_id={$loser->id}")
            ->assertOk()
            ->assertJsonPath('data.winner.name', 'Ganador')
            ->assertJsonPath('data.loser.name', 'Perdedor');
    }
}
