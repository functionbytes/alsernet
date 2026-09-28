<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;

class StoreAiPromptBlockRequest extends AiPromptBlockFormRequest
{
    public function rules(): array
    {
        $rules = $this->baseRules();
        $rules['key'][] = Rule::unique(AiPromptBlock::class, 'key')
            ->where('channel', $this->input('channel'))
            ->where('locale', $this->input('locale'));

        return $rules;
    }
}
