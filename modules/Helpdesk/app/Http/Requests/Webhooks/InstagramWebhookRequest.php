<?php

namespace Modules\Helpdesk\Http\Requests\Webhooks;

class InstagramWebhookRequest extends MetaWebhookRequest
{
    // Instagram comparte el app secret de la app de Facebook (misma plataforma Meta).
    protected function appSecretConfigKey(): string
    {
        return 'helpdesk.integrations.facebook.app_secret';
    }

    protected function channelLabel(): string
    {
        return 'Instagram';
    }
}
