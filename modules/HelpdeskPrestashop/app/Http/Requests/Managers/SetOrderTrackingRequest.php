<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class SetOrderTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string', 'max:64'],
            'carrier_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'tracking_number.required' => 'Introduce el número de seguimiento.',
            'tracking_number.max' => 'El número de seguimiento no puede superar los 64 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tracking_number' => 'número de seguimiento',
            'carrier_id' => 'transportista',
        ];
    }
}
