<?php

namespace Modules\Engagement\Connectors;

use InvalidArgumentException;
use Modules\Engagement\Contracts\CustomerDataConnector;
use Modules\Engagement\Models\PlatformIntegration;

/**
 * Factory que resuelve el connector concreto para una plataforma dada.
 * Centraliza el mapping platform → class para que añadir nuevas plataformas
 * sea modificar un solo array.
 */
class ConnectorFactory
{
    /**
     * Mapping platform → connector class.
     */
    protected array $connectors = [
        PlatformIntegration::PLATFORM_PRESTASHOP => PrestaShopCustomerConnector::class,
        PlatformIntegration::PLATFORM_ERP => ErpCustomerConnector::class,
    ];

    /**
     * Cache de instancias para evitar reconstruir entre múltiples acciones
     * en una misma request.
     *
     * @var array<string, CustomerDataConnector>
     */
    protected array $instances = [];

    public function for(PlatformIntegration $integration): CustomerDataConnector
    {
        return $this->forPlatform($integration->platform);
    }

    public function forPlatform(string $platform): CustomerDataConnector
    {
        if (isset($this->instances[$platform])) {
            return $this->instances[$platform];
        }

        if (! isset($this->connectors[$platform])) {
            throw new InvalidArgumentException("No hay connector registrado para la plataforma '{$platform}'.");
        }

        $class = $this->connectors[$platform];

        return $this->instances[$platform] = app($class);
    }

    /**
     * Plataformas con connector implementado (útil para UI: qué tabs mostrar).
     */
    public function availablePlatforms(): array
    {
        return array_keys($this->connectors);
    }

    /**
     * Permite registrar connectors externos en runtime (extensiones).
     */
    public function register(string $platform, string $connectorClass): void
    {
        $this->connectors[$platform] = $connectorClass;
        unset($this->instances[$platform]);
    }
}
