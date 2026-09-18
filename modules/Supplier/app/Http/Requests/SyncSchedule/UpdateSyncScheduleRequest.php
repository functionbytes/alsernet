<?php

namespace Modules\Supplier\Http\Requests\SyncSchedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSyncScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.sync.config');
    }

    public function rules(): array
    {
        return [
            'sync_type' => ['required', 'string', 'in:model,product'],
            'label' => ['required', 'string', 'max:100'],
            'hour' => ['required', 'integer', 'min:0', 'max:23'],
            'minute' => ['required', 'integer', 'min:0', 'max:59'],
            'is_enabled' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sync_type.required' => 'El tipo de sincronizacion es obligatorio.',
            'sync_type.in' => 'El tipo de sincronizacion seleccionado no es valido.',
            'label.required' => 'La etiqueta es obligatoria.',
            'label.max' => 'La etiqueta no puede superar los 100 caracteres.',
            'hour.required' => 'La hora es obligatoria.',
            'hour.min' => 'La hora minima es 0.',
            'hour.max' => 'La hora maxima es 23.',
            'minute.required' => 'El minuto es obligatorio.',
            'minute.min' => 'El minuto minimo es 0.',
            'minute.max' => 'El minuto maximo es 59.',
        ];
    }

    public function attributes(): array
    {
        return [
            'sync_type' => 'tipo de sincronizacion',
            'label' => 'etiqueta',
            'hour' => 'hora',
            'minute' => 'minuto',
            'is_enabled' => 'estado activo',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_enabled' => $this->boolean('is_enabled'),
        ]);
    }
}
