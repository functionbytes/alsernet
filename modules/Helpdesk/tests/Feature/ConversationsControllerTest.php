<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Mail\CustomerOutboundEmail;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Models\Macro;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConversationsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    private ConversationStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $this->openStatus = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->manager = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $this->manager->assignRole($role);
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_conversations_index(): void
    {
        $this->get(route('manager.helpdesk.conversations.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('manager.helpdesk.conversations.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_conversations_index(): void
    {
        Conversation::factory()->count(3)->create();

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.index'))
            ->assertOk()
            ->assertViewIs('helpdesk::helpdesk.inbox.index')
            ->assertViewHas('conversations');
    }

    /**
     * El contador por inbox del sidebar usa un unico GROUP BY (antes 1 COUNT
     * por inbox activo — N+1). Verifica que el conteo agregado siga siendo
     * correcto por inbox, no solo que la query se redujo.
     */
    public function test_sidebar_shows_correct_conversation_count_per_inbox(): void
    {
        $inboxA = Inbox::create([
            'name' => 'Inbox A', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true,
        ]);
        $inboxB = Inbox::create([
            'name' => 'Inbox B', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true,
        ]);

        Conversation::factory()->count(3)->create(['inbox_id' => $inboxA->id]);
        Conversation::factory()->count(1)->create(['inbox_id' => $inboxB->id]);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.index'))
            ->assertOk();

        $sidebarInboxes = $response->viewData('sidebarInboxes');

        $this->assertSame(3, $sidebarInboxes->firstWhere('id', $inboxA->id)->conversations_count);
        $this->assertSame(1, $sidebarInboxes->firstWhere('id', $inboxB->id)->conversations_count);
    }

    // ─── create ───────────────────────────────────────────────────────────────

    public function test_manager_can_view_create_form(): void
    {
        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.create'))
            ->assertOk()
            ->assertViewIs('helpdesk::helpdesk.conversations.create');
    }

    public function test_user_without_create_permission_cannot_access_create_form(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.conversations.view');

        $this->actingAs($user)
            ->get(route('manager.helpdesk.conversations.create'))
            ->assertForbidden();
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_manager_can_create_conversation(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.store'), [
                'customer_id' => $customer->id,
                'subject' => 'Test conversation subject',
                'priority' => 'normal',
                'status_id' => $this->openStatus->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_conversations', [
            'customer_id' => $customer->id,
            'subject' => 'Test conversation subject',
            'priority' => 'normal',
        ], 'helpdesk');
    }

    public function test_store_validation_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.store'), [])
            ->assertSessionHasErrors(['customer_id', 'subject', 'priority', 'status_id']);
    }

    public function test_store_validation_rejects_invalid_priority(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.store'), [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'priority' => 'invalid-priority',
                'status_id' => $this->openStatus->id,
            ])
            ->assertSessionHasErrors(['priority']);
    }

    public function test_user_without_create_permission_cannot_store_conversation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.conversations.view');
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->post(route('manager.helpdesk.conversations.store'), [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'priority' => 'normal',
                'status_id' => $this->openStatus->id,
            ])
            ->assertForbidden();
    }

    // ─── show ─────────────────────────────────────────────────────────────────

    public function test_manager_can_view_conversation(): void
    {
        $conversation = $this->createConversation();

        $this->assertNotNull($conversation->fresh()->status);

        // show() redirects to the unified inbox view with the conversation pre-selected
        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.show', $conversation))
            ->assertRedirect(route('manager.helpdesk.conversations.index', ['selected' => $conversation->id]));
    }

    public function test_user_without_view_permission_cannot_view_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createConversation();

        $this->actingAs($user)
            ->get(route('manager.helpdesk.conversations.show', $conversation))
            ->assertForbidden();
    }

    // ─── update (form) ────────────────────────────────────────────────────────

    public function test_manager_can_update_conversation_subject(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.conversations.update', $conversation), [
                'subject' => 'Updated subject',
                'priority' => 'high',
                'status_id' => $this->openStatus->id,
            ])
            ->assertRedirect(route('manager.helpdesk.conversations.show', $conversation));

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'subject' => 'Updated subject',
            'priority' => 'high',
        ], 'helpdesk');
    }

    public function test_user_without_update_permission_cannot_update_conversation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.conversations.view');
        $conversation = $this->createConversation();

        $this->actingAs($user)
            ->put(route('manager.helpdesk.conversations.update', $conversation), [
                'subject' => 'Updated',
                'priority' => 'normal',
                'status_id' => $this->openStatus->id,
            ])
            ->assertForbidden();
    }

    // ─── update (AJAX — priority) ─────────────────────────────────────────────

    public function test_manager_can_update_priority_via_ajax(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.conversations.update', $conversation), [
                'action' => 'update_priority',
                'priority' => 'urgent',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'priority' => 'urgent',
        ], 'helpdesk');
    }

    // ─── update (AJAX — tag) ──────────────────────────────────────────────────

    public function test_manager_can_add_tag_via_ajax(): void
    {
        $conversation = $this->createConversation();
        $tag = ConversationTag::create([
            'name' => 'Billing',
            'slug' => 'billing',
            'color' => '#ff0000',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.conversations.update', $conversation), [
                'action' => 'add_tag',
                'tag_id' => $tag->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(
            $conversation->conversationTags()->where('tag_id', $tag->id)->exists()
        );
    }

    public function test_adding_tag_creates_activity_item_and_broadcasts_it_live(): void
    {
        Event::fake([ConversationMessageCreated::class]);

        $conversation = $this->createConversation();
        $tag = ConversationTag::create([
            'name' => 'Billing',
            'slug' => 'billing',
            'color' => '#ff0000',
            'is_active' => true,
        ]);

        // El modal real de etiquetas (tags.blade.php) envía siempre 'tag_ids'
        // (sync completo), no la acción singular 'add_tag'/'tag_id'.
        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.conversations.update', $conversation), [
                'tag_ids' => [$tag->id],
            ])
            ->assertOk();

        $activity = $conversation->items()->where('type', 'activity')->latest('id')->first();

        $this->assertNotNull($activity, 'debe crearse un ConversationItem de actividad al añadir la etiqueta');
        $this->assertSame('label_added', $activity->activity_type);
        $this->assertSame('Billing', $activity->activity_data['label'] ?? null);
        $this->assertSame($this->manager->full_name.' añadió la etiqueta "Billing"', $activity->body);

        Event::assertDispatched(
            ConversationMessageCreated::class,
            fn ($event) => $event->item->id === $activity->id
        );
    }

    public function test_manager_can_remove_tag_via_ajax(): void
    {
        $conversation = $this->createConversation();
        $tag = ConversationTag::create([
            'name' => 'Support',
            'slug' => 'support',
            'color' => '#0000ff',
            'is_active' => true,
        ]);
        $conversation->conversationTags()->attach($tag->id);

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.conversations.update', $conversation), [
                'action' => 'remove_tag',
                'tag_id' => $tag->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFalse(
            $conversation->conversationTags()->where('tag_id', $tag->id)->exists()
        );
    }

    // ─── destroy / restore ────────────────────────────────────────────────────

    public function test_manager_can_soft_delete_conversation(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->delete(route('manager.helpdesk.conversations.destroy', $conversation))
            ->assertRedirect(route('manager.helpdesk.conversations.index'));

        $this->assertSoftDeleted('helpdesk_conversations', ['id' => $conversation->id], 'helpdesk');
    }

    public function test_user_without_delete_permission_cannot_destroy_conversation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.conversations.view');
        $conversation = $this->createConversation();

        $this->actingAs($user)
            ->delete(route('manager.helpdesk.conversations.destroy', $conversation))
            ->assertForbidden();
    }

    public function test_manager_can_restore_soft_deleted_conversation(): void
    {
        $conversation = $this->createConversation();
        $conversation->delete();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.conversations.restore', $conversation->id))
            ->assertRedirect(route('manager.helpdesk.conversations.index'));

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'deleted_at' => null,
        ], 'helpdesk');
    }

    // ─── close ────────────────────────────────────────────────────────────────

    public function test_manager_can_close_conversation(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.close', $conversation))
            ->assertOk()
            ->assertJsonPath('success', true);

        $conversation->refresh();
        $this->assertNotNull($conversation->closed_at);
    }

    // ─── reopen ─────────────────────────────────────────────────────────────────

    public function test_manager_can_reopen_conversation(): void
    {
        $conversation = $this->createConversation();
        $conversation->close();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.reopen', $conversation))
            ->assertOk()
            ->assertJsonPath('success', true);

        $conversation->refresh();
        $this->assertNull($conversation->closed_at);
    }

    // ─── snooze ─────────────────────────────────────────────────────────────────

    public function test_manager_can_snooze_conversation(): void
    {
        $conversation = $this->createConversation();
        $until = now()->addHours(3)->format('Y-m-d H:i:s');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.snooze', $conversation), [
                'until' => $until,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $conversation->refresh();
        $this->assertNotNull($conversation->snoozed_until);
    }

    // ─── archive / unarchive ────────────────────────────────────────────────────

    public function test_manager_can_archive_conversation(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.archive', $conversation))
            ->assertOk()
            ->assertJsonPath('success', true);

        $conversation->refresh();
        $this->assertTrue($conversation->is_archived);
    }

    public function test_manager_can_unarchive_conversation(): void
    {
        $conversation = $this->createConversation();
        $conversation->archive();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.unarchive', $conversation))
            ->assertOk()
            ->assertJsonPath('success', true);

        $conversation->refresh();
        $this->assertFalse($conversation->is_archived);
    }

    // ─── mark spam ──────────────────────────────────────────────────────────────

    public function test_manager_can_mark_conversation_as_spam(): void
    {
        $conversation = $this->createConversation();
        $customer = $conversation->customer;

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.mark-spam', $conversation))
            ->assertOk()
            ->assertJsonPath('success', true);

        $conversation->refresh();
        $customer->refresh();
        $this->assertTrue($conversation->is_spam);
        $this->assertTrue($conversation->is_archived);
        $this->assertNotNull($customer->banned_at);
    }

    // ─── block contact ──────────────────────────────────────────────────────────

    public function test_manager_can_block_contact(): void
    {
        $conversation = $this->createConversation();
        $customer = $conversation->customer;

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.block-contact', $conversation))
            ->assertOk()
            ->assertJsonPath('success', true);

        $customer->refresh();
        $this->assertNotNull($customer->banned_at);
    }

    // ─── assign (ajax) ──────────────────────────────────────────────────────────

    public function test_manager_can_assign_conversation_via_ajax(): void
    {
        $conversation = $this->createConversation();
        $agent = User::factory()->create();

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.conversations.update', $conversation), [
                'assignee_id' => $agent->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $conversation->refresh();
        $this->assertEquals($agent->id, $conversation->assignee_id);
    }

    // ─── merge ──────────────────────────────────────────────────────────────────

    public function test_manager_can_merge_conversations(): void
    {
        $customer = Customer::factory()->create();
        $source = $this->createConversation(['customer_id' => $customer->id]);
        $target = $this->createConversation(['customer_id' => $customer->id]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.merge', $source), [
                'target_id' => $target->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $source->refresh();
        $target->refresh();
        $this->assertSoftDeleted('helpdesk_conversations', ['id' => $source->id], 'helpdesk');
    }

    // ─── create ticket ──────────────────────────────────────────────────────────

    public function test_manager_can_create_ticket_from_conversation(): void
    {
        if (! class_exists(Ticket::class)) {
            $this->markTestSkipped('HelpdeskTickets module not available.');
        }

        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.ticket', $conversation))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_tickets', [
            'customer_id' => $conversation->customer_id,
            'source' => 'conversation',
        ], 'helpdesk');
    }

    // ─── send-email ──────────────────────────────────────────────────────────

    public function test_send_email_sets_external_id_on_item_and_mailable(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create();
        $conversation = $this->createConversation(['customer_id' => $customer->id]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.send-email', $conversation), [
                'subject' => 'Test subject',
                'body' => 'Test body',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $item = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->where('type', 'email_sent')
            ->firstOrFail();

        $this->assertNotEmpty($item->external_id);

        Mail::assertQueued(
            CustomerOutboundEmail::class,
            fn (CustomerOutboundEmail $mail) => $mail->getEmailLogExternalId() === $item->external_id
        );
    }

    public function test_send_email_fails_when_customer_has_no_email(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create(['email' => null]);
        $conversation = $this->createConversation(['customer_id' => $customer->id]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.send-email', $conversation), [
                'subject' => 'Test subject',
                'body' => 'Test body',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        Mail::assertNothingQueued();
    }

    // ─── email-templates (internal-only exclusion + placeholder rendering) ─────

    public function test_email_templates_list_excludes_internal_only_templates(): void
    {
        $internal = $this->createMailerTemplate('helpdesk.new_ticket_agent', 'Nuevo ticket #{TICKET_NUMBER} — {PRIORITY}');
        $customerFacing = $this->createMailerTemplate('helpdesk.ticket_created', 'Hemos recibido tu solicitud — #{TICKET_NUMBER}');

        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.email-templates'))
            ->assertOk();

        $keys = collect($response->json('templates'))->pluck('key');

        $this->assertTrue($keys->contains($customerFacing->key));
        $this->assertFalse($keys->contains($internal->key));
    }

    public function test_preview_email_template_resolves_ticket_number_and_subject_placeholders(): void
    {
        $template = $this->createMailerTemplate(
            'helpdesk.ticket_created',
            'Hemos recibido tu solicitud — #{TICKET_NUMBER}',
            '<p>Hola {CUSTOMER_NAME}, tu ticket es {TICKET_NUMBER} ({SUBJECT}).</p>'
        );

        $customer = Customer::factory()->create(['name' => 'Maxi Angeli']);
        $conversation = $this->createConversation(['customer_id' => $customer->id]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.email-templates.preview', $conversation).'?template_id='.$template->id)
            ->assertOk();

        $subject = $response->json('subject');
        $body = $response->json('body');

        $this->assertStringNotContainsString('{TICKET_NUMBER}', $subject);
        $this->assertStringNotContainsString('{TICKET_NUMBER}', $body);
        $this->assertStringNotContainsString('{SUBJECT}', $body);
        $this->assertStringContainsString('#'.$conversation->id, $subject);
    }

    public function test_preview_email_template_rejects_internal_only_template(): void
    {
        $internal = $this->createMailerTemplate('helpdesk.new_ticket_agent', 'Nuevo ticket #{TICKET_NUMBER} — {PRIORITY}');
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.email-templates.preview', $conversation).'?template_id='.$internal->id)
            ->assertNotFound();
    }

    // ─── merge (inbox scoping) ─────────────────────────────────────────────────

    /**
     * merge() mueve los mensajes hacia la conversación destino, así que el
     * agente debe poder acceder al inbox del destino, no solo al del origen.
     * Un agente restringido al inbox A no puede fusionar hacia el inbox B.
     */
    public function test_agent_cannot_merge_into_a_conversation_in_an_inbox_they_cannot_access(): void
    {
        $inboxA = Inbox::create(['name' => 'Inbox A', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        $inboxB = Inbox::create(['name' => 'Inbox B', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);

        $customer = Customer::factory()->create();
        $source = $this->createConversation(['customer_id' => $customer->id, 'inbox_id' => $inboxA->id]);
        $target = $this->createConversation(['customer_id' => $customer->id, 'inbox_id' => $inboxB->id]);

        $agent = User::factory()->create();
        // Ambos permisos de ruta (helpdesk.view + conversations.update) para
        // pasar el middleware y aislar el chequeo de inbox de la policy.
        $agent->givePermissionTo(['helpdesk.view', 'helpdesk.conversations.update']);
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $inboxA->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversations.merge', $source), ['target_id' => $target->id])
            ->assertForbidden();

        // El origen no debe haberse borrado si la fusión fue rechazada.
        $this->assertDatabaseHas('helpdesk_conversations', ['id' => $source->id, 'deleted_at' => null], 'helpdesk');
    }

    public function test_agent_can_merge_when_they_access_both_inboxes(): void
    {
        $inbox = Inbox::create(['name' => 'Inbox A', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);

        $customer = Customer::factory()->create();
        $source = $this->createConversation(['customer_id' => $customer->id, 'inbox_id' => $inbox->id]);
        $target = $this->createConversation(['customer_id' => $customer->id, 'inbox_id' => $inbox->id]);

        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdesk.view', 'helpdesk.conversations.update']);
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $inbox->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversations.merge', $source), ['target_id' => $target->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('helpdesk_conversations', ['id' => $source->id], connection: 'helpdesk');
    }

    // ─── macros-picker (#61 ve-apply-macro) ────────────────────────────────────

    public function test_macros_picker_returns_readable_actions_summary(): void
    {
        Macro::factory()->create([
            'name' => 'Reclamo de pedido',
            'is_shared' => true,
            'is_active' => true,
            'actions' => [
                ['type' => 'assign_agent', 'value' => ''],
                ['type' => 'change_priority', 'value' => ''],
                ['type' => 'add_tag', 'value' => ''],
            ],
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.conversations.macros-picker'))
            ->assertOk();

        $macro = collect($response->json('macros'))->firstWhere('name', 'Reclamo de pedido');

        $this->assertNotNull($macro);
        $this->assertSame(3, $macro['actions_count']);
        $this->assertSame('Asignar agente · Cambiar prioridad · Agregar etiqueta', $macro['actions_summary']);
        $this->assertSame(
            ['Asignar agente', 'Cambiar prioridad', 'Agregar etiqueta'],
            array_column($macro['actions'], 'label')
        );
    }

    // ─── pane (right panel: tab "Carritos" exclusivo de PrestaShop) ────────────

    public function test_pane_hides_carts_tab_when_customer_has_no_prestashop_link(): void
    {
        $conversation = $this->createConversation();

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.pane', $conversation))
            ->assertOk();

        $response->assertDontSee('data-bv-tab-content="carts"', false);
        $response->assertDontSee('data-bv-tab="carts"', false);
    }

    public function test_pane_shows_carts_tab_with_prestashop_orders_when_customer_is_linked(): void
    {
        Setting::set('prestashop.integration_enabled', '1', 'integrations');

        $conversation = $this->createConversation();
        $conversation->customer->linkExternalId('prestashop', 'PS-123');

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.pane', $conversation))
            ->assertOk();

        $response->assertSee('data-bv-tab="carts"', false);
        $response->assertSee('data-bv-tab-content="carts"', false);
        $response->assertSee('id="bv-carts-tab"', false);
        // Un único tab "carts" en el DOM (antes había un stub duplicado en el
        // módulo HelpdeskPrestashop además del bloque completo en el core).
        $this->assertSame(1, substr_count($response->getContent(), 'id="bv-carts-tab"'));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createConversation(array $overrides = []): Conversation
    {
        $conversation = Conversation::factory()->create($overrides);
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        return $conversation;
    }

    private function createMailerTemplate(string $key, string $subject, string $content = '<p>{CUSTOMER_NAME}</p>'): MailerTemplate
    {
        $langId = $this->ensureTestLang();

        $template = MailerTemplate::create([
            'key' => $key,
            'name' => $key,
            'is_enabled' => true,
            'is_protected' => false,
            'module' => 'helpdesk',
        ]);

        MailerTemplateLang::create([
            'mailer_template_id' => $template->id,
            'lang_id' => $langId,
            'subject' => $subject,
            'content' => $content,
        ]);

        return $template;
    }

    private function ensureTestLang(): int
    {
        $langId = DB::table('langs')->where('available', 1)->value('id');

        if (! $langId) {
            $langId = DB::table('langs')->insertGetId([
                'uid' => Str::uuid()->toString(),
                'title' => 'Español',
                'iso_code' => 'es',
                'lenguage_code' => 'es',
                'locate' => 'es_ES',
                'date_format_full' => 'd/m/Y H:i',
                'date_format_lite' => 'd/m/Y',
                'available' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Locale::clearResolvedLegacyLangId();

        return (int) $langId;
    }
}
