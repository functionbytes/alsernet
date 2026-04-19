<?php

namespace Modules\Mailrelay\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Contracts\MailProviderInterface;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Exceptions\ProviderException;
use Modules\Mailrelay\Providers\Mail\AwsSesProvider;
use Modules\Mailrelay\Providers\Mail\MailrelayProvider;
use Modules\Mailrelay\Providers\Mail\MailtrapProvider;
use Modules\Mailrelay\Providers\Mail\PostmarkProvider;
use Modules\Mailrelay\Providers\Mail\SendGridProvider;

/**
 * Manager para crear y gestionar providers de email
 * Implementa patrón Factory
 */
class ProviderManager
{
    /**
     * Mapa de drivers disponibles
     */
    private array $providers = [
        'mailrelay' => MailrelayProvider::class,
        'mailtrap' => MailtrapProvider::class,
        'sendgrid' => SendGridProvider::class,
        'aws_ses' => AwsSesProvider::class,
        'postmark' => PostmarkProvider::class,
    ];

    /**
     * Cache de instancias de providers
     */
    private array $instances = [];

    /** Cache key for the full providers collection */
    private const CACHE_KEY_ALL = 'mailrelay.providers.all';

    /** TTL for provider cache in seconds (1 hour) */
    private const CACHE_TTL = 3600;

    /**
     * Obtener la colección completa de providers desde cache (1 query total por hora).
     *
     * @return Collection<int, MailProvider>
     */
    private function allCached(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_ALL,
            self::CACHE_TTL,
            fn (): Collection => MailProvider::query()->orderBy('priority', 'desc')->orderBy('name')->get()
        );
    }

    /**
     * Obtener instancia de provider por nombre del driver
     *
     * @param  string  $name  Nombre del driver (mailrelay, mailtrap)
     *
     * @throws \Exception Si el provider no está configurado o no existe
     */
    public function driver(string $name): MailProviderInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $providerConfig = $this->allCached()
            ->where('driver', $name)
            ->where('is_active', true)
            ->first();

        if (! $providerConfig) {
            throw new \Exception("Provider '{$name}' no está configurado o no está activo");
        }

        $this->instances[$name] = $this->createProvider($providerConfig->driver, $providerConfig->credentials);

        return $this->instances[$name];
    }

    /**
     * Obtener provider por ID de configuración
     *
     * @param  int  $providerId  ID de mail_providers
     *
     * @throws \Exception Si el provider no existe o está inactivo
     */
    public function byId(int $providerId): MailProviderInterface
    {
        $cacheKey = "mail_provider_{$providerId}";

        if (isset($this->instances[$cacheKey])) {
            return $this->instances[$cacheKey];
        }

        $providerConfig = $this->allCached()->firstWhere('id', $providerId);

        if (! $providerConfig) {
            throw new \Exception("Provider con id '{$providerId}' no existe");
        }

        if (! $providerConfig->is_active) {
            throw new \Exception("Provider '{$providerConfig->name}' está desactivado");
        }

        $this->instances[$cacheKey] = $this->createProvider(
            $providerConfig->driver,
            $providerConfig->credentials
        );

        return $this->instances[$cacheKey];
    }

    /**
     * Obtener provider por defecto
     *
     * @throws \Exception Si no hay provider por defecto configurado
     */
    public function default(): MailProviderInterface
    {
        $defaultProvider = $this->allCached()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (! $defaultProvider) {
            throw new \Exception('No hay provider por defecto configurado');
        }

        return $this->byId($defaultProvider->id);
    }

    /**
     * Crear instancia del provider según tipo
     *
     * @throws \Exception Si el driver no está soportado
     */
    private function createProvider(string $driver, array $credentials): MailProviderInterface
    {
        if (! isset($this->providers[$driver])) {
            throw new \Exception("Provider driver '{$driver}' no está soportado");
        }

        $providerClass = $this->providers[$driver];

        return new $providerClass($credentials);
    }

    /**
     * Listar drivers disponibles
     *
     * @return array Lista de nombres de drivers
     */
    public function availableDrivers(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Listar providers configurados y activos
     */
    public function configured(): Collection
    {
        return $this->allCached()->where('is_active', true)->values();
    }

    /**
     * Listar todos los providers (activos e inactivos)
     */
    public function all(): Collection
    {
        return $this->allCached()
            ->sortByDesc('is_default')
            ->sortByDesc('priority')
            ->sortBy('name')
            ->values();
    }

    /**
     * Test de conexión para un provider
     *
     * @param  int  $providerId  ID del provider a testear
     * @return array ['success' => bool, 'message' => string]
     */
    public function testProvider(int $providerId): array
    {
        try {
            $provider = $this->byId($providerId);
            $isConnected = $provider->testConnection();

            // Actualizar estado en BD
            $providerConfig = MailProvider::find($providerId);
            if ($providerConfig) {
                $providerConfig->update([
                    'connection_ok' => $isConnected,
                    'last_tested_at' => now(),
                ]);
            }

            return [
                'success' => $isConnected,
                'message' => $isConnected
                    ? "Conexión exitosa con {$provider->getName()}"
                    : "Fallo en la conexión con {$provider->getName()}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Obtener información de un driver
     *
     * @param  string  $driver  Nombre del driver
     * @return array Información del driver
     */
    public function getDriverInfo(string $driver): array
    {
        $info = [
            'mailrelay' => [
                'name' => 'Mailrelay',
                'description' => 'Plataforma profesional de email marketing con alto deliverability',
                'features' => ['Bulk sending', 'Templates', 'Statistics', 'High volume'],
                'required_credentials' => ['api_url', 'api_key', 'from_email', 'from_name'],
            ],
            'mailtrap' => [
                'name' => 'Mailtrap',
                'description' => 'Email testing para desarrollo y staging',
                'features' => ['Email testing', 'Sandbox', 'Production sending', 'Webhooks'],
                'required_credentials' => ['api_token', 'from_email', 'from_name'],
            ],
            'sendgrid' => [
                'name' => 'SendGrid',
                'description' => 'Plataforma de envío de emails transaccionales y marketing de Twilio',
                'features' => ['High deliverability', 'Bulk sending', 'Templates', 'Analytics', 'Webhooks'],
                'required_credentials' => ['api_key', 'from_email', 'from_name'],
            ],
            'aws_ses' => [
                'name' => 'AWS SES',
                'description' => 'Amazon Simple Email Service - Servicio escalable de envío de emails',
                'features' => ['High volume', 'Low cost', 'Templates', 'Configuration sets', 'CloudWatch'],
                'required_credentials' => ['access_key', 'secret_key', 'region', 'from_email', 'from_name'],
            ],
            'postmark' => [
                'name' => 'Postmark',
                'description' => 'Servicio especializado en emails transaccionales con alto deliverability',
                'features' => ['Fast delivery', 'Templates', 'Analytics', 'Message streams', 'Webhooks'],
                'required_credentials' => ['server_token', 'from_email', 'from_name'],
            ],
        ];

        return $info[$driver] ?? [
            'name' => $driver,
            'description' => 'Provider no documentado',
            'features' => [],
            'required_credentials' => [],
        ];
    }

    /**
     * Validar credenciales de un provider sin guardarlo
     *
     * @param  string  $driver  Nombre del driver
     * @param  array  $credentials  Credenciales a validar
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateCredentials(string $driver, array $credentials): array
    {
        try {
            $provider = $this->createProvider($driver, $credentials);
            $isValid = $provider->testConnection();

            return [
                'valid' => $isValid,
                'message' => $isValid
                    ? 'Credenciales válidas'
                    : 'No se pudo conectar con las credenciales proporcionadas',
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Enviar email con failover automático entre providers
     *
     * @param  string  $to  Email destinatario
     * @param  string  $subject  Asunto
     * @param  string  $htmlContent  Contenido HTML
     * @param  array  $options  Opciones adicionales
     * @param  int|null  $preferredProviderId  ID del provider preferido (se intenta primero)
     * @return array ['success' => bool, 'message_id' => string|null, 'provider_id' => int, 'provider_name' => string]
     *
     * @throws ProviderException Si todos los providers fallan
     */
    public function sendWithFailover(
        string $to,
        string $subject,
        string $htmlContent,
        array $options = [],
        ?int $preferredProviderId = null,
    ): array {
        $providers = $this->getOrderedProviders($preferredProviderId);

        if ($providers->isEmpty()) {
            throw ProviderException::notConfigured();
        }

        $errors = [];

        foreach ($providers as $providerConfig) {
            try {
                $provider = $this->createProvider($providerConfig->driver, $providerConfig->credentials);
                $result = $provider->send($to, $subject, $htmlContent, $options);

                if ($result['success']) {
                    return array_merge($result, [
                        'provider_id' => $providerConfig->id,
                        'provider_name' => $provider->getName(),
                    ]);
                }

                $errors[$providerConfig->name] = $result['error'] ?? 'Unknown error';
            } catch (\Throwable $e) {
                $errors[$providerConfig->name] = $e->getMessage();
            }

            Log::warning('Provider failover: send failed, trying next', [
                'provider' => $providerConfig->name,
                'to' => $to,
                'error' => $errors[$providerConfig->name],
            ]);
        }

        throw ProviderException::allProvidersFailed($errors);
    }

    /**
     * Enviar bulk con failover automático entre providers
     *
     * @param  array  $recipients  Lista de destinatarios
     * @param  string  $subject  Asunto
     * @param  string  $htmlContent  Contenido HTML
     * @param  array  $options  Opciones adicionales
     * @param  int|null  $preferredProviderId  ID del provider preferido (se intenta primero)
     * @return array ['success' => bool, 'campaign_id' => string|null, 'sent_count' => int, 'provider_id' => int, 'provider_name' => string]
     *
     * @throws ProviderException Si todos los providers fallan
     */
    public function sendBulkWithFailover(
        array $recipients,
        string $subject,
        string $htmlContent,
        array $options = [],
        ?int $preferredProviderId = null,
    ): array {
        $providers = $this->getOrderedProviders($preferredProviderId);

        if ($providers->isEmpty()) {
            throw ProviderException::notConfigured();
        }

        $errors = [];

        foreach ($providers as $providerConfig) {
            try {
                $provider = $this->createProvider($providerConfig->driver, $providerConfig->credentials);
                $result = $provider->sendBulk($recipients, $subject, $htmlContent, $options);

                if ($result['success']) {
                    return array_merge($result, [
                        'provider_id' => $providerConfig->id,
                        'provider_name' => $provider->getName(),
                    ]);
                }

                $errors[$providerConfig->name] = $result['error'] ?? 'Unknown error';
            } catch (\Throwable $e) {
                $errors[$providerConfig->name] = $e->getMessage();
            }

            Log::warning('Provider failover: sendBulk failed, trying next', [
                'provider' => $providerConfig->name,
                'recipients_count' => count($recipients),
                'error' => $errors[$providerConfig->name],
            ]);
        }

        throw ProviderException::allProvidersFailed($errors);
    }

    /**
     * Obtener providers ordenados para failover: preferido primero, luego por prioridad DESC
     *
     * @return Collection<int, MailProvider>
     */
    private function getOrderedProviders(?int $preferredProviderId): Collection
    {
        $providers = $this->allCached()
            ->where('is_active', true)
            ->where('connection_ok', true)
            ->sortByDesc('priority')
            ->values();

        if (! $preferredProviderId) {
            return $providers;
        }

        $preferred = $providers->firstWhere('id', $preferredProviderId);

        if (! $preferred) {
            return $providers;
        }

        return $providers
            ->reject(fn (MailProvider $p) => $p->id === $preferredProviderId)
            ->prepend($preferred)
            ->values();
    }

    /**
     * Limpiar cache de instancias en memoria y en Redis
     */
    public function clearCache(): void
    {
        $this->instances = [];
        Cache::forget(self::CACHE_KEY_ALL);
    }
}
