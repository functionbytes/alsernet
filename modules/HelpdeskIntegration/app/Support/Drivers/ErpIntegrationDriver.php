<?php

namespace Modules\HelpdeskIntegration\Support\Drivers;

use Illuminate\Support\Facades\Cache;
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

    /**
     * class_exists() sigue siendo true aunque el módulo HelpdeskErp esté
     * deshabilitado (es un monolito, la clase siempre está en el autoload):
     * usa el helper de estado real del módulo (instalado+activo+toggle de
     * Settings → Integraciones), igual que el resto del sistema.
     */
    public function isAvailable(): bool
    {
        return class_exists(ErpContextService::class) && helpdesk_erp_enabled();
    }

    /**
     * Busca en ERP via ErpContextService (manager Oracle) y normaliza la
     * respuesta al mismo formato {id,name,email,meta} que el resto de drivers.
     */
    public function search(string $query, string $type, int $offset = 0): DriverResult
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

        // Las búsquedas por nombre/apellidos/teléfono recorren la tabla de
        // clientes entera en Oracle (14-20 s, sin índice): se cachean 5 min
        // para que repetirla (volver de una ficha, cambiar de plataforma y
        // volver) sea instantáneo. Solo se cachean respuestas correctas.
        $cacheKey = 'helpdeskintegration:erp-search:'.sha1(mb_strtolower(trim($query)).'|'.$erpType.'|'.$offset);

        try {
            // Por id (resync/verificación de vínculo) no: es instantáneo y
            // tiene que reflejar el estado actual.
            $cacheable = $erpType !== 'customer_id';
            $results = $cacheable ? Cache::get($cacheKey) : null;

            if (! is_array($results)) {
                $results = app(ErpContextService::class)->searchCustomers($query, $erpType, $offset);

                if ($cacheable) {
                    Cache::put($cacheKey, $results, now()->addMinutes(5));
                }
            }
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
        //
        // El modal de búsqueda externa manda type=auto, así que además se
        // deduce: consulta numérica cuya fila no coincide por IDCLIENTE,
        // tarjeta ni código internet → el manager la encontró por teléfono.
        $normalized = app(PhoneNormalizerService::class)->normalize($query);
        $numeric = $normalized !== null && preg_match('/^\+?\d{6,15}$/', $normalized) === 1;

        $phoneFor = function (array $r) use ($type, $normalized, $numeric): ?string {
            if ($type === 'phone') {
                return $normalized;
            }

            if ($type !== 'auto' || ! $numeric) {
                return null;
            }

            $digits = ltrim($normalized, '+');
            foreach (['id', 'card', 'code_internet'] as $field) {
                if ((string) ($r[$field] ?? '') === $digits) {
                    return null;
                }
            }

            return $normalized;
        };

        return DriverResult::ok(array_map(fn ($r) => [
            'id' => (string) ($r['id'] ?? ''),
            'name' => trim(($r['label'] ?? '').' '.($r['surnames'] ?? '')),
            'email' => $r['email'] ?? '',
            'meta' => 'ERP-'.($r['id'] ?? ''),
            'nif' => $r['cif'] ?? null,
            'phone' => $phoneFor($r),
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
