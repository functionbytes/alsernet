<?php

namespace Modules\HelpdeskBirthday\Services;

use Illuminate\Support\Facades\Log;
use Modules\Erp\Services\ErpService;
use Throwable;

/**
 * Operaciones sobre un bono concreto de Gestión.
 *
 * No hay cupón del día ni código único: cada cliente recibe el suyo, emitido
 * por Gestión al preparar la campaña (ver BirthdayBonoGenerator). Lo que queda
 * aquí es lo que se hace con un bono YA emitido — hoy, marcarlo como consumido
 * cuando la tienda se lo descontó al cliente pero el ERP no se enteró.
 *
 * Contrato de gestión (respuestas XML, no JSON):
 *   GET /api-gestion/bono/{idbono}/?codigo_verificacion=…&importe_venta=…&origen=…
 *   PUT /api-gestion/marcar-bono/{idbono}/?origen=…
 */
class BirthdayCouponService
{
    /** AlvarezERP::MARCAR_BONO_CONSUMIR — marcar el bono como gastado. */
    private const OPERATION_CONSUME = 2;

    public function __construct(
        private readonly ErpService $erp,
    ) {}

    /**
     * Reintenta marcar el bono como consumido en gestión.
     *
     * ESTO ESCRIBE EN EL ERP. Se usa solo para el caso en que el cliente ya se
     * llevó el descuento en la tienda pero el bono no llegó a descontarse:
     * hasta que se marque, ese bono se puede volver a gastar.
     *
     * `operacion` = 2 (consumir), el mismo valor que usa el override de
     * PrestaShop (AlvarezERP::MARCAR_BONO_CONSUMIR).
     *
     * @return array{ok: bool, message: string}
     */
    public function markAsUsed(string $publicCode, float $saleAmount): array
    {
        // El código público es "{idbono}-{codigo_verificacion}".
        [$idBono, $verification] = array_pad(explode('-', $publicCode, 2), 2, '');

        if ($idBono === '') {
            return ['ok' => false, 'message' => 'Código de cupón vacío.'];
        }

        try {
            $response = $this->erp->marcarBono(
                idBono: $idBono,
                operacion: (string) self::OPERATION_CONSUME,
                codigoVerificacion: $verification,
                importeVenta: $saleAmount,
                importeInicialTarjetaRegalo: 0.0,
                origen: (string) config('helpdeskbirthday.coupon.origin', 'gestion'),
            );
        } catch (Throwable $e) {
            Log::error('[HelpdeskBirthday] Falló el marcado del bono en gestión', [
                'coupon' => $publicCode,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }

        // Se mira `success`, no si la respuesta llegó: Gestión rechaza el
        // consumo con un 400 y el motivo en texto ("No se permite consumir un
        // bono que no se encuentre activo", "El codigo de verificacion no es
        // correcto", "El bono no cumple con el importe de venta minimo").
        // Dando por buena cualquier respuesta, el panel decía "bono marcado"
        // sobre un canje que el ERP había rechazado.
        if (($response['success'] ?? false) !== true) {
            Log::warning('[HelpdeskBirthday] Gestión rechazó el marcado del bono', [
                'coupon' => $publicCode,
                'amount' => $saleAmount,
                'reason' => $response['message'] ?? null,
            ]);

            return [
                'ok' => false,
                // El texto de Gestión es accionable —dice qué pasa con ese
                // bono— así que se enseña tal cual en vez de un genérico.
                'message' => (string) ($response['message'] ?? 'Gestión no respondió al marcar el bono.'),
            ];
        }

        Log::info('[HelpdeskBirthday] Bono marcado en gestión', [
            'coupon' => $publicCode,
            'amount' => $saleAmount,
        ]);

        return ['ok' => true, 'message' => 'Bono marcado como consumido en gestión.'];
    }
}
