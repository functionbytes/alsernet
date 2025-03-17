<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Bus\Queueable;

class UserNeedsConfirmation extends Notification
{
    use Queueable;

    protected $confirmation_code;

    public function __construct($confirmation_code)
    {
        $this->confirmation_code = $confirmation_code;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject(app_name().': '.__('exceptions.frontend.auth.confirmation.confirm'))
            ->line(__('strings.emails.auth.click_to_confirm'))
            ->action(__('buttons.emails.auth.confirm_account'), route('frontend.auth.account.confirm', $this->confirmation_code))
            ->line(__('strings.emails.auth.thank_you_for_using_app'));
    }
}
