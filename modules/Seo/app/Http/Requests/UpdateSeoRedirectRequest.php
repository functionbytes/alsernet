<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeoRedirectRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $redirectId = $this->route('redirect')->id;
        $isRegex = $this->boolean('is_regex');
        $isWildcard = $this->boolean('is_wildcard');

        $sourcePathRules = [
            'required',
            'string',
            'max:255',
            Rule::unique('seo_redirects', 'source_path')->ignore($redirectId),
        ];

        if ($isRegex) {
            $sourcePathRules[] = function (string $attr, mixed $val, \Closure $fail): void {
                if (@preg_match($val, '') === false) {
                    $fail('El patrón regex no es válido.');
                }
            };
        } elseif (! $isWildcard) {
            $sourcePathRules[] = 'regex:/^\/[a-zA-Z0-9\/_-]*$/';
        }

        return [
            'source_path' => $sourcePathRules,
            'target_path' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'integer', Rule::in([301, 302, 307, 308])],
            'is_active' => ['sometimes', 'boolean'],
            'is_regex' => ['sometimes', 'boolean'],
            'is_wildcard' => ['sometimes', 'boolean'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
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
            'active_until.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize plain paths (not regex or wildcard patterns)
        if ($this->has('source_path') && ! $this->boolean('is_regex') && ! $this->boolean('is_wildcard')) {
            $sourcePath = $this->input('source_path');
            if (! str_starts_with($sourcePath, '/')) {
                $this->merge(['source_path' => '/'.ltrim($sourcePath, '/')]);
            }
        }
    }
}
