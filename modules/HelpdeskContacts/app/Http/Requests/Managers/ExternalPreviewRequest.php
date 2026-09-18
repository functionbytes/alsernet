<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ExternalPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.view');
    }

    /**
     * Antes exigía email siempre — un cliente ERP encontrado por teléfono
     * (bastante común, el manager no siempre tiene el email cargado) nunca
     * podía pedir la ficha completa: la validación rechazaba la petición
     * antes de llegar al controlador, aunque ErpContextService::getCustomerContext()
     * ya soporta buscar por teléfono como fallback.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'in:erp,prestashop'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'platform.in' => 'La ficha completa solo está disponible para ERP y PrestaShop.',
            'email.required_without' => 'Se necesita email o teléfono para pedir la ficha completa.',
            'email.email' => 'El email no es válido.',
            'phone.required_without' => 'Se necesita email o teléfono para pedir la ficha completa.',
        ];
    }
}
