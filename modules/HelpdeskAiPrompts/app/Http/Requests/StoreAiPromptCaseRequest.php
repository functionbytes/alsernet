<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;

class StoreAiPromptCaseRequest extends AiPromptCaseFormRequest
{
    public function rules(): array
    {
        $rules = $this->baseRules();
        $rules['key'][] = Rule::unique(AiPromptCase::class, 'key')->where('channel', $this->input('channel'));

        return $rules;
    }
}
