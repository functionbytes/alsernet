<?php

namespace Modules\Chat\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Chat\Models\Customers\CustomerAttribute;

class UpdateCustomAttributesRequest extends FormRequest
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
        // Get all contact custom attribute definitions for this account
        $customAttributes = CustomerAttribute::forAccount(auth()->user()->account_id)
            ->forCustomers()
            ->get();

        // Build validation rules dynamically based on attribute types
        $rules = [];
        foreach ($customAttributes as $attribute) {
            $key = "custom_attributes.{$attribute->attribute_key}";

            switch ($attribute->attribute_display_type) {
                case CustomerAttribute::DISPLAY_TYPE_TEXT:
                    $rules[$key] = 'nullable|string|max:500';
                    break;

                case CustomerAttribute::DISPLAY_TYPE_NUMBER:
                case CustomerAttribute::DISPLAY_TYPE_CURRENCY:
                case CustomerAttribute::DISPLAY_TYPE_PERCENT:
                    $rules[$key] = 'nullable|numeric';
                    break;

                case CustomerAttribute::DISPLAY_TYPE_LINK:
                    $rules[$key] = 'nullable|url|max:500';
                    break;

                case CustomerAttribute::DISPLAY_TYPE_DATE:
                    $rules[$key] = 'nullable|date';
                    break;

                case CustomerAttribute::DISPLAY_TYPE_LIST:
                    if ($attribute->attribute_values) {
                        $rules[$key] = 'nullable|in:'.implode(',', $attribute->attribute_values);
                    } else {
                        $rules[$key] = 'nullable|string';
                    }
                    break;

                case CustomerAttribute::DISPLAY_TYPE_CHECKBOX:
                    $rules[$key] = 'nullable|boolean';
                    break;
            }

            // Add regex validation if specified
            if ($attribute->regex_pattern) {
                $rules[$key] = ($rules[$key] ?? 'nullable').'|regex:'.$attribute->regex_pattern;
            }
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'custom_attributes.*.string' => 'El atributo debe ser texto.',
            'custom_attributes.*.max' => 'El atributo no puede exceder :max caracteres.',
            'custom_attributes.*.numeric' => 'El atributo debe ser un número.',
            'custom_attributes.*.url' => 'El atributo debe ser una URL válida.',
            'custom_attributes.*.date' => 'El atributo debe ser una fecha válida.',
            'custom_attributes.*.in' => 'El valor seleccionado no es válido.',
            'custom_attributes.*.boolean' => 'El atributo debe ser verdadero o falso.',
        ];
    }
}
