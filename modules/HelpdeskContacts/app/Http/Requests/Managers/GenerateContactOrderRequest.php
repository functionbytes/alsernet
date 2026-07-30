<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Modules\HelpdeskPrestashop\Http\Requests\Managers\GenerateOrderRequest;

/**
 * Datos de cliente para generar pedido / enviar link de pago desde el panel de
 * Contactos. Reutiliza las reglas del flujo nativo de PrestaShop (mismo
 * AssistedCartService debajo, que exige nombre/email/dirección/ciudad/país) y
 * solo cambia el gate al permiso de comercio de Contactos.
 *
 * Antes estas dos acciones validaban inline con reglas más laxas (campos
 * nullable, country max:2), permitiendo generar un pedido o un link de pago
 * real sin nombre ni dirección ni email.
 */
class GenerateContactOrderRequest extends GenerateOrderRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.commerce');
    }
}
