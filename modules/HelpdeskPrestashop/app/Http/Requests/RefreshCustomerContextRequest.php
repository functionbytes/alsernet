<?php

namespace Modules\HelpdeskPrestashop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefreshCustomerContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.refresh') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
        ];
    }

    /**
     * Get the validated email from the route parameter.
     */
    public function email(): string
    {
        return $this->route('email');
    }
}
