<?php

namespace Modules\HelpdeskBirthday\Services\Redemption;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Support\HmacSigner;
use Throwable;

/**
 * Lee los canjes por el bridge de PrestaShop (`voucher.redemptions`).
 *
 * Es la vía buena para producción: no necesita que webadmin alcance la base de
 * datos de la tienda, y la consulta la resuelve PrestaShop, que es quien conoce
 * su propio esquema.
 *
 * El contrato de las filas que devuelve —y que iguala SqlRedemptionReader— es:
 *
 *   code, code_source, voucher_name, cart_rule_id,
 *   order_id, order_reference, order_total, order_date, order_valid, order_state,
 *   customer_id, customer_email, discount,
 *   erp => [marked, bono, operation, response, sale_amount, marked_at]
 *
 * `code` puede venir a null y no es un error: PrestaShop borra la `cart_rule`
 * al consumirla, así que en la mayoría de los canjes antiguos el código solo
 * sobrevive en el registro de Gestión, y en 363 de ellos en ninguna parte. Esos
 * se atribuyen por el email del pedido.
 */
class BridgeRedemptionReader implements BirthdayRedemptionReader
{
    /** Tope del helper del bridge; pedir más no trae más. */
    private const MAX_PER_PAGE = 500;

    public function isAvailable(): bool
    {
        return $this->apiUrl() !== '' && $this->secret() !== '';
    }

    public function label(): string
    {
        return 'bridge';
    }

    public function redemptions(?string $from, ?string $to, ?string $nameLike = null, int $limit = 500, int $offset = 0): array
    {
        $data = $this->call([
            'from' => $from,
            'to' => $to,
            'name_like' => $nameLike,
            'limit' => min($limit, self::MAX_PER_PAGE),
            'offset' => $offset,
        ]);

        return $data['redemptions'] ?? [];
    }

    public function count(?string $from, ?string $to, ?string $nameLike = null): int
    {
        // limit 1 en vez de 0: el helper obliga a un mínimo de 1, y lo que se
        // quiere de esta llamada es el total, no las filas.
        $data = $this->call([
            'from' => $from,
            'to' => $to,
            'name_like' => $nameLike,
            'limit' => 1,
            'offset' => 0,
        ]);

        return (int) ($data['total'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function call(array $payload): array
    {
        if (! $this->isAvailable()) {
            Log::warning('[HelpdeskBirthday] El bridge de PrestaShop no está configurado.');

            return [];
        }

        // El bridge rechaza claves nulas en algunos filtros, y de paso el
        // payload entra en la clave de caché: cuantas menos, mejor reutiliza.
        $payload = array_filter(
            array_merge(['action' => 'voucher.redemptions'], $payload),
            static fn ($v): bool => $v !== null && $v !== ''
        );

        $body = json_encode($payload);
        $timestamp = time();

        try {
            $response = Http::connectTimeout((int) config('helpdeskprestashop.http_connect_timeout', 2))
                // Más generoso que el resto de llamadas al bridge: esto lee
                // páginas de 500 canjes, no la ficha de un cliente.
                ->timeout((int) config('helpdeskbirthday.bridge_timeout', 30))
                ->withHeaders([
                    'X-Alsernet-Signature' => HmacSigner::sign($this->secret(), $timestamp, $body),
                    'X-Alsernet-Timestamp' => (string) $timestamp,
                    // Es una LECTURA: no lleva clave de idempotencia, y no debe
                    // figurar en $writeActions del bridge.
                    'X-Alsernet-Action' => 'voucher.redemptions',
                    'Content-Type' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($this->apiUrl());
        } catch (Throwable $e) {
            Log::warning('[HelpdeskBirthday] No se pudo hablar con el bridge de PrestaShop', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('[HelpdeskBirthday] El bridge respondió con error al pedir los canjes', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            return [];
        }

        $json = $response->json();

        if (($json['ok'] ?? false) !== true) {
            Log::warning('[HelpdeskBirthday] El bridge rechazó la consulta de canjes', [
                'error' => $json['error'] ?? null,
            ]);

            return [];
        }

        return (array) ($json['data'] ?? []);
    }

    private function apiUrl(): string
    {
        return (string) config('helpdeskprestashop.api_url', '');
    }

    private function secret(): string
    {
        return (string) config('helpdeskprestashop.webhook_secret', '');
    }
}
