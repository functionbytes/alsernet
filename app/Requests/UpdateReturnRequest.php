<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Return\ReturnRequest;

class UpdateReturnRequest extends FormRequest
{
    public function authorize()
    {
        // Verificar que el usuario tenga permisos para actualizar esta devolución
        $return = $this->route('return') ?? ReturnRequest::find($this->route('id'));

        if (!$return) {
            return false;
        }

        // Verificar que la devolución se pueda modificar
        if (!$return->canBeModified()) {
            return false;
        }

        // Si es un admin, puede modificar cualquier devolución
        if (auth()->user() && auth()->user()->hasRole('admin')) {
            return true;
        }

        // Si es el cliente propietario
        if (auth()->user()) {
            return $return->id_customer == auth()->user()->id_customer ||
                $return->email == auth()->user()->email;
        }

        return false;
    }

    public function rules()
    {
        $return = $this->route('return') ?? ReturnRequest::find($this->route('id'));

        return [
            'customer_name' => [
                'sometimes',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'
            ],
            'email' => [
                'sometimes',
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
                'sometimes',
                'integer',
                'exists:return_types,id_return_type'
            ],
            'id_return_reason' => [
                'sometimes',
                'integer',
                'exists:return_reasons,id_return_reason',
                function ($attribute, $value, $fail) {
                    $returnTypeId = $this->id_return_type ?? ($this->route('return')?->id_return_type);

                    if ($returnTypeId) {
                        $reason = \App\Models\Return\ReturnReason::find($value);

                        if ($reason) {
                            $typeMapping = [
                                1 => 'refund',
                                2 => 'replacement',
                                3 => 'repair'
                            ];

                            $expectedType = $typeMapping[$returnTypeId] ?? 'all';

                            if (!$reason->isValidForReturnType($expectedType)) {
                                $fail('El motivo seleccionado no es válido para este tipo de devolución.');
                            }
                        }
                    }
                }
            ],
            'logistics_mode' => [
                'sometimes',
                Rule::in(['customer_transport', 'home_pickup', 'store_delivery', 'inpost'])
            ],
            'description' => [
                'sometimes',
                'string',
                'min:' . config('returns.validation.min_description_length', 10),
                'max:' . config('returns.validation.max_description_length', 1000)
            ],
            'product_quantity' => [
                'sometimes',
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
                'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4}[0-9]{7}([A-Z0-9]?){0,16}$/'
            ],
            'pickup_date' => [
                'nullable',
                'date',
                'after:today',
                'before:' . now()->addDays(30)->format('Y-m-d'),
                'required_if:logistics_mode,home_pickup'
            ],
            // Campos solo para administradores
            'id_return_status' => [
                'sometimes',
                'integer',
                'exists:return_status,id_return_status',
                function ($attribute, $value, $fail) {
                    if (!auth()->user() || !auth()->user()->hasRole('admin')) {
                        $fail('Solo los administradores pueden cambiar el estado.');
                    }
                }
            ],
            'product_quantity_reinjected' => [
                'sometimes',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) {
                    if (!auth()->user() || !auth()->user()->hasRole('admin')) {
                        $fail('Solo los administradores pueden modificar la cantidad reinyectada.');
                    }

                    $productQuantity = $this->product_quantity ?? ($this->route('return')?->product_quantity ?? 0);
                    if ($value > $productQuantity) {
                        $fail('La cantidad reinyectada no puede ser mayor que la cantidad del producto.');
                    }
                }
            ],
            'is_refunded' => [
                'sometimes',
                'boolean',
                function ($attribute, $value, $fail) {
                    if (!auth()->user() || !auth()->user()->hasRole('admin')) {
                        $fail('Solo los administradores pueden marcar como reembolsado.');
                    }
                }
            ],
            'admin_notes' => [
                'nullable',
                'string',
                'max:1000',
                function ($attribute, $value, $fail) {
                    if (!auth()->user() || !auth()->user()->hasRole('admin')) {
                        $fail('Solo los administradores pueden agregar notas administrativas.');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'customer_name.regex' => 'El nombre solo puede contener letras y espacios.',
            'email.email' => 'El email debe tener un formato válido.',
            'phone.regex' => 'El teléfono debe tener un formato válido.',
            'id_return_type.exists' => 'El tipo de devolución seleccionado no es válido.',
            'id_return_reason.exists' => 'El motivo de devolución seleccionado no es válido.',
            'logistics_mode.in' => 'El modo de logística seleccionado no es válido.',
            'description.min' => 'La descripción debe tener al menos :min caracteres.',
            'description.max' => 'La descripción no puede exceder :max caracteres.',
            'product_quantity.min' => 'La cantidad debe ser al menos 1.',
            'product_quantity.max' => 'La cantidad no puede exceder 100 unidades.',
            'return_address.required_if' => 'La dirección de devolución es obligatoria para recogida a domicilio.',
            'iban.regex' => 'El formato del IBAN no es válido.',
            'pickup_date.after' => 'La fecha de recogida debe ser posterior a hoy.',
            'pickup_date.before' => 'La fecha de recogida no puede ser más de 30 días en el futuro.',
            'id_return_status.exists' => 'El estado seleccionado no es válido.',
            'product_quantity_reinjected.min' => 'La cantidad reinyectada no puede ser negativa.'
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

        if ($this->has('phone') && $this->phone) {
            $this->merge([
                'phone' => preg_replace('/[^\+0-9]/', '', $this->phone)
            ]);
        }

        if ($this->has('iban') && $this->iban) {
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
            $this->validateStatusTransition($validator);
            $this->validateModificationPermissions($validator);
        });
    }

    private function validateStatusTransition($validator)
    {
        if ($this->has('id_return_status')) {
            $return = $this->route('return') ?? ReturnRequest::find($this->route('id'));
            $newStatusId = $this->id_return_status;

            if ($return) {
                $returnService = app(\App\Services\ReturnService::class);

                if (!$returnService->isValidStatusTransition($return->id_return_status, $newStatusId)) {
                    $validator->errors()->add('id_return_status', 'La transición de estado no es válida.');
                }
            }
        }
    }

    private function validateModificationPermissions($validator)
    {
        $return = $this->route('return') ?? ReturnRequest::find($this->route('id'));

        if ($return && !$return->canBeModified()) {
            // Solo permitir campos específicos si la devolución no se puede modificar completamente
            $restrictedFields = [
                'id_return_type', 'id_return_reason', 'logistics_mode',
                'product_quantity', 'description'
            ];

            foreach ($restrictedFields as $field) {
                if ($this->has($field)) {
                    $validator->errors()->add($field, 'Este campo no se puede modificar en el estado actual de la devolución.');
                }
            }
        }
    }

    /**
     * Obtener solo los campos que el usuario actual puede modificar
     */
    public function getModifiableFields(): array
    {
        $return = $this->route('return') ?? ReturnRequest::find($this->route('id'));
        $isAdmin = auth()->user() && auth()->user()->hasRole('admin');

        $baseFields = [
            'customer_name', 'email', 'phone', 'return_address', 'iban', 'pickup_date'
        ];

        if ($return && $return->canBeModified()) {
            $baseFields = array_merge($baseFields, [
                'id_return_type', 'id_return_reason', 'logistics_mode',
                'description', 'product_quantity'
            ]);
        }

        if ($isAdmin) {
            $baseFields = array_merge($baseFields, [
                'id_return_status', 'product_quantity_reinjected',
                'is_refunded', 'admin_notes'
            ]);
        }

        return array_intersect_key($this->validated(), array_flip($baseFields));
    }
}
