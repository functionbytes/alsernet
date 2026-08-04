<?php

namespace Modules\HelpdeskIntegration\Support\Drivers;

use Modules\Helpdesk\Services\PhoneNormalizerService;
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
            ['value' => 'nif', 'label' => 'NIF / DNI'],
            ['value' => 'customer_id', 'label' => 'ID de gestión'],
            ['value' => 'name', 'label' => 'Nombre o apellido'],
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

        // El manager decide internamente por el contenido de $query (email si
        // lleva '@', numérico → IDCLIENTE/IDTARJETA/CODIGO_INTERNET/teléfono,
        // texto → CIF/apellidos/nombre) — $type es solo una pista informativa
        // que el manager ignora, pero se pasa explícita en vez de forzar
        // 'email' por defecto para no mentir sobre lo que se está buscando.
        $erpType = match ($type) {
            'nif' => 'nif',
            'customer_id' => 'customer_id',
            'phone' => 'phone',
            'name' => 'name',
            default => 'email',
        };

        try {
            $results = app(ErpContextService::class)->searchCustomers($query, $erpType);
        } catch (Throwable) {
            return DriverResult::failed();
        }

        // El manager (CustomerController@search) trae cif/tarjeta/código
        // internet/estado/fechas por fila, pero el teléfono NO viaja en esa
        // fila — solo se usa como filtro interno contra CLIENTETELEFONO_CENT
        // (join oculto). Cuando la búsqueda fue por teléfono, se sabe que el
        // número tecleado es el del cliente encontrado, así que se expone
        // normalizado (misma limpieza de formato que usa el resto de la app
        // para guardar teléfonos — PhoneNormalizerService::normalize()) en
        // vez de dejarlo vacío en la ficha de confirmación. Para búsquedas
        // por nombre/email/NIF no hay teléfono disponible desde aquí.
        $phone = $type === 'phone'
            ? app(PhoneNormalizerService::class)->normalize($query)
            : null;

        return DriverResult::ok(array_map(fn ($r) => [
            'id' => (string) ($r['id'] ?? ''),
            'name' => trim(($r['label'] ?? '').' '.($r['surnames'] ?? '')),
            'email' => $r['email'] ?? '',
            'meta' => 'ERP-'.($r['id'] ?? ''),
            'nif' => $r['cif'] ?? null,
            'phone' => $phone,
            'card' => $r['card'] ?? null,
            'code_internet' => $r['code_internet'] ?? null,
            'active' => $r['available'] ?? null,
            'created_at' => $r['created'] ?? null,
        ], array_values(array_filter($results, fn ($r) => ! empty($r['id'])))));
    }

    public function resync(string $externalId): DriverResult
    {
        return $this->search($externalId, 'customer_id');
    }
}
