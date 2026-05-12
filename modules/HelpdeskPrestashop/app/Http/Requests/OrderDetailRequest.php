<?php

namespace Modules\HelpdeskPrestashop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.orders.detail.view') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
