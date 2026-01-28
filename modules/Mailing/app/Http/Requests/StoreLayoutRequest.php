<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLayoutRequest extends FormRequest
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
                Rule::unique('mailing_layouts', 'name')->where('user_id', auth()->id()),
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'html_content' => [
                'required',
                'string',
            ],
            'is_default' => [
                'sometimes',
                'boolean',
            ],
            'status' => [
                'sometimes',
                Rule::in(['draft', 'published', 'archived']),
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
            'name.required' => 'The layout name is required.',
            'name.string' => 'The layout name must be a string.',
            'name.max' => 'The layout name must not exceed 255 characters.',
            'name.unique' => 'A layout with this name already exists for your account.',
            'description.string' => 'The description must be a string.',
            'description.max' => 'The description must not exceed 1000 characters.',
            'html_content.required' => 'The HTML content is required.',
            'html_content.string' => 'The HTML content must be a string.',
            'is_default.boolean' => 'The is_default field must be a boolean value.',
            'status.in' => 'The status must be one of: draft, published, archived.',
        ];
    }
}
