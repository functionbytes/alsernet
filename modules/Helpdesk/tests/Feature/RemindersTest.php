<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Jobs\SendReminderNotificationJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Reminder;
use Modules\Helpdesk\Notifications\ReminderDueNotification;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Personal reminders (#59 ve-reminder): create endpoint + due-notification job.
 */
class RemindersTest extends HelpdeskTestCase
{
    public function test_manager_can_create_reminder_and_job_is_scheduled(): void
    {
        Queue::fake();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.reminders.store'), [
                'title' => 'Llamar al cliente',
                'remind_at' => now()->addMinutes(30)->toIso8601String(),
                'email_notify' => true,
            ])
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('helpdesk_reminders', [
            'user_id' => $this->manager->id,
            'title' => 'Llamar al cliente',
            'email_notify' => true,
        ], 'helpdesk');

        Queue::assertPushed(SendReminderNotificationJob::class);
    }

    public function test_reminder_can_be_attached_to_a_conversation(): void
    {
        Queue::fake();
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.reminders.store', $conversation), [
                'title' => 'Revisar pedido',
                'remind_at' => now()->addHour()->toIso8601String(),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('helpdesk_reminders', [
            'conversation_id' => $conversation->id,
            'title' => 'Revisar pedido',
        ], 'helpdesk');
    }

    public function test_reminder_requires_a_future_date(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.reminders.store'), [
                'title' => 'Pasado',
                'remind_at' => now()->subHour()->toIso8601String(),
            ])
            ->assertStatus(422);
    }

    public function test_due_job_notifies_the_agent(): void
    {
        Notification::fake();
        $reminder = Reminder::factory()->create([
            'user_id' => $this->manager->id,
            'email_notify' => true,
            'notified_at' => null,
            'completed_at' => null,
        ]);

        (new SendReminderNotificationJob($reminder->id))->handle();

        Notification::assertSentTo($this->manager, ReminderDueNotification::class);
        $this->assertNotNull($reminder->fresh()->notified_at);
    }

    public function test_due_job_is_noop_when_reminder_completed(): void
    {
        Notification::fake();
        $reminder = Reminder::factory()->completed()->create(['user_id' => $this->manager->id]);

        (new SendReminderNotificationJob($reminder->id))->handle();

        Notification::assertNothingSent();
    }

    public function test_agent_cannot_create_reminder_for_conversation_outside_their_inbox(): void
    {
        Queue::fake();

        // Agente con el permiso genérico pero SIN acceso al inbox de la conversación.
        $agent = User::factory()->create();
        $agent->givePermissionTo('helpdesk.conversations.view');

        $conversation = $this->createConversation();

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversations.reminders.store', $conversation), [
                'title' => 'Intruso',
                'remind_at' => now()->addHour()->toIso8601String(),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('helpdesk_reminders', [
            'conversation_id' => $conversation->id,
        ], 'helpdesk');
    }

    private function createConversation(array $overrides = []): Conversation
    {
        $conversation = Conversation::factory()->create(array_merge(['channel' => 'web'], $overrides));
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        return $conversation;
    }
}
