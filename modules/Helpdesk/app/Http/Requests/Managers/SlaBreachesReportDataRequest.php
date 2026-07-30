<?php

namespace Modules\Helpdesk\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class SlaBreachesReportDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.reports.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'agent_id.integer' => 'El agente debe ser un identificador válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'agent_id' => 'agente',
        ];
    }
}
