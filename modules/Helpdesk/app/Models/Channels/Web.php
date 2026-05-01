<?php

namespace Modules\Helpdesk\Models\Channels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Inbox;

class Web extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_channel_webs';

    protected $fillable = [
        'account_id',
        'website_url',
        'website_token',
        'hmac_token',
        'widget_color',
        'widget_position',
        'widget_bubble_launcher_title',
        'widget_launcher_icon',
        'widget_bubble_color',
        'widget_custom_styles',
        'welcome_title',
        'welcome_tagline',
        'pre_chat_form_enabled',
        'pre_chat_form_options',
        'offline_message_enabled',
        'offline_message',
        'show_availability_status',
        'business_hours',
        'reply_time_message',
        'show_powered_by',
    ];

    protected function casts(): array
    {
        return [
            'pre_chat_form_enabled' => 'boolean',
            'pre_chat_form_options' => 'array',
            'offline_message_enabled' => 'boolean',
            'show_availability_status' => 'boolean',
            'business_hours' => 'array',
            'widget_custom_styles' => 'array',
            'show_powered_by' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $web) {
            if (empty($web->website_token)) {
                $web->website_token = Str::random(32);
            }

            if (empty($web->hmac_token)) {
                $web->hmac_token = Str::random(32);
            }
        });
    }

    /**
     * Get the inbox for this channel (polymorphic).
     */
    public function inbox(): MorphOne
    {
        return $this->morphOne(Inbox::class, 'channel');
    }

    /**
     * Get the widget configuration for JavaScript embedding.
     */
    public function getWidgetConfig(): array
    {
        return [
            'websiteToken' => $this->website_token,
            'baseUrl' => url('/'),
            'widgetColor' => $this->widget_color,
            'widgetPosition' => $this->widget_position ?? 'right',
            'widgetBubbleLauncherTitle' => $this->widget_bubble_launcher_title,
            'widgetLauncherIcon' => $this->widget_launcher_icon,
            'widgetBubbleColor' => $this->widget_bubble_color ?? $this->widget_color,
            'widgetCustomStyles' => $this->widget_custom_styles ?? [],
            'welcomeTitle' => $this->welcome_title,
            'welcomeTagline' => $this->welcome_tagline,
            'preChatFormEnabled' => (bool) $this->pre_chat_form_enabled,
            'preChatFormOptions' => $this->pre_chat_form_options ?? [],
            'offlineMessageEnabled' => (bool) ($this->offline_message_enabled ?? true),
            'offlineMessage' => $this->offline_message,
            'showAvailabilityStatus' => (bool) ($this->show_availability_status ?? true),
            'businessHours' => $this->business_hours ?? [],
            'replyTime' => $this->reply_time_message,
            'showPoweredBy' => (bool) ($this->show_powered_by ?? true),
        ];
    }

    /**
     * Regenerate HMAC token.
     */
    public function regenerateHmacToken(): void
    {
        $this->update(['hmac_token' => Str::random(32)]);
    }
}
