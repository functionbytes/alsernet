<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Return\ReturnRequest;

class CreateReturnRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_order' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    // Verificar que no exista una devolución activa para este pedido
                    $existingReturn = ReturnRequest::where('id_order', $value)
                        ->where('email', $this->email)
                        ->whereHas('status', function($q) {
                            $q->where('active', true)
                                ->whereNotIn('id_return_state', [5]); // No cerradas
                        })
                        ->first();

                    if ($existingReturn) {
                        $fail('Ya existe una solicitud de devolución activa para este pedido.');
                    }
                }
            ],
            'id_order_detail' => 'required|integer|min:1',
            'customer_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255'
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\s\-\(\)]+$/'
            ],
            'id_return_type' => [
                'required',
                'integer',
                'exists:return_types,id_return_type'
            ],
            'id_return_reason' => [
                'required',
                'integer',
                'exists:return_reasons,id_return_reason',
                function ($attribute, $value, $fail) {
                    // Verificar que el motivo sea válido para el tipo de devolución
                    if ($this->id_return_type) {
                        $reason = \App\Models\Return\ReturnReason::find($value);
                        $returnType = \App\Models\Return\ReturnType::find($this->id_return_type);

                        if ($reason && $returnType) {
                            $typeMapping = [
                                1 => 'refund',    // Reembolso
                                2 => 'replacement', // Reemplazo
                                3 => 'repair'     // Reparación
                            ];

                            $expectedType = $typeMapping[$this->id_return_type] ?? 'all';

                            if (!$reason->isValidForReturnType($expectedType)) {
                                $fail('El motivo seleccionado no es válido para este tipo de devolución.');
                            }
                        }
                    }
                }
            ],
            'logistics_mode' => [
                'required',
                Rule::in(['customer_transport', 'home_pickup', 'store_delivery', 'inpost'])
            ],
            'description' => [
                'required',
                'string',
                'min:' . config('returns.validation.min_description_length', 10),
                'max:' . config('returns.validation.max_description_length', 1000)
            ],
            'product_quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100'
            ],
            'return_address' => [
                'nullable',
                'string',
                'max:500',
                'required_if:logistics_mode,home_pickup'
            ],
            'iban' => [
                'nullable',
                'string',
                'size:24',
                'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4}[0-9]{7}([A-Z0-9]?){0,16}$/',
                'required_if:id_return_type,1' // Requerido para reembolsos
            ],
            'id_customer' => 'nullable|integer|min:0',
            'id_address' => 'nullable|integer|min:0',
            'pickup_date' => [
                'nullable',
                'date',
                'after:today',
                'before:' . now()->addDays(30)->format('Y-m-d'),
                'required_if:logistics_mode,home_pickup'
            ],
            'terms_accepted' => [
                'required',
                'accepted'
            ]
        ];
    }

    public function messages()
    {
        return [
            'id_order.required' => 'El número de pedido es obligatorio.',
            'id_order.integer' => 'El número de pedido debe ser un número válido.',
            'id_order_detail.required' => 'El detalle del pedido es obligatorio.',
            'customer_name.required' => 'El nombre del cliente es obligatorio.',
            'customer_name.regex' => 'El nombre solo puede contener letras y espacios.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'phone.regex' => 'El teléfono debe tener un formato válido.',
            'id_return_type.required' => 'Debe seleccionar un tipo de devolución.',
            'id_return_type.exists' => 'El tipo de devolución seleccionado no es válido.',
            'id_return_reason.required' => 'Debe seleccionar un motivo de devolución.',
            'id_return_reason.exists' => 'El motivo de devolución seleccionado no es válido.',
            'logistics_mode.required' => 'Debe seleccionar un modo de logística.',
            'logistics_mode.in' => 'El modo de logística seleccionado no es válido.',
            'description.required' => 'La descripción es obligatoria.',
            'description.min' => 'La descripción debe tener al menos :min caracteres.',
            'description.max' => 'La descripción no puede exceder :max caracteres.',
            'product_quantity.required' => 'La cantidad de productos es obligatoria.',
            'product_quantity.min' => 'La cantidad debe ser al menos 1.',
            'product_quantity.max' => 'La cantidad no puede exceder 100 unidades.',
            'return_address.required_if' => 'La dirección de devolución es obligatoria para recogida a domicilio.',
            'iban.required_if' => 'El IBAN es obligatorio para solicitudes de reembolso.',
            'iban.regex' => 'El formato del IBAN no es válido.',
            'pickup_date.required_if' => 'La fecha de recogida es obligatoria para recogida a domicilio.',
            'pickup_date.after' => 'La fecha de recogida debe ser posterior a hoy.',
            'pickup_date.before' => 'La fecha de recogida no puede ser más de 30 días en el futuro.',
            'terms_accepted.required' => 'Debe aceptar los términos y condiciones.',
            'terms_accepted.accepted' => 'Debe aceptar los términos y condiciones.'
        ];
    }

    protected function prepareForValidation()
    {
        // Limpiar y normalizar datos
        if ($this->has('customer_name')) {
            $this->merge([
                'customer_name' => trim($this->customer_name)
            ]);
        }

        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email))
            ]);
        }

        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/[^\+0-9]/', '', $this->phone)
            ]);
        }

        if ($this->has('iban')) {
            $this->merge([
                'iban' => strtoupper(preg_replace('/\s+/', '', $this->iban))
            ]);
        }

        if ($this->has('description')) {
            $this->merge([
                'description' => trim($this->description)
            ]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validaciones adicionales después de las reglas básicas
            $this->validateReturnPolicy($validator);
            $this->validateOrderStatus($validator);
            $this->validateReturnPeriod($validator);
        });
    }

    private function validateReturnPolicy($validator)
    {
        // Aquí podrías agregar validaciones basadas en políticas de devolución
        // Por ejemplo, verificar si el producto es elegible para devolución
    }

    private function validateOrderStatus($validator)
    {
        // Verificar que el pedido esté en un estado que permita devoluciones
        $allowedStatuses = explode(',', config('returns.return_order_statuses', '5,4'));

        // Esta validación requeriría acceso a la tabla de pedidos
        // Se implementaría según la estructura de tu sistema de pedidos
    }

    private function validateReturnPeriod($validator)
    {
        // Verificar que estemos dentro del período de devolución
        $returnDaysLimit = config('returns.return_days_limit', 30);

        // Esta validación también requeriría acceso a la fecha del pedido original
        // Se implementaría según tu sistema de pedidos
    }
}
