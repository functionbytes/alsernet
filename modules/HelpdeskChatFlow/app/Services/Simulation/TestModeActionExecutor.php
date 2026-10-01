<?php

namespace Modules\HelpdeskChatFlow\Services\Simulation;

use Closure;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionExecutor;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionParameters;

/**
 * Action executor for the editor's test panel. Read actions run for real with
 * source `test` (they only look data up); actions that modify data are NOT
 * executed: they answer ok with a `simulated` marker so the flow can continue.
 *
 * Only loaded when HelpdeskAiPrompts exists (the simulator checks first).
 */
class TestModeActionExecutor extends ActionExecutor
{
    /** @var Closure(string): bool */
    private readonly Closure $isWrite;

    /**
     * @param  (Closure(string): bool)|null  $isWrite  Overrides the "does this action modify data" lookup (tests).
     */
    public function __construct(private readonly ActionExecutor $inner, ?Closure $isWrite = null)
    {
        // The parent's dependencies are never used: run() delegates to $inner.
        $this->isWrite = $isWrite ?? static fn (string $key): bool => self::actionModifiesData($key);
    }

    public function run(string $key, array $args, array $ctx, string $source = 'ai'): array
    {
        if (($this->isWrite)($key)) {
            return [
                'ok' => true,
                'content' => (string) json_encode(['simulated' => true, 'message' => 'Acción de escritura simulada en modo prueba: no se ha ejecutado.']),
                'status' => 'simulated',
            ];
        }

        return $this->inner->run($key, $args, $ctx, 'test');
    }

    private static function actionModifiesData(string $key): bool
    {
        $action = AiAction::query()->where('key', $key)->first();

        return $action !== null
            && ActionParameters::needsConfirmation($action->only(['type', 'parameters', 'config', 'rules']));
    }
}
