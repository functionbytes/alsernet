<?php

namespace Modules\Mailrelay\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ValidateEmailRequest extends FormRequest
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
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
            ],
            'validation_types' => [
                'sometimes',
                'array',
            ],
            'validation_types.*' => [
                'string',
                'in:syntax,dns,smtp,external,ml',
            ],
            'force_revalidate' => [
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
            'email.required' => 'The email address is required.',
            'email.email' => 'The email address must be valid.',
            'email.max' => 'The email address must not exceed 255 characters.',
            'validation_types.array' => 'The validation types must be an array.',
            'validation_types.*.in' => 'The validation type must be one of: syntax, dns, smtp, external, ml.',
        ];
    }
}
