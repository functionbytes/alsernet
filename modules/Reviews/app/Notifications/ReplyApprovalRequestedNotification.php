<?php

namespace Modules\Reviews\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Reviews\Models\ReviewReply;

class ReplyApprovalRequestedNotification extends Notification
{
    public function __construct(
        private readonly ReviewReply $reply
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $review = $this->reply->review;
        $requester = $this->reply->approvalRequestedBy;
        $excerpt = mb_substr($this->reply->reply_text, 0, 150);

        if (mb_strlen($this->reply->reply_text) > 150) {
            $excerpt .= '...';
        }

        $mail = (new MailMessage)
            ->subject('Respuesta pendiente de aprobacion')
            ->greeting("Hola {$notifiable->name},")
            ->line("**{$requester?->name}** ha solicitado tu aprobacion para publicar una respuesta a una resena.")
            ->line("**Resena de:** {$review->reviewer_name}")
            ->line("**Calificacion:** {$review->star_rating->label()}")
            ->line("**Extracto de la respuesta:** {$excerpt}");

        if ($this->reply->approval_note) {
            $mail->line("**Nota del solicitante:** {$this->reply->approval_note}");
        }

        return $mail
            ->action('Ver y aprobar', route('reviews.replies.pending-approvals'))
            ->line('Accede al panel para aprobar o rechazar esta respuesta.');
    }

    public function toArray(object $notifiable): array
    {
        $review = $this->reply->review;

        return [
            'reply_id' => $this->reply->id,
            'review_id' => $review->id,
            'reviewer_name' => $review->reviewer_name,
            'requested_by' => $this->reply->approval_requested_by,
            'requested_at' => $this->reply->approval_requested_at?->toDateTimeString(),
        ];
    }
}
