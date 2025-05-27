<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Bus\Queueable;

class UserEnroll extends Notification
{
    use Queueable;

    private $course;
    public function __construct($course)
    {
        $this->course = $course;
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

            'id' => $this->course['title'],
            'image' => $this->course['image'],
            'data' => 'You are Enrolled',
        ];
    }

}
