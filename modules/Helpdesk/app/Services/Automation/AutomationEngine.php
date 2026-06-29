<?php

namespace Modules\Helpdesk\Services\Automation;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\AutomationRule;

class AutomationEngine
{
    public function __construct(
        private readonly ConditionEvaluator $evaluator,
        private readonly AutomationActionRegistry $registry,
    ) {}

    /**
     * Run all active automation rules that match the given event and context.
     *
     * @param  array<string, mixed>  $context
     */
    public function runFor(string $eventName, array $context): void
    {
        $rules = AutomationRule::query()
            ->where('trigger_event', $eventName)
            ->where('is_active', true)
            ->orderByDesc('order')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->evaluator->evaluate($rule->conditions ?? [], $context)) {
                continue;
            }

            $this->executeActions($rule, $context);

            $rule->update(['last_run_at' => now()]);
            $rule->increment('run_count');
        }
    }

    /**
     * Dry-run a single rule: returns what would happen without executing.
     *
     * @param  array<string, mixed>  $context
     * @return array{matched: bool, actions_planned: array<string>}
     */
    public function dryRun(AutomationRule $rule, array $context): array
    {
        return [
            'matched' => $this->evaluator->evaluate($rule->conditions ?? [], $context),
            'actions_planned' => array_column($rule->actions ?? [], 'type'),
        ];
    }

    /**
     * Execute all actions for a matched rule, continuing on individual failures.
     *
     * @param  array<string, mixed>  $context
     */
    private function executeActions(AutomationRule $rule, array $context): void
    {
        foreach ($rule->actions ?? [] as $actionConfig) {
            $type = $actionConfig['type'] ?? null;
            $params = $actionConfig['params'] ?? [];

            if (! $type) {
                continue;
            }

            try {
                $action = $this->registry->resolve($type);
                $action->execute($params, $context);
            } catch (\Throwable $e) {
                Log::error('Automation action failed', [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'action_type' => $type,
                    'error' => $e->getMessage(),
                ]);

                $rule->update(['last_error' => mb_substr($e->getMessage(), 0, 500)]);
            }
        }
    }
}
