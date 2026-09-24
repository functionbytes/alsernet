<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class RemoveCartVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.carts.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Indica el código del cupón.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
        ];
    }
}
