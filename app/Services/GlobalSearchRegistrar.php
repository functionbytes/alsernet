<?php

namespace App\Services;

/**
 * Stub for the global search registration service. Modules register their
 * searchable resources here during boot. Replace with the full implementation
 * once the global search feature is wired up — for now this keeps the app
 * from exploding because AppServiceProvider::boot() calls register().
 */
class GlobalSearchRegistrar
{
    /**
     * @var array<string, array{label: string, route: string, icon?: string}>
     */
    private array $resources = [];

    public function register(): void
    {
        // Modules hook into this via container events. Intentionally empty.
    }

    public function addResource(string $key, array $resource): void
    {
        $this->resources[$key] = $resource;
    }

    /**
     * @return array<string, array{label: string, route: string, icon?: string}>
     */
    public function resources(): array
    {
        return $this->resources;
    }
}
