<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:500'],
            'minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'logged_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.max' => 'La descripcion no puede superar los 500 caracteres.',
            'minutes.required' => 'Los minutos son obligatorios.',
            'minutes.integer' => 'Los minutos deben ser un numero entero.',
            'minutes.min' => 'Los minutos deben ser al menos 1.',
            'minutes.max' => 'Los minutos no pueden superar los 480.',
            'logged_at.date' => 'La fecha de registro debe ser una fecha valida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'description' => 'descripcion',
            'minutes' => 'minutos',
            'logged_at' => 'fecha de registro',
        ];
    }
}
