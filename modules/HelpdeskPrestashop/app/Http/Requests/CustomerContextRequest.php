<?php

namespace Modules\HelpdeskPrestashop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.view') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
        ];
    }
}
