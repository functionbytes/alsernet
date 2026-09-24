<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Resolver devolución (pieza 35): nuevo estado real de la RMA, aviso opcional
 * por la plantilla del core y el mensaje que el agente dejará en el chat.
 */
class RefundsRmaStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'state_id' => ['required', 'integer', 'min:1'],
            'notify' => ['sometimes', 'boolean'],
            // Denegar exige motivo: se valida en after() porque depende del estado.
            'message' => ['nullable', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $denied = (int) config('helpdeskprestashop.ext.refunds.rma_states.denied', 4);
                if ((int) $this->input('state_id') === $denied && trim((string) $this->input('message', '')) === '') {
                    $validator->errors()->add('message', 'Para denegar la devolución escribe el motivo para el cliente.');
                }
            },
        ];
    }
}
