<?php

namespace Modules\HelpdeskChat\Models\Channels;

use App\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Modules\HelpdeskChat\Models\Accounts\Inbox;

class Web extends Model
{
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

    protected $casts = [
        'pre_chat_form_enabled' => 'boolean',
        'pre_chat_form_options' => 'array',
        'offline_message_enabled' => 'boolean',
        'show_availability_status' => 'boolean',
        'business_hours' => 'array',
        'widget_custom_styles' => 'array',
        'show_powered_by' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($webWidget) {
            // Generate tokens if not set
            if (empty($webWidget->website_token)) {
                $webWidget->website_token = Str::random(32);
            }
            if (empty($webWidget->hmac_token)) {
                $webWidget->hmac_token = Str::random(32);
            }
        });
    }

    /**
     * Get the account that owns the web widget.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the inbox for this channel (polymorphic).
     */
    public function inbox(): MorphOne
    {
        return $this->morphOne(Inbox::class, 'channel');
    }

    /**
     * Get the widget script URL for embedding.
     */
    public function getScriptUrl(): string
    {
        return url("/widget/script/{$this->website_token}");
    }

    /**
     * Get the widget configuration for JavaScript.
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
            'preChatFormEnabled' => $this->pre_chat_form_enabled,
            'preChatFormOptions' => $this->pre_chat_form_options ?? [],
            'offlineMessageEnabled' => $this->offline_message_enabled ?? true,
            'offlineMessage' => $this->offline_message,
            'showAvailabilityStatus' => $this->show_availability_status ?? true,
            'businessHours' => $this->business_hours ?? [],
            'replyTimeMessage' => $this->reply_time_message,
            'showPoweredBy' => $this->show_powered_by ?? true,
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
