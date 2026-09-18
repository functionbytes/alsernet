<?php

namespace Modules\Helpdesk\Tests\Feature\Automation;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Automation\Actions\MuteConversationAction;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Regression test: execute() updated `helpdesk_conversations.muted_at`, a
 * column that does not exist at all — a guaranteed QueryException on every
 * run. Muting is actually per-agent, in helpdesk_user_conversation_meta.
 */
class MuteConversationActionTest extends HelpdeskTestCase
{
    public function test_execute_mutes_for_the_assignee_without_crashing(): void
    {
        $agent = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'status_id' => $this->openStatus->id,
            'assignee_id' => $agent->id,
        ]);

        (new MuteConversationAction)->execute([], ['conversation' => $conversation]);

        $meta = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('user_id', $agent->id)
            ->where('conversation_id', $conversation->id)
            ->first();

        $this->assertNotNull($meta);
        $this->assertNotNull($meta->muted_until);
    }

    public function test_execute_is_a_noop_without_an_assignee(): void
    {
        $conversation = Conversation::factory()->create([
            'status_id' => $this->openStatus->id,
            'assignee_id' => null,
        ]);

        (new MuteConversationAction)->execute([], ['conversation' => $conversation]);

        $count = DB::connection('helpdesk')
            ->table('helpdesk_user_conversation_meta')
            ->where('conversation_id', $conversation->id)
            ->count();

        $this->assertSame(0, $count);
    }
}
