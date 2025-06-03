<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ReturnCost;

class StoreReturnCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el controller
    }

    public function rules(): array
    {
        return [
            'cost_type' => [
                'required',
                'string',
                'in:' . implode(',', [
                    ReturnCost::TYPE_SHIPPING,
                    ReturnCost::TYPE_RESTOCKING,
                    ReturnCost::TYPE_INSPECTION,
                    ReturnCost::TYPE_DAMAGE,
                    ReturnCost::TYPE_OTHER
                ])
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:9999.99'
            ],
            'description' => [
                'nullable',
                'string',
                'max:255'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'cost_type.required' => 'El tipo de costo es obligatorio',
            'cost_type.in' => 'El tipo de costo seleccionado no es válido',
            'amount.required' => 'El monto es obligatorio',
            'amount.numeric' => 'El monto debe ser un número',
            'amount.min' => 'El monto mínimo es 0.01€',
            'amount.max' => 'El monto máximo es 9,999.99€',
            'description.max' => 'La descripción no puede exceder 255 caracteres'
        ];
    }

    public function attributes(): array
    {
        return [
            'cost_type' => 'tipo de costo',
            'amount' => 'monto',
            'description' => 'descripción'
        ];
    }
}

