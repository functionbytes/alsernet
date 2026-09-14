<?php

namespace Modules\Document\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Notification\Models\NotificationPreference;

class PaymentReceived extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public mixed $payment,
        public mixed $order
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (NotificationPreference::isEnabled($notifiable->id, 'in_app', 'payment.received')) {
            $channels[] = 'database';
        }

        if (NotificationPreference::isEnabled($notifiable->id, 'push', 'payment.received')) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $orderNumber = $this->order->number ?? ($this->order->id ?? 'N/A');
        $amount = $this->payment->amount ?? 0;

        return [
            'payment_id' => $this->payment->id ?? null,
            'order_id' => $this->order->id ?? null,
            'order_number' => $orderNumber,
            'amount' => $amount,
            'title' => 'Pago recibido',
            'message' => "Se ha recibido un pago de €{$amount} para el pedido #{$orderNumber}",
            'icon' => 'fa-duotone fas fa-credit-card',
            'color' => 'success',
            'action_url' => route('manager.orders.show', $this->order->id ?? 1),
            'action_text' => 'Ver pedido',
            'priority' => 'high',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $orderNumber = $this->order->number ?? ($this->order->id ?? 'N/A');
        $amount = $this->payment->amount ?? 0;

        return new BroadcastMessage([
            'payment_id' => $this->payment->id ?? null,
            'order_id' => $this->order->id ?? null,
            'order_number' => $orderNumber,
            'amount' => $amount,
            'title' => 'Pago recibido',
            'message' => "Se ha recibido un pago de €{$amount} para el pedido #{$orderNumber}",
            'icon' => 'fa-duotone fas fa-credit-card',
            'color' => 'success',
            'action_url' => route('manager.orders.show', $this->order->id ?? 1),
            'created_at' => now()->toIso8601String(),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'payment.received';
    }
}
