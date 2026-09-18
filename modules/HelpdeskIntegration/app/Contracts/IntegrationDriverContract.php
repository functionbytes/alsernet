<?php

namespace Modules\HelpdeskIntegration\Contracts;

use Modules\HelpdeskIntegration\Support\DriverResult;

interface IntegrationDriverContract
{
    /**
     * Clave estable que debe coincidir con IntegrationProvider::driver.
     */
    public function platform(): string;

    public function defaultLabel(): string;

    public function defaultIcon(): string;

    public function defaultColor(): ?string;

    /**
     * @return list<array{value: string, label: string}>
     */
    public function defaultSearchTypes(): array;

    /**
     * Si el servicio subyacente (modulo/paquete externo) esta instalado y usable.
     */
    public function isAvailable(): bool;

    /**
     * Busca clientes en la plataforma remota. La implementación NUNCA debe
     * lanzar: los fallos del servicio subyacente (timeout, conexión, etc.)
     * se devuelven como DriverResult::failed() para que la UI pueda
     * distinguirlos de una búsqueda sin coincidencias.
     */
    public function search(string $query, string $type): DriverResult;

    /**
     * Reverifica que un vinculo existente siga siendo valido en la
     * plataforma remota. Usar DriverResult::found($externalId) para saber
     * si el id sigue resolviendo a un cliente.
     */
    public function resync(string $externalId): DriverResult;
}
