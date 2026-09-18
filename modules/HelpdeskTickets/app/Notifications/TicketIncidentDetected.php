<?php

namespace Modules\HelpdeskTickets\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Aviso de que varios tickets recientes tratan del mismo problema.
 *
 * Va por correo y por base de datos: quien está de guardia mira la campana,
 * quien no está delante del panel necesita el correo.
 */
class TicketIncidentDetected extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, Ticket>  $sample
     */
    public function __construct(
        public readonly string $label,
        public readonly int $size,
        public readonly Collection $sample,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[Helpdesk] {$this->size} tickets sobre el mismo problema")
            ->line("Se han recibido {$this->size} tickets que parecen tratar de lo mismo:")
            ->line("**{$this->label}**")
            ->line('Ejemplos:');

        foreach ($this->sample as $ticket) {
            $mail->line("- #{$ticket->ticket_number}: ".mb_substr((string) $ticket->subject, 0, 120));
        }

        return $mail
            ->action('Ver tickets', route('manager.helpdesk.tickets.index'))
            ->line('Conviene investigarlo una vez y responder en bloque, en lugar de ticket a ticket.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_incident',
            'label' => $this->label,
            'size' => $this->size,
            'ticket_ids' => $this->sample->pluck('id')->all(),
        ];
    }
}
