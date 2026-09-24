<?php

namespace Modules\HelpdeskErp\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * El manager terminó de escanear en Oracle los pedidos de un cliente.
 *
 * Se emite a dos sitios:
 *  - erp-orders-ready.{md5 email}: el canal histórico, con el email que manda
 *    el manager (el de Gestión). Su autorizador (routes/channels.php global)
 *    lo cruza con el email del contacto del helpdesk; si los dos emails no
 *    coinciden, nadie se puede suscribir.
 *  - helpdesk.erp.customer.{id}: uno por cada contacto del helpdesk vinculado
 *    a ese cliente de Gestión (external id 'erp') o con ese mismo email. No
 *    depende del email; lo autoriza HelpdeskErpServiceProvider.
 */
class ErpOrdersReady implements ShouldBroadcast
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    public const CUSTOMER_CHANNEL_PREFIX = 'helpdesk.erp.customer.';

    /**
     * @param  string  $email  Email que manda el manager (el de Gestión).
     * @param  int|null  $customerId  IDCLIENTE de Gestión (lo manda el manager).
     * @param  list<int>  $helpdeskCustomerIds  Contactos del helpdesk que lo reciben.
     */
    public function __construct(
        public readonly string $email,
        public readonly ?int $customerId = null,
        public readonly array $helpdeskCustomerIds = [],
    ) {}

    public static function customerChannelName(int $helpdeskCustomerId): string
    {
        return self::CUSTOMER_CHANNEL_PREFIX.$helpdeskCustomerId;
    }

    /**
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('erp-orders-ready.'.md5(strtolower(trim($this->email))))];

        foreach (array_unique(array_map('intval', $this->helpdeskCustomerIds)) as $id) {
            if ($id > 0) {
                $channels[] = new PrivateChannel(self::customerChannelName($id));
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'erp.orders.ready';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'email_hash' => md5(strtolower(trim($this->email))),
            'customer_id' => $this->customerId,
            'erp_customer_id' => $this->customerId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
