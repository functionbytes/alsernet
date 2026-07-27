<?php

namespace Modules\Modules\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuFeaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('modules.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'array'],
            'enabled.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'enabled.array' => 'El listado de elementos activados es inválido.',
        ];
    }
}
