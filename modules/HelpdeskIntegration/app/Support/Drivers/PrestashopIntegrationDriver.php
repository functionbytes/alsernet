<?php

namespace Modules\HelpdeskIntegration\Support\Drivers;

use Modules\Helpdesk\Services\CustomerCommerceSyncService;
use Modules\HelpdeskIntegration\Contracts\IntegrationDriverContract;
use Modules\HelpdeskIntegration\Support\DriverResult;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Throwable;

class PrestashopIntegrationDriver implements IntegrationDriverContract
{
    public function __construct(
        private readonly CustomerCommerceSyncService $sync,
    ) {}

    public function platform(): string
    {
        return 'prestashop';
    }

    public function defaultLabel(): string
    {
        return 'PrestaShop';
    }

    public function defaultIcon(): string
    {
        return 'fas fa-cart-shopping';
    }

    public function defaultColor(): ?string
    {
        return '#df1d1d';
    }

    public function defaultSearchTypes(): array
    {
        return [
            ['value' => 'email', 'label' => 'Email'],
            ['value' => 'name', 'label' => 'Nombre'],
            ['value' => 'id', 'label' => 'ID de PrestaShop'],
            ['value' => 'nif', 'label' => 'NIF / DNI'],
        ];
    }

    /**
     * class_exists() sigue siendo true aunque el módulo HelpdeskPrestashop
     * esté deshabilitado (es un monolito, la clase siempre está en el
     * autoload): usa el helper de estado real del módulo
     * (instalado+activo+toggle de Settings → Integraciones), igual que el
     * resto del sistema.
     */
    public function isAvailable(): bool
    {
        return class_exists(PrestashopContextService::class) && helpdesk_prestashop_enabled();
    }

    public function search(string $query, string $type, int $offset = 0): DriverResult
    {
        // Sin paginación en PrestaShop: todo llega en la primera página.
        if ($offset > 0) {
            return DriverResult::ok([]);
        }

        try {
            return DriverResult::ok($this->sync->searchCustomersOrFail($query, $type));
        } catch (Throwable) {
            return DriverResult::failed();
        }
    }

    public function resync(string $externalId): DriverResult
    {
        return $this->search($externalId, 'id');
    }
}
