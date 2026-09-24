<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddressShipClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.orders.ship_claim') ?? false;
    }

    public function rules(): array
    {
        $types = array_keys((array) config('helpdeskprestashop.ext.address.ship_claim.types', []));
        $maxAttachments = (int) config('helpdeskprestashop.ext.address.ship_claim.max_attachments', 10);

        return [
            'type' => ['required', 'string', Rule::in($types)],
            'detail' => ['required', 'string', 'min:3', 'max:1200'],
            'carrier' => ['nullable', 'string', 'max:64'],
            'tracking_number' => ['nullable', 'string', 'max:64'],
            'conversation_id' => ['nullable', 'integer', 'min:1'],
            'attachments' => ['nullable', 'array', 'max:'.$maxAttachments],
            'attachments.*.name' => ['required', 'string', 'max:120'],
            'attachments.*.url' => ['required', 'string', 'max:500', 'url:http,https'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Elige el tipo de incidencia.',
            'type.in' => 'Tipo de incidencia no válido.',
            'detail.required' => 'Describe la incidencia para la reclamación.',
            'detail.min' => 'Describe la incidencia para la reclamación.',
            'detail.max' => 'El detalle no puede superar los 1200 caracteres.',
            'attachments.max' => 'Demasiados adjuntos seleccionados.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo de incidencia',
            'detail' => 'detalle',
            'carrier' => 'transportista',
            'tracking_number' => 'número de seguimiento',
        ];
    }
}
