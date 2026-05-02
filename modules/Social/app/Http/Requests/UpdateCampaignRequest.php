<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('campaign'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la campaña es obligatorio',
            'name.max' => 'El nombre no puede exceder 255 caracteres',
            'color.required' => 'Debes seleccionar un color',
            'color.regex' => 'El formato del color no es válido (debe ser hexadecimal)',
            'start_date.date' => 'La fecha de inicio no es válida',
            'end_date.date' => 'La fecha de fin no es válida',
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
            'status.required' => 'El estado es obligatorio',
            'status.boolean' => 'El estado debe ser verdadero o falso',
        ];
    }

    protected function prepareForValidation(): void
    {
        $status = $this->has('status') ? (bool) $this->input('status') : false;

        $this->merge([
            'status' => $status,
        ]);
    }
}
