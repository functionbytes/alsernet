<?php

namespace Modules\Template\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Ajustar según políticas de autorización
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $templateId = $this->route('template')->id ?? null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('templates', 'slug')->ignore($templateId),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'content' => [
                'required',
                'string',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'inherit' => [
                'nullable',
                'string',
                Rule::exists('templates', 'slug')->whereNot('id', $templateId),
            ],
            'author' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'slug' => 'slug',
            'content' => 'contenido',
            'description' => 'descripción',
            'inherit' => 'heredar de',
            'author' => 'autor',
            'status' => 'estado',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'slug.unique' => 'Este slug ya está en uso.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones.',
            'content.required' => 'El contenido es obligatorio.',
            'inherit.exists' => 'La plantilla padre seleccionada no existe.',
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug from name if not provided
        if (! $this->has('slug') || empty($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }

        // Set status to inactive by default if not provided
        if (! $this->has('status') || empty($this->status)) {
            $this->merge([
                'status' => 'inactive',
            ]);
        }

        // Set template_path based on slug
        $this->merge([
            'template_path' => 'platform/themes/'.Str::slug($this->slug),
        ]);
    }
}
