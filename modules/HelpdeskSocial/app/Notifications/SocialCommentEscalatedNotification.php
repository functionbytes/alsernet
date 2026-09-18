<?php

namespace Modules\HelpdeskSocial\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialCommentEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly SocialComment $comment)
    {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'helpdesk_social_escalated',
            'title' => 'Escalación requerida',
            'message' => "Comentario #{$this->comment->id} de {$this->comment->author_name} requiere atención humana.",
            'entity_id' => $this->comment->id,
            'action_url' => route('helpdesksocial.inbox.show', $this->comment),
            'metadata' => [
                'platform' => $this->comment->platform,
                'intent' => $this->comment->intent,
                'urgency' => $this->comment->urgency,
            ],
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toWebPush(mixed $notifiable): array
    {
        $array = $this->toArray($notifiable);

        return [
            'title' => $array['title'],
            'body' => $array['message'],
            'url' => $array['action_url'],
            'tag' => $array['type'],
            'requireInteraction' => true,
            'data' => [
                'type' => $array['type'],
                'entity_id' => $array['entity_id'],
                'metadata' => $array['metadata'] ?? [],
            ],
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $url = route('helpdesksocial.inbox.show', $this->comment);
        $name = trim(($notifiable->firstname ?? '').' '.($notifiable->lastname ?? '')) ?: 'Hola';

        return (new MailMessage)
            ->subject("[Escalación] Comentario #{$this->comment->id} requiere atención")
            ->markdown('helpdesksocial::emails.social-comment-escalated', [
                'comment' => $this->comment,
                'url' => $url,
                'name' => $name,
            ]);
    }
}
