<?php

namespace Modules\Supplier\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class RegenerateChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.view.products');
    }

    public function rules(): array
    {
        return [
            'chat_uid' => ['required', 'string', 'size:26'],
        ];
    }

    public function messages(): array
    {
        return [
            'chat_uid.required' => 'El chat es obligatorio.',
            'chat_uid.size' => 'El identificador del chat no es valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'chat_uid' => 'chat',
        ];
    }
}
