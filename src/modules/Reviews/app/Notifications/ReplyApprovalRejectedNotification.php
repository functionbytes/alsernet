<?php

namespace Modules\Reviews\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Reviews\Models\ReviewReply;

class ReplyApprovalRejectedNotification extends Notification
{
    public function __construct(
        private readonly ReviewReply $reply,
        private readonly string $rejectionReason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $review = $this->reply->review;

        return (new MailMessage)
            ->subject('Tu respuesta ha sido rechazada')
            ->greeting("Hola {$notifiable->name},")
            ->line('Un supervisor ha revisado tu respuesta y no ha podido aprobarla.')
            ->line("**Resena de:** {$review->reviewer_name}")
            ->line("**Motivo del rechazo:** {$this->rejectionReason}")
            ->line('Puedes editar la respuesta y volver a solicitar aprobacion.')
            ->action('Ver resena', route('reviews.show', $review->id))
            ->line('Gracias por usar nuestro sistema de gestion de resenas.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reply_id' => $this->reply->id,
            'review_id' => $this->reply->review_id,
            'rejection_reason' => $this->rejectionReason,
        ];
    }
}
