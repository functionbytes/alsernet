<?php

namespace Modules\Social\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Social\Models\Post;

class PostRejectedNotification extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject('❌ Your Post Has Been Rejected')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your post has been rejected by {$reviewer->name}.")
            ->line('**Content Preview:** '.str($this->post->content)->limit(100))
            ->line("**Networks:** {$accountNames}")
            ->line("**Rejection Reason:** {$this->post->review_notes}")
            ->action('Edit & Resubmit', route('admin.social.publishing.edit', $this->post))
            ->line('You can make changes and submit it for review again.');
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
            'rejected_at' => $this->post->reviewed_at?->toDateTimeString(),
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
