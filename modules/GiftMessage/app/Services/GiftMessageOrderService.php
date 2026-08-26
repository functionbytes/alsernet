<?php

namespace Modules\GiftMessage\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Support\HmacSigner;

class GiftMessageOrderService
{
    public function __construct(
        private readonly GiftMessageGenerationService $generationService,
        private readonly GiftMessageConfigService $configService
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function ordersWithGiftMessage(): array
    {
        $orders = $this->callBridge('giftmessage.orders_with_message', [])['orders'] ?? [];

        return $this->attachExistingGenerations($orders);
    }

    /**
     * Cada id se resuelve contra el numero de gestion (npedidocli) y contra el
     * id_order de PrestaShop, asi que vale cualquiera de los dos. La clave del
     * payload sigue siendo `gestion_ids` porque es el contrato del bridge.
     *
     * @param  array<int, string>  $ids
     * @return array<int, array<string, mixed>>
     */
    public function searchByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $orders = $this->callBridge('giftmessage.search_by_gestion', ['gestion_ids' => $ids])['orders'] ?? [];

        return $this->attachPrintPreview($this->attachExistingGenerations($orders));
    }

    /**
     * Adelanta a que tamano va a salir el mensaje de cada pedido, para poder
     * avisar ANTES de mandar a imprimir un lote de doscientas tarjetas y
     * descubrir luego que varias salieron ilegibles.
     *
     * @param  array<int, array<string, mixed>>  $orders
     * @return array<int, array<string, mixed>>
     */
    private function attachPrintPreview(array $orders): array
    {
        if ($orders === []) {
            return $orders;
        }

        $pdfService = app(GiftMessagePdfService::class);
        $maxLength = (int) ($this->configService->current()->max_message_length ?: 0);

        return array_map(function (array $order) use ($pdfService, $maxLength) {
            $message = (string) ($order['gift_message'] ?? '');
            $envelope = $pdfService->previewMetrics('envelope', $message, (string) ($order['npedidocli'] ?? ''));
            $card = $pdfService->previewMetrics('card', $message, (string) ($order['npedidocli'] ?? ''));

            $order['print_preview'] = [
                'length' => mb_strlen($message),
                'too_long' => $maxLength > 0 && mb_strlen($message) > $maxLength,
                'envelope' => ['font_size' => $envelope['t1']['font_size'], 'fits' => $envelope['t1']['fits']],
                'card' => ['font_size' => $card['t1']['font_size'], 'fits' => $card['t1']['fits']],
                'min_font_size' => $envelope['t1']['min_font_size'],
            ];

            return $order;
        }, $orders);
    }

    /**
     * Anade `existing_generations` a cada fila: los PDF ya generados antes
     * para ese mismo pedido (por npedidocli, id_gestion o id_order), para que
     * el listado pueda ofrecer "ver el PDF" en vez de obligar a regenerarlo.
     *
     * @param  array<int, array<string, mixed>>  $orders
     * @return array<int, array<string, mixed>>
     */
    private function attachExistingGenerations(array $orders): array
    {
        if ($orders === []) {
            return $orders;
        }

        $index = $this->generationService->orderNumberIndex();

        return array_map(function (array $order) use ($index) {
            $keys = array_filter([
                isset($order['npedidocli']) ? (string) $order['npedidocli'] : null,
                isset($order['id_gestion']) ? (string) $order['id_gestion'] : null,
                isset($order['id_order']) ? (string) $order['id_order'] : null,
            ]);

            $matches = [];
            foreach ($keys as $key) {
                foreach ($index[$key] ?? [] as $match) {
                    $matches[$match['id']] = $match;
                }
            }

            // Mas reciente primero y una sola por tipo: aunque queden lotes
            // antiguos que incluyan este pedido, en el listado solo interesa el
            // PDF vigente de sobre y el de tarjeta. Sin esto la columna PDF
            // acumulaba "Ver tarjeta / Ver sobre" repetidos y ninguno se
            // distinguia del otro.
            usort($matches, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

            $latestByType = [];
            foreach ($matches as $match) {
                $latestByType[$match['type']] ??= $match;
            }

            $order['existing_generations'] = array_values($latestByType);

            return $order;
        }, $orders);
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
