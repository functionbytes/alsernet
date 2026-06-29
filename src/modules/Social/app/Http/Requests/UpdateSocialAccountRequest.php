<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Social\Enums\AccountStatus;

class UpdateSocialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('account'));
    }

    public function rules(): array
    {
        $statusValues = implode(',', array_column(AccountStatus::cases(), 'value'));

        return [
            'username' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'status' => "required|in:{$statusValues}",
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio',
            'username.max' => 'El nombre de usuario no puede exceder 255 caracteres',
            'name.required' => 'El nombre para mostrar es obligatorio',
            'name.max' => 'El nombre para mostrar no puede exceder 255 caracteres',
            'status.required' => 'El estado es obligatorio',
            'status.in' => 'El estado seleccionado no es válido',
        ];
    }
}
