<?php

namespace Modules\Widget\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sidebar_id' => 'required|string',
            'items' => 'nullable|array',
        ];
    }
}
