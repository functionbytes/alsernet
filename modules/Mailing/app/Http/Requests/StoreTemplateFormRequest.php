<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateFormRequest extends FormRequest
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
            'template_id' => [
                'required',
                'integer',
                'exists:mailing_templates,id',
            ],
            'form_name' => [
                'required',
                'string',
                'max:255',
            ],
            'form_fields' => [
                'required',
                'array',
                'min:1',
            ],
            'form_fields.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            'form_fields.*.label' => [
                'required',
                'string',
                'max:255',
            ],
            'form_fields.*.type' => [
                'required',
                'string',
                'max:50',
            ],
            'form_fields.*.required' => [
                'boolean',
            ],
            'form_attributes' => [
                'nullable',
                'array',
            ],
            'form_attributes.*.key' => [
                'required_with:form_attributes',
                'string',
                'max:255',
            ],
            'form_attributes.*.value' => [
                'required_with:form_attributes',
                'string',
                'max:1000',
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
            'template_id.required' => 'The template ID is required.',
            'template_id.integer' => 'The template ID must be an integer.',
            'template_id.exists' => 'The selected template does not exist.',
            'form_name.required' => 'The form name is required.',
            'form_name.string' => 'The form name must be a string.',
            'form_name.max' => 'The form name must not exceed 255 characters.',
            'form_fields.required' => 'At least one form field is required.',
            'form_fields.array' => 'Form fields must be an array.',
            'form_fields.min' => 'At least one form field must be provided.',
            'form_fields.*.name.required' => 'Each form field must have a name.',
            'form_fields.*.name.string' => 'Each form field name must be a string.',
            'form_fields.*.name.max' => 'Each form field name must not exceed 255 characters.',
            'form_fields.*.label.required' => 'Each form field must have a label.',
            'form_fields.*.label.string' => 'Each form field label must be a string.',
            'form_fields.*.label.max' => 'Each form field label must not exceed 255 characters.',
            'form_fields.*.type.required' => 'Each form field must have a type.',
            'form_fields.*.type.string' => 'Each form field type must be a string.',
            'form_fields.*.type.max' => 'Each form field type must not exceed 50 characters.',
            'form_fields.*.required.boolean' => 'Each form field required status must be a boolean.',
            'form_attributes.array' => 'Form attributes must be an array.',
            'form_attributes.*.key.required_with' => 'Each form attribute must have a key.',
            'form_attributes.*.key.string' => 'Each form attribute key must be a string.',
            'form_attributes.*.key.max' => 'Each form attribute key must not exceed 255 characters.',
            'form_attributes.*.value.required_with' => 'Each form attribute must have a value.',
            'form_attributes.*.value.string' => 'Each form attribute value must be a string.',
            'form_attributes.*.value.max' => 'Each form attribute value must not exceed 1000 characters.',
        ];
    }
}
