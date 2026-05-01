<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.companies.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'in:1-10,11-50,51-200,201-1000,1000+'],
            'website' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'health_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la empresa es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'domain.max' => 'El dominio no puede superar los 255 caracteres.',
            'industry.max' => 'La industria no puede superar los 100 caracteres.',
            'size.in' => 'El tamaño seleccionado no es valido.',
            'website.url' => 'El sitio web debe ser una URL valida.',
            'website.max' => 'La URL del sitio web no puede superar los 500 caracteres.',
            'notes.max' => 'Las notas no pueden superar los 2000 caracteres.',
            'health_score.integer' => 'El health score debe ser un numero entero.',
            'health_score.min' => 'El health score no puede ser menor a 0.',
            'health_score.max' => 'El health score no puede ser mayor a 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'domain' => 'dominio',
            'industry' => 'industria',
            'size' => 'tamaño',
            'website' => 'sitio web',
            'notes' => 'notas',
            'health_score' => 'health score',
        ];
    }
}
