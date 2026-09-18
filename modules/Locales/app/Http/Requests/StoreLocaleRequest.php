<?php

namespace Modules\Locales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('locale.create');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'alpha_dash', 'max:10', 'unique:locales,code'],
            'language_code' => ['nullable', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['nullable', 'string', 'max:100'],
            'flag' => ['nullable', 'string', 'max:10'],
            'rtl' => ['boolean'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ];
    }
}
