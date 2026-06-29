<?php

namespace Modules\Engagement\Connectors;

use Illuminate\Support\Facades\Http;
use Modules\Engagement\Models\PlatformIntegration;

/**
 * Connector que llama al endpoint api.php instalado en PrestaShop por el
 * plugin alsernet_chat (v1.1.0+). Cada acción se firma HMAC-SHA256 con
 * el webhook_secret de la integración.
 */
class PrestaShopCustomerConnector extends AbstractCustomerDataConnector
{
    protected array $supportedActions = [
        'profile',
        'orders',
        'orderDetail',
        'addresses',
        'returns',
        'vouchers',
        'cart',
        'messages',
    ];

    public function platform(): string
    {
        return PlatformIntegration::PLATFORM_PRESTASHOP;
    }

    public function profile(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->remember($i, 'profile', $lookup, $force,
            fn () => $this->call($i, 'customer.profile', ['lookup' => $lookup]));
    }

    public function orders(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->remember($i, 'orders', $lookup, $force,
            fn () => $this->call($i, 'customer.orders', ['lookup' => $lookup]));
    }

    public function orderDetail(PlatformIntegration $i, string|int $orderId, bool $force = false): array
    {
        $lookup = ['order_id' => (int) $orderId];

        return $this->remember($i, 'orderDetail', $lookup, $force,
            fn () => $this->call($i, 'order.detail', ['order_id' => (int) $orderId]));
    }

    public function addresses(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->remember($i, 'addresses', $lookup, $force,
            fn () => $this->call($i, 'customer.addresses', ['lookup' => $lookup]));
    }

    public function returns(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->remember($i, 'returns', $lookup, $force,
            fn () => $this->call($i, 'customer.returns', ['lookup' => $lookup]));
    }

    public function vouchers(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->remember($i, 'vouchers', $lookup, $force,
            fn () => $this->call($i, 'customer.vouchers', ['lookup' => $lookup]));
    }

    public function cart(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->remember($i, 'cart', $lookup, $force,
            fn () => $this->call($i, 'customer.cart', ['lookup' => $lookup]));
    }

    public function messages(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->remember($i, 'messages', $lookup, $force,
            fn () => $this->call($i, 'customer.messages', ['lookup' => $lookup]));
    }

    /**
     * Inicia una devolución (no usa cache, siempre escribe).
     *
     * @param  array<int, array{order_detail_id: int, quantity: int}>  $items
     */
    public function startOrderReturn(PlatformIntegration $integration, int $orderId, array $items, ?string $reason = null): array
    {
        try {
            $data = $this->call($integration, 'order.start_return', [
                'order_id' => $orderId,
                'items' => ['lines' => $items, 'reason' => $reason],
            ]);

            return $this->ok($data);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Realiza la llamada HTTP firmada al api.php del plugin PrestaShop.
     */
    protected function call(PlatformIntegration $integration, string $action, array $body): ?array
    {
        $endpoint = rtrim($integration->store_url, '/').'/modules/alsernet_chat/api.php';
        $body = array_merge(['action' => $action], $body);
        $rawBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $rawBody, $integration->webhook_secret);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Alsernet-Signature' => $signature,
            'X-Alsernet-Action' => $action,
        ])
            ->timeout(8)
            ->withBody($rawBody, 'application/json')
            ->post($endpoint);

        if ($response->status() === 401) {
            throw new \RuntimeException('PrestaShop API returned 401 (firma inválida o secret desincronizado)');
        }

        if (! $response->successful()) {
            throw new \RuntimeException("PrestaShop API HTTP {$response->status()}");
        }

        $json = $response->json();
        if (! is_array($json) || ! ($json['ok'] ?? false)) {
            throw new \RuntimeException($json['error'] ?? 'PrestaShop API returned !ok');
        }

        return $this->fixEncoding($json['data'] ?? null);
    }

    private function fixEncoding(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->fixEncoding($v), $value);
        }

        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        // Fix double-encoded UTF-8 (common when PS MySQL connection uses Latin-1 charset).
        // Strategy: collapse each UTF-8 code-point to its raw byte value (ISO-8859-1),
        // then check if those raw bytes form valid UTF-8 with fewer characters.
        // If yes, the string was double-encoded and the collapsed version is the correct one.
        $raw = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');

        if (mb_check_encoding($raw, 'UTF-8') && mb_strlen($raw, 'UTF-8') < mb_strlen($value, 'UTF-8')) {
            return $raw;
        }

        return $value;
    }
}
