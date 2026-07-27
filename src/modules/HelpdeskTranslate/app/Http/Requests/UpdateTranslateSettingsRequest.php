<?php

namespace Modules\HelpdeskTranslate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Support\OutboundUrlGuard;

class UpdateTranslateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk-translate.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'in:deepl,libretranslate'],
            'default_target' => ['required', 'string', Rule::in(config('helpdesktranslate.supported_languages', []))],
            'auto_translate_incoming' => ['nullable', 'boolean'],
            'auto_translate_outgoing' => ['nullable', 'boolean'],
            'deepl_key' => ['nullable', 'string', 'max:255'],
            'deepl_url' => ['nullable', 'string', Rule::in(config('helpdesktranslate.deepl_allowed_urls', []))],
            // A diferencia de deepl_url (allowlist cerrada), este endpoint es
            // libre — sin el guard SSRF, un titular de este permiso podia
            // apuntarlo a una IP interna y exfiltrar los mensajes de clientes
            // que se envian a "traducir" en cada mensaje entrante/saliente.
            'libretranslate_endpoint' => [
                'nullable', 'string', 'url:http,https', 'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value !== null && $value !== '' && ! OutboundUrlGuard::isSafe($value)) {
                        $fail(__('helpdesktranslate::messages.validation.libretranslate_endpoint_unsafe'));
                    }
                },
            ],
            'libretranslate_api_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider.required' => __('helpdesktranslate::messages.validation.provider_required'),
            'provider.in' => __('helpdesktranslate::messages.validation.provider_in'),
            'default_target.required' => __('helpdesktranslate::messages.validation.default_target_required'),
            'default_target.in' => __('helpdesktranslate::messages.validation.default_target_in'),
            'deepl_key.max' => __('helpdesktranslate::messages.validation.deepl_key_max'),
            'deepl_url.in' => __('helpdesktranslate::messages.validation.deepl_url_in'),
            'libretranslate_endpoint.url' => __('helpdesktranslate::messages.validation.libretranslate_endpoint_url'),
        ];
    }

    public function attributes(): array
    {
        return [
            'provider' => __('helpdesktranslate::messages.attributes.provider'),
            'default_target' => __('helpdesktranslate::messages.attributes.default_target'),
            'auto_translate_incoming' => __('helpdesktranslate::messages.attributes.auto_translate_incoming'),
            'auto_translate_outgoing' => __('helpdesktranslate::messages.attributes.auto_translate_outgoing'),
            'deepl_key' => __('helpdesktranslate::messages.attributes.deepl_key'),
            'deepl_url' => __('helpdesktranslate::messages.attributes.deepl_url'),
            'libretranslate_endpoint' => __('helpdesktranslate::messages.attributes.libretranslate_endpoint'),
        ];
    }
}
