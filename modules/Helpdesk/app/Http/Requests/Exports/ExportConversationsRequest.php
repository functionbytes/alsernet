<?php

namespace Modules\Helpdesk\Http\Requests\Exports;

use Illuminate\Foundation\Http\FormRequest;

class ExportConversationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.exports.create');
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'max:50'],
            'channel_type' => ['nullable', 'string', 'in:web,email,whatsapp,facebook,instagram'],
            'inbox_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'channel_type.in' => 'El canal debe ser: web, email, whatsapp, facebook o instagram.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_from' => 'fecha inicial',
            'date_to' => 'fecha final',
            'status' => 'estado',
            'channel_type' => 'canal',
            'inbox_id' => 'inbox',
        ];
    }
}
