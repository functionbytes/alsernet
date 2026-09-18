<?php

namespace Modules\HelpdeskIntegration\Services;

use Modules\HelpdeskIntegration\Models\IntegrationProvider;

/**
 * CRUD del catalogo de proveedores. Los creados desde el panel son siempre
 * custom (sin driver); los nativos solo permiten editar su presentacion.
 */
class IntegrationProviderService
{
    public function create(array $data): IntegrationProvider
    {
        return IntegrationProvider::query()->create([...$data, 'driver' => null]);
    }

    public function update(IntegrationProvider $provider, array $data): IntegrationProvider
    {
        if ($provider->isNative()) {
            unset($data['platform'], $data['driver']);
        }

        if (array_key_exists('credentials', $data)) {
            $data['credentials'] = $this->mergeCredentials($provider->credentials ?? [], $data['credentials'] ?? []);
        }

        $provider->update($data);

        return $provider->refresh();
    }

    public function delete(IntegrationProvider $provider): void
    {
        $provider->delete();
    }

    /**
     * Los campos de credenciales enviados en blanco (ej. password dejado
     * vacio a proposito) no sobreescriben el valor ya guardado.
     */
    private function mergeCredentials(array $existing, array $incoming): array
    {
        $incoming = array_filter($incoming, fn (mixed $value): bool => $value !== null && $value !== '');

        return [...$existing, ...$incoming];
    }
}
