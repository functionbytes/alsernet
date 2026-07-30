<?php

namespace Modules\Helpdesk\Tests\Feature\Automation;

use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Events\ConversationStatusChanged;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Services\Automation\Actions\ChangeStatusAction;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Regression test for a crash bug: execute() dispatched `new ConversationStatusChanged($conversation)`
 * with a single argument, but the event's constructor requires both $conversation and $newStatus
 * (no default) — this threw an ArgumentCountError every time a workflow/macro changed status
 * via this action.
 */
class ChangeStatusActionTest extends HelpdeskTestCase
{
    public function test_execute_changes_status_without_crashing(): void
    {
        Event::fake([ConversationStatusChanged::class]);

        $closed = ConversationStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#9ca3af', 'is_open' => false, 'is_default' => false, 'order' => 2]
        );

        $conversation = Conversation::factory()->create(['status_id' => $this->openStatus->id]);

        (new ChangeStatusAction)->execute(
            ['status' => 'resolved'],
            ['conversation' => $conversation]
        );

        $fresh = $conversation->fresh();
        $this->assertFalse($fresh->status->is_open);
        $this->assertNotNull($fresh->closed_at);

        Event::assertDispatched(ConversationStatusChanged::class, function ($event) use ($conversation) {
            return $event->conversation->id === $conversation->id && ! $event->newStatus->is_open;
        });
    }
}
