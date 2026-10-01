<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\HelpdeskAiPrompts\Models\AiAction;

class StoreAiActionRequest extends AiActionFormRequest
{
    protected function actionType(): string
    {
        $type = (string) $this->input('type');

        return in_array($type, [AiAction::TYPE_BRIDGE, AiAction::TYPE_HTTP], true) ? $type : AiAction::TYPE_BRIDGE;
    }

    public function rules(): array
    {
        return parent::rules() + [
            'type' => ['required', Rule::in([AiAction::TYPE_BRIDGE, AiAction::TYPE_HTTP])],
            'key' => ['required', 'string', 'max:48', 'regex:/^[a-z0-9_]+$/', Rule::unique(AiAction::class, 'key')],
        ];
    }
}
