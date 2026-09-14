<?php

namespace Modules\Supplier\Http\Requests\Source;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.sources.manage');
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'source_type' => ['required', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'trust_level' => ['nullable', 'string', 'in:low,medium,high'],
            'usage_notes' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_active' => ['boolean'],
            'extraction_mode' => ['nullable', 'string', 'in:manual,ai'],
            'configuration' => ['nullable', 'array'],
            'content_urls' => ['nullable', 'array'],
            'content_urls.*.url' => ['nullable', 'url', 'max:2048'],
            'content_urls.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El nombre de la fuente es obligatorio.',
            'label.max' => 'El nombre no puede superar los 255 caracteres.',
            'source_type.required' => 'El tipo de fuente es obligatorio.',
            'trust_level.in' => 'El nivel de confianza debe ser bajo, medio o alto.',
            'priority.min' => 'La prioridad minima es 1.',
            'priority.max' => 'La prioridad maxima es 100.',
            'extraction_mode.in' => 'El modo de extraccion debe ser manual o ia.',
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'nombre',
            'source_type' => 'tipo de fuente',
            'description' => 'descripcion',
            'trust_level' => 'nivel de confianza',
            'usage_notes' => 'notas de uso',
            'priority' => 'prioridad',
            'is_active' => 'estado activo',
            'extraction_mode' => 'modo de extraccion',
            'configuration' => 'configuracion',
            'content_urls' => 'URLs de referencia de contenido',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Solo normaliza is_active cuando el formulario lo envía. El formulario de
        // edición ya no expone este campo (se conserva el valor actual de la
        // fuente) — si aquí se forzara un default a false, cada guardado
        // desactivaría la fuente silenciosamente.
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        }
    }
}
