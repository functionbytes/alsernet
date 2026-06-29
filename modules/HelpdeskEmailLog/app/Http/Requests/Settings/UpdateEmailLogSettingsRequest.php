<?php

namespace Modules\HelpdeskEmailLog\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailLogSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskemaillog.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'store_body' => ['nullable', 'in:1'],
            'max_body_bytes' => ['required', 'integer', 'min:1', 'max:10240'],
            'retention_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'stale_queued_hours' => ['required', 'integer', 'min:0', 'max:8760'],
            'per_page' => ['required', 'integer', 'in:10,25,50,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_body_bytes.required' => 'El tamaño máximo del cuerpo es obligatorio.',
            'max_body_bytes.integer' => 'El tamaño máximo debe ser un número entero.',
            'max_body_bytes.min' => 'El tamaño mínimo es 1 KB.',
            'max_body_bytes.max' => 'El tamaño máximo es 10.240 KB (10 MB).',
            'retention_days.required' => 'Los días de retención son obligatorios.',
            'retention_days.integer' => 'Los días de retención deben ser un número entero.',
            'retention_days.min' => 'El valor mínimo es 0 (sin purga).',
            'retention_days.max' => 'El valor máximo es 3.650 días (10 años).',
            'stale_queued_hours.required' => 'Las horas de cola obsoleta son obligatorias.',
            'stale_queued_hours.integer' => 'Las horas deben ser un número entero.',
            'stale_queued_hours.min' => 'El valor mínimo es 0 (desactivado).',
            'stale_queued_hours.max' => 'El valor máximo es 8.760 horas (1 año).',
            'per_page.required' => 'El paginado por defecto es obligatorio.',
            'per_page.in' => 'El paginado debe ser 10, 25, 50 o 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'store_body' => 'almacenar cuerpo',
            'max_body_bytes' => 'tamaño máximo del cuerpo',
            'retention_days' => 'días de retención',
            'stale_queued_hours' => 'horas de cola obsoleta',
            'per_page' => 'registros por página',
        ];
    }
}
