<?php

namespace Modules\Chat\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('customer'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'country_code' => 'nullable|string|max:5',
            'phone_number' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:5000',
            'company_name' => 'nullable|string|max:255',
            'social_profiles' => 'nullable|array',
            'social_profiles.linkedin' => 'nullable|url|max:500',
            'social_profiles.facebook' => 'nullable|url|max:500',
            'social_profiles.instagram' => 'nullable|url|max:500',
            'social_profiles.twitter' => 'nullable|url|max:500',
            'social_profiles.github' => 'nullable|url|max:500',
            'additional_attributes' => 'nullable|array',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.max' => 'El email no puede exceder 255 caracteres.',
            'country_code.max' => 'El código de país no puede exceder 5 caracteres.',
            'phone_number.max' => 'El número de teléfono no puede exceder 50 caracteres.',
            'city.max' => 'La ciudad no puede exceder 255 caracteres.',
            'country.max' => 'El país no puede exceder 255 caracteres.',
            'bio.max' => 'La biografía no puede exceder 5000 caracteres.',
            'company_name.max' => 'El nombre de la empresa no puede exceder 255 caracteres.',
            'social_profiles.array' => 'Los perfiles sociales deben ser un array.',
            'social_profiles.linkedin.url' => 'El perfil de LinkedIn debe ser una URL válida.',
            'social_profiles.linkedin.max' => 'El perfil de LinkedIn no puede exceder 500 caracteres.',
            'social_profiles.facebook.url' => 'El perfil de Facebook debe ser una URL válida.',
            'social_profiles.facebook.max' => 'El perfil de Facebook no puede exceder 500 caracteres.',
            'social_profiles.instagram.url' => 'El perfil de Instagram debe ser una URL válida.',
            'social_profiles.instagram.max' => 'El perfil de Instagram no puede exceder 500 caracteres.',
            'social_profiles.twitter.url' => 'El perfil de Twitter debe ser una URL válida.',
            'social_profiles.twitter.max' => 'El perfil de Twitter no puede exceder 500 caracteres.',
            'social_profiles.github.url' => 'El perfil de GitHub debe ser una URL válida.',
            'social_profiles.github.max' => 'El perfil de GitHub no puede exceder 500 caracteres.',
            'additional_attributes.array' => 'Los atributos adicionales deben ser un array.',
        ];
    }
}
