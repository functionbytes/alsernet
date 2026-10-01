<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunRegressionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdesk.ai-prompts.manage');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'questions' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('helpdeskaiprompts_quality.regression.max_questions')],
        ];
    }
}
