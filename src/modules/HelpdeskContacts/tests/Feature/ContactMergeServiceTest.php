<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
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
}
