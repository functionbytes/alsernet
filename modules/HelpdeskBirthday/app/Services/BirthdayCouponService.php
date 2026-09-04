<?php

namespace Modules\HelpdeskBirthday\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Services\ErpService;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Throwable;

/**
 * Resuelve el cupón del día.
 *
 * El código lo fija un admin; las fechas de validez y el importe se consultan a
 * gestión para que la plantilla diga lo que el bono vale de verdad y no lo que
 * alguien tecleó hace tres meses. Si gestión no responde se cae a los valores
 * manuales y la campaña queda marcada como `manual`, visible en el panel.
 *
 * Contrato de gestión (GET /api-gestion/bono/{idbono}/), respuesta XML:
 *   fvalidez_desde, fvalidez_hasta, importe, importeminimoventa,
 *   descripcion_tipo, estado_extendido
 */
class BirthdayCouponService
{
    /** AlvarezERP::MARCAR_BONO_CONSUMIR — marcar el bono como gastado. */
    private const OPERATION_CONSUME = 2;

    public function __construct(
        private readonly ErpService $erp,
    ) {}

    /**
     * @param  array<string, mixed>  $settings  valores del panel; caen a la config
     * @return array<string, mixed> campos de cupón listos para la campaña
     */
    public function resolve(array $settings = []): array
    {
        $code = trim((string) ($settings['coupon_code'] ?? config('helpdeskbirthday.coupon.code', '')));
        $verification = trim((string) ($settings['coupon_verification_code'] ?? config('helpdeskbirthday.coupon.verification_code', '')));

        if ($code === '') {
            return [];
        }

        $manual = [
            'coupon_code' => $this->publicCode($code, $verification),
            'coupon_valid_from' => $this->date($settings['coupon_valid_from'] ?? null),
            'coupon_valid_to' => $this->date($settings['coupon_valid_to'] ?? null),
            'coupon_amount' => $this->decimal($settings['coupon_amount'] ?? null),
            'coupon_min_purchase' => $this->decimal($settings['coupon_min_purchase'] ?? null),
            'coupon_source' => BirthdayCampaign::SOURCE_MANUAL,
            'coupon_meta' => null,
        ];

        $shouldValidate = (bool) ($settings['validate_against_erp']
            ?? config('helpdeskbirthday.coupon.validate_against_erp', true));

        if (! $shouldValidate) {
            return $manual;
        }

        $fromErp = $this->queryErp($code, $verification);

        // Sin respuesta utilizable nos quedamos con lo configurado a mano: es
        // mejor enviar el cupón con las fechas del panel que no felicitar.
        return $fromErp === null ? $manual : array_merge($manual, $fromErp);
    }

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

        if ($response === null) {
            return ['ok' => false, 'message' => 'Gestión no respondió al marcar el bono.'];
        }

        Log::info('[HelpdeskBirthday] Bono marcado en gestión', [
            'coupon' => $publicCode,
            'amount' => $saleAmount,
        ]);

        return ['ok' => true, 'message' => 'Bono marcado como consumido en gestión.'];
    }

    /**
     * @return array<string, mixed>|null null si gestión no da una respuesta usable
     */
    private function queryErp(string $code, string $verification): ?array
    {
        try {
            $response = $this->erp->consultaBono($code, $verification, 0.0, (string) config('helpdeskbirthday.coupon.origin', 'gestion'));
        } catch (Throwable $e) {
            Log::warning('[HelpdeskBirthday] No se pudo consultar el bono en gestión', [
                'coupon' => $code,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! ($response['success'] ?? false) || ! is_array($response['data'] ?? null)) {
            Log::warning('[HelpdeskBirthday] Gestión no reconoce el bono configurado', [
                'coupon' => $code,
                'message' => $response['message'] ?? null,
            ]);

            return null;
        }

        $data = $response['data'];

        return [
            'coupon_valid_from' => $this->date($data['fvalidez_desde'] ?? null),
            'coupon_valid_to' => $this->date($data['fvalidez_hasta'] ?? null),
            'coupon_amount' => $this->decimal($data['importe'] ?? null),
            'coupon_min_purchase' => $this->decimal($data['importeminimoventa'] ?? null),
            'coupon_source' => BirthdayCampaign::SOURCE_ERP,
            'coupon_meta' => [
                'type' => $data['descripcion_tipo'] ?? null,
                'state' => $data['estado_extendido'] ?? null,
                'checked_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Formato que ve el cliente. PrestaShop crea el cart_rule con el código
     * "{idbono}-{codigo_verificacion}" (ver CartRule::createCartRuleAlvarez),
     * así que el correo tiene que decir exactamente eso o no se lo podrán canjear.
     */
    private function publicCode(string $code, string $verification): string
    {
        return $verification !== '' ? "{$code}-{$verification}" : $code;
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
