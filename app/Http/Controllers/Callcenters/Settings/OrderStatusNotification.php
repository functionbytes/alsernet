<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $status;

    public function __construct($order, string $status)
    {
        $this->order = $order;
        $this->status = $status;
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        // Configuraciones específicas por tipo de estado
        $importantStatuses = ['shipped', 'delivered', 'cancelled'];

        if (in_array($this->status, $importantStatuses)) {
            if ($notifiable->canReceiveNotification('mail', self::class)) {
                $channels[] = 'mail';
            }

            if ($notifiable->canReceiveNotification('push', self::class)) {
                $channels[] = 'broadcast';
            }

            if ($notifiable->canReceiveNotification('sms', self::class)) {
                // SMS solo para estados muy importantes
                if (in_array($this->status, ['delivered', 'cancelled'])) {
                    $channels[] = 'sms';
                }
            }
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $statusMessages = [
            'processing' => 'Tu pedido está siendo procesado',
            'shipped' => 'Tu pedido ha sido enviado',
            'delivered' => 'Tu pedido ha sido entregado',
            'cancelled' => 'Tu pedido ha sido cancelado',
        ];

        return (new MailMessage)
            ->subject('Actualización de pedido #' . $this->order->id)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line($statusMessages[$this->status] ?? 'El estado de tu pedido ha cambiado.')
            ->line('Número de pedido: #' . $this->order->id)
            ->action('Ver pedido', url('/orders/' . $this->order->id))
            ->line('¡Gracias por tu compra!');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Actualización de pedido',
            'message' => "Tu pedido #{$this->order->id} ahora está: {$this->status}",
            'action_url' => url('/orders/' . $this->order->id),
            'action_text' => 'Ver pedido',
            'type' => 'order_status',
            'order_id' => $this->order->id,
            'status' => $this->status,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Actualización de pedido',
            'message' => "Tu pedido #{$this->order->id} ahora está: {$this->status}",
            'type' => 'order_status',
            'order_id' => $this->order->id,
            'status' => $this->status,
        ]);
    }

    public function toPush($notifiable): array
    {
        return [
            'title' => 'Actualización de pedido',
            'body' => "Tu pedido #{$this->order->id} ahora está: {$this->status}",
            'icon' => 'order',
            'data' => [
                'type' => 'order_status',
                'order_id' => $this->order->id,
                'status' => $this->status,
                'action_url' => url('/orders/' . $this->order->id),
            ],
        ];
    }

    public function toSms($notifiable): string
    {
        return "Actualización de pedido #{$this->order->id}: {$this->status}. Ver detalles: " . url('/orders/' . $this->order->id);
    }
}
