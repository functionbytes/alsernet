<?php

namespace Modules\HelpdeskTickets\Http\Requests;

/**
 * "Probar regla": solo condiciones. El disparador es un evento (no se puede
 * reproducir sobre tickets ya existentes) y las acciones no se ejecutan, así
 * que ninguno de los dos hace falta para la prueba en seco.
 */
class PreviewTicketOpsAutomationRequest extends StoreTicketOpsAutomationRequest
{
    public function rules(): array
    {
        return self::conditionRules();
    }

    protected function validaAcciones(): bool
    {
        return false;
    }
}
