<?php

namespace Modules\Supplier\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendChatRequest extends FormRequest
{
    /**
     * Models that are 5-10x more expensive than the cheap tier. Only users
     * with the `suppliers.ai.premium` permission may select them.
     */
    private const ALL_MODELS = [
        'gpt-4o-mini', 'gpt-4o', 'claude-3-5-haiku', 'claude-3-5-sonnet',
        'gemini-2.5-flash', 'gemini-2.5-pro',
        // deprecated aliases — el servicio los redirige a gemini-2.5-flash
        'gemini-2.0-flash', 'gemini-2.0-flash-lite',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.view.products') ?? false;
    }

    public function rules(): array
    {
        return [
            'chat_uid'   => ['nullable', 'string'],
            'message'    => ['nullable', 'string'],
            'prompt_uid' => ['nullable', 'string'],
            'model'      => ['nullable', 'string', 'in:'.implode(',', self::ALL_MODELS)],
            'web_search' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'model.in' => 'El modelo seleccionado no esta permitido para tu nivel de acceso.',
        ];
    }

    public function attributes(): array
    {
        return [
            'chat_uid' => 'chat',
            'message' => 'mensaje',
            'prompt_uid' => 'prompt',
            'model' => 'modelo',
            'web_search' => 'busqueda web',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'web_search' => $this->boolean('web_search', false),
        ]);
    }
}
