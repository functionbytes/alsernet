<?php

namespace Modules\HelpdeskEmailActivity\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskEmailActivity\Enums\SuppressionReason;
use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Anonimización de un destinatario en el log de correo (derecho de supresión
 * del RGPD, art. 17).
 *
 * Lo que hace y lo que NO hace, porque la diferencia importa:
 *
 * - NO borra las filas. Un email borrado desaparecería de los totales, las
 *   tasas de entrega y las gráficas, falseando el histórico. Se conserva el
 *   esqueleto (estado, fechas, módulo) y se vacía lo personal.
 * - La dirección se sustituye por un seudónimo derivado con HMAC-SHA256 de la
 *   APP_KEY. Es PSEUDONIMIZACIÓN, no anonimización perfecta: quien tenga la
 *   clave y una lista de direcciones candidatas podría recalcular el hash. A
 *   cambio, los envíos de una misma persona siguen agrupados, que es lo que
 *   permite que las métricas por destinatario sigan cuadrando. Si el caso de
 *   uso exige irreversibilidad total, habría que guardar un id aleatorio por
 *   dirección en vez de derivarlo.
 * - El asunto se conserva: no es un dato personal por sí mismo y es lo único
 *   que permite entender después qué se envió. Si un asunto concreto lleva
 *   datos personales, ese email hay que purgarlo aparte.
 *
 * Todo va en una transacción: una anonimización a medias dejaría filas con la
 * dirección sustituida pero el cuerpo intacto, que es el peor de los mundos.
 */
class RecipientAnonymizerService
{
    /** Dominio del seudónimo — reservado por RFC 2606, nunca resuelve. */
    private const PSEUDONYM_DOMAIN = 'anonimizado.invalid';

    public function __construct(private readonly EmailSuppressionService $suppressions) {}

    /**
     * Qué se vería afectado, para enseñarlo ANTES de ejecutar nada.
     *
     * @return array{emails: int, attachments: int, with_body: int}
     */
    public function preview(string $email): array
    {
        $logs = $this->query($email);

        return [
            'emails' => (clone $logs)->count(),
            'attachments' => (clone $logs)->whereNotNull('attachments')->where('attachments', '!=', '[]')->count(),
            'with_body' => (clone $logs)->where(function ($q) {
                $q->whereNotNull('body_html')->orWhereNotNull('body_text');
            })->count(),
        ];
    }

    /**
     * @return array{emails: int}
     */
    public function anonymize(string $email, bool $purgeBody, bool $replaceAddress, bool $suppress): array
    {
        $email = mb_strtolower(trim($email));
        $pseudonym = $this->pseudonymFor($email);

        $affected = DB::transaction(function () use ($email, $pseudonym, $purgeBody, $replaceAddress): int {
            $count = 0;

            // Por lotes: una anonimización puede tocar miles de filas y cada una
            // necesita reescribir sus arrays de direcciones en PHP (no hay forma
            // sensata de hacerlo en SQL sobre columnas JSON).
            $this->query($email)->chunkById(200, function (Collection $logs) use ($email, $pseudonym, $purgeBody, $replaceAddress, &$count) {
                foreach ($logs as $log) {
                    $changes = [];

                    if ($purgeBody) {
                        $changes += [
                            'body_html' => null,
                            'body_text' => null,
                            'raw_headers' => null,
                            'attachments' => null,
                        ];
                    }

                    if ($replaceAddress) {
                        $changes += [
                            'to_addresses' => $this->replaceIn($log->to_addresses, $email, $pseudonym),
                            'cc_addresses' => $this->replaceIn($log->cc_addresses, $email, $pseudonym),
                            'bcc_addresses' => $this->replaceIn($log->bcc_addresses, $email, $pseudonym),
                        ];

                        // recipients_index no se toca aquí: EmailLog::booting()
                        // lo recalcula en 'saving' cuando cambian las
                        // direcciones, así que basta con reescribirlas.
                    }

                    if ($changes !== []) {
                        $log->forceFill($changes)->save();
                        $count++;
                    }
                }
            });

            return $count;
        });

        // La supresión va FUERA de la transacción a propósito: es una lista
        // aparte y su fallo no debe deshacer una anonimización ya hecha (el
        // dato personal ya está borrado, revertirlo sería peor).
        if ($suppress) {
            $this->suppressions->suppress(
                $email,
                SuppressionReason::Manual,
                notes: __('helpdeskemailactivity::emaillog.gdpr.suppression_note'),
            );
        }

        return ['emails' => $affected];
    }

    /**
     * Seudónimo estable para una dirección. HMAC (no un hash pelado) para que
     * no baste con una rainbow table de correos comunes.
     */
    public function pseudonymFor(string $email): string
    {
        $digest = hash_hmac('sha256', mb_strtolower(trim($email)), (string) config('app.key'));

        return 'anon-'.substr($digest, 0, 16).'@'.self::PSEUDONYM_DOMAIN;
    }

    /**
     * @param  array<int, string>|null  $addresses
     * @return array<int, string>|null
     */
    private function replaceIn(?array $addresses, string $email, string $pseudonym): ?array
    {
        if ($addresses === null) {
            return null;
        }

        return collect($addresses)
            ->map(fn ($address) => mb_strtolower(trim((string) $address)) === $email ? $pseudonym : $address)
            ->values()
            ->all();
    }

    /**
     * Envíos en los que esa dirección aparece como destinatario. Se filtra por
     * recipients_index (To+Cc+Bcc ya desnormalizados) y no por el JSON, para
     * poder usar el mismo camino que el buscador.
     */
    private function query(string $email)
    {
        $email = mb_strtolower(trim($email));

        return EmailLog::query()
            ->withTrashed()
            ->where('recipients_index', 'like', '%'.addcslashes($email, '%_\\').'%');
    }
}
