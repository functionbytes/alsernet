<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialMentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.mentions.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:new,reviewed,archived,dismissed'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'estado',
        ];
    }
}
