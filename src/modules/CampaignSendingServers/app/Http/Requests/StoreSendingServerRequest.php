<?php

namespace Modules\CampaignSendingServers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CampaignSendingServers\Models\SendingServer;

class StoreSendingServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('campaign_sending_servers.manage.all') ?? false;
    }

    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(config('campaign_sending_servers.providers', [])))],
            'status' => ['nullable', 'in:active,inactive'],
            'default_from_email' => ['nullable', 'email'],
            'quota_value' => ['nullable', 'integer', 'min:0'],
            'quota_base' => ['nullable', 'integer', 'min:1'],
            'quota_unit' => ['nullable', 'in:second,minute,hour,day'],
        ];

        $rules += match ($type) {
            SendingServer::TYPE_SMTP, SendingServer::TYPE_AMAZON_SMTP => [
                'host' => ['required', 'string'],
                'smtp_port' => ['required', 'integer', 'between:1,65535'],
                'smtp_protocol' => ['nullable', 'in:tls,ssl,none'],
                'smtp_username' => ['required', 'string'],
                'smtp_password' => ['required', 'string'],
                'aws_region' => $type === SendingServer::TYPE_AMAZON_SMTP ? ['nullable', 'string'] : ['prohibited'],
            ],
            SendingServer::TYPE_SENDMAIL => [
                'sendmail_path' => ['required', 'string'],
            ],
            default => [],
        };

        return $rules;
    }
}
