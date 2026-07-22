<?php

namespace Modules\Helpdesk\Tests\Feature\Automation;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Events\ConversationAssigned;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Automation\Actions\AssignAgentAction;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Regression test for a crash bug: execute() dispatched `new ConversationAssigned($conversation)`
 * with a single argument, but the event's constructor requires both $conversation and $assignee
 * (no default) — this threw an ArgumentCountError every time a workflow/macro auto-assigned a
 * conversation via this action.
 */
class AssignAgentActionTest extends HelpdeskTestCase
{
    public function test_execute_assigns_conversation_to_explicit_agent_without_crashing(): void
    {
        Event::fake([ConversationAssigned::class]);

        $agent = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        (new AssignAgentAction)->execute(
            ['agent_id' => $agent->id],
            ['conversation' => $conversation]
        );

        $this->assertSame($agent->id, $conversation->fresh()->assignee_id);

        // El actor es la automatización, no una persona: byUserId debe quedar null.
        Event::assertDispatched(ConversationAssigned::class, function ($event) use ($conversation, $agent) {
            return $event->conversation->id === $conversation->id
                && $event->assignee->id === $agent->id
                && $event->byUserId === null;
        });
    }

    public function test_execute_does_nothing_when_agent_id_does_not_resolve_to_a_user(): void
    {
        Event::fake([ConversationAssigned::class]);

        $conversation = Conversation::factory()->create();
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        (new AssignAgentAction)->execute(
            ['agent_id' => 999999],
            ['conversation' => $conversation]
        );

        Event::assertNotDispatched(ConversationAssigned::class);
    }
}
