<?php

namespace Modules\Engagement\Connectors;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Modules\Engagement\Models\PlatformIntegration;

/**
 * Connector para el ERP propio (caso Alvarez — Oracle, en otro proyecto).
 *
 * Llama a /api/erp/customer/... del módulo Erp del proyecto manager.
 * Soporta más datos que cualquier ecommerce: deudas, balance, fidelización,
 * albaranes, facturas, vales, bonos, cuentas bancarias.
 *
 * config esperado:
 *   {
 *     "auth_token": "<encrypted bearer token>",
 *     "lookup_strategy": "email" (default) | "external_id_only"
 *   }
 *
 * store_url debe apuntar al base path de la API ERP, ej:
 *   https://manager.alvarez.local/api/erp
 */
class ErpCustomerConnector extends AbstractCustomerDataConnector
{
    protected array $supportedActions = [
        'profile',
        'orders',
        'orderDetail',
        'addresses',
        'vouchers',
        'messages',
        'deliveryNotes',
        'invoices',
        'payments',
        'debts',
        'balance',
        'bonuses',
        'loyaltyPoints',
    ];

    public function platform(): string
    {
        return PlatformIntegration::PLATFORM_ERP;
    }

    public function profile(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->remember($i, 'profile', $lookup, $force, function () use ($i, $lookup) {
            $customerId = $this->resolveExternalId($i, $lookup);

            if (! $customerId) {
                return null;
            }

            $payload = $this->fetch($i, "/customer/{$customerId}");
            $personal = $this->fetch($i, "/customer/{$customerId}/personal");

            return [
                'id' => (int) $customerId,
                'summary' => $payload,
                'personal' => $personal,
            ];
        });
    }

    public function orders(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->fetchByCustomer($i, 'orders', $lookup, $force, '/orders');
    }

    public function orderDetail(PlatformIntegration $i, string|int $orderId, bool $force = false): array
    {
        $key = ['order_id' => $orderId];

        return $this->remember($i, 'orderDetail', $key, $force, function () use ($i, $orderId) {
            // ERP requires customer_id in the URL — front must pass it as part of orderId
            // Convention: "{customer_id}:{order_id}"
            $parts = explode(':', (string) $orderId, 2);
            if (count($parts) !== 2) {
                return null;
            }

            return $this->fetch($i, "/customer/{$parts[0]}/orders/{$parts[1]}");
        });
    }

    public function addresses(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->fetchByCustomer($i, 'addresses', $lookup, $force, '/addresses');
    }

    public function vouchers(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->fetchByCustomer($i, 'vouchers', $lookup, $force, '/vouchers');
    }

    public function messages(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        // ERP no expone "messages" como tal — devolvemos los catálogos como
        // proxy de "comunicaciones programadas" si en el futuro se mapea.
        return $this->fetchByCustomer($i, 'messages', $lookup, $force, '/catalogs');
    }

    public function deliveryNotes(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->fetchByCustomer($i, 'deliveryNotes', $lookup, $force, '/delivery-notes');
    }

    public function invoices(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->fetchByCustomer($i, 'invoices', $lookup, $force, '/invoices');
    }

    public function payments(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->fetchByCustomer($i, 'payments', $lookup, $force, '/payments');
    }

    public function debts(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->fetchByCustomer($i, 'debts', $lookup, $force, '/debts');
    }

    public function balance(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->fetchByCustomer($i, 'balance', $lookup, $force, '/balance');
    }

    public function bonuses(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->fetchByCustomer($i, 'bonuses', $lookup, $force, '/bonuses');
    }

    public function loyaltyPoints(PlatformIntegration $i, array $lookup, bool $force = false): array
    {
        return $this->fetchByCustomer($i, 'loyaltyPoints', $lookup, $force, '/loyalty-points');
    }

    /**
     * Helper común: resuelve external_id y llama a /customer/{id}{path}.
     */
    protected function fetchByCustomer(
        PlatformIntegration $integration,
        string $action,
        array $lookup,
        bool $force,
        string $path,
    ): array {
        return $this->remember($integration, $action, $lookup, $force, function () use ($integration, $lookup, $path) {
            $customerId = $this->resolveExternalId($integration, $lookup);

            if (! $customerId) {
                return null;
            }

            return $this->fetch($integration, "/customer/{$customerId}{$path}");
        });
    }

    /**
     * Resuelve el IDCLIENTE en Oracle a partir del lookup. Si viene
     * external_id se usa directamente; si no, se hace /customer/search?q=email
     * y se cachea el mapping email→id por 1h.
     */
    protected function resolveExternalId(PlatformIntegration $integration, array $lookup): ?int
    {
        if (! empty($lookup['external_id'])) {
            return (int) $lookup['external_id'];
        }

        $email = $lookup['email'] ?? null;
        if (! $email) {
            return null;
        }

        $key = sprintf('engagement.connector.erp.%d.email_to_id.%s', $integration->id, md5(strtolower($email)));

        return Cache::remember($key, 3600, function () use ($integration, $email) {
            $response = Http::withHeaders($this->headers($integration))
                ->timeout(8)
                ->get(rtrim($integration->store_url, '/').'/customer/search', ['q' => $email, 'limit' => 1]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json('data') ?? [];

            return ! empty($data[0]['id']) ? (int) $data[0]['id'] : null;
        });
    }

    /**
     * Realiza una petición GET al ERP y devuelve el `data` de la respuesta.
     */
    protected function fetch(PlatformIntegration $integration, string $path): ?array
    {
        $response = Http::withHeaders($this->headers($integration))
            ->timeout(8)
            ->get(rtrim($integration->store_url, '/').$path);

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new \RuntimeException("ERP API HTTP {$response->status()} on {$path}");
        }

        $json = $response->json();
        if (! is_array($json) || ! ($json['success'] ?? false)) {
            throw new \RuntimeException($json['error'] ?? 'ERP API returned !success');
        }

        return $json['data'] ?? [];
    }

    /**
     * Headers de auth y JSON. El token se guarda encriptado en config.
     */
    protected function headers(PlatformIntegration $integration): array
    {
        $token = $this->resolveAuthToken($integration);

        $headers = [
            'Accept' => 'application/json',
        ];

        if ($token) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return $headers;
    }

    /**
     * Decripta el token Sanctum guardado en config.auth_token. Soporta token
     * en plano para entornos de desarrollo (no encriptado).
     */
    protected function resolveAuthToken(PlatformIntegration $integration): ?string
    {
        $config = (array) ($integration->config ?? []);
        $raw = $config['auth_token'] ?? null;

        if (! $raw) {
            return null;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return $raw;
        }
    }
}
