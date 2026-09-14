<?php

namespace Modules\Helpdesk\Services\Automation;

use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;

class AutomationActionRegistry
{
    /** @var array<string, string> type → FQCN */
    private array $actions = [];

    /**
     * Register an action class by its type identifier.
     */
    public function register(string $type, string $class): void
    {
        $this->actions[$type] = $class;
    }

    /**
     * Resolve an action instance by type.
     *
     * @throws \InvalidArgumentException
     */
    public function resolve(string $type): AutomationAction
    {
        if (! isset($this->actions[$type])) {
            throw new \InvalidArgumentException("Automation action type '{$type}' is not registered.");
        }

        return app($this->actions[$type]);
    }

    /**
     * All registered actions with their schema (used by the UI).
     *
     * @return array<string, array{label: string, schema: array<string, mixed>}>
     */
    public function all(): array
    {
        $result = [];

        foreach ($this->actions as $type => $class) {
            $result[$type] = [
                'type' => $type,
                'schema' => $class::paramSchema(),
            ];
        }

        return $result;
    }
}
