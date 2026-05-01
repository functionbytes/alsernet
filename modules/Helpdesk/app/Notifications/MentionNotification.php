<?php

namespace Modules\Helpdesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly ConversationItem $message,
        public readonly string $mentionedByName,
    ) {
        $this->onQueue('notifications-high');
    }

    /**
     * Channels dinámicos por preferencia del usuario:
     * - database + broadcast siempre
     * - mail si user.agentSettings.email_on_mention === true (opt-in)
     */
    public function via(mixed $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        $prefs = $notifiable->agentSettings?->preferences ?? [];
        if (is_string($prefs)) {
            $prefs = json_decode($prefs, true) ?: [];
        }
        $optedInEmail = (bool) data_get($prefs, 'email_on_mention', false);
        if ($optedInEmail && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'helpdesk_mention',
            'title' => 'Te mencionaron en una conversación',
            'message' => "{$this->mentionedByName} te mencionó en la conversación #{$this->conversation->id}",
            'preview' => Str::limit(strip_tags($this->message->body ?? ''), 140),
            'entity_id' => $this->conversation->id,
            'action_url' => route('manager.helpdesk.conversations.index', ['selected' => $this->conversation->id]),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $url = route('manager.helpdesk.conversations.index', ['selected' => $this->conversation->id]);
        $preview = Str::limit(strip_tags($this->message->body ?? ''), 240);
        $name = trim(($notifiable->firstname ?? '').' '.($notifiable->lastname ?? '')) ?: 'Hola';

        return (new MailMessage)
            ->subject("[@mención] {$this->mentionedByName} te mencionó · #{$this->conversation->id}")
            ->greeting("Hola {$name},")
            ->line("**{$this->mentionedByName}** te mencionó en una conversación.")
            ->line('> '.$preview)
            ->action('Ver conversación', $url)
            ->line('Puedes desactivar estos correos desde tus preferencias de agente.');
    }
}
