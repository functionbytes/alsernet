<?php

namespace Modules\HelpdeskErp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskerp.view') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => $this->route('email')]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email proporcionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
        ];
    }
}
