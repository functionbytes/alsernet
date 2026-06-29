<?php

namespace Modules\User\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit-users');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = optional(User::where('uid', $this->input('uid'))->first())->id;

        return [
            'firstname' => ['required', 'string', 'min:2', 'max:100'],
            'lastname' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', "unique:users,email,{$userId}"],
            'available' => ['required', 'in:0,1'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'password' => ['nullable', 'string', 'min:8'],
            'identification' => ['nullable', 'string', 'max:50'],
            'cellphone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'company' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'timezone'],
            'verified' => ['nullable', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'El correo electrónico ya está registrado en otro usuario.',
            'role.exists' => 'El rol seleccionado no existe.',
            'timezone.timezone' => 'La zona horaria seleccionada no es válida.',
        ];
    }
}
