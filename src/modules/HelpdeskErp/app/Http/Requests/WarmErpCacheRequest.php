<?php

namespace Modules\HelpdeskErp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarmErpCacheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskerp.refresh') ?? false;
    }

    public function rules(): array
    {
        return [
            'emails' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'emails.array' => 'Los correos deben enviarse como una lista.',
        ];
    }

    public function attributes(): array
    {
        return [
            'emails' => 'correos electrónicos',
        ];
    }
}
