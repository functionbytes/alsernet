<?php

namespace Modules\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'iso_code' => ['required', 'string', 'max:10'],
            'lenguage_code' => ['required', 'string', 'max:10'],
            'locate' => ['required', 'string', 'max:20'],
            'date_format_full' => ['required', 'string', 'max:50'],
            'date_format_lite' => ['required', 'string', 'max:50'],
            'available' => ['required', 'boolean'],
            'categories' => ['nullable', 'string'],
        ];
    }
}
