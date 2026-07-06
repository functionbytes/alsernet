<?php

namespace Modules\HelpdeskEmailLog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendEmailLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskemaillog.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'to' => ['nullable', 'email:rfc', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'to.email' => 'Introduce una dirección de correo válida.',
            'to.max' => 'La dirección no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'to' => 'dirección de correo',
        ];
    }
}
