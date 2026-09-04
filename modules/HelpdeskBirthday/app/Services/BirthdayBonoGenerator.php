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
     * @param  Collection<int, BirthdayRecipient>  $chunk
     */
    private function sendBatch(Collection $chunk, string $description): string
    {
        $lineas = $chunk->map(fn (BirthdayRecipient $r): string => sprintf(
            '<linea><idcliente>%s</idcliente><idtbono_promocion>%d</idtbono_promocion><observacion>%s</observacion></linea>',
            (int) $r->erp_customer_id,
            $this->bonoType(),
            e($description),
        ))->implode('');

        $response = $this->erp->post('/api-gestion/generacion-bono/', [
            // Con hora: el ejemplo de la documentación usa
            // '2020-02-20T12:00:00' y el endpoint rechaza la fecha suelta.
            'fecha' => now()->format('Y-m-d\TH:i:s'),
            'descripcion' => $description,
            'generar_bonos' => '1',
            'xml_lineas' => '<?xml version="1.0" encoding="UTF-8" ?><lineas>'.$lineas.'</lineas>',
        ]);

        // La respuesta es el id de la generación: <response>100267866</response>.
        $batchId = is_array($response)
            ? (string) ($response['response'] ?? $response[0] ?? '')
            : (string) $response;

        if (trim($batchId) === '') {
            throw new \RuntimeException('Gestión no devolvió el identificador de la generación de bonos.');
        }

        return trim($batchId);
    }
}
