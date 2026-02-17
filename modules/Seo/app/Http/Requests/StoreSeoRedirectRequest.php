<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeoRedirectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_path' => [
                'required',
                'string',
                'max:255',
                'regex:/^\/[a-zA-Z0-9\/_-]*$/',
                'unique:seo_redirects,source_path',
            ],
            'target_path' => [
                'required',
                'string',
                'max:255',
            ],
            'status_code' => [
                'required',
                'integer',
                Rule::in([301, 302, 307, 308]),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
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
            'source_path.required' => 'La ruta origen es obligatoria.',
            'source_path.unique' => 'Ya existe una redireccion con esta ruta origen.',
            'source_path.regex' => 'La ruta origen debe ser una ruta URL valida que inicie con /.',
            'target_path.required' => 'La ruta destino es obligatoria.',
            'status_code.required' => 'El codigo de estado es obligatorio.',
            'status_code.in' => 'El codigo de estado debe ser 301, 302, 307 o 308.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize paths
        if ($this->has('source_path')) {
            $sourcePath = $this->input('source_path');
            if (! str_starts_with($sourcePath, '/')) {
                $this->merge([
                    'source_path' => '/'.ltrim($sourcePath, '/'),
                ]);
            }
        }

        // Set default is_active if not provided
        if (! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
