<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialModuleSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesksocial.rules.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'auto_reply_enabled' => ['boolean'],
            'intent_classification_enabled' => ['boolean'],
            'intent_provider' => ['nullable', 'string', 'in:rules,openai,hybrid'],
            'comments_enabled' => ['boolean'],
            'meta_enabled' => ['boolean'],
            'whatsapp_enabled' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'intent_provider.in' => 'El proveedor de intención seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'auto_reply_enabled' => 'respuesta automática',
            'intent_classification_enabled' => 'clasificación de intenciones',
            'intent_provider' => 'proveedor de intención',
            'comments_enabled' => 'comentarios',
            'meta_enabled' => 'Meta',
            'whatsapp_enabled' => 'WhatsApp',
        ];
    }
}
