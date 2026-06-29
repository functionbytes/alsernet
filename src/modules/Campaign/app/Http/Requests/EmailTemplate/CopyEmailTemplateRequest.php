<?php

namespace Modules\Campaign\Http\Requests\EmailTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\CopyPageTemplateDto;
use Modules\Campaign\Models\Template\SystemEmailTemplate;

class CopyEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', new SystemEmailTemplate);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => trans('campaign::email-templates.errors.name_required'),
        ];
    }

    public function toDto(string $sourceUid): CopyPageTemplateDto
    {
        return CopyPageTemplateDto::fromRequest($sourceUid, $this->validated());
    }
}
