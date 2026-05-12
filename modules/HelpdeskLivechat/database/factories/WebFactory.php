<?php

namespace Modules\HelpdeskLivechat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class WebFactory extends Factory
{
    protected $model = Web::class;

    public function definition(): array
    {
        return [
            'account_id' => 1,
            'website_token' => Str::random(32),
            'hmac_token' => Str::random(64),
            'website_url' => 'https://example.com',
            'widget_color' => '#b10100',
            'widget_position' => 'bottom-right',
            'welcome_title' => 'Hola!',
            'welcome_tagline' => '¿En qué te ayudamos?',
            'pre_chat_form_enabled' => false,
            'offline_message_enabled' => true,
            'business_hours' => ['enabled' => false],
            'trusted_domains' => null,

            // Home screen feature flags
            'show_avatars' => true,
            'show_help_center' => true,
            'hide_suggested_articles' => false,
            'show_tickets_section' => true,
            'enable_send_message' => true,
            'enable_create_ticket' => true,
            'enable_search_help' => true,

            // Chat screen feature flags
            'show_timestamps' => true,
            'typing_indicator' => true,
            'sound_notifications' => true,
            'enable_email_transcripts' => false,
        ];
    }

    /**
     * Set trusted domains to restrict widget origins.
     *
     * @param  array<string>  $domains
     */
    public function withTrustedDomains(array $domains): static
    {
        return $this->state([
            'trusted_domains' => implode("\n", $domains),
        ]);
    }

    /**
     * Enable business hours with a Monday–Friday 09:00–18:00 schedule.
     */
    public function withBusinessHours(): static
    {
        return $this->state([
            'business_hours' => [
                'enabled' => true,
                'timezone' => 'UTC',
                'schedule' => [
                    'monday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'],
                    'tuesday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'],
                    'wednesday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'],
                    'thursday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'],
                    'friday' => ['enabled' => true, 'start' => '09:00', 'end' => '18:00'],
                    'saturday' => ['enabled' => false, 'start' => '00:00', 'end' => '00:00'],
                    'sunday' => ['enabled' => false, 'start' => '00:00', 'end' => '00:00'],
                ],
            ],
        ]);
    }

    /**
     * Enable the pre-chat form.
     */
    public function withPreChatForm(): static
    {
        return $this->state([
            'pre_chat_form_enabled' => true,
        ]);
    }

    /**
     * Enable email transcript delivery for this widget.
     */
    public function withEmailTranscripts(): static
    {
        return $this->state([
            'enable_email_transcripts' => true,
        ]);
    }

    /**
     * Enable HMAC identity verification.
     */
    public function withIdentityVerification(): static
    {
        return $this->state([
            'enforce_identity_verification' => true,
        ]);
    }

    /**
     * Enable the live view (rrweb) feature.
     */
    public function withLiveView(): static
    {
        return $this->state([
            'enable_live_view' => true,
        ]);
    }
}
