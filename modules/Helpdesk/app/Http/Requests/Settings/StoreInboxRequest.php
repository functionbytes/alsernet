<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Inbox;

class StoreInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'channel_type' => ['required', Rule::in(Inbox::availableChannelTypes())],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{3,8}$/'],
            'icon' => ['nullable', 'string', 'max:64'],
            'default_assignee_id' => ['nullable', 'integer', 'exists:'.config('database.default').'.users,id'],
            'default_group_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_groups,id'],
            'greeting_enabled' => ['boolean'],
            'greeting_message' => ['nullable', 'string', 'max:5000'],
            'working_hours_enabled' => ['boolean'],
            'working_hours' => ['nullable', 'array'],
            'out_of_office_message' => ['nullable', 'string', 'max:5000'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del inbox es obligatorio.',
            'channel_type.required' => 'El tipo de canal es obligatorio.',
            'channel_type.in' => 'El canal seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'channel_type' => 'canal',
            'default_assignee_id' => 'agente por defecto',
            'default_group_id' => 'equipo por defecto',
        ];
    }
}
