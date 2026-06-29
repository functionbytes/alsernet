<?php

namespace Modules\Analytics\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'email' => ['required', 'email', 'max:255'],
            'format' => ['required', 'in:pdf,excel,csv'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'frequency' => 'frecuencia',
            'email' => 'email de destino',
            'format' => 'formato',
        ];
    }
}
