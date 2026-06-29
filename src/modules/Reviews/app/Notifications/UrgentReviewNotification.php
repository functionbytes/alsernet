<?php

namespace Modules\Reviews\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\NotificationService;

class UrgentReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $queue = 'notifications';

    public function __construct(
        public readonly Review $review
    ) {}

    public function via(object $notifiable): array
    {
        $channels = app(NotificationService::class)
            ->getEnabledChannels($notifiable, NotificationService::TYPE_URGENT_REVIEW);

        $enabled = array_filter($channels, fn (string $ch) => in_array($ch, ['database', 'mail']));

        if (config('reviews.general.notifications.slack_webhook')) {
            $enabled[] = 'slack';
        }

        return array_values($enabled);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rating = $this->review->star_rating->value();
        $excerpt = Str::limit($this->review->comment ?? '', 200);
        $reviewUrl = route('reviews.show', $this->review->id);

        return (new MailMessage)
            ->subject("⚠️ Reseña urgente: {$rating} estrella(s) sin respuesta — {$this->review->location->name}")
            ->view('reviews::emails.urgent-review', [
                'review' => $this->review,
                'notifiable' => $notifiable,
                'rating' => $rating,
                'excerpt' => $excerpt,
                'reviewUrl' => $reviewUrl,
            ]);
    }

    public function toSlack(object $notifiable): array
    {
        $rating = $this->review->star_rating->value();
        $stars = str_repeat('★', $rating).str_repeat('☆', 5 - $rating);
        $excerpt = Str::limit($this->review->comment ?? '(sin comentario)', 200);
        $reviewUrl = route('reviews.show', $this->review->id);

        return [
            'text' => "⚠️ *Reseña urgente sin respuesta* — {$stars}",
            'attachments' => [
                [
                    'color' => '#FA896B',
                    'fields' => [
                        ['title' => 'Ubicacion', 'value' => $this->review->location->name, 'short' => true],
                        ['title' => 'Autor', 'value' => $this->review->reviewer_name, 'short' => true],
                        ['title' => 'Calificacion', 'value' => "{$rating}/5 {$stars}", 'short' => true],
                        ['title' => 'Fecha', 'value' => $this->review->review_time->format('d/m/Y H:i'), 'short' => true],
                        ['title' => 'Comentario', 'value' => $excerpt, 'short' => false],
                    ],
                    'actions' => [
                        ['type' => 'button', 'text' => 'Responder ahora', 'url' => $reviewUrl, 'style' => 'danger'],
                    ],
                ],
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'location_id' => $this->review->location_id,
            'location' => $this->review->location->name,
            'reviewer_name' => $this->review->reviewer_name,
            'rating' => $this->review->star_rating->toNumeric(),
            'comment' => $this->review->comment,
            'review_time' => $this->review->review_time->toDateTimeString(),
            'type' => 'urgent_review',
        ];
    }
}
