<?php

namespace Modules\HelpdeskSocial\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialSlaBreachNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SocialComment $comment,
        public readonly string $breachType = 'response',
    ) {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toArray(mixed $notifiable): array
    {
        $typeLabel = $this->breachType === 'resolution' ? 'resolución' : 'respuesta';

        return [
            'type' => 'helpdesk_social_sla_breach',
            'title' => "SLA de {$typeLabel} incumplido",
            'message' => "El comentario de {$this->comment->author_name} en {$this->comment->platform} ha excedido el SLA de {$typeLabel}.",
            'entity_id' => $this->comment->id,
            'action_url' => route('helpdesksocial.inbox.show', $this->comment),
            'metadata' => [
                'platform' => $this->comment->platform,
                'breach_type' => $this->breachType,
                'deadline' => $this->comment->sla_response_deadline?->toIso8601String(),
                'author_name' => $this->comment->author_name,
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
        $typeLabel = $this->breachType === 'resolution' ? 'resolución' : 'respuesta';
        $name = trim(($notifiable->firstname ?? '').' '.($notifiable->lastname ?? '')) ?: 'Hola';

        return (new MailMessage)
            ->subject("[Social] SLA de {$typeLabel} incumplido — {$this->comment->platform}")
            ->markdown('helpdesksocial::emails.sla-breach', [
                'comment' => $this->comment,
                'url' => $url,
                'name' => $name,
                'breachType' => $typeLabel,
            ]);
    }
}
