<?php

namespace Modules\Campaign\Http\Requests\PageTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\CopyPageTemplateDto;
use Modules\Campaign\Models\Template\PageTemplate;

class CopyPageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', new PageTemplate);
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
            'name.required' => trans('campaign::page-templates.errors.name_required'),
        ];
    }

    public function toDto(string $sourceUid): CopyPageTemplateDto
    {
        return CopyPageTemplateDto::fromRequest($sourceUid, $this->validated());
    }
}
