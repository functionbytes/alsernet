<?php

namespace Modules\HelpdeskIntegration\Services;

use Illuminate\Validation\ValidationException;
use Modules\Helpdesk\Models\CustomerExternalId;
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

        if (array_key_exists('platform', $data) && $data['platform'] !== $provider->platform) {
            $this->assertNoLiveLinks($provider, 'renombrar');
        }

        if (array_key_exists('credentials', $data) || array_key_exists('credentials_clear', $data)) {
            $data['credentials'] = $this->mergeCredentials(
                $provider->credentials ?? [],
                $data['credentials'] ?? [],
                $data['credentials_clear'] ?? [],
            );
        }

        unset($data['credentials_clear']);

        $provider->update($data);

        return $provider->refresh();
    }

    public function delete(IntegrationProvider $provider): void
    {
        $this->assertNoLiveLinks($provider, 'eliminar');

        $provider->delete();
    }

    /**
     * Bloquea el borrado o renombrado de un proveedor mientras existan
     * clientes con un vinculo vivo (helpdesk_customer_external_ids) a su
     * platform key. Sin esto el vinculo queda huerfano pero visible en la
     * ficha del cliente, y su unlink() devuelve 422 para siempre: la
     * validacion exige que el platform siga existiendo en el catalogo.
     */
    private function assertNoLiveLinks(IntegrationProvider $provider, string $action): void
    {
        $hasLiveLinks = CustomerExternalId::query()->forPlatform($provider->platform)->exists();

        if (! $hasLiveLinks) {
            return;
        }

        throw ValidationException::withMessages([
            'platform' => "No se puede {$action} este proveedor: hay clientes con un vínculo activo a él. Desvincúlalos primero.",
        ]);
    }

    /**
     * Los campos de credenciales enviados en blanco (ej. password dejado
     * vacio a proposito) no sobreescriben el valor ya guardado: dejar un
     * campo vacio significa "no tocar", no "borrar". Para purgar un secreto
     * rotado hay que pedirlo explicitamente via $clear (nombres de campos a
     * eliminar del array existente antes de fusionar lo nuevo).
     *
     * @param  array<int, string>  $clear
     */
    private function mergeCredentials(array $existing, array $incoming, array $clear = []): array
    {
        foreach ($clear as $key) {
            unset($existing[$key]);
        }

        $incoming = array_filter($incoming, fn (mixed $value): bool => $value !== null && $value !== '');

        return [...$existing, ...$incoming];
    }
}
