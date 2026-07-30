<?php

namespace Modules\Helpdesk\Http\Requests\Webhooks;

class WhatsAppWebhookRequest extends MetaWebhookRequest
{
    protected function appSecretConfigKey(): string
    {
        return 'helpdesk.integrations.whatsapp.app_secret';
    }

    protected function channelLabel(): string
    {
        return 'WhatsApp';
    }
}
