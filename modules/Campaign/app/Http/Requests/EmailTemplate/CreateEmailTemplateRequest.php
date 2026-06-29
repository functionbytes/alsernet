<?php

namespace Modules\Campaign\Http\Requests\EmailTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\CreatePageTemplateDto;
use Modules\Campaign\Models\Template\SystemEmailTemplate;

class CreateEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', new SystemEmailTemplate);
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
            'name.required' => trans('campaign::email-templates.errors.name_required'),
            'template.required' => trans('campaign::email-templates.errors.template_required'),
        ];
    }

    public function toDto(): CreatePageTemplateDto
    {
        return CreatePageTemplateDto::fromRequest($this->validated());
    }
}
