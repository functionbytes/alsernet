<?php

namespace Modules\Helpdesk\Tests\Feature\Automation;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Services\Automation\Actions\AddLabelAction;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Regression test: execute() attached raw label_ids straight into the pivot
 * without checking the tags still exist — a stale/deleted tag id in an old
 * workflow rule threw a QueryException (FK violation) on every run.
 */
class AddLabelActionTest extends HelpdeskTestCase
{
    public function test_execute_attaches_existing_tags(): void
    {
        $tag = ConversationTag::create([
            'name' => 'VIP', 'slug' => 'vip', 'color' => '#ff0000', 'is_active' => true,
        ]);
        $conversation = Conversation::factory()->create(['status_id' => $this->openStatus->id]);

        (new AddLabelAction)->execute(
            ['label_ids' => [$tag->id]],
            ['conversation' => $conversation]
        );

        $this->assertTrue($conversation->conversationTags()->where('tag_id', $tag->id)->exists());
    }

    public function test_execute_skips_stale_tag_ids_without_crashing(): void
    {
        $conversation = Conversation::factory()->create(['status_id' => $this->openStatus->id]);

        (new AddLabelAction)->execute(
            ['label_ids' => [999999]],
            ['conversation' => $conversation]
        );

        $this->assertSame(0, $conversation->conversationTags()->count());
    }
}
