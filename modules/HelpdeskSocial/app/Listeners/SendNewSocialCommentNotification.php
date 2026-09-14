<?php

namespace Modules\HelpdeskSocial\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskSocial\Events\IntentClassified;
use Modules\HelpdeskSocial\Notifications\NewSocialCommentNotification;
use Spatie\Permission\Models\Role;

/**
 * Escucha IntentClassified (no SocialCommentReceived): la urgencia del
 * comentario la fija ClassifyIntentJob, que corre encolado DESPUÉS de que se
 * dispara SocialCommentReceived. Escuchar el evento de recepción comprobaba
 * siempre $comment->urgency === null, así que este aviso nunca se enviaba.
 */
class SendNewSocialCommentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(IntentClassified $event): void
    {
        $comment = $event->comment;

        // Solo notificar si la urgencia es medium o superior
        if (! in_array($event->intent->urgency, ['medium', 'high', 'critical'], true)) {
            return;
        }

        $agentRoles = array_filter(
            ['helpdesk-agent', 'administrative', 'manager'],
            fn ($r) => Role::where('name', $r)->where('guard_name', 'web')->exists()
        );

        $recipients = $agentRoles ? User::role(array_values($agentRoles))->where('available', true)->get() : collect();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new NewSocialCommentNotification($comment));
    }

    public function failed(IntentClassified $event, \Throwable $exception): void
    {
        Log::error('SendNewSocialCommentNotification failed', [
            'comment_id' => $event->comment->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
