<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.workflows.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'trigger_type' => ['required', 'in:conversation_created,message_received,conversation_closed,csat_answered,tag_added,sla_breach,manual'],
            'trigger_config' => ['nullable', 'string'],
            'nodes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del workflow es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'description.max' => 'La descripcion no puede superar los 1000 caracteres.',
            'trigger_type.required' => 'El tipo de trigger es obligatorio.',
            'trigger_type.in' => 'El tipo de trigger seleccionado no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'trigger_type' => 'tipo de trigger',
            'trigger_config' => 'configuracion del trigger',
            'nodes' => 'nodos',
            'is_active' => 'estado activo',
        ];
    }
}
