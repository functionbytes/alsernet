<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'assignment_mode' => ['required', 'in:manual,round_robin,load_balanced'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'users' => ['nullable', 'array'],
            'users.*' => ['exists:users,id'],
            'user_priorities' => ['nullable', 'array'],
            'user_priorities.*' => ['in:primary,backup'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'assignment_mode.required' => 'El modo de asignacion es obligatorio.',
            'assignment_mode.in' => 'El modo de asignacion debe ser manual, round_robin o load_balanced.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'assignment_mode' => 'modo de asignacion',
            'is_default' => 'grupo predeterminado',
            'is_active' => 'estado',
        ];
    }
}
