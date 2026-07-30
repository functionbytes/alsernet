<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Jobs\UnsnoozeConversationJob;
use Modules\Helpdesk\Models\Conversation;

class SnoozeTest extends InboxTestCase
{
    public function test_snooze_sets_snoozed_until_and_dispatches_job(): void
    {
        Queue::fake();

        $conversation = $this->createConversation();
        $until = now()->addHours(2)->toDateTimeString();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.snooze', $conversation), [
                'until' => $until,
            ])
            ->assertOk()
            ->assertJsonStructure(['success', 'snoozed_until']);

        $this->assertNotNull(
            Conversation::find($conversation->id)->snoozed_until
        );

        Queue::assertPushed(UnsnoozeConversationJob::class);
    }

    public function test_snooze_validation_until_must_be_future(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.snooze', $conversation), [
                'until' => now()->subHour()->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['until']);
    }

    public function test_snooze_validation_until_is_required(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.snooze', $conversation), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['until']);
    }

    public function test_unsnooze_job_clears_snoozed_until_when_due(): void
    {
        $conversation = $this->createConversation();
        $conversation->update([
            'snoozed_until' => now()->subMinute(),
            'snoozed_by' => $this->manager->id,
        ]);

        $job = new UnsnoozeConversationJob($conversation);
        $job->handle();

        $this->assertNull(
            Conversation::find($conversation->id)->snoozed_until
        );
    }

    public function test_unsnooze_job_does_nothing_when_snooze_still_active(): void
    {
        $conversation = $this->createConversation();
        $snoozedUntil = now()->addHour();
        $conversation->update([
            'snoozed_until' => $snoozedUntil,
            'snoozed_by' => $this->manager->id,
        ]);

        $job = new UnsnoozeConversationJob($conversation);
        $job->handle();

        $this->assertNotNull(
            Conversation::find($conversation->id)->snoozed_until
        );
    }

    public function test_snooze_requires_auth(): void
    {
        $conversation = $this->createConversation();

        $this->post(route('manager.helpdesk.conversations.snooze', $conversation), [
            'until' => now()->addHour()->toDateTimeString(),
        ])->assertRedirect();
    }
}
