<?php

namespace Modules\Helpdesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Helpdesk\Models\Reminder;

class ReminderDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Reminder $reminder,
    ) {}

    public function via(mixed $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($this->reminder->email_notify) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'reminder_due',
            'title' => 'Recordatorio',
            'message' => $this->reminder->title,
            'entity_id' => $this->reminder->id,
            'action_url' => $this->actionUrl(),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Recordatorio: '.$this->reminder->title)
            ->greeting('Hola,')
            ->line('Tienes un recordatorio pendiente:')
            ->line('**'.$this->reminder->title.'**');

        if ($this->reminder->notes) {
            $mail->line($this->reminder->notes);
        }

        return $mail->action('Ir a la bandeja', $this->actionUrl());
    }

    private function actionUrl(): string
    {
        return $this->reminder->conversation_id
            ? route('manager.helpdesk.conversations.index', ['selected' => $this->reminder->conversation_id])
            : route('manager.helpdesk.conversations.index');
    }
}
