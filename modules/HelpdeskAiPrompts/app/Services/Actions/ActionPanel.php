<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

use Illuminate\Database\Eloquent\Builder;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiActionRun;

/**
 * Datos de lectura de la pestaña "Acciones" (estadísticas y filas de la
 * tabla) y de los modales de prueba. Nunca carga `secrets`.
 */
class ActionPanel
{
    private const WINDOW_DAYS = 30;

    private const LIST_COLUMNS = ['id', 'key', 'name', 'description', 'type', 'is_active', 'parameters', 'config', 'rules', 'version'];

    private const TYPE_ORDER = [AiAction::TYPE_BRIDGE => 0, AiAction::TYPE_HTTP => 1, AiAction::TYPE_BUILTIN => 2];

    /**
     * @return array{stats: array<string, int|float>, rows: array<int, array<string, mixed>>}
     */
    public function forIndex(): array
    {
        $actions = AiAction::query()->get(self::LIST_COLUMNS);
        $uses = $this->usesByKey();

        $rows = $actions
            ->sortBy(fn (AiAction $a): string => (self::TYPE_ORDER[$a->type] ?? 9).'|'.mb_strtolower($a->name))
            ->map(fn (AiAction $a): array => [
                'action' => $a,
                'target' => $this->target($a),
                'write' => $this->isWrite($a),
                'uses' => (int) ($uses[$a->key] ?? 0),
                'test_params' => $a->type === AiAction::TYPE_BUILTIN ? [] : $this->testParams($a),
            ])
            ->values()
            ->all();

        return [
            'stats' => ['active_actions' => $actions->where('is_active', true)->count()] + $this->runStats(),
            'rows' => $rows,
        ];
    }

    /**
     * Parámetros que ve la IA, con la marca `only_unverified` en los que el
     * sistema pide solo cuando el cliente no está verificado (el email).
     *
     * @return array<int, array<string, mixed>>
     */
    public function testParams(AiAction $action): array
    {
        $definition = $this->definition($action);
        $verifiedNames = array_column(ActionParameters::effective($definition, true), 'name');

        return array_map(
            fn (array $param): array => $param + ['only_unverified' => ! in_array($param['name'], $verifiedNames, true)],
            ActionParameters::effective($definition, false),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function effectiveParams(AiAction $action, bool $verified): array
    {
        return ActionParameters::effective($this->definition($action), $verified);
    }

    public function isWrite(AiAction $action): bool
    {
        if ($action->type === AiAction::TYPE_BUILTIN) {
            return false;
        }

        return ActionParameters::needsConfirmation($this->definition($action));
    }

    public function target(AiAction $action): ?string
    {
        if ($action->type === AiAction::TYPE_BRIDGE) {
            return $action->config['action'] ?? null;
        }

        if ($action->type === AiAction::TYPE_HTTP) {
            $probe = (string) preg_replace('/\{\{[^}]*\}\}/', 'x', (string) ($action->config['url'] ?? ''));

            return parse_url($probe, PHP_URL_HOST) ?: null;
        }

        return null;
    }

    /**
     * Usos reales de los últimos 30 días (las pruebas del panel no cuentan).
     *
     * @return array<string, int>
     */
    private function usesByKey(): array
    {
        return $this->recentRuns()
            ->selectRaw('action_key, count(*) as total')
            ->groupBy('action_key')
            ->pluck('total', 'action_key')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * @return array{runs_30d: int, error_rate: float, denied_rate: float}
     */
    private function runStats(): array
    {
        $totals = $this->recentRuns()
            ->selectRaw("count(*) as total, coalesce(sum(status = 'error'), 0) as errors, coalesce(sum(status = 'denied'), 0) as denied")
            ->first();

        $total = (int) ($totals->total ?? 0);

        return [
            'runs_30d' => $total,
            'error_rate' => $total > 0 ? round((int) $totals->errors / $total * 100, 1) : 0.0,
            'denied_rate' => $total > 0 ? round((int) $totals->denied / $total * 100, 1) : 0.0,
        ];
    }

    private function recentRuns(): Builder
    {
        return AiActionRun::query()
            ->where('source', '!=', 'test')
            ->where('created_at', '>=', now()->subDays(self::WINDOW_DAYS));
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(AiAction $action): array
    {
        return [
            'type' => $action->type,
            'config' => $action->config,
            'rules' => $action->rules,
            'parameters' => $action->parameters,
        ];
    }
}
