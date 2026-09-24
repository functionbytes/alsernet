<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Illuminate\Database\Eloquent\Model;

/**
 * Marca la petición en curso cuando un controlador ya dejó su propia entrada
 * en el log 'helpdeskprestashop' (como el vale de compensación). La
 * auditoría genérica (OpsmapAuditStoreWrite) mira esta marca para no
 * registrar la misma acción dos veces.
 */
class OpsmapMarkAuditedRequest
{
    public const ATTRIBUTE = 'opsmap.audited';

    public function handle(Model $activity): void
    {
        if (($activity->getAttribute('log_name') ?? null) !== 'helpdeskprestashop') {
            return;
        }

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        if (app()->bound('request')) {
            app('request')->attributes->set(self::ATTRIBUTE, true);
        }
    }
}
