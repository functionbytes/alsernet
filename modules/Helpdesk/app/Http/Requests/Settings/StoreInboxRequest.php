<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Support\OutboundUrlGuard;

class StoreInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'channel_type' => ['required', Rule::in(Inbox::availableChannelTypes())],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{3,8}$/'],
            'icon' => ['nullable', 'string', 'max:64'],
            'default_assignee_id' => ['nullable', 'integer', 'exists:'.config('database.default').'.users,id'],
            'default_group_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_groups,id'],
            'greeting_enabled' => ['boolean'],
            'greeting_message' => ['nullable', 'string', 'max:5000'],
            'working_hours_enabled' => ['boolean'],
            'working_hours' => ['nullable', 'array'],
            'out_of_office_message' => ['nullable', 'string', 'max:5000'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * 29-sep-2026 (SSRF): la URL del feed de catálogo del widget
     * (widget[product_feed_url]) la descarga el servidor (FeedCatalogDriver):
     * solo https y con host público. Se valida aparte (no en rules()) para no
     * meter `widget` en validated(), que se usa para crear el Inbox.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $feedUrl = $this->input('widget.product_feed_url');

            if ($feedUrl === null || $feedUrl === '') {
                return;
            }

            $ok = is_string($feedUrl)
                && strlen($feedUrl) <= 2048
                && strtolower((string) parse_url($feedUrl, PHP_URL_SCHEME)) === 'https'
                && filter_var($feedUrl, FILTER_VALIDATE_URL) !== false
                && parse_url($feedUrl, PHP_URL_USER) === null
                && OutboundUrlGuard::isSafe($feedUrl);

            if (! $ok) {
                $validator->errors()->add(
                    'widget.product_feed_url',
                    'La URL del product feed debe ser https y apuntar a un servidor público.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del inbox es obligatorio.',
            'channel_type.required' => 'El tipo de canal es obligatorio.',
            'channel_type.in' => 'El canal seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'channel_type' => 'canal',
            'default_assignee_id' => 'agente por defecto',
            'default_group_id' => 'equipo por defecto',
        ];
    }
}
