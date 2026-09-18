<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.update');
    }

    public function rules(): array
    {
        return [
            'shareable_id' => ['required', 'integer'],
            'shareable_type' => ['required', 'in:file,folder'],
            'shared_with_user_id' => ['required', 'integer', 'exists:users,id', 'different:'.auth()->id()],
            'role' => ['required', 'in:view,comment,edit'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'shareable_id.required' => 'El recurso a compartir es obligatorio.',
            'shareable_type.required' => 'El tipo de recurso es obligatorio.',
            'shareable_type.in' => 'El tipo de recurso debe ser archivo o carpeta.',
            'shared_with_user_id.required' => 'El usuario destinatario es obligatorio.',
            'shared_with_user_id.exists' => 'El usuario seleccionado no existe.',
            'shared_with_user_id.different' => 'No puedes compartir contigo mismo.',
            'role.required' => 'El rol es obligatorio.',
            'role.in' => 'El rol debe ser view, comment o edit.',
            'expires_at.after' => 'La fecha de expiración debe ser futura.',
        ];
    }

    public function attributes(): array
    {
        return [
            'shareable_id' => 'recurso',
            'shareable_type' => 'tipo de recurso',
            'shared_with_user_id' => 'usuario',
            'role' => 'rol',
            'expires_at' => 'fecha de expiración',
        ];
    }
}
