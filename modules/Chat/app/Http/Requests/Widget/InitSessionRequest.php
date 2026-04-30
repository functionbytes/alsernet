<?php

namespace Modules\Chat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class InitSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'custom_attributes' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'The email must not exceed 255 characters.',
            'name.max' => 'The name must not exceed 255 characters.',
            'phone_number.max' => 'The phone number must not exceed 50 characters.',
            'custom_attributes.array' => 'Custom attributes must be an array.',
        ];
    }
}
