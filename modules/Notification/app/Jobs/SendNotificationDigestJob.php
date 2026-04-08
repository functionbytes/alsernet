<?php

namespace Modules\Notification\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Notification\Notifications\NotificationDigestNotification;

class SendNotificationDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(private readonly User $user) {}

    public function handle(): void
    {
        $unread = $this->user
            ->unreadNotifications()
            ->where('created_at', '>=', now()->subDay())
            ->get();

        if ($unread->isEmpty()) {
            return;
        }

        $this->user->notify(new NotificationDigestNotification($unread));
    }
}
