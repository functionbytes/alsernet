<?php

namespace Modules\HelpdeskIntegration\Support;

use Illuminate\Support\Collection;
use Modules\HelpdeskIntegration\Contracts\IntegrationDriverContract;
use RuntimeException;

class IntegrationDriverRegistry
{
    /** @var array<string, class-string<IntegrationDriverContract>> */
    private array $drivers = [];

    /** @var array<string, IntegrationDriverContract> */
    private array $resolved = [];

    /**
     * @param  class-string<IntegrationDriverContract>  $class
     */
    public function register(string $key, string $class): void
    {
        $this->drivers[$key] = $class;

        // Re-registrar una clave debe invalidar la instancia memoizada para
        // que get() no devuelva el driver anterior (p. ej. fakes en tests).
        unset($this->resolved[$key]);
    }

    public function has(string $key): bool
    {
        return isset($this->drivers[$key]);
    }

    public function get(string $key): IntegrationDriverContract
    {
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        if (! $this->has($key)) {
            throw new RuntimeException("Integration driver '{$key}' is not registered.");
        }

        $instance = app($this->drivers[$key]);

        if (! $instance instanceof IntegrationDriverContract) {
            throw new RuntimeException("Driver class for '{$key}' must implement IntegrationDriverContract.");
        }

        return $this->resolved[$key] = $instance;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->drivers);
    }

    /**
     * @return Collection<string, IntegrationDriverContract>
     */
    public function all(): Collection
    {
        return collect($this->drivers)->keys()->mapWithKeys(fn (string $key) => [$key => $this->get($key)]);
    }
}
