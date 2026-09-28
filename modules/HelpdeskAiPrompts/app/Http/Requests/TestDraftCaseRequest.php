<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

/**
 * "Probar borrador": validates the same shape as the case form but never
 * checks key uniqueness — the draft is never saved.
 */
class TestDraftCaseRequest extends AiPromptCaseFormRequest
{
    public function rules(): array
    {
        return $this->baseRules();
    }
}
