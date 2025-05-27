<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Bus\Queueable;

class UserNeedsPasswordReset extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject(app_name().': '.__('strings.emails.auth.password_reset_subject'))
            ->line(__('strings.emails.auth.password_cause_of_email'))
            ->action(__('buttons.emails.auth.reset_password'), route('frontend.auth.password.reset.form', $this->token))
            ->line(__('strings.emails.auth.password_if_not_requested'));
    }

}
