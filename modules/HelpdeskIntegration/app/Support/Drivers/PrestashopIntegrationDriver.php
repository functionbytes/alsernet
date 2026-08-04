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

    public function isAvailable(): bool
    {
        return class_exists(PrestashopContextService::class);
    }

    public function search(string $query, string $type): DriverResult
    {
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
