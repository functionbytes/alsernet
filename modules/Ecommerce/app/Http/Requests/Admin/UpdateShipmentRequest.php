<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_id' => ['nullable', 'string', 'max:255'],
            'tracking_link' => ['nullable', 'url', 'max:500'],
            'shipping_company_name' => ['nullable', 'string', 'max:255'],
            'estimate_date_shipped' => ['nullable', 'date'],
            'date_shipped' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'tracking_link.url' => 'El enlace de seguimiento debe ser una URL valida.',
            'tracking_link.max' => 'El enlace de seguimiento no puede superar los 500 caracteres.',
            'tracking_id.max' => 'El ID de rastreo no puede superar los 255 caracteres.',
            'shipping_company_name.max' => 'El nombre de la empresa no puede superar los 255 caracteres.',
            'estimate_date_shipped.date' => 'La fecha estimada debe ser una fecha valida.',
            'date_shipped.date' => 'La fecha de envio debe ser una fecha valida.',
            'note.max' => 'La nota no puede superar los 1000 caracteres.',
            'weight.numeric' => 'El peso debe ser un valor numerico.',
            'weight.min' => 'El peso no puede ser negativo.',
            'price.numeric' => 'El precio debe ser un valor numerico.',
            'price.min' => 'El precio no puede ser negativo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tracking_id' => 'ID de rastreo',
            'tracking_link' => 'enlace de seguimiento',
            'shipping_company_name' => 'empresa de envio',
            'estimate_date_shipped' => 'fecha estimada de envio',
            'date_shipped' => 'fecha de envio',
            'note' => 'nota',
            'weight' => 'peso',
            'price' => 'precio',
        ];
    }
}
