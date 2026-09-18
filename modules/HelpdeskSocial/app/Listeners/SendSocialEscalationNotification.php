<?php

namespace Modules\HelpdeskSocial\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskSocial\Events\SocialCommentEscalated;
use Modules\HelpdeskSocial\Notifications\SocialCommentEscalatedNotification;
use Spatie\Permission\Models\Role;

class SendSocialEscalationNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(SocialCommentEscalated $event): void
    {
        $comment = $event->comment;

        $agentRoles = array_filter(
            ['helpdesk-agent', 'administrative', 'manager'],
            fn ($r) => Role::where('name', $r)->where('guard_name', 'web')->exists()
        );

        $recipients = $agentRoles ? User::role(array_values($agentRoles))->where('is_active', true)->get() : collect();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SocialCommentEscalatedNotification($comment));
    }

    public function failed(SocialCommentEscalated $event, \Throwable $exception): void
    {
        Log::error('SendSocialEscalationNotification failed', [
            'comment_id' => $event->comment->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
