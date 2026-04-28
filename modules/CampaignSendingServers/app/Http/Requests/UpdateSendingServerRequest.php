<?php

namespace Modules\CampaignSendingServers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CampaignSendingServers\Models\SendingServer;

class UpdateSendingServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaign_sending_servers.manage.all') ?? false;
    }

    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'in:'.implode(',', array_keys(config('campaign_sending_servers.providers', [])))],
            'status' => ['nullable', 'in:active,inactive'],
            'default_from_email' => ['nullable', 'email'],
            'quota_value' => ['nullable', 'integer', 'min:0'],
            'quota_base' => ['nullable', 'integer', 'min:1'],
            'quota_unit' => ['nullable', 'in:second,minute,hour,day'],
        ];

        // Las credenciales no son obligatorias en update (mantener si se omiten)
        $rules += match ($type) {
            SendingServer::TYPE_SMTP, SendingServer::TYPE_AMAZON_SMTP => [
                'host' => ['nullable', 'string'],
                'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
                'smtp_protocol' => ['nullable', 'in:tls,ssl,none'],
                'smtp_username' => ['nullable', 'string'],
                'smtp_password' => ['nullable', 'string'],
                'aws_region' => ['nullable', 'string'],
            ],
            SendingServer::TYPE_SENDMAIL => [
                'sendmail_path' => ['nullable', 'string'],
            ],
            default => [],
        };

        return $rules;
    }

    /**
     * No persistir credenciales que llegan vacías (mantener las existentes).
     */
    protected function passedValidation(): void
    {
        foreach (['smtp_password', 'aws_secret_access_key', 'api_key', 'api_secret_key'] as $key) {
            if (array_key_exists($key, $this->validator->validated()) && $this->validator->validated()[$key] === null) {
                $this->offsetUnset($key);
            }
        }
    }
}
