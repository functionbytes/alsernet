<?php

namespace Modules\Forms\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;

class NewFormSubmissionNotification extends Notification
{
    public function __construct(
        private readonly Form $form,
        private readonly FormSubmission $submission
    ) {}

    public function via(mixed $notifiable): array
    {
        return array_values(array_filter(
            ['mail', 'database'],
            fn ($ch) => $notifiable->canReceiveNotification($ch, 'forms.new_submission')
        )) ?: ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Nueva respuesta en '.$this->form->name,
            'message' => 'Se recibió una nueva respuesta en el formulario.',
            'url' => route('settings.forms.submissions.show', [$this->form, $this->submission]),
            'icon' => 'fas fa-wpforms',
            'type' => 'info',
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva respuesta en '.$this->form->name)
            ->line('Se recibió una nueva respuesta en el formulario "'.$this->form->name.'".')
            ->action('Ver respuesta', route('settings.forms.submissions.show', [$this->form, $this->submission]));
    }
}
