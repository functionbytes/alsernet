<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketCategoryField;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketCategoryFieldsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected User $user;

    protected TicketCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('helpdesk')->statement('SET FOREIGN_KEY_CHECKS=0');
        DB::connection('helpdesk')->table('helpdesk_ticket_category_fields')->truncate();
        DB::connection('helpdesk')->table('helpdesk_ticket_categories')->truncate();
        DB::connection('helpdesk')->statement('SET FOREIGN_KEY_CHECKS=1');

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
        $this->user->givePermissionTo('helpdesk.tickets.settings');

        $this->category = TicketCategory::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function validFieldData(array $overrides = []): array
    {
        return array_merge([
            'type' => 'text',
            'label' => 'Mi campo',
            'width' => 'full',
        ], $overrides);
    }

    private function createField(array $attributes = []): TicketCategoryField
    {
        return TicketCategoryField::create(array_merge([
            'ticket_category_id' => $this->category->id,
            'type' => 'text',
            'label' => 'Campo base',
            'key' => 'campo_base',
            'width' => 'full',
            'sort_order' => 1,
        ], $attributes));
    }

    // -------------------------------------------------------------------------
    // GET index
    // -------------------------------------------------------------------------

    public function test_index_returns_fields_ordered_by_sort_order(): void
    {
        $third = $this->createField(['key' => 'third', 'label' => 'Third', 'sort_order' => 3]);
        $first = $this->createField(['key' => 'first', 'label' => 'First', 'sort_order' => 1]);
        $second = $this->createField(['key' => 'second', 'label' => 'Second', 'sort_order' => 2]);

        $response = $this->actingAs($this->user)
            ->getJson(route('manager.helpdesk.settings.ticket-categories.fields.index', $this->category));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$first->id, $second->id, $third->id], $ids);
    }

    // -------------------------------------------------------------------------
    // POST store
    // -------------------------------------------------------------------------

    public function test_store_creates_field_with_provided_key(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $this->category), [
                'type' => 'text',
                'label' => 'My Field',
                'key' => 'my_field',
                'width' => 'full',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.key', 'my_field');

        $this->assertDatabaseHas('helpdesk_ticket_category_fields', [
            'ticket_category_id' => $this->category->id,
            'key' => 'my_field',
            'label' => 'My Field',
        ], 'helpdesk');
    }

    public function test_store_auto_generates_key_from_label_when_key_is_empty(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $this->category), [
                'type' => 'text',
                'label' => 'Nombre Completo',
                'width' => 'half',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.key', 'nombre_completo');
    }

    public function test_store_appends_suffix_when_auto_generated_key_already_exists(): void
    {
        $this->createField(['key' => 'nombre_completo', 'label' => 'Existing']);

        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $this->category), [
                'type' => 'text',
                'label' => 'Nombre Completo',
                'width' => 'full',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.key', 'nombre_completo_2');
    }

    public function test_store_appends_incrementing_suffix_when_multiple_collisions(): void
    {
        $this->createField(['key' => 'nombre_completo', 'label' => 'Existing 1']);
        $this->createField(['key' => 'nombre_completo_2', 'label' => 'Existing 2', 'sort_order' => 2]);

        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $this->category), [
                'type' => 'text',
                'label' => 'Nombre Completo',
                'width' => 'full',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.key', 'nombre_completo_3');
    }

    public function test_store_validates_required_label(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $this->category), [
                'type' => 'text',
                'width' => 'full',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['label']);
    }

    public function test_store_validates_required_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $this->category), [
                'label' => 'My Field',
                'width' => 'full',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_store_rejects_invalid_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $this->category), [
                'type' => 'invalid_type',
                'label' => 'My Field',
                'width' => 'full',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_store_rejects_invalid_width(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $this->category), [
                'type' => 'text',
                'label' => 'My Field',
                'width' => 'quarter',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['width']);
    }

    public function test_store_rejects_duplicate_key_in_same_category(): void
    {
        $this->createField(['key' => 'my_key']);

        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $this->category), [
                'type' => 'text',
                'label' => 'Another field',
                'key' => 'my_key',
                'width' => 'full',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['key']);
    }

    public function test_store_allows_same_key_in_different_category(): void
    {
        $this->createField(['key' => 'shared_key']);

        $otherCategory = TicketCategory::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $otherCategory), [
                'type' => 'text',
                'label' => 'Same key different category',
                'key' => 'shared_key',
                'width' => 'full',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.key', 'shared_key');
    }

    public function test_store_accepts_all_valid_types(): void
    {
        foreach (TicketCategoryField::TYPES as $index => $type) {
            $response = $this->actingAs($this->user)
                ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.store', $this->category), [
                    'type' => $type,
                    'label' => "Field for {$type}",
                    'key' => "field_{$type}_{$index}",
                    'width' => 'full',
                ]);

            $response->assertCreated("Failed for type: {$type}");
        }
    }

    // -------------------------------------------------------------------------
    // PATCH update
    // -------------------------------------------------------------------------

    public function test_update_changes_label_and_width(): void
    {
        $field = $this->createField(['key' => 'original_key', 'label' => 'Original label', 'width' => 'full']);

        $response = $this->actingAs($this->user)
            ->patchJson(route('manager.helpdesk.settings.ticket-categories.fields.update', [$this->category, $field]), [
                'type' => 'text',
                'label' => 'Updated label',
                'width' => 'half',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.label', 'Updated label')
            ->assertJsonPath('data.width', 'half');

        $this->assertDatabaseHas('helpdesk_ticket_category_fields', [
            'id' => $field->id,
            'label' => 'Updated label',
            'width' => 'half',
        ], 'helpdesk');
    }

    public function test_update_allows_same_key_on_own_field(): void
    {
        $field = $this->createField(['key' => 'existing_key', 'label' => 'Original']);

        $response = $this->actingAs($this->user)
            ->patchJson(route('manager.helpdesk.settings.ticket-categories.fields.update', [$this->category, $field]), [
                'type' => 'text',
                'label' => 'Updated label',
                'key' => 'existing_key',
                'width' => 'full',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.key', 'existing_key');
    }

    public function test_update_rejects_key_already_used_by_another_field(): void
    {
        $this->createField(['key' => 'taken_key', 'label' => 'Field A', 'sort_order' => 1]);
        $fieldB = $this->createField(['key' => 'field_b', 'label' => 'Field B', 'sort_order' => 2]);

        $response = $this->actingAs($this->user)
            ->patchJson(route('manager.helpdesk.settings.ticket-categories.fields.update', [$this->category, $fieldB]), [
                'type' => 'text',
                'label' => 'Field B updated',
                'key' => 'taken_key',
                'width' => 'full',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['key']);
    }

    // -------------------------------------------------------------------------
    // DELETE destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_field(): void
    {
        $field = $this->createField(['key' => 'to_delete']);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('manager.helpdesk.settings.ticket-categories.fields.destroy', [$this->category, $field]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('helpdesk_ticket_category_fields', [
            'id' => $field->id,
        ], 'helpdesk');
    }

    // -------------------------------------------------------------------------
    // POST reorder
    // -------------------------------------------------------------------------

    public function test_reorder_updates_sort_orders(): void
    {
        $fieldA = $this->createField(['key' => 'field_a', 'label' => 'A', 'sort_order' => 1]);
        $fieldB = $this->createField(['key' => 'field_b', 'label' => 'B', 'sort_order' => 2]);
        $fieldC = $this->createField(['key' => 'field_c', 'label' => 'C', 'sort_order' => 3]);

        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.reorder', $this->category), [
                'items' => [
                    ['id' => $fieldC->id, 'sort_order' => 1],
                    ['id' => $fieldA->id, 'sort_order' => 2],
                    ['id' => $fieldB->id, 'sort_order' => 3],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_ticket_category_fields', ['id' => $fieldC->id, 'sort_order' => 1], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_ticket_category_fields', ['id' => $fieldA->id, 'sort_order' => 2], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_ticket_category_fields', ['id' => $fieldB->id, 'sort_order' => 3], 'helpdesk');
    }

    public function test_reorder_validates_required_items(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.reorder', $this->category), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_reorder_ignores_fields_from_other_categories(): void
    {
        $ownField = $this->createField(['key' => 'own', 'sort_order' => 1]);
        $otherCategory = TicketCategory::factory()->create();
        $foreignField = TicketCategoryField::create([
            'ticket_category_id' => $otherCategory->id,
            'type' => 'text',
            'label' => 'Foreign',
            'key' => 'foreign',
            'width' => 'full',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.settings.ticket-categories.fields.reorder', $this->category), [
                'items' => [
                    ['id' => $ownField->id, 'sort_order' => 10],
                    ['id' => $foreignField->id, 'sort_order' => 20],
                ],
            ])->assertOk();

        $this->assertDatabaseHas('helpdesk_ticket_category_fields', ['id' => $ownField->id, 'sort_order' => 10], 'helpdesk');
        // Foreign field sort_order should not have changed
        $this->assertDatabaseHas('helpdesk_ticket_category_fields', ['id' => $foreignField->id, 'sort_order' => 1], 'helpdesk');
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_unauthenticated_request_is_redirected(): void
    {
        $this->get(route('manager.helpdesk.settings.ticket-categories.fields.index', $this->category))
            ->assertRedirect();
    }

    public function test_user_without_role_gets_403(): void
    {
        $userWithoutRole = User::factory()->create();
        $userWithoutRole->givePermissionTo('helpdesk.tickets.settings');

        $this->actingAs($userWithoutRole)
            ->getJson(route('manager.helpdesk.settings.ticket-categories.fields.index', $this->category))
            ->assertForbidden();
    }

    public function test_agent_role_without_settings_role_gets_403(): void
    {
        // Route middleware is role:super-admin|super-settings.
        // A helpdesk-agent with the permission but wrong role is still rejected.
        $agentRole = Role::firstOrCreate(['name' => 'helpdesk-agent', 'guard_name' => 'web']);
        $agent = User::factory()->create();
        $agent->assignRole($agentRole);
        $agent->givePermissionTo('helpdesk.tickets.settings');

        $this->actingAs($agent)
            ->getJson(route('manager.helpdesk.settings.ticket-categories.fields.index', $this->category))
            ->assertForbidden();
    }
}
