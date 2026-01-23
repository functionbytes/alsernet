<?php

namespace Modules\HelpdeskChat\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;

class ConversationMention extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Conversation $conversation,
        public ConversationMessage $message,
        public User $mentionedBy
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->hasNotificationEnabled('email', 'conversation_mention')) {
            $channels[] = 'mail';
        }

        if ($notifiable->hasNotificationEnabled('browser', 'conversation_mention')) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You were mentioned in a conversation')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->mentionedBy->name.' mentioned you in a conversation.')
            ->line('ConversationMessage: '.substr($this->message->content, 0, 100).(strlen($this->message->content) > 100 ? '...' : ''))
            ->action('View Conversation', route('admin.conversation.show', $this->conversation))
            ->line('Click the button above to view the conversation and respond.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'conversation_mention',
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->message->id,
            'contact_name' => $this->conversation->contact->name,
            'mentioned_by' => $this->mentionedBy->name,
            'mentioned_by_id' => $this->mentionedBy->id,
            'message_preview' => substr($this->message->content, 0, 100),
            'url' => route('admin.conversation.show', $this->conversation),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
