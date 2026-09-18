<?php

namespace Modules\HelpdeskBirthday\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa de cupones que un cliente consumió en la tienda pero que nunca se
 * descontaron en gestión.
 *
 * Es dinero: el pedido salió con el descuento aplicado y el bono sigue vivo en
 * el ERP, así que se puede volver a usar. Hasta ahora solo se veía entrando a
 * mirar la tabla `marcarbono` a mano.
 */
class UnmarkedCouponsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array<string, mixed>>  $orders
     */
    public function __construct(
        public readonly array $orders,
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
        $count = count($this->orders);
        $refs = collect($this->orders)->take(3)->pluck('order_reference')->filter()->implode(', ');

        return [
            'type' => 'helpdesk_birthday_unmarked_coupons',
            'title' => $count === 1 ? 'Un cupón se usó sin descontarse en gestión' : "{$count} cupones sin descontar en gestión",
            'message' => "Se aplicó el descuento en la tienda pero el bono no llegó a marcarse en el ERP, así que sigue disponible. Pedidos: {$refs}".($count > 3 ? '…' : ''),
            'entity_id' => $this->campaignId,
            'action_url' => $this->campaignId !== null
                ? url("/panel/helpdeskbirthday/campaigns/{$this->campaignId}/redemptions")
                : url('/panel/helpdeskbirthday/campaigns'),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
