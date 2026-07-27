<?php

namespace Modules\HelpdeskIntegration\Support\Drivers;

use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskIntegration\Contracts\IntegrationDriverContract;
use Modules\HelpdeskIntegration\Support\DriverResult;
use Throwable;

class ErpIntegrationDriver implements IntegrationDriverContract
{
    public function platform(): string
    {
        return 'erp';
    }

    public function defaultLabel(): string
    {
        return 'Gestión (ERP)';
    }

    public function defaultIcon(): string
    {
        return 'fas fa-clipboard-list';
    }

    public function defaultColor(): ?string
    {
        return '#f59e0b';
    }

    public function defaultSearchTypes(): array
    {
        return [
            ['value' => 'email', 'label' => 'Email'],
            ['value' => 'phone', 'label' => 'Teléfono'],
            ['value' => 'id', 'label' => 'NIF / DNI'],
        ];
    }

    public function isAvailable(): bool
    {
        return class_exists(ErpContextService::class);
    }

    /**
     * Busca en ERP via ErpContextService (manager Oracle) y normaliza la
     * respuesta al mismo formato {id,name,email,meta} que el resto de drivers.
     */
    public function search(string $query, string $type): DriverResult
    {
        if (! $this->isAvailable()) {
            return DriverResult::failed();
        }

        $erpType = match ($type) {
            'id' => 'nif',
            'phone' => 'phone',
            default => 'email',
        };

        try {
            $results = app(ErpContextService::class)->searchCustomers($query, $erpType);
        } catch (Throwable) {
            return DriverResult::failed();
        }

        return DriverResult::ok(array_map(fn ($r) => [
            'id' => (string) ($r['id'] ?? ''),
            'name' => trim(($r['label'] ?? '').' '.($r['surnames'] ?? '')),
            'email' => $r['email'] ?? '',
            'meta' => 'ERP-'.($r['id'] ?? ''),
        ], array_values(array_filter($results, fn ($r) => ! empty($r['id'])))));
    }

    public function resync(string $externalId): DriverResult
    {
        return $this->search($externalId, 'id');
    }
}
