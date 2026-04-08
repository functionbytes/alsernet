<?php

namespace Modules\Template\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'unique:menus,slug', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'location' => 'nullable|string',
            'status' => 'boolean',
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
            'location' => 'ubicación',
            'status' => 'estado',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del menú es obligatorio.',
            'name.string' => 'El nombre del menú debe ser texto.',
            'name.max' => 'El nombre no puede superar los :max caracteres.',
            'slug.unique' => 'Este slug ya está en uso. Elige otro.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones.',
            'slug.max' => 'El slug no puede superar los :max caracteres.',
        ];
    }
}
