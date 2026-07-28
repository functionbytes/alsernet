<?php

namespace Modules\GiftMessage\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Support\HmacSigner;

class GiftMessageOrderService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function ordersWithGiftMessage(): array
    {
        return $this->callBridge('giftmessage.orders_with_message', [])['orders'] ?? [];
    }

    /**
     * @param  array<int, string>  $gestionIds
     * @return array<int, array<string, mixed>>
     */
    public function searchByGestion(array $gestionIds): array
    {
        if ($gestionIds === []) {
            return [];
        }

        return $this->callBridge('giftmessage.search_by_gestion', ['gestion_ids' => $gestionIds])['orders'] ?? [];
    }

    /**
     * Busca un pedido puntual dentro del listado que expone
     * `giftmessage.orders_with_message` (pedidos de los ultimos 30 dias con
     * `current_state` 27 o 59). No existe todavia una accion de bridge para
     * resolver un pedido suelto por id, asi que un pedido que solo aparece via
     * `searchByGestion` (sin ese filtro de fecha/estado) no se encontrara aqui.
     *
     * El flujo de generacion de PDF (`GiftMessageGenerationController::generate()`)
     * ya no depende de este metodo: recibe las filas completas que el frontend
     * ya tenia resueltas en pantalla, evitando este problema por completo. Se
     * mantiene por si un futuro consumidor necesita resolver un pedido suelto.
     *
     * @return array<string, mixed>|null
     */
    public function orderForPdf(int $orderId): ?array
    {
        foreach ($this->ordersWithGiftMessage() as $order) {
            if ((int) ($order['id_order'] ?? 0) === $orderId) {
                return $order;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function callBridge(string $action, array $payload): ?array
    {
        $apiUrl = (string) config('giftmessage.bridge_url', '');
        $secret = (string) config('giftmessage.bridge_secret', '');

        if ($apiUrl === '' || $secret === '') {
            Log::info('GiftMessage: bridge URL o secreto no configurados — se devuelve null.', [
                'action' => $action,
            ]);

            return null;
        }

        $bodyJson = json_encode(array_merge(['action' => $action], $payload));
        $timestamp = time();
        $signature = HmacSigner::sign($secret, $timestamp, $bodyJson);

        try {
            $response = Http::connectTimeout((int) config('giftmessage.bridge_http_connect_timeout', 2))
                ->timeout((int) config('giftmessage.bridge_http_timeout', 10))
                ->withHeaders([
                    'X-Alsernet-Signature' => $signature,
                    'X-Alsernet-Timestamp' => (string) $timestamp,
                    'X-Alsernet-Action' => $action,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($bodyJson, 'application/json')
                ->post($apiUrl);

            if (! $response->successful()) {
                Log::warning('GiftMessage: respuesta no exitosa del bridge.', [
                    'action' => $action,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $decoded = $response->json();

            if (! ($decoded['ok'] ?? false)) {
                Log::warning('GiftMessage: el bridge devolvio ok=false.', [
                    'action' => $action,
                    'error' => $decoded['error'] ?? 'unknown',
                ]);

                return null;
            }

            return $decoded['data'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('GiftMessage: error de red al llamar al bridge.', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
