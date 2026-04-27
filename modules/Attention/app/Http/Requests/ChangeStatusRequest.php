<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attention.change-status') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:received,in_process,resolved,closed'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser uno de: recibido, en proceso, resuelto, cerrado.',
            'comment.max' => 'El comentario no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'estado',
            'comment' => 'comentario',
        ];
    }
}
