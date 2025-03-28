<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Sendmail;

class CustomerCustomNotifications extends Notification
{
    use Queueable;

    public function __construct(Sendmail $senmail)
    {
        $this->senmail = $senmail;
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
            'mailsubject' => $this->senmail->mailsubject,
            'mailtext' => $this->senmail->mailtext,
            'mailsendtag' => $this->senmail->tag,
            'mailsendtagcolor' => $this->senmail->selecttagcolor,
            'status' => 'mail',

        ];
    }
}
