<?php

namespace Modules\Helpdesk\Http\Requests\Webhooks;

class FacebookWebhookRequest extends MetaWebhookRequest
{
    protected function appSecretConfigKey(): string
    {
        return 'helpdesk.integrations.facebook.app_secret';
    }

    protected function channelLabel(): string
    {
        return 'Facebook';
    }
}
