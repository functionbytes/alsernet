<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewSocialTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.templates.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:helpdesk_social_templates,id'],
            'variables' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => 'La plantilla es obligatoria.',
            'template_id.exists' => 'La plantilla seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'template_id' => 'plantilla',
            'variables' => 'variables',
        ];
    }
}
