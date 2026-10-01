<?php

namespace Modules\HelpdeskAiPrompts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionParameters;

/**
 * Lista de acciones activas para el editor de ChatFlow (nodo `ai_action`).
 * Nunca expone config ni secretos.
 */
class AiActionCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $actions = AiAction::query()
            ->where('is_active', true)
            ->whereIn('type', [AiAction::TYPE_BRIDGE, AiAction::TYPE_HTTP])
            ->orderBy('key')
            ->get(['key', 'name', 'description', 'type', 'parameters', 'config', 'rules'])
            ->map(function (AiAction $action): array {
                $definition = $action->only(['type', 'parameters', 'config', 'rules']);
                $parameters = array_values(array_filter(
                    ActionParameters::effective($definition, false),
                    fn (array $param): bool => $param['name'] !== ActionParameters::CONFIRM,
                ));

                return [
                    'key' => $action->key,
                    'name' => $action->name,
                    'description' => $action->description,
                    'type' => $action->type,
                    'parameters' => $parameters,
                    'write' => ActionParameters::needsConfirmation($definition),
                ];
            })
            ->all();

        return response()->json(['data' => $actions]);
    }
}
