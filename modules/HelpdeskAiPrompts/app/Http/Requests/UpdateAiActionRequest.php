<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Modules\HelpdeskAiPrompts\Models\AiAction;

/**
 * El tipo y la clave no cambian al editar: el tipo sale de la acción de la ruta.
 */
class UpdateAiActionRequest extends AiActionFormRequest
{
    protected function actionType(): string
    {
        /** @var AiAction $action */
        $action = $this->route('aiAction');

        return $action->type;
    }
}
