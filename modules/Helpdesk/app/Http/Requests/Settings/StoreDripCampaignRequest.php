<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreDripCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.drip-campaigns.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'trigger_type' => ['required', 'in:tag_added,conversation_closed,csat_low,manual'],
            'trigger_value' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'steps' => ['nullable', 'array'],
            'steps.*.delay_minutes' => ['required', 'integer', 'min:0'],
            'steps.*.channel' => ['required', 'in:widget,email,whatsapp'],
            'steps.*.template_type' => ['nullable', 'in:text,hsm'],
            'steps.*.body' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'description.max' => 'La descripcion no puede superar los 1000 caracteres.',
            'trigger_type.required' => 'El tipo de disparador es obligatorio.',
            'trigger_type.in' => 'El tipo de disparador seleccionado no es valido.',
            'trigger_value.max' => 'El valor del disparador no puede superar los 255 caracteres.',
            'steps.*.delay_minutes.required' => 'El tiempo de espera del paso es obligatorio.',
            'steps.*.delay_minutes.integer' => 'El tiempo de espera debe ser un numero entero.',
            'steps.*.delay_minutes.min' => 'El tiempo de espera no puede ser negativo.',
            'steps.*.channel.required' => 'El canal del paso es obligatorio.',
            'steps.*.channel.in' => 'El canal seleccionado no es valido.',
            'steps.*.template_type.in' => 'El tipo de plantilla seleccionado no es valido.',
            'steps.*.body.max' => 'El mensaje del paso no puede superar los 2000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'trigger_type' => 'tipo de disparador',
            'trigger_value' => 'valor del disparador',
            'is_active' => 'estado',
            'steps' => 'pasos',
            'steps.*.delay_minutes' => 'tiempo de espera',
            'steps.*.channel' => 'canal',
            'steps.*.template_type' => 'tipo de plantilla',
            'steps.*.body' => 'mensaje',
        ];
    }
}
