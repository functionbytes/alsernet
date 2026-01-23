<?php

namespace Modules\HelpdeskChat\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;

class NewMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ConversationMessage $message,
        public Conversation $conversation
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->hasNotificationEnabled('email', 'new_message')) {
            $channels[] = 'mail';
        }

        if ($notifiable->hasNotificationEnabled('browser', 'new_message')) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->message->sender_type === 'App\\Models\\Contacts\\Contact'
            ? $this->message->sender->name
            : 'Internal';

        return (new MailMessage)
            ->subject('New ConversationMessage in Conversation')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have received a new message in a conversation.')
            ->line('From: '.$senderName)
            ->line('ConversationMessage: '.substr($this->message->content, 0, 100).(strlen($this->message->content) > 100 ? '...' : ''))
            ->action('View Conversation', route('admin.conversation.show', $this->conversation))
            ->line('Click the button above to view and respond to the message.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_message',
            'message_id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'contact_name' => $this->conversation->contact->name,
            'message_preview' => substr($this->message->content, 0, 100),
            'sender_type' => $this->message->sender_type,
            'sender_id' => $this->message->sender_id,
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
