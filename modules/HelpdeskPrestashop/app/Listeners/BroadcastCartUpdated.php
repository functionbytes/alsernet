<?php

namespace Modules\HelpdeskPrestashop\Listeners;

use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Events\PsCartLiveUpdate;
use Modules\HelpdeskPrestashop\Events\PsCartUpdated;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * El carrito de PrestaShop del cliente cambió (añadió/quitó un producto,
 * cambió de dirección). Invalida el contexto cacheado de ese cliente y, si
 * tiene una conversación abierta en el inbox ahora mismo, avisa en vivo para
 * que el panel se refresque sin esperar a que el agente reabra la
 * conversación ni a que caduque la caché (hasta 5 min).
 */
class BroadcastCartUpdated
{
    public function __construct(
        private readonly PrestashopContextService $prestashop
    ) {}

    public function handle(PsCartUpdated $event): void
    {
        $email = $event->email();

        if ($email === null || $email === '') {
            return;
        }

        $this->prestashop->forgetCache($email);

        $customer = Customer::query()->where('email', $email)->first();

        if ($customer === null) {
            return;
        }

        $conversationId = Conversation::query()
            ->where('customer_id', $customer->id)
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->orderByDesc('last_message_at')
            ->value('id');

        if ($conversationId === null) {
            return;
        }

        PsCartLiveUpdate::dispatch((int) $conversationId, $event->cartId());
    }
}
