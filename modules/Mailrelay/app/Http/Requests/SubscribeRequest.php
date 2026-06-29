<?php

namespace Modules\Mailrelay\Http\Requests;

use App\Rules\EmailDomainIsNot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
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
                'unique:subscribers,email',
                EmailDomainIsNot::disposable(),
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'custom_fields' => [
                'sometimes',
                'array',
            ],
            'custom_fields.*' => [
                'string',
                'max:500',
            ],
            'group_ids' => [
                'sometimes',
                'array',
            ],
            'group_ids.*' => [
                'integer',
                'exists:mailrelay_groups,id',
            ],
            'sync_to_mailrelay' => [
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
            'email.unique' => 'This email address is already subscribed.',
            'name.max' => 'The name must not exceed 255 characters.',
            'group_ids.*.exists' => 'One or more selected groups do not exist.',
        ];
    }
}
