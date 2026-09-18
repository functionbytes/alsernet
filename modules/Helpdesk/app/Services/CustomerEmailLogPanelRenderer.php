<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\View;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskEmailActivity\Contracts\EmailLogEntityPanelRenderer;
use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Lado Helpdesk del punto de extensión EntityPanelRegistry de
 * HelpdeskEmailActivity (ver el contrato EmailLogEntityPanelRenderer y el
 * docblock de EntityPanelRegistry, y su primer ejemplo real:
 * Modules\HelpdeskTickets\Services\TicketEmailLogPanelRenderer) — cuando el
 * detalle de un email en HelpdeskEmailActivity referencia un Customer
 * (entity_type === Customer::class, p. ej. un correo de verificación de
 * identidad o de portal), muestra un resumen mínimo del cliente: no hay una
 * relación barata y directa desde Customer hacia "sus tickets" o "sus
 * conversaciones" que valga la pena consultar aquí sin overhead (a
 * diferencia de Ticket::customer(), que sí es una FK simple) — así que este
 * panel se limita a los datos ya cargados en el propio Customer.
 */
class CustomerEmailLogPanelRenderer implements EmailLogEntityPanelRenderer
{
    public function supports(string $entityType): bool
    {
        return $entityType === Customer::class;
    }

    public function render(EmailLog $emailLog): ?string
    {
        $customer = Customer::find($emailLog->entity_id);

        if (! $customer) {
            return null;
        }

        return View::make('helpdesk::partials.email-log-panel-customer', [
            'customer' => $customer,
        ])->render();
    }
}
