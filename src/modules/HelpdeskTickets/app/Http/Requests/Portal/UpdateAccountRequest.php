<?php

namespace Modules\HelpdeskTickets\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session()->has('portal_customer_id');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'phone.max' => 'El telefono no puede superar los 30 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'phone' => 'telefono',
        ];
    }
}
