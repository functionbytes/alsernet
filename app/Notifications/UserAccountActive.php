<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserAccountActive extends Notification
{
    use Queueable;


    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject(app_name())
            ->line(__('strings.emails.auth.account_confirmed'))
            ->action(__('labels.frontend.auth.login_button'), route('frontend.auth.login'))
            ->line(__('strings.emails.auth.thank_you_for_using_app'));
    }

}
