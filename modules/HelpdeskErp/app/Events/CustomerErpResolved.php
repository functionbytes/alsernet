<?php

namespace Modules\HelpdeskErp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * La búsqueda del cliente en el ERP ha terminado — encontrado o no.
 *
 * Existe porque el vínculo es asíncrono y las automatizaciones no lo son: las
 * reglas de ticket.created corren mucho antes de que LinkCustomerToErpJob
 * salga de la cola helpdesk-erp, así que no pueden mirar datos del ERP. Este
 * evento es el momento en que sí se puede, y lleva el origen (el ticket o la
 * conversación que provocó la búsqueda) para que el enrutado actúe sobre ese
 * y no sobre todo lo que el cliente tenga abierto.
 *
 * Se emite también cuando NO se encontró al cliente: "este remitente no está
 * en gestión" es una condición sobre la que se quiere enrutar igual.
 */
class CustomerErpResolved
{
    use Dispatchable, SerializesModels;

    /**
     * @param  int  $customerId  Cliente del helpdesk.
     * @param  int|null  $erpCustomerId  IDCLIENTE de Oracle, o null si no se encontró.
     * @param  'linked'|'not_found'|'error'  $status  Resultado de la búsqueda.
     * @param  'ticket'|'conversation'|null  $sourceType  Qué provocó la búsqueda.
     * @param  int|null  $sourceId  Id del ticket o de la conversación de origen.
     */
    public function __construct(
        public readonly int $customerId,
        public readonly ?int $erpCustomerId,
        public readonly string $status,
        public readonly ?string $sourceType = null,
        public readonly ?int $sourceId = null,
    ) {}

    public function wasFound(): bool
    {
        return $this->status === 'linked' && $this->erpCustomerId !== null;
    }
}
