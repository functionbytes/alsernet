<?php

namespace Modules\Supplier\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SaveChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.view.products');
    }

    public function rules(): array
    {
        return [
            'chat_uid' => ['required', 'string', 'size:26'],
            'content'  => ['required', 'string'],
            'name'     => ['nullable', 'string', 'max:500'],
            'brand'    => ['nullable', 'string', 'max:255'],
            'sources'  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'chat_uid.required' => 'El chat es obligatorio.',
            'chat_uid.size'     => 'El identificador del chat no es valido.',
            'content.required'  => 'El contenido es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'chat_uid' => 'chat',
            'content'  => 'contenido',
            'name'     => 'nombre',
            'brand'    => 'marca',
        ];
    }
}
