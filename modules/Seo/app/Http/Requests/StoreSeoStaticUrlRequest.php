<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSeoStaticUrlRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'starts_with:http',
                'unique:seo_static_urls,url',
            ],
            'priority' => [
                'required',
                'numeric',
                'between:0,1',
            ],
            'changefreq' => [
                'required',
                'in:always,hourly,daily,weekly,monthly,yearly,never',
            ],
            'is_active' => [
                'required',
                'in:0,1',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'La URL es obligatoria.',
            'url.starts_with' => 'La URL debe comenzar con http o https.',
            'url.unique' => 'Ya existe una URL estatica con esta direccion.',
            'priority.required' => 'La prioridad es obligatoria.',
            'priority.between' => 'La prioridad debe estar entre 0 y 1.',
            'changefreq.required' => 'La frecuencia de cambio es obligatoria.',
            'changefreq.in' => 'La frecuencia de cambio no es valida.',
        ];
    }
}
