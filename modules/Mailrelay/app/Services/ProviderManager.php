<?php

namespace Modules\Mailrelay\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Mailrelay\Contracts\MailProviderInterface;
use Modules\Mailrelay\Entities\MailProvider;
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

    /**
     * Obtener instancia de provider por nombre del driver
     *
     * @param  string  $name  Nombre del driver (mailrelay, mailtrap)
     *
     * @throws \Exception Si el provider no está configurado o no existe
     */
    public function driver(string $name): MailProviderInterface
    {
        // Verificar si ya tenemos una instancia en cache
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // Buscar configuración en BD
        $providerConfig = MailProvider::where('driver', $name)
            ->where('is_active', true)
            ->first();

        if (! $providerConfig) {
            throw new \Exception("Provider '{$name}' no está configurado o no está activo");
        }

        // Crear y cachear instancia
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
        // Verificar cache por ID
        $cacheKey = "mail_provider_{$providerId}";

        if (isset($this->instances[$cacheKey])) {
            return $this->instances[$cacheKey];
        }

        $providerConfig = MailProvider::findOrFail($providerId);

        if (! $providerConfig->is_active) {
            throw new \Exception("Provider '{$providerConfig->name}' está desactivado");
        }

        // Crear y cachear instancia
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
        $defaultProvider = MailProvider::where('is_default', true)
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
    public function configured(): \Illuminate\Database\Eloquent\Collection
    {
        return MailProvider::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Listar todos los providers (activos e inactivos)
     */
    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return MailProvider::orderBy('is_default', 'desc')
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get();
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
     * Limpiar cache de instancias
     */
    public function clearCache(): void
    {
        $this->instances = [];
    }
}
