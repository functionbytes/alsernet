<?php

namespace App\Http\Api\V1\Manifest;

class MobileModuleRegistry
{
    /** @var MobileModuleManifest[] */
    private array $modules = [];

    public function register(MobileModuleManifest $manifest): void
    {
        $this->modules[$manifest->alias()] = $manifest;
    }

    /** @return MobileModuleManifest[] */
    public function all(): array
    {
        return array_values($this->modules);
    }

    /** @return MobileModuleManifest[] */
    public function forAudience(string $audience): array
    {
        return array_values(array_filter(
            $this->modules,
            fn (MobileModuleManifest $m) => in_array($audience, $m->audiences(), true)
        ));
    }

    public function find(string $alias): ?MobileModuleManifest
    {
        return $this->modules[$alias] ?? null;
    }
}
