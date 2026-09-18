<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Compliance;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Acción sobre el propio usuario autenticado; la ruta ya exige auth.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
        ];
    }
}
