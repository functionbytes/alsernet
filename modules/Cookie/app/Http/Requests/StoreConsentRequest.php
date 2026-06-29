<?php

namespace Modules\Cookie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:accept_all,reject_all,custom'],
            'accepted_categories' => ['nullable', 'array'],
            'accepted_categories.*' => ['string', 'in:essential,analytics,marketing,preferences'],
            'is_update' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La acción de consentimiento es obligatoria.',
            'action.in' => 'La acción debe ser aceptar todo, rechazar todo o personalizar.',
            'accepted_categories.array' => 'Las categorías deben enviarse como lista.',
            'accepted_categories.*.in' => 'Una o más categorías seleccionadas no son válidas.',
        ];
    }
}
