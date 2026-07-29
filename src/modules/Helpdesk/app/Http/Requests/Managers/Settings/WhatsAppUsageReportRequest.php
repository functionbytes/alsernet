<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class WhatsAppUsageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.whatsapp-templates.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date' => 'La fecha "desde" no es válida.',
            'to.date' => 'La fecha "hasta" no es válida.',
            'to.after_or_equal' => 'La fecha "hasta" debe ser posterior o igual a la fecha "desde".',
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => 'fecha desde',
            'to' => 'fecha hasta',
        ];
    }
}
