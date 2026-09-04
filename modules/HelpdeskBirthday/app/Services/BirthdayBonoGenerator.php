<?php

namespace Modules\HelpdeskBirthday\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Services\ErpService;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Throwable;

/**
 * Genera en Gestión el bono de cumpleaños de cada destinatario.
 *
 * `POST /api-gestion/generacion-bono/` admite varias líneas en la misma
 * llamada —una por cliente, con su idcliente— y devuelve el identificador de la
 * GENERACIÓN, no el de cada bono. Ver "generacion-bono" en la documentación de
 * integración (pág. 34).
 *
 * De ahí la doble fase: primero se manda el lote, y después hay que averiguar
 * qué bono le tocó a cada uno. Mientras no exista esa segunda consulta, la
 * generación queda registrada (id de lote y fecha) pero el destinatario se
 * marca como pendiente de código: es preferible eso a mandarle un correo con un
 * cupón vacío o con el de otra persona.
 */
class BirthdayBonoGenerator
{
    /** Tamaño del lote: una llamada por cada N clientes. */
    private const CHUNK = 100;

    public function __construct(
        private readonly ErpService $erp,
    ) {}

    /**
     * ¿Está configurado el tipo de bono? Sin él Gestión no sabe qué generar.
     */
    public function isConfigured(): bool
    {
        return $this->bonoType() > 0;
    }

    public function bonoType(): int
    {
        return (int) config('helpdeskbirthday.coupon.bono_type_id', 0);
    }

    /**
     * Pide a Gestión los bonos de estos destinatarios.
     *
     * @param  Collection<int, BirthdayRecipient>  $recipients
     * @return array{generated: int, failed: int, batches: list<string>}
     */
    public function generateFor(Collection $recipients, string $description): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException(
                'Falta el tipo de bono de cumpleaños (helpdeskbirthday.coupon.bono_type_id): '
                .'Gestión no puede generar un bono sin saber de qué tipo es.'
            );
        }

        $generated = 0;
        $failed = 0;
        $batches = [];

        // Solo quien tenga id de cliente en Gestión: el bono se emite contra un
        // idcliente, no contra un correo.
        $conId = $recipients->filter(fn (BirthdayRecipient $r): bool => (string) $r->erp_customer_id !== '');

        foreach ($conId->chunk(self::CHUNK) as $chunk) {
            try {
                $batchId = $this->sendBatch($chunk, $description);
            } catch (Throwable $e) {
                Log::error('[HelpdeskBirthday] Falló la generación de bonos', [
                    'count' => $chunk->count(),
                    'error' => $e->getMessage(),
                ]);

                foreach ($chunk as $recipient) {
                    $recipient->update(['coupon_error' => mb_substr($e->getMessage(), 0, 250)]);
                }

                $failed += $chunk->count();

                continue;
            }

            $batches[] = $batchId;

            foreach ($chunk as $recipient) {
                $recipient->update([
                    'coupon_generated_at' => now(),
                    'coupon_error' => null,
                ]);
            }

            $generated += $chunk->count();
        }

        Log::info('[HelpdeskBirthday] Bonos pedidos a Gestión', [
            'generated' => $generated,
            'failed' => $failed,
            'batches' => $batches,
        ]);

        return ['generated' => $generated, 'failed' => $failed, 'batches' => $batches];
    }

    /**
     * Una llamada por lote. El XML y la URL los arma ErpService, que es quien
     * conoce Gestión (ver ErpService::generarBonos y config erp.endpoints).
     *
     * @param  Collection<int, BirthdayRecipient>  $chunk
     */
    private function sendBatch(Collection $chunk, string $description): string
    {
        $lineas = $chunk->map(fn (BirthdayRecipient $r): array => [
            'idcliente' => (int) $r->erp_customer_id,
            'idtbono_promocion' => $this->bonoType(),
            'observacion' => $description,
        ])->values()->all();

        $result = $this->erp->generarBonos($lineas, $description);

        if (($result['success'] ?? false) !== true) {
            throw new \RuntimeException($result['message'] ?? 'Gestión no aceptó la generación de bonos.');
        }

        return (string) $result['batch_id'];
    }

    /**
     * Rellena los datos del bono de un destinatario preguntándoselos a Gestión
     * (GET /api-gestion/bono/{id}/): importe, validez y estado salen de ahí, no
     * de lo que supongamos por nuestra cuenta.
     *
     * Devuelve true si el bono existe y quedó guardado.
     */
    public function syncDetails(BirthdayRecipient $recipient): bool
    {
        if (! $recipient->coupon_code) {
            return false;
        }

        $response = $this->erp->consultaBono(
            (string) $recipient->coupon_code,
            (string) $recipient->coupon_verification_code,
            0.0,
            (string) config('helpdeskbirthday.coupon.origin', 'web'),
        );

        if (($response['success'] ?? false) !== true || ! is_array($response['data'] ?? null)) {
            $recipient->update([
                'coupon_error' => mb_substr((string) ($response['message'] ?? 'Gestión no reconoce el bono.'), 0, 250),
            ]);

            return false;
        }

        $data = $response['data'];

        $recipient->update([
            // El código de verificación lo confirma Gestión: es el que hará
            // falta después para consumir el bono.
            'coupon_verification_code' => (string) ($data['codigo_verificacion'] ?? $recipient->coupon_verification_code),
            // Importe y validez tal como los concedió Gestión, no como los
            // pidió la campaña: es lo que hay que contestar cuando el cliente
            // pregunta cuánto era su bono.
            'coupon_amount' => $this->decimal($data['importe'] ?? null),
            'coupon_min_purchase' => $this->decimal($data['importeminimoventa'] ?? null),
            'coupon_valid_from' => $this->date($data['fvalidez_desde'] ?? null),
            'coupon_valid_to' => $this->date($data['fvalidez_hasta'] ?? null),
            'coupon_status' => $this->text($data['descripcion_estado_extendido'] ?? null),
            // La respuesta entera: trae campos que hoy no se pintan (tipo,
            // almacén de creación, catálogos de consumo) y que permiten
            // reconstruir el estado sin volver a preguntar.
            'coupon_data' => $data,
            'coupon_error' => null,
        ]);

        return true;
    }

    private function decimal(mixed $value): ?float
    {
        // El XML devuelve <importe/> vacío cuando no aplica, que llega como
        // array vacío tras el parseo: eso no es un cero, es "sin dato".
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function date(mixed $value): ?string
    {
        $text = $this->text($value);

        return $text !== null && preg_match('/^\d{4}-\d{2}-\d{2}/', $text) === 1
            ? substr($text, 0, 10)
            : null;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null || is_array($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }
}
