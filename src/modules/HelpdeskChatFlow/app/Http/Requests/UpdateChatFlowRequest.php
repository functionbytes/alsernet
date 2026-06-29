<?php

namespace Modules\HelpdeskChatFlow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskChatFlow\Models\ChatFlow;

class UpdateChatFlowRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['nodes', 'trigger_conditions'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $decoded = json_decode($this->input($field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$field => $decoded]);
                }
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('chatflow.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'inbox_id' => ['nullable', 'exists:helpdesk_inboxes,id'],
            'trigger_type' => ['sometimes', 'required', 'in:conversation_start,keyword,manual,no_agent'],
            'trigger_conditions' => ['nullable', 'array'],
            'trigger_conditions.keywords' => ['sometimes', 'array'],
            'trigger_conditions.keywords.*' => ['string', 'max:100'],
            'trigger_conditions.timeout_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            // Behaviour toggles from the editor settings panel. They must be
            // listed explicitly: defining nested rules makes validated() prune
            // any trigger_conditions key that lacks its own rule.
            'trigger_conditions.multilingual' => ['sometimes', 'boolean'],
            'trigger_conditions.sentiment_escalation' => ['sometimes', 'boolean'],
            'trigger_conditions.sentiment_message' => ['sometimes', 'nullable', 'string', 'max:500'],
            'trigger_conditions.escape_enabled' => ['sometimes', 'boolean'],
            'trigger_conditions.escape_message' => ['sometimes', 'nullable', 'string', 'max:500'],
            'trigger_conditions.handoff_summary' => ['sometimes', 'boolean'],
            'trigger_conditions.ab_variant_id' => ['sometimes', 'nullable', 'integer'],
            'trigger_conditions.ab_split' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:99'],
            // Proactive outbound trigger from a business event (PrestaShop/ERP).
            'trigger_conditions.business_event' => ['sometimes', 'nullable', 'in:abandoned_cart,order_status,order_ready'],
            'nodes' => ['nullable', 'array'],
            'nodes.*.type' => ['required', 'string', 'in:'.implode(',', ChatFlow::NODE_TYPES)],
            'status' => ['nullable', 'in:draft,active,archived'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del flow es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'trigger_type.required' => 'El tipo de activación es obligatorio.',
            'trigger_type.in' => 'El tipo de activación seleccionado no es válido.',
            'inbox_id.exists' => 'El inbox seleccionado no existe.',
            'nodes.*.type.in' => 'Uno de los nodos tiene un tipo no válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'inbox_id' => 'inbox',
            'trigger_type' => 'tipo de activación',
            'trigger_conditions' => 'condiciones de activación',
            'nodes' => 'nodos',
            'status' => 'estado',
            'priority' => 'prioridad',
        ];
    }
}
