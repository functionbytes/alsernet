<?php

namespace Modules\Chat\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;

class NewMessage extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public ?int $recipientUserId = null;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ConversationMessage $message,
        public Conversation $conversation
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $this->recipientUserId = $notifiable->id;

        // Always send to database and broadcast for real-time updates
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->message->sender_type === 'Modules\\Chat\\Models\\Customers\\Customer'
            ? $this->message->sender->name
            : 'Internal';

        return (new MailMessage)
            ->subject('New Message in Conversation')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have received a new message in a conversation.')
            ->line('From: '.$senderName)
            ->line('Message: '.substr($this->message->content, 0, 100).(strlen($this->message->content) > 100 ? '...' : ''))
            ->action('View Conversation', route('chat.conversations.show', $this->conversation))
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
            'customer_name' => $this->conversation->customer->name,
            'message_preview' => substr($this->message->content, 0, 100),
            'sender_type' => $this->message->sender_type,
            'sender_id' => $this->message->sender_id,
            'url' => route('chat.conversations.show', $this->conversation),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $data = $this->toArray($notifiable);

        return new BroadcastMessage([
            'id' => $this->id ?? uniqid(),
            'type' => 'new_message',
            'title' => 'Nuevo mensaje',
            'message' => 'Mensaje de '.$this->conversation->customer->name,
            'icon' => 'fas fa-comment',
            'color' => 'primary',
            'action_url' => $data['url'],
            'action_text' => 'Ver conversación',
            'data' => $data,
            'created_at' => now()->toIso8601String(),
        ]);
    }

    public function broadcastOn(): array
    {
        if (! $this->recipientUserId) {
            return [];
        }

        return [new PrivateChannel('users.'.$this->recipientUserId)];
    }

    public function broadcastType(): string
    {
        return 'chat.message.new';
    }
}
