<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Services\ContactMergeService;
use Modules\HelpdeskLivechat\Models\WidgetSession;
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
}
