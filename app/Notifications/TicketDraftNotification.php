<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketDraftNotification extends Notification
{
    use Queueable;


    public function __construct($ticketData)
    {
        $this->ticket = $ticketData;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket['ticket_id'],
            'title' => $this->ticket['created_or_respond'],
            'draft_description' => $this->ticket['ticket_description'],
            'draftnotify' => 'draftcreated',
            'created_username' => $this->ticket['username'],
            'link' => route('admin.ticketshow',$this->ticket['ticket_id']),
        ];
    }

}
