<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.edit')
            || $this->user()->can('Forms.follow-ups.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'send_after_days' => ['required', 'integer', 'min:1', 'max:365'],
            'email_template_id' => ['nullable', 'integer'],
            'recipient_type' => ['required', 'in:admin,submitter,both'],
            'condition_field_key' => ['nullable', 'string', 'max:100'],
            'condition_value' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'send_after_days.min' => 'El follow-up debe enviarse al menos 1 día después.',
            'send_after_days.max' => 'El follow-up debe enviarse en los siguientes 365 días.',
            'recipient_type.in' => 'El tipo de destinatario no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'send_after_days' => 'días para enviar',
            'email_template_id' => 'plantilla de email',
            'recipient_type' => 'tipo de destinatario',
        ];
    }
}
