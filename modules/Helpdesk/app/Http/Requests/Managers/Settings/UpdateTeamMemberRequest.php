<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamMemberRequest extends FormRequest
{
    /** Roles that may be assigned through the team-member settings form. */
    public const ASSIGNABLE_ROLES = ['admin', 'manager', 'support', 'callcenter'];

    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'max_concurrent_conversations' => ['nullable', 'integer', 'min:0', 'max:999'],
            'accepts_conversations' => ['required', 'in:yes,no,working_hours'],
            'working_hours' => ['nullable', 'array'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['exists:helpdesk.helpdesk_groups,id'],
            // Only helpdesk-specific roles are assignable — prevents privilege escalation.
            'role' => ['nullable', Rule::in(self::ASSIGNABLE_ROLES)],
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
            'max_concurrent_conversations.integer' => 'El límite de conversaciones debe ser un número entero.',
            'max_concurrent_conversations.min' => 'El límite de conversaciones no puede ser negativo.',
            'max_concurrent_conversations.max' => 'El límite de conversaciones no puede superar 999.',
            'accepts_conversations.required' => 'La disponibilidad es obligatoria.',
            'accepts_conversations.in' => 'La disponibilidad debe ser: sí, no o según horario laboral.',
            'groups.*.exists' => 'Uno o más grupos seleccionados no existen.',
            'role.in' => 'El rol seleccionado no está permitido en la configuración del equipo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'firstname' => 'nombre',
            'lastname' => 'apellido',
            'email' => 'correo electrónico',
            'max_concurrent_conversations' => 'límite de conversaciones concurrentes',
            'accepts_conversations' => 'disponibilidad',
            'working_hours' => 'horario laboral',
            'groups' => 'grupos',
            'role' => 'rol',
        ];
    }
}
