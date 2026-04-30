<?php

namespace Modules\Chat\Notifications;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;

class ConversationMention extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public ?int $recipientUserId = null;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Conversation $conversation,
        public ConversationMessage $message,
        public User $mentionedBy
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
            ->line('Message: '.substr($this->message->content, 0, 100).(strlen($this->message->content) > 100 ? '...' : ''))
            ->action('View Conversation', route('chat.conversations.show', $this->conversation))
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
            'customer_name' => $this->conversation->customer->name,
            'mentioned_by' => $this->mentionedBy->name,
            'mentioned_by_id' => $this->mentionedBy->id,
            'message_preview' => substr($this->message->content, 0, 100),
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
            'type' => 'conversation_mention',
            'title' => 'Te mencionaron',
            'message' => $this->mentionedBy->name.' te mencionó en una conversación',
            'icon' => 'fas fa-at',
            'color' => 'info',
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
        return 'chat.conversation.mention';
    }
}
