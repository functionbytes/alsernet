<?php

namespace Modules\HelpdeskChatFlow\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\HelpdeskChatFlow\Models\ChatFlow;

/**
 * Validates a flow import (JSON file upload or raw JSON string).
 *
 * The raw payload is decoded in prepareForValidation() and merged as `flow`,
 * so the whole structure — nodes, edges, trigger_type and the shape of
 * trigger_conditions (mirroring StoreChatFlowRequest/UpdateChatFlowRequest) —
 * is validated declaratively here instead of inline in the controller. The
 * runtime graph validation (broken go_to_step, unreachable nodes, …) still
 * runs in the controller through ChatFlowValidator, same as on publish.
 */
class ImportChatFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('chatflow.create');
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->hasFile('file') && $this->file('file')->isValid()
            ? (string) file_get_contents($this->file('file')->getRealPath())
            : (string) $this->input('json');

        $decoded = json_decode($raw, true);

        $this->merge(['flow' => is_array($decoded) ? $decoded : null]);
    }

    public function rules(): array
    {
        $maxNodes = (int) config('helpdeskchatflow.import.max_nodes', 500);

        return [
            'file' => ['sometimes', 'file', 'max:2048', 'mimes:json,txt'],
            'json' => ['sometimes', 'string', 'max:1048576'],

            'flow' => ['required', 'array'],
            'flow.name' => ['nullable', 'string', 'max:255'],
            'flow.description' => ['nullable', 'string', 'max:1000'],
            'flow.trigger_type' => ['nullable', 'string', 'in:'.implode(',', ChatFlow::TRIGGER_TYPES)],

            // trigger_conditions must be a well-formed object with the shapes
            // the engine reads (keywords, timeouts, A/B config, toggles…).
            'flow.trigger_conditions' => ['nullable', 'array'],
            'flow.trigger_conditions.keywords' => ['sometimes', 'array'],
            'flow.trigger_conditions.keywords.*' => ['string', 'max:100'],
            'flow.trigger_conditions.timeout_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'flow.trigger_conditions.multilingual' => ['sometimes', 'boolean'],
            'flow.trigger_conditions.sentiment_escalation' => ['sometimes', 'boolean'],
            'flow.trigger_conditions.sentiment_message' => ['sometimes', 'nullable', 'string', 'max:500'],
            'flow.trigger_conditions.escape_enabled' => ['sometimes', 'boolean'],
            'flow.trigger_conditions.escape_message' => ['sometimes', 'nullable', 'string', 'max:500'],
            'flow.trigger_conditions.handoff_summary' => ['sometimes', 'boolean'],
            'flow.trigger_conditions.ab_variant_id' => ['sometimes', 'nullable', 'integer'],
            'flow.trigger_conditions.ab_split' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:99'],
            'flow.trigger_conditions.business_event' => ['sometimes', 'nullable', 'in:abandoned_cart,order_status,order_ready'],

            // 1 MB of JSON can carry thousands of tiny nodes that degrade the
            // validator and the React editor — explicit node/edge caps.
            'flow.nodes' => ['required', 'array', 'min:1', 'max:'.$maxNodes],
            'flow.nodes.*' => ['array'],
            'flow.nodes.*.id' => ['required', 'string', 'max:64'],
            'flow.nodes.*.type' => ['required', 'string', 'in:'.implode(',', ChatFlow::NODE_TYPES)],
            'flow.edges' => ['sometimes', 'array', 'max:'.($maxNodes * 4)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $nodes = $this->input('flow.nodes');

            if (is_array($nodes) && ! collect($nodes)->contains(fn ($n) => ($n['type'] ?? '') === 'start')) {
                $v->errors()->add('flow.nodes', 'El flow importado no tiene nodo de inicio.');
            }
        });
    }

    /**
     * The decoded, structurally validated flow payload.
     *
     * @return array<string, mixed>
     */
    public function flowData(): array
    {
        return (array) $this->input('flow');
    }

    public function messages(): array
    {
        $maxNodes = (int) config('helpdeskchatflow.import.max_nodes', 500);

        return [
            'file.file' => 'El archivo no es válido.',
            'file.max' => 'El archivo no puede superar los 2 MB.',
            'file.mimes' => 'El archivo debe ser un JSON exportado del editor.',
            'json.max' => 'El contenido del flow es demasiado grande.',
            'flow.required' => 'El archivo no es un flow válido (JSON malformado).',
            'flow.array' => 'El archivo no es un flow válido (JSON malformado).',
            'flow.nodes.required' => 'El archivo no es un flow válido (falta «nodes»).',
            'flow.nodes.max' => "El flow importado supera el máximo de {$maxNodes} nodos.",
            'flow.edges.max' => 'El flow importado tiene demasiadas conexiones.',
            'flow.nodes.*.id.required' => 'El archivo contiene nodos con un formato no reconocido.',
            'flow.nodes.*.id.string' => 'El archivo contiene nodos con un formato no reconocido.',
            'flow.nodes.*.type.required' => 'El archivo contiene nodos con un formato no reconocido.',
            'flow.nodes.*.type.in' => 'El archivo contiene nodos con un formato no reconocido.',
            'flow.trigger_type.in' => 'El tipo de activación del flow importado no es válido.',
            'flow.trigger_conditions.array' => 'Las condiciones de activación del flow importado no son válidas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo',
            'json' => 'contenido del flow',
            'flow' => 'flow importado',
            'flow.nodes' => 'nodos del flow',
            'flow.trigger_conditions' => 'condiciones de activación',
        ];
    }

    /**
     * The chatflow index surfaces feedback via session('error') toasts (not an
     * errors bag), so structural failures redirect back the same way the old
     * inline checks did.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            back()->with('error', (string) $validator->errors()->first())
        );
    }
}
