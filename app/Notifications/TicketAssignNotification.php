<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Bus\Queueable;

use App\Models\Ticket\Ticket;

class TicketAssignNotification extends Notification
{
    use Queueable;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->ticket_id,
            'title' => $this->ticket->subject,
            'category' => $this->ticket->category_id ? $this->ticket->category != null ? $this->ticket->category->name : null : null,
            'status' => $this->ticket->status,
            'ticketassign' => $this->ticket->myassignuser_id ? 'yes' : 'no',
            'ticketassignee_id' => $this->ticket->myassignuser_id,
            'overduestatus' => $this->ticket->overduestatus,
            'link' => route('admin.ticketshow',$this->ticket->ticket_id),
            'clink' => route('loadmore.load_data',$this->ticket->ticket_id),
        ];
    }

}
