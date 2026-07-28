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
