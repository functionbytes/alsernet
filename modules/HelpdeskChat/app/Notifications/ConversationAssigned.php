<?php

namespace Modules\HelpdeskChat\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class ConversationAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Conversation $conversation,
        public User $assignedBy
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->hasNotificationEnabled('email', 'conversation_assigned')) {
            $channels[] = 'mail';
        }

        if ($notifiable->hasNotificationEnabled('browser', 'conversation_assigned')) {
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
            ->subject('New Conversation Assigned to You')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new conversation has been assigned to you by '.$this->assignedBy->name.'.')
            ->line('Contact: '.$this->conversation->contact->name)
            ->line('Inbox: '.$this->conversation->inbox->name)
            ->action('View Conversation', route('admin.conversation.show', $this->conversation))
            ->line('Please review and respond at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'conversation_assigned',
            'conversation_id' => $this->conversation->id,
            'contact_name' => $this->conversation->contact->name,
            'inbox_name' => $this->conversation->inbox->name,
            'assigned_by' => $this->assignedBy->name,
            'assigned_by_id' => $this->assignedBy->id,
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
