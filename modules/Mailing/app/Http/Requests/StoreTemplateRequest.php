<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mailing_templates', 'name')->where('user_id', auth()->id()),
            ],
            'subject' => [
                'required',
                'string',
                'max:255',
            ],
            'html_content' => [
                'required',
                'string',
            ],
            'text_content' => [
                'nullable',
                'string',
            ],
            'category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'status' => [
                'sometimes',
                Rule::in(['draft', 'published', 'archived']),
            ],
            'is_default' => [
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
            'name.required' => 'The template name is required.',
            'name.string' => 'The template name must be a string.',
            'name.max' => 'The template name must not exceed 255 characters.',
            'name.unique' => 'A template with this name already exists for your account.',
            'subject.required' => 'The email subject is required.',
            'subject.string' => 'The email subject must be a string.',
            'subject.max' => 'The email subject must not exceed 255 characters.',
            'html_content.required' => 'The HTML content is required.',
            'html_content.string' => 'The HTML content must be a string.',
            'text_content.string' => 'The text content must be a string.',
            'category.string' => 'The category must be a string.',
            'category.max' => 'The category must not exceed 100 characters.',
            'status.in' => 'The status must be one of: draft, published, archived.',
            'is_default.boolean' => 'The is_default field must be a boolean value.',
        ];
    }
}
