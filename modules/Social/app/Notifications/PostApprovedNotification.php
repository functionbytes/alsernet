<?php

namespace Modules\Social\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Social\Models\Post;

class PostApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Post $post
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $reviewer = $this->post->reviewer;
        $accountNames = $this->post->socialAccounts->pluck('username')->join(', ');

        $message = (new MailMessage)
            ->subject('✅ Your Post Has Been Approved')
            ->greeting("Great news, {$notifiable->name}!")
            ->line("Your post has been approved by {$reviewer->name}.")
            ->line('**Content Preview:** '.str($this->post->content)->limit(100))
            ->line("**Networks:** {$accountNames}")
            ->line('**Scheduled for:** '.$this->post->scheduled_at?->format('M d, Y H:i'));

        if ($this->post->review_notes) {
            $message->line("**Reviewer Notes:** {$this->post->review_notes}");
        }

        return $message
            ->action('View Post', route('admin.social.publishing.edit', $this->post))
            ->line('Your post will be published as scheduled.');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'reviewer_name' => $this->post->reviewer->name,
            'content_preview' => str($this->post->content)->limit(50),
            'review_notes' => $this->post->review_notes,
            'approved_at' => $this->post->reviewed_at?->toDateTimeString(),
            'action_url' => route('admin.social.publishing.edit', $this->post),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
