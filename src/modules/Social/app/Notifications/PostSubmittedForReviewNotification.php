<?php

namespace Modules\Social\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Social\Models\Post;

class PostSubmittedForReviewNotification extends Notification implements ShouldQueue
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
        $creator = $this->post->creator;
        $accountNames = $this->post->socialAccounts->pluck('username')->join(', ');

        return (new MailMessage)
            ->subject('📝 New Post Awaiting Review')
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$creator->name} has submitted a new post for review.")
            ->line('**Content Preview:** '.str($this->post->content)->limit(100))
            ->line("**Networks:** {$accountNames}")
            ->line('**Scheduled for:** '.$this->post->scheduled_at?->format('M d, Y H:i'))
            ->action('Review Post', route('admin.social.approval.index'))
            ->line('Please review and approve or reject this post.');
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
            'creator_name' => $this->post->creator->name,
            'content_preview' => str($this->post->content)->limit(50),
            'scheduled_at' => $this->post->scheduled_at?->toDateTimeString(),
            'action_url' => route('admin.social.approval.index'),
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
