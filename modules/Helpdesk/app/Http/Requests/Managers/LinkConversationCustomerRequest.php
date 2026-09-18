<?php

namespace Modules\Helpdesk\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkConversationCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('conversation'));
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('helpdesk.helpdesk_customers', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'El cliente es obligatorio.',
            'customer_id.integer' => 'El cliente debe ser un identificador válido.',
            'customer_id.exists' => 'El cliente indicado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
        ];
    }
}
