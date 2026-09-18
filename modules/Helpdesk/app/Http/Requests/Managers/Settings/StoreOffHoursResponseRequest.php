<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Modules\Helpdesk\Http\Requests\Managers\Settings\Concerns\AutoReplyMessageRequest;

/** Reglas/mensajes compartidos en AutoReplyMessageRequest. */
class StoreOffHoursResponseRequest extends AutoReplyMessageRequest
{
    /**
     * Bolsa nombrada: por si en el futuro se reutiliza este formulario en una
     * vista combinada con los de bienvenida/despedida.
     */
    protected $errorBag = 'offHours';
}
