<?php

namespace Modules\HelpdeskSla\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesksla.manage');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'is_recurring' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'La fecha es obligatoria.',
            'name.required' => 'El nombre del festivo es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date' => 'fecha',
            'name' => 'nombre',
            'is_recurring' => 'recurrente',
        ];
    }
}
