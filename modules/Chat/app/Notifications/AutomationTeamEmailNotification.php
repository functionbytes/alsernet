<?php

namespace Modules\Chat\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Chat\Models\Conversations\Conversation;

class AutomationTeamEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Conversation $conversation,
        public string $subject,
        public string $message
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url("/chats/chat/conversations/{$this->conversation->id}");

        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message)
            ->line('**Conversation Details:**')
            ->line('Customers: '.($this->conversation->customer->name ?? 'Unknown'))
            ->line('Inbox: '.($this->conversation->inbox->name ?? 'Unknown'))
            ->line('Status: '.ucfirst($this->conversation->status))
            ->action('View Conversation', $url)
            ->line('This notification was sent by an automation rule.');
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
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
