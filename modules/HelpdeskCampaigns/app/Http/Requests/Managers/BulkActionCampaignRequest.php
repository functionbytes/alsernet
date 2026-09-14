<?php

namespace Modules\HelpdeskCampaigns\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.campaigns.manage');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete,activate,pause,end'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:helpdesk.helpdesk_campaigns,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción seleccionada no es válida.',
            'ids.required' => 'Debe seleccionar al menos una campaña.',
            'ids.array' => 'El listado de campañas no es válido.',
            'ids.min' => 'Debe seleccionar al menos una campaña.',
            'ids.*.integer' => 'Los identificadores de campaña no son válidos.',
            'ids.*.exists' => 'Una de las campañas seleccionadas no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'acción',
            'ids' => 'campañas',
            'ids.*' => 'campaña',
        ];
    }
}
