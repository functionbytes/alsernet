<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.manage') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$id],
            'assignment_limit' => ['nullable', 'integer', 'min:0'],
            'accepts_conversations' => ['required', 'in:yes,no,working_hours'],
            'working_hours' => ['nullable', 'array'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['exists:helpdesk.helpdesk_groups,id'],
            'role' => ['nullable', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'firstname.required' => 'El nombre es obligatorio.',
            'firstname.max' => 'El nombre no puede superar los 255 caracteres.',
            'lastname.required' => 'El apellido es obligatorio.',
            'lastname.max' => 'El apellido no puede superar los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Ya existe un usuario con este correo electrónico.',
            'assignment_limit.integer' => 'El límite de asignaciones debe ser un número entero.',
            'assignment_limit.min' => 'El límite de asignaciones no puede ser negativo.',
            'accepts_conversations.required' => 'La disponibilidad es obligatoria.',
            'accepts_conversations.in' => 'La disponibilidad debe ser: sí, no o según horario laboral.',
            'groups.*.exists' => 'Uno o más grupos seleccionados no existen.',
            'role.exists' => 'El rol seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'firstname' => 'nombre',
            'lastname' => 'apellido',
            'email' => 'correo electrónico',
            'assignment_limit' => 'límite de asignaciones',
            'accepts_conversations' => 'disponibilidad',
            'working_hours' => 'horario laboral',
            'groups' => 'grupos',
            'role' => 'rol',
        ];
    }
}
