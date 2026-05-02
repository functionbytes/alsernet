<?php

namespace Modules\Engagement\Http\Requests\Sdk;

use Illuminate\Foundation\Http\FormRequest;

class TrackBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.name' => ['required', 'string', 'max:64'],
            'events.*.properties' => ['nullable', 'array'],
            'events.*.ts' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'events.required' => 'Se requiere al menos un evento.',
            'events.max' => 'No se pueden enviar más de 50 eventos por lote.',
            'events.*.name.required' => 'El nombre del evento es obligatorio.',
            'events.*.ts.required' => 'La marca de tiempo del evento es obligatoria.',
            'events.*.ts.date' => 'La marca de tiempo no tiene un formato válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'events' => 'eventos',
            'events.*.name' => 'nombre del evento',
            'events.*.ts' => 'marca de tiempo',
        ];
    }
}
