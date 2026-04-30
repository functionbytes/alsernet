<?php

namespace Modules\Chat\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Chat\Models\Conversations\Conversation;

class ConversationAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Conversation $conversation,
        public ?User $assignedBy = null
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('chat.conversations.show', $this->conversation);
        $assignedByText = $this->assignedBy
            ? "by {$this->assignedBy->name}"
            : 'automatically';

        return (new MailMessage)
            ->subject('New Conversation Assigned to You')
            ->greeting('New Assignment!')
            ->line("A conversation has been assigned to you {$assignedByText}.")
            ->line("**Customer:** {$this->conversation->customer->name}")
            ->line("**Inbox:** {$this->conversation->inbox->name}")
            ->line('**Priority:** '.ucfirst($this->conversation->priority ?? 'normal'))
            ->when($this->conversation->slaPolicy, function ($message) {
                return $message->line("**SLA Policy:** {$this->conversation->slaPolicy->name}");
            })
            ->action('View Conversation', $url)
            ->line('Please respond to the customer as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'customer_name' => $this->conversation->customer->name,
            'inbox_name' => $this->conversation->inbox->name,
            'assigned_by' => $this->assignedBy?->name,
        ];
    }
}
