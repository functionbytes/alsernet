<?php

namespace Modules\Chat\Services;

use Illuminate\Support\Collection;

class ChannelCredentialsValidator
{
    /**
     * Check if credentials are configured for a specific channel.
     */
    public static function isConfigured(string $channel): bool
    {
        return match ($channel) {
            'facebook' => self::hasFacebookCredentials(),
            'instagram' => self::hasInstagramCredentials(),
            'whatsapp' => self::hasWhatsappCredentials(),
            'email' => self::hasEmailCredentials(),
            'telegram' => self::hasTelegramCredentials(),
            'twilio' => self::hasTwilioCredentials(),
            default => false,
        };
    }

    /**
     * Get all channel configurations with their status.
     */
    public static function getChannelStatus(): Collection
    {
        return collect([
            'facebook' => [
                'name' => 'Facebook Pages',
                'icon' => 'bi-facebook',
                'configured' => self::hasFacebookCredentials(),
                'required' => ['FACEBOOK_APP_ID', 'FACEBOOK_APP_SECRET'],
                'docs' => 'https://developers.facebook.com/docs/messenger-platform/quickstart',
            ],
            'instagram' => [
                'name' => 'Instagram DM',
                'icon' => 'bi-instagram',
                'configured' => self::hasInstagramCredentials(),
                'required' => ['INSTAGRAM_APP_ID', 'INSTAGRAM_APP_SECRET'],
                'docs' => 'https://developers.facebook.com/docs/instagram-api/getting-started',
            ],
            'whatsapp' => [
                'name' => 'WhatsApp Business',
                'icon' => 'bi-whatsapp',
                'configured' => self::hasWhatsappCredentials(),
                'required' => ['WHATSAPP_VERIFY_TOKEN', 'WHATSAPP_APP_SECRET'],
                'docs' => 'https://developers.facebook.com/docs/whatsapp/cloud-api/get-started',
            ],
            'email' => [
                'name' => 'Email',
                'icon' => 'bi-envelope',
                'configured' => self::hasEmailCredentials(),
                'required' => ['GMAIL_CLIENT_ID', 'OUTLOOK_CLIENT_ID'],
                'optional' => true,
                'docs' => 'https://support.google.com/accounts/answer/185833',
            ],
            'telegram' => [
                'name' => 'Telegram Bot',
                'icon' => 'bi-telegram',
                'configured' => self::hasTelegramCredentials(),
                'required' => ['TELEGRAM_BOT_TOKEN'],
                'optional' => true,
                'docs' => 'https://core.telegram.org/bots#botfather',
            ],
            'twilio' => [
                'name' => 'Twilio SMS/WhatsApp',
                'icon' => 'bi-telephone',
                'configured' => self::hasTwilioCredentials(),
                'required' => ['TWILIO_ACCOUNT_SID', 'TWILIO_AUTH_TOKEN'],
                'optional' => true,
                'docs' => 'https://www.twilio.com/docs/sms/quickstart',
            ],
        ]);
    }

    /**
     * Get missing credentials for a channel.
     */
    public static function getMissingCredentials(string $channel): array
    {
        $status = self::getChannelStatus();
        $channelStatus = $status->get($channel);

        if (! $channelStatus) {
            return [];
        }

        $missing = [];
        $required = $channelStatus['required'] ?? [];

        foreach ($required as $credential) {
            if (! config("channels.{$channel}.".self::envToConfig($credential))) {
                $missing[] = $credential;
            }
        }

        return $missing;
    }

    /**
     * Get a helpful message about missing credentials.
     */
    public static function getMissingCredentialsMessage(string $channel): ?string
    {
        $missing = self::getMissingCredentials($channel);

        if (empty($missing)) {
            return null;
        }

        $channelStatus = self::getChannelStatus()->get($channel);
        $missingList = implode(', ', $missing);
        $docs = $channelStatus['docs'] ?? '#';

        return "Missing credentials for {$channelStatus['name']}: {$missingList}. ".
               "<a href='{$docs}' target='_blank'>Learn more</a>";
    }

    // ============================================================================
    // Private Helper Methods
    // ============================================================================

    private static function hasFacebookCredentials(): bool
    {
        return ! empty(config('channels.facebook.app_id')) &&
               ! empty(config('channels.facebook.app_secret'));
    }

    private static function hasInstagramCredentials(): bool
    {
        $appId = config('channels.instagram.app_id');
        $appSecret = config('channels.instagram.app_secret');

        // Instagram can fallback to Facebook credentials
        if (empty($appId)) {
            $appId = config('channels.facebook.app_id');
        }
        if (empty($appSecret)) {
            $appSecret = config('channels.facebook.app_secret');
        }

        return ! empty($appId) && ! empty($appSecret);
    }

    private static function hasWhatsappCredentials(): bool
    {
        return ! empty(config('channels.whatsapp.verify_token')) &&
               ! empty(config('channels.whatsapp.app_secret'));
    }

    private static function hasEmailCredentials(): bool
    {
        return ! empty(env('GMAIL_CLIENT_ID')) ||
               ! empty(env('OUTLOOK_CLIENT_ID'));
    }

    private static function hasTelegramCredentials(): bool
    {
        return ! empty(env('TELEGRAM_BOT_TOKEN'));
    }

    private static function hasTwilioCredentials(): bool
    {
        return ! empty(env('TWILIO_ACCOUNT_SID')) &&
               ! empty(env('TWILIO_AUTH_TOKEN'));
    }

    private static function envToConfig(string $env): string
    {
        return strtolower(str_replace('_', '_', $env));
    }
}
