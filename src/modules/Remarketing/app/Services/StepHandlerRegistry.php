<?php

namespace Modules\Remarketing\Services;

use Modules\Remarketing\Contracts\StepHandlerInterface;

class StepHandlerRegistry
{
    /** @var array<string, StepHandlerInterface> */
    private array $handlers = [];

    public function register(string $type, StepHandlerInterface $handler): void
    {
        $this->handlers[$type] = $handler;
    }

    public function has(string $type): bool
    {
        return isset($this->handlers[$type]);
    }

    public function get(string $type): ?StepHandlerInterface
    {
        return $this->handlers[$type] ?? null;
    }

    /** @return string[] */
    public function types(): array
    {
        return array_keys($this->handlers);
    }
}
