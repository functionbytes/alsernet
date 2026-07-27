<?php

namespace Modules\HelpdeskErp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchErpCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskerp.prospect.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.string' => 'El término de búsqueda debe ser texto.',
            'type.string' => 'El tipo de búsqueda debe ser texto.',
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => 'término de búsqueda',
            'type' => 'tipo de búsqueda',
        ];
    }
}
