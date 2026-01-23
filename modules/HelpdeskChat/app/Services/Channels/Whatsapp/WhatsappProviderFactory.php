<?php

namespace Modules\HelpdeskChat\Services\Channels\Whatsapp;

use App\Models\Channels\Whatsapp;
use Modules\HelpdeskChat\Contracts\WhatsappProviderInterface;
use Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers\CloudApiProvider;
use Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers\Dialog360Provider;
use Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers\EvolutionApiProvider;

class WhatsappProviderFactory
{
    /**
     * Create the appropriate provider instance based on the Whatsapp model.
     */
    public static function make(Whatsapp $whatsapp): WhatsappProviderInterface
    {
        return match ($whatsapp->provider) {
            'evolution_api' => new EvolutionApiProvider($whatsapp),
            '360dialog' => new Dialog360Provider($whatsapp),
            'whatsapp_cloud' => new CloudApiProvider($whatsapp),
            default => throw new \InvalidArgumentException("Unsupported provider: {$whatsapp->provider}"),
        };
    }

    /**
     * Create provider from provider name.
     */
    public static function makeFromProvider(string $provider, Whatsapp $whatsapp): WhatsappProviderInterface
    {
        return match ($provider) {
            'evolution_api' => new EvolutionApiProvider($whatsapp),
            '360dialog' => new Dialog360Provider($whatsapp),
            'whatsapp_cloud' => new CloudApiProvider($whatsapp),
            default => throw new \InvalidArgumentException("Unsupported provider: {$provider}"),
        };
    }
}
