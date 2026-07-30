<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Events\CustomerMerged;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskContacts\Services\ContactMergeService;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

/**
 * Tests for ContactMergeService::transferChats().
 *
 * Regression: transferChats() previously re-pointed a non-existent model
 * (Modules\HelpdeskLiveChat\Models\Chat), so the class_exists() guard always
 * returned early and the loser's web chat history (helpdesk_widget_sessions)
 * was silently orphaned after a merge. The real model is
 * Modules\HelpdeskLivechat\Models\WidgetSession (table helpdesk_widget_sessions).
 */
class ContactMergeServiceTest extends TestCase
{
    use DatabaseTransactions;

    /** Roll back writes on both the default and helpdesk connections. */
    protected $connectionsToTransact = [null, 'helpdesk'];

    public function test_merge_repoints_widget_sessions_from_loser_to_winner(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();

        $loserSession = WidgetSession::factory()->forCustomer($loser->id)->create();
        $winnerSession = WidgetSession::factory()->forCustomer($winner->id)->create();

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertDatabaseHas('helpdesk_widget_sessions', [
            'id' => $loserSession->id,
            'customer_id' => $winner->id,
        ], 'helpdesk');

        $this->assertDatabaseHas('helpdesk_widget_sessions', [
            'id' => $winnerSession->id,
            'customer_id' => $winner->id,
        ], 'helpdesk');

        $this->assertDatabaseMissing('helpdesk_widget_sessions', [
            'customer_id' => $loser->id,
        ], 'helpdesk');
    }

    public function test_merge_repoints_conversations_from_loser_to_winner(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $loser->id]);

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertDatabaseHas('helpdesk_conversations', [
            'id' => $conversation->id,
            'customer_id' => $winner->id,
        ], 'helpdesk');
    }

    public function test_merge_repoints_tickets_from_loser_to_winner(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();
        $ticket = Ticket::create([
            'customer_id' => $loser->id,
            'subject' => 'Ticket del perdedor',
            'ticket_number' => 'TCK-TEST-'.uniqid(),
            'priority' => 'normal',
            'source' => 'web',
        ]);

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id' => $ticket->id,
            'customer_id' => $winner->id,
        ], 'helpdesk');
    }

    public function test_merge_copies_missing_integration_ids_from_loser_to_winner(): void
    {
        $winner = Customer::factory()->create(['whatsapp_phone' => null]);
        $loser = Customer::factory()->create(['whatsapp_phone' => '+34600000000']);

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertSame('+34600000000', $winner->refresh()->whatsapp_phone);
    }

    /**
     * ERP/PrestaShop no son columnas directas de Customer — se vinculan via
     * externalIds. Regresion cubierta aqui: copyMissingIntegrationIds()
     * referenciaba antes 'erp_customer_id' (columna inexistente), asi que
     * este enlace nunca se copiaba realmente en una fusion.
     */
    public function test_merge_copies_missing_external_id_links_from_loser_to_winner(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();
        $loser->linkExternalId('erp', 'ERP-999');

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertSame('ERP-999', $winner->refresh()->externalIdFor('erp'));
    }

    public function test_merge_does_not_overwrite_winners_existing_external_id_link(): void
    {
        $winner = Customer::factory()->create();
        $winner->linkExternalId('erp', 'ERP-WINNER');
        $loser = Customer::factory()->create();
        $loser->linkExternalId('erp', 'ERP-LOSER');

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertSame('ERP-WINNER', $winner->refresh()->externalIdFor('erp'));
    }

    public function test_merge_does_not_overwrite_winners_existing_integration_ids(): void
    {
        $winner = Customer::factory()->create(['whatsapp_phone' => '+34611111111']);
        $loser = Customer::factory()->create(['whatsapp_phone' => '+34600000000']);

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertSame('+34611111111', $winner->refresh()->whatsapp_phone);
    }

    public function test_merge_recalculates_winners_conversation_count(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();
        Conversation::factory()->count(2)->create(['customer_id' => $winner->id]);
        Conversation::factory()->count(3)->create(['customer_id' => $loser->id]);

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertSame(5, $winner->refresh()->total_conversations);
    }

    /**
     * El hallazgo de la auditoria: nada probaba que el perdedor realmente
     * desaparece del listado de contactos tras la fusion.
     */
    public function test_merge_soft_deletes_the_loser(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertSoftDeleted('helpdesk_customers', ['id' => $loser->id], connection: 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customers', ['id' => $winner->id, 'deleted_at' => null], 'helpdesk');
    }

    /* ── Paridad con CustomerMergeAction (fusión unificada) ──────────────── */

    /**
     * Paridad con CustomerMergeAction: la fusión desde Contactos migraba
     * conversaciones/tickets/chats pero dejaba huérfanas las filas de
     * helpdesk_customer_inboxes del perdedor (soft-deleted). Tras delegar en
     * CustomerMergeAction deben re-apuntarse al ganador, deduplicando por el
     * unique (customer_id, inbox_id).
     */
    public function test_merge_migrates_customer_inboxes_to_winner_without_duplicates(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();

        $sharedInbox = Inbox::create(['name' => 'Merge Shared '.uniqid(), 'channel_type' => Inbox::CHANNEL_WEB]);
        $loserOnlyInbox = Inbox::create(['name' => 'Merge Loser '.uniqid(), 'channel_type' => Inbox::CHANNEL_WEB]);

        $now = now();
        DB::connection('helpdesk')->table('helpdesk_customer_inboxes')->insert([
            ['customer_id' => $winner->id, 'inbox_id' => $sharedInbox->id, 'created_at' => $now, 'updated_at' => $now],
            ['customer_id' => $loser->id, 'inbox_id' => $sharedInbox->id, 'created_at' => $now, 'updated_at' => $now],
            ['customer_id' => $loser->id, 'inbox_id' => $loserOnlyInbox->id, 'created_at' => $now, 'updated_at' => $now],
        ]);

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertDatabaseMissing('helpdesk_customer_inboxes', ['customer_id' => $loser->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customer_inboxes', [
            'customer_id' => $winner->id,
            'inbox_id' => $loserOnlyInbox->id,
        ], 'helpdesk');
        $this->assertSame(1, DB::connection('helpdesk')
            ->table('helpdesk_customer_inboxes')
            ->where('customer_id', $winner->id)
            ->where('inbox_id', $sharedInbox->id)
            ->count());
    }

    /**
     * Paridad: las sesiones (helpdesk_customer_sessions) del perdedor deben
     * migrar al ganador — antes quedaban colgando de un customer soft-deleted.
     */
    public function test_merge_migrates_customer_sessions_to_winner(): void
    {
        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();

        $sessionId = 'merge-session-'.uniqid();
        DB::connection('helpdesk')->table('helpdesk_customer_sessions')->insert([
            'customer_id' => $loser->id,
            'session_id' => $sessionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertDatabaseHas('helpdesk_customer_sessions', [
            'session_id' => $sessionId,
            'customer_id' => $winner->id,
        ], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_customer_sessions', ['customer_id' => $loser->id], 'helpdesk');
    }

    /**
     * Paridad: la fusión desde Contactos ahora emite CustomerMerged, igual
     * que la fusión del core, para que los módulos suscritos reaccionen a
     * ambas rutas.
     */
    public function test_merge_dispatches_customer_merged_event(): void
    {
        Event::fake([CustomerMerged::class]);

        $winner = Customer::factory()->create();
        $loser = Customer::factory()->create();

        app(ContactMergeService::class)->merge($winner, $loser);

        Event::assertDispatched(CustomerMerged::class, function (CustomerMerged $event) use ($winner, $loser): bool {
            return $event->base->id === $winner->id && $event->mergee->id === $loser->id;
        });
    }

    /**
     * Paridad: los atributos básicos vacíos del ganador se rellenan desde el
     * perdedor (antes la fusión de Contactos solo copiaba los IDs sociales).
     */
    public function test_merge_backfills_missing_basic_attributes_from_loser(): void
    {
        $winner = Customer::factory()->create(['phone' => null]);
        $loser = Customer::factory()->create(['phone' => '+34911222333']);

        app(ContactMergeService::class)->merge($winner, $loser);

        $this->assertSame('+34911222333', $winner->refresh()->phone);
    }
}
