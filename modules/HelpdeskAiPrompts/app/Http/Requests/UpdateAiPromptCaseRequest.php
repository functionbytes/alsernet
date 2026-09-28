<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;

class UpdateAiPromptCaseRequest extends AiPromptCaseFormRequest
{
    public function rules(): array
    {
        $rules = $this->baseRules();
        $rules['key'][] = Rule::unique(AiPromptCase::class, 'key')
            ->where('channel', $this->input('channel'))
            ->ignore($this->route('case'));

        return $rules;
    }
}
