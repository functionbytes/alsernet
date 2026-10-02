<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MergeConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('conversation'));
    }

    public function rules(): array
    {
        $source = $this->route('conversation')?->getKey();

        return [
            'target_id' => ['required', 'integer', 'not_in:'.$source],
        ];
    }

    public function messages(): array
    {
        return [
            'target_id.not_in' => __('helpdesk::helpdesk.messages.merge_self'),
        ];
    }

    /**
     * Mismo formato que el resto de errores de merge() ({success: false,
     * message}), más `errors` para quien muestre el error por campo.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
