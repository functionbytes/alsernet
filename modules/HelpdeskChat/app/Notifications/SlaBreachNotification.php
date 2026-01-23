<?php

namespace Modules\HelpdeskChat\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class SlaBreachNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Conversation $conversation,
        public string $breachType // 'first_response' or 'resolution'
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
        $type = $this->breachType === 'first_response' ? 'First Response' : 'Resolution';
        $url = route('admin.conversation.show', $this->conversation);

        return (new MailMessage)
            ->error()
            ->subject("SLA Breach Alert: {$type} Target Missed")
            ->greeting('SLA Breach Detected!')
            ->line("The {$type} SLA target has been breached for conversation #{$this->conversation->id}.")
            ->line("**Contact:** {$this->conversation->contact->name}")
            ->line("**Inbox:** {$this->conversation->inbox->name}")
            ->line("**SLA Policy:** {$this->conversation->slaPolicy->name}")
            ->action('View Conversation', $url)
            ->line('Please take immediate action to address this conversation.');
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
            'breach_type' => $this->breachType,
            'contact_name' => $this->conversation->contact->name,
            'inbox_name' => $this->conversation->inbox->name,
            'sla_policy_name' => $this->conversation->slaPolicy->name,
        ];
    }
}
