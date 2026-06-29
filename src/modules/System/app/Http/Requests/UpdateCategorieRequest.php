<?php

namespace Modules\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uid' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'available' => ['required', 'boolean'],
        ];
    }
}
