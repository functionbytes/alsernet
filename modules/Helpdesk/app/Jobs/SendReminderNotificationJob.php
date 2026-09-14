<?php

namespace Modules\Helpdesk\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Reminder;
use Modules\Helpdesk\Notifications\ReminderDueNotification;

/**
 * Notifies the agent when a personal reminder falls due (#59 ve-reminder).
 * Dispatched with a delay until remind_at; a no-op if the reminder was completed
 * or already notified.
 */
class SendReminderNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly int $reminderId,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $reminder = Reminder::query()->find($this->reminderId);

        if (! $reminder || $reminder->completed_at || $reminder->notified_at) {
            return;
        }

        $user = User::find($reminder->user_id);

        if (! $user) {
            return;
        }

        $user->notify(new ReminderDueNotification($reminder));

        $reminder->update(['notified_at' => now()]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendReminderNotificationJob failed', [
            'reminder_id' => $this->reminderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
