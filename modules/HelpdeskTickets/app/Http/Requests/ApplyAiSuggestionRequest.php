<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyAiSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'field' => ['required', 'string', 'in:category,priority'],
        ];
    }

    public function messages(): array
    {
        return [
            'field.required' => 'El campo a aplicar es obligatorio.',
            'field.in' => 'Solo se puede aplicar la categoría o la prioridad sugerida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'field' => 'campo',
        ];
    }
}
