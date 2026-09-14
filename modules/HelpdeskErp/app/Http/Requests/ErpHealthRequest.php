<?php

namespace Modules\HelpdeskErp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ErpHealthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskerp.health.view') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
