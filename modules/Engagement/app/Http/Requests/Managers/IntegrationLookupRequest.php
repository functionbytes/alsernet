<?php

namespace Modules\Engagement\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IntegrationLookupRequest extends FormRequest
{
    public const VALID_ACTIONS = [
        'profile', 'orders', 'orderDetail', 'addresses', 'returns',
        'vouchers', 'cart', 'messages', 'deliveryNotes', 'invoices',
        'payments', 'debts', 'balance', 'bonuses', 'loyaltyPoints',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'inbox_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'actions' => ['required', 'array', 'min:1', 'max:15'],
            'actions.*' => ['required', 'string', 'in:'.implode(',', self::VALID_ACTIONS)],
            'lookup' => ['required', 'array'],
            'lookup.email' => ['nullable', 'email'],
            'lookup.external_id' => ['nullable', 'string'],
            'lookup.order_id' => ['nullable', 'integer'],
            'force' => ['nullable', 'boolean'],
            'platform' => ['nullable', 'string', 'in:prestashop,erp,shopify,woocommerce'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $lookup = $this->input('lookup', []);
            if (empty($lookup['email']) && empty($lookup['external_id']) && empty($lookup['order_id'])) {
                $v->errors()->add('lookup', 'Debes proporcionar email, external_id u order_id.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'inbox_id.required' => 'El inbox es obligatorio.',
            'inbox_id.exists' => 'El inbox indicado no existe.',
            'actions.required' => 'Debes indicar al menos una acción.',
            'actions.*.in' => 'Acción no soportada.',
            'lookup.email.email' => 'El email no es válido.',
            'lookup' => 'Debes proporcionar email, external_id u order_id.',
        ];
    }

    public function attributes(): array
    {
        return [
            'inbox_id' => 'inbox',
            'actions' => 'acciones',
            'lookup.email' => 'email',
            'lookup.external_id' => 'external_id',
        ];
    }
}
