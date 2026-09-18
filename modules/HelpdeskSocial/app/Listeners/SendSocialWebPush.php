<?php

namespace Modules\HelpdeskSocial\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Events\SocialCommentEscalated;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Events\SocialCommentReplied;
use Modules\HelpdeskSocial\Notifications\NewSocialCommentNotification;
use Modules\HelpdeskSocial\Notifications\SocialCommentEscalatedNotification;
use Modules\HelpdeskSocial\Services\WebPushService;

class SendSocialWebPush implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly WebPushService $webPushService,
    ) {}

    public function handle(SocialCommentReceived|SocialCommentReplied|SocialCommentEscalated $event): void
    {
        $permission = 'helpdesksocial.view';

        if ($event instanceof SocialCommentReceived) {
            $this->webPushService->sendToPermission(
                $permission,
                new NewSocialCommentNotification($event->comment)
            );

            return;
        }

        if ($event instanceof SocialCommentEscalated) {
            $this->webPushService->sendToPermission(
                $permission,
                new SocialCommentEscalatedNotification($event->comment)
            );

            return;
        }

        if ($event instanceof SocialCommentReplied) {
            $this->webPushService->sendRawToPermission($permission, [
                'title' => 'Respuesta enviada',
                'body' => "Se respondió el comentario #{$event->comment->id} en {$event->comment->platform}.",
                'url' => route('helpdesksocial.inbox.show', $event->comment),
                'tag' => 'helpdesk_social_replied',
                'data' => [
                    'type' => 'helpdesk_social_replied',
                    'comment_id' => $event->comment->id,
                ],
            ]);

            return;
        }
    }

    public function failed(SocialCommentReceived|SocialCommentReplied|SocialCommentEscalated $event, \Throwable $exception): void
    {
        Log::error('SendSocialWebPush failed', [
            'event' => get_class($event),
            'comment_id' => $event->comment->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
