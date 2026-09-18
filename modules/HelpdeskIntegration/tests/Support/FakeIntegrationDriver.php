<?php

namespace Modules\HelpdeskIntegration\Tests\Support;

use Modules\HelpdeskIntegration\Contracts\IntegrationDriverContract;
use Modules\HelpdeskIntegration\Support\DriverResult;

/**
 * Driver determinista para tests: evita que link()/sync() golpeen los
 * servicios remotos reales (ERP Oracle / bridge PrestaShop). Se configura
 * mediante propiedades estáticas y se registra en el IntegrationDriverRegistry
 * bajo la clave del driver a sustituir.
 */
class FakeIntegrationDriver implements IntegrationDriverContract
{
    /** Simula la plataforma caída: search/resync devuelven failed(). */
    public static bool $platformUp = true;

    /** Si resync() debe encontrar el external_id consultado. */
    public static bool $resyncResult = true;

    /** @var list<array{id: string, name: string, email: string, meta: string}> */
    public static array $searchResults = [];

    public static function reset(): void
    {
        self::$platformUp = true;
        self::$resyncResult = true;
        self::$searchResults = [];
    }

    public function platform(): string
    {
        return 'fake';
    }

    public function defaultLabel(): string
    {
        return 'Fake';
    }

    public function defaultIcon(): string
    {
        return 'fas fa-plug';
    }

    public function defaultColor(): ?string
    {
        return null;
    }

    public function defaultSearchTypes(): array
    {
        return [['value' => 'email', 'label' => 'Email']];
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function search(string $query, string $type): DriverResult
    {
        return self::$platformUp
            ? DriverResult::ok(self::$searchResults)
            : DriverResult::failed();
    }

    public function resync(string $externalId): DriverResult
    {
        if (! self::$platformUp) {
            return DriverResult::failed();
        }

        return DriverResult::ok(self::$resyncResult
            ? [['id' => $externalId, 'name' => 'Fake', 'email' => '', 'meta' => 'FAKE-'.$externalId]]
            : []);
    }
}
