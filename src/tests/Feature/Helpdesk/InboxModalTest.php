<?php

namespace Tests\Feature\Helpdesk;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Group;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InboxModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);
    }

    public function test_inbox_page_loads_with_modals(): void
    {
        $group = Group::factory()->create(['name' => 'Soporte Técnico']);
        $tag = ConversationTag::factory()->create(['name' => 'Soporte técnico']);
        $group->update(['tag_id' => $tag->id]);

        $conversation = Conversation::factory()->create([
            'group_id' => $group->id,
            'assignee_id' => $this->admin->id,
        ]);
        $conversation->conversationTags()->attach($tag->id);

        $response = $this->actingAs($this->admin)
            ->get(route('helpdesk.managers.inbox.show', $conversation->id));

        $response->assertOk();
        $response->assertSee('data-bv-modal-name="block-contact"', false);
        $response->assertSee('data-bv-modal-name="mark-spam"', false);
        $response->assertSee('data-bv-modal-name="delete-conv"', false);
        $response->assertSee('Bloquear contacto');
        $response->assertSee('Marcar como spam');
        $response->assertSee('Eliminar conversación');
    }

    public function test_block_contact_modal_has_context_card(): void
    {
        $conversation = Conversation::factory()->create([
            'client_name' => 'María García López',
            'client_email' => 'maria@test.com',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('helpdesk.managers.inbox.show', $conversation->id));

        $response->assertOk();
        $response->assertSee('María García López');
        $response->assertSee('maria@test.com');
        $response->assertSee('fa-triangle-exclamation');
    }

    public function test_mark_spam_modal_shows_warning(): void
    {
        $conversation = Conversation::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('helpdesk.managers.inbox.show', $conversation->id));

        $response->assertOk();
        $response->assertSee('La conversación se archivará');
        $response->assertSee('fa-ban');
    }

    public function test_delete_conv_modal_shows_danger(): void
    {
        $conversation = Conversation::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('helpdesk.managers.inbox.show', $conversation->id));

        $response->assertOk();
        $response->assertSee('Esta acción no se puede deshacer');
        $response->assertSee('fa-trash-can');
    }

    public function test_js_opens_modals_instead_of_native_confirm(): void
    {
        $conversation = Conversation::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('helpdesk.managers.inbox.show', $conversation->id));

        $response->assertOk();

        $content = $response->getContent();

        // Ensure no native confirm() in the page scripts for these actions
        $this->assertStringNotContainsString("confirm('", $content);
        $this->assertStringNotContainsString('confirm("', $content);

        // But modals should be triggered
        $this->assertStringContainsString('bv-modal-open="mark-spam"', $content);
        $this->assertStringContainsString('bv-modal-open="block-contact"', $content);
        $this->assertStringContainsString('bv-modal-open="delete-conv"', $content);
    }
}
