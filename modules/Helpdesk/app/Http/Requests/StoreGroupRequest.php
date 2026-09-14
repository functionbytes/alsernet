<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'assignment_mode' => ['required', 'in:round_robin,load_balanced,manual'],
            'default' => ['nullable', 'boolean'],
            'members' => ['required', 'array', 'min:1'],
            'members.*.user_id' => ['required', 'exists:users,id'],
            'members.*.priority' => ['required', 'in:primary,backup'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del grupo es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'assignment_mode.required' => 'El modo de asignación es obligatorio.',
            'assignment_mode.in' => 'El modo de asignación debe ser: round robin, balanceo de carga o manual.',
            'members.required' => 'Debe agregar al menos un miembro al grupo.',
            'members.min' => 'Debe agregar al menos un miembro al grupo.',
            'members.*.user_id.required' => 'El usuario del miembro es obligatorio.',
            'members.*.user_id.exists' => 'Uno o más usuarios seleccionados no existen.',
            'members.*.priority.required' => 'La prioridad del miembro es obligatoria.',
            'members.*.priority.in' => 'La prioridad debe ser: primario o de respaldo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'assignment_mode' => 'modo de asignación',
            'default' => 'predeterminado',
            'members' => 'miembros',
        ];
    }
}
