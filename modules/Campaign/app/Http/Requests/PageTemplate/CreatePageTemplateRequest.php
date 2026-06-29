<?php

namespace Modules\Campaign\Http\Requests\PageTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\CreatePageTemplateDto;
use Modules\Campaign\Models\Template\PageTemplate;

class CreatePageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', new PageTemplate);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'template' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => trans('campaign::page-templates.errors.name_required'),
            'template.required' => trans('campaign::page-templates.errors.template_required'),
        ];
    }

    public function toDto(): CreatePageTemplateDto
    {
        return CreatePageTemplateDto::fromRequest($this->validated());
    }
}
