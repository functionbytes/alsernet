<?php

namespace Modules\HelpdeskBirthday\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;

/**
 * Borra el dato personal de este módulo cuando un cliente ejerce su derecho de
 * supresión.
 *
 * `helpdesk_birthday_recipients` guarda email, nombre y **fecha de nacimiento**:
 * dato personal en toda regla, y de los sensibles. La cascada de
 * HelpdeskCompliance cubre core/Tickets/ChatFlow/EmailLog, pero no conoce este
 * módulo — y no debería: es el satélite quien se suscribe al evento, igual que
 * los renderers de panel se registran solos en EmailLogEntityPanelRegistry.
 *
 * Se ANONIMIZA en vez de borrar la fila: si se borrase, los contadores de la
 * campaña (enviados, abiertos, canjeados) dejarían de cuadrar y perderíamos la
 * trazabilidad de un envío que sí ocurrió. Lo que desaparece es la persona,
 * no el hecho de que se envió un correo.
 *
 * Es una obligación legal, no una función del panel: NO se gatea con
 * helpdesk_birthday_enabled(). Con el módulo apagado el dato sigue en la tabla
 * y hay que borrarlo igual.
 */
class AnonymizeBirthdayRecipients implements ShouldQueue
{
    public string $queue = 'helpdeskcompliance';

    public function handle(CustomerGdprDeleted $event): void
    {
        $email = mb_strtolower(trim((string) $event->customerEmail));

        if ($email === '') {
            return;
        }

        $anonymized = BirthdayRecipient::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->update([
                // El hash permite seguir deduplicando y detectar reenvíos sin
                // guardar la dirección; no es reversible.
                'email' => 'anonimizado-'.substr(hash('sha256', $email), 0, 16).'@anonimo.local',
                'name' => null,
                // La fecha de nacimiento es el dato más sensible que guardamos.
                'birth_date' => null,
                'erp_customer_id' => null,
                'updated_at' => now(),
            ]);

        if ($anonymized > 0) {
            Log::info('[HelpdeskBirthday] Destinatarios anonimizados por GDPR', [
                'count' => $anonymized,
            ]);
        }
    }

    public function failed(CustomerGdprDeleted $event, \Throwable $e): void
    {
        // Un fallo aquí deja PII sin borrar: tiene que quedar rastro fuerte.
        Log::error('[HelpdeskBirthday] FALLÓ la anonimización GDPR: queda dato personal sin borrar', [
            'customer_id' => $event->customer->id ?? null,
            'error' => $e->getMessage(),
        ]);
    }
}
