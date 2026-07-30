<?php

namespace Modules\Helpdesk\Services\Automation;

use Illuminate\Support\Facades\DB;
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

            // Un único UPDATE en vez de update + increment (dos escrituras).
            $rule->update([
                'last_run_at' => now(),
                'run_count' => DB::raw('run_count + 1'),
            ]);
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
     * Execute all actions for a matched rule atomically: the whole action set
     * runs inside a transaction on the helpdesk connection, so a failure
     * mid-list rolls back every DB mutation instead of leaving partial state.
     *
     * Side effects that leave the DB (webhooks, outbound messages) are queued
     * jobs dispatched with afterCommit() by the actions themselves, so a
     * rollback also cancels their dispatch.
     *
     * @param  array<string, mixed>  $context
     */
    private function executeActions(AutomationRule $rule, array $context): void
    {
        $currentType = null;

        try {
            DB::connection('helpdesk')->transaction(function () use ($rule, $context, &$currentType): void {
                foreach ($rule->actions ?? [] as $actionConfig) {
                    $type = $actionConfig['type'] ?? null;
                    $params = $actionConfig['params'] ?? [];

                    if (! $type) {
                        continue;
                    }

                    $currentType = $type;
                    $action = $this->registry->resolve($type);
                    $action->execute($params, $context);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Automation action failed', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'action_type' => $currentType,
                'error' => $e->getMessage(),
            ]);

            // Fuera de la transacción (ya revertida): el last_error sí persiste.
            $rule->update(['last_error' => mb_substr($e->getMessage(), 0, 500)]);
        }
    }
}
