<?php

namespace Modules\HelpdeskCompliance\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class RequestIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdeskcompliance.view');
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:export,delete_soft,delete_hard'],
            'status' => ['nullable', 'string', 'in:pending,completed,failed'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de solicitud no es válido.',
            'status.in' => 'El estado de la solicitud no es válido.',
            'per_page.max' => 'El máximo por página es 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo',
            'status' => 'estado',
            'per_page' => 'por página',
        ];
    }
}
