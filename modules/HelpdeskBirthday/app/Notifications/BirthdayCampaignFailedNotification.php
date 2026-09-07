<?php

namespace Modules\HelpdeskBirthday\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa de que la campaña de cumpleaños del día se abortó y hoy no va a salir
 * ninguna felicitación.
 *
 * Sin este aviso el fallo es mudo: `prepare` corre a las 06:00 sin nadie
 * delante, deja la campaña en `failed` y nadie se entera hasta que alguien
 * entra al panel — potencialmente un día entero de cumpleaños sin felicitar.
 */
class BirthdayCampaignFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $campaignDate,
        public readonly string $reason,
        public readonly ?int $campaignId = null,
    ) {
        $this->onQueue('notifications-high');
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'helpdesk_birthday_campaign_failed',
            'title' => 'La campaña de cumpleaños no se envió',
            'message' => "La campaña del {$this->campaignDate} se abortó y hoy no saldrá ninguna felicitación. Motivo: {$this->reason}",
            'entity_id' => $this->campaignId,
            'action_url' => $this->campaignId !== null
                ? url("/panel/helpdeskbirthday/campaigns/{$this->campaignId}")
                : url('/panel/helpdeskbirthday/campaigns'),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Un aviso por campaña y motivo: si algo reintenta `prepare` varias veces
     * el mismo día, no queremos inundar la campana de notificaciones.
     */
    public function uniqueId(): string
    {
        return 'birthday-failed-'.$this->campaignDate;
    }
}
