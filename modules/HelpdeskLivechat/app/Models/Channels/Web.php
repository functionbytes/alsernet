<?php

namespace Modules\HelpdeskLivechat\Models\Channels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;

class Web extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected static function newFactory(): WebFactory
    {
        return WebFactory::new();
    }

    protected $table = 'helpdesk_channel_webs';

    protected $fillable = [
        // Identity / account
        'account_id',
        'website_url',
        'website_token',
        'hmac_token',

        // Legacy style (kept for backwards compat)
        'widget_color',
        'widget_position',
        'widget_bubble_launcher_title',
        'widget_launcher_icon',
        'widget_bubble_color',
        'widget_custom_styles',
        'welcome_title',
        'welcome_tagline',
        'show_availability_status',
        'show_powered_by',
        'reply_time_message',

        // Home screen flags
        'show_avatars',
        'show_help_center',
        'hide_suggested_articles',
        'show_tickets_section',
        'enable_send_message',
        'enable_file_upload',
        'enable_emoji',
        'allowed_file_types',
        'enable_create_ticket',
        'enable_search_help',

        // Chat screen text
        'welcome_message',
        'input_placeholder',
        'queue_message',

        // Launcher
        'side_spacing',
        'bottom_spacing',
        'hide_launcher',

        // Style / branding
        'secondary_color',
        'header_title',
        'logo_url',
        'team_avatars',

        // Additional feature flags
        'show_timestamps',
        'typing_indicator',
        'sound_notifications',
        'enable_email_transcripts',

        // Offline / availability
        'pre_chat_form_enabled',
        'pre_chat_form_options',
        'offline_message_enabled',
        'offline_message',
        'business_hours',

        // Automation / timeouts
        'enable_auto_transfer',
        'auto_transfer_minutes',
        'enable_auto_inactive',
        'auto_inactive_minutes',
        'enable_auto_close',
        'auto_close_minutes',

        // Security
        'trusted_domains',
        'enforce_identity_verification',

        // Forms
        'pre_chat_info',
        'post_chat_form_enabled',
        'post_chat_info',

        // Chat page
        'chat_page_title',
        'chat_page_subtitle',

        // CMS integration
        'cms_type',
        'platform_integration_id',

        // Live assistance
        'enable_live_view',
        'enable_screen_share',
    ];

    public const CMS_TYPES = [
        'custom' => 'Custom (HTML/JS)',
        'prestashop' => 'PrestaShop',
        'shopify' => 'Shopify',
        'woocommerce' => 'WooCommerce / WordPress',
        'magento' => 'Magento',
        'wordpress' => 'WordPress (sin WooCommerce)',
    ];

    protected function casts(): array
    {
        return [
            // Legacy
            'pre_chat_form_enabled' => 'boolean',
            'pre_chat_form_options' => 'array',
            'offline_message_enabled' => 'boolean',
            'show_availability_status' => 'boolean',
            'business_hours' => 'array',
            'widget_custom_styles' => 'array',
            'show_powered_by' => 'boolean',

            // Home screen
            'show_avatars' => 'boolean',
            'show_help_center' => 'boolean',
            'hide_suggested_articles' => 'boolean',
            'show_tickets_section' => 'boolean',
            'enable_send_message' => 'boolean',
            'enable_file_upload' => 'boolean',
            'enable_emoji' => 'boolean',
            'allowed_file_types' => 'array',
            'enable_create_ticket' => 'boolean',
            'enable_search_help' => 'boolean',

            // Launcher
            'side_spacing' => 'integer',
            'bottom_spacing' => 'integer',
            'hide_launcher' => 'boolean',

            // Branding
            'team_avatars' => 'array',

            // Feature flags
            'show_timestamps' => 'boolean',
            'typing_indicator' => 'boolean',
            'sound_notifications' => 'boolean',
            'enable_email_transcripts' => 'boolean',

            // Automation
            'enable_auto_transfer' => 'boolean',
            'auto_transfer_minutes' => 'integer',
            'enable_auto_inactive' => 'boolean',
            'auto_inactive_minutes' => 'integer',
            'enable_auto_close' => 'boolean',
            'auto_close_minutes' => 'integer',

            // Security
            'enforce_identity_verification' => 'boolean',

            // Forms
            'post_chat_form_enabled' => 'boolean',

            // CMS integration
            'platform_integration_id' => 'integer',

            // Live assistance
            'enable_live_view' => 'boolean',
            'enable_screen_share' => 'boolean',
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

        // Invalidate all related caches whenever the record is saved.
        static::saved(function (self $web) {
            Cache::forget("widget_config_{$web->id}");
            Cache::forget('helpdesklivechat:web:token:'.$web->website_token);
            Cache::forget('helpdesklivechat:trusted_domains:'.$web->website_token);
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
     *
     * Results are cached for 5 minutes and invalidated on save.
     *
     * Keys are grouped into two forms:
     *  - snake_case (canonical): consumed by widget-store.ts and new widget code.
     *  - camelCase (legacy): kept for backwards compatibility with the embedded widget
     *    JS that was shipped before the snake_case refactor. Do not remove until the
     *    widget frontend has been migrated to read only snake_case keys.
     *
     * @deprecated camelCase keys below — duplicates of their snake_case counterparts.
     *   Remove after migrating the widget to snake_case:
     *   - widgetColor          → primary_color
     *   - widgetPosition       → position
     *   - widgetBubbleLauncherTitle → widget_bubble_launcher_title
     *   - widgetLauncherIcon   → widget_launcher_icon
     *   - widgetBubbleColor    → widget_bubble_color
     *   - widgetCustomStyles   → widget_custom_styles
     *   - welcomeTitle         → welcome_title
     *   - welcomeTagline       → welcome_tagline
     *   - preChatFormEnabled   → pre_chat_form_enabled
     *   - preChatFormOptions   → pre_chat_form_options
     *   - offlineMessageEnabled → offline_message_enabled
     *   - offlineMessage       → offline_message
     *   - showAvailabilityStatus → show_availability_status
     *   - businessHours        → business_hours
     *   - replyTime            → reply_time
     *   - showPoweredBy        → show_powered_by
     *
     * @return array<string, mixed>
     */
    public function getWidgetConfig(): array
    {
        return Cache::remember("widget_config_{$this->id}", 300, function () {
            return [
                // Identity
                'websiteToken' => $this->website_token,
                'baseUrl' => url('/'),

                // Style — primary snake_case keys consumed by widget-store.ts
                'primary_color' => $this->widget_color ?? '#b10100',
                'secondary_color' => $this->secondary_color ?? '#ffffff',
                'header_title' => $this->header_title ?? 'Chat de Soporte',
                'logo_url' => $this->logo_url,
                'team_avatars' => $this->team_avatars ?? [],

                // Legacy branding (no prior snake_case equivalent — added here as canonical form)
                'widget_bubble_launcher_title' => $this->widget_bubble_launcher_title,
                'widget_launcher_icon' => $this->widget_launcher_icon,
                'widget_bubble_color' => $this->widget_bubble_color ?? $this->widget_color,
                'widget_custom_styles' => $this->widget_custom_styles ?? [],
                'welcome_title' => $this->welcome_title,
                'welcome_tagline' => $this->welcome_tagline,
                'show_availability_status' => (bool) ($this->show_availability_status ?? true),
                'business_hours' => $this->business_hours ?? [],
                'reply_time' => $this->reply_time_message,
                'show_powered_by' => (bool) ($this->show_powered_by ?? true),
                'offline_message_enabled' => (bool) ($this->offline_message_enabled ?? true),
                'pre_chat_form_options' => $this->pre_chat_form_options ?? [],

                // @deprecated camelCase aliases — kept for backwards compat; remove after widget migration
                'widgetColor' => $this->widget_color ?? '#b10100',
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

                // Launcher
                'position' => $this->widget_position ?? 'right',
                'side_spacing' => $this->side_spacing ?? 16,
                'bottom_spacing' => $this->bottom_spacing ?? 16,
                'hide_launcher' => (bool) ($this->hide_launcher ?? false),

                // Home screen feature flags
                'show_avatars' => (bool) ($this->show_avatars ?? true),
                'show_help_center' => (bool) ($this->show_help_center ?? true),
                'hide_suggested_articles' => (bool) ($this->hide_suggested_articles ?? false),
                'show_tickets_section' => (bool) ($this->show_tickets_section ?? true),
                'enable_send_message' => (bool) ($this->enable_send_message ?? true),
                'enable_create_ticket' => (bool) ($this->enable_create_ticket ?? true),
                'enable_search_help' => (bool) ($this->enable_search_help ?? true),

                // Chat screen text
                'welcome_message' => $this->welcome_message ?? 'Hola! ¿Cómo podemos ayudarte?',
                'input_placeholder' => $this->input_placeholder ?? 'Escribe tu mensaje...',
                'queue_message' => $this->queue_message,
                'offline_message' => $this->offline_message,

                // Additional feature flags
                'show_timestamps' => (bool) ($this->show_timestamps ?? true),
                'typing_indicator' => (bool) ($this->typing_indicator ?? true),
                'sound_notifications' => (bool) ($this->sound_notifications ?? true),
                'enable_email_transcripts' => (bool) ($this->enable_email_transcripts ?? false),
                'enable_file_upload' => (bool) ($this->enable_file_upload ?? true),
                'enable_emoji' => (bool) ($this->enable_emoji ?? true),
                'allowed_file_types' => $this->allowed_file_types ?? ['images', 'documents'],

                // Forms
                'pre_chat_form_enabled' => (bool) $this->pre_chat_form_enabled,
                'pre_chat_info' => $this->pre_chat_info,
                'post_chat_form_enabled' => (bool) ($this->post_chat_form_enabled ?? false),
                'post_chat_info' => $this->post_chat_info,

                // Chat page
                'chat_page_title' => $this->chat_page_title,
                'chat_page_subtitle' => $this->chat_page_subtitle,

                // Automation / timeouts
                'enable_auto_transfer' => (bool) ($this->enable_auto_transfer ?? false),
                'auto_transfer_minutes' => $this->auto_transfer_minutes ?? 5,
                'enable_auto_inactive' => (bool) ($this->enable_auto_inactive ?? false),
                'auto_inactive_minutes' => $this->auto_inactive_minutes ?? 10,
                'enable_auto_close' => (bool) ($this->enable_auto_close ?? false),
                'auto_close_minutes' => $this->auto_close_minutes ?? 15,

                // Security — trusted_domains is intentionally omitted (server-side only)
                'enforce_identity_verification' => (bool) ($this->enforce_identity_verification ?? false),

                // Availability
                'is_open' => $this->isWithinBusinessHours(),

                // Live assistance
                'enable_live_view' => (bool) ($this->enable_live_view ?? false),
                'enable_screen_share' => (bool) ($this->enable_screen_share ?? false),

                // Tracking — read from Setting so admins can tune them without redeploys
                'tracking' => [
                    'heartbeatIntervalMs' => (int) (Setting::get('livechat.tracking.heartbeat_interval_seconds') ?? 15) * 1000,
                    'sdkBatchIntervalMs' => (int) (Setting::get('livechat.tracking.sdk_batch_interval_ms') ?? 1500),
                    'sdkBatchSize' => (int) (Setting::get('livechat.tracking.sdk_batch_size') ?? 10),
                ],
            ];
        });
    }

    /**
     * Evaluate whether the current time falls within configured business hours.
     * Returns true when business hours are disabled (always available).
     */
    public function isWithinBusinessHours(): bool
    {
        $hours = $this->business_hours ?? [];

        if (empty($hours['enabled']) || $hours['enabled'] === false) {
            return true;
        }

        $tz = $hours['timezone'] ?? config('app.timezone', 'UTC');
        $now = now()->setTimezone($tz);
        $dayKey = strtolower($now->format('l')); // monday, tuesday, ...

        $today = $hours['schedule'][$dayKey] ?? null;

        if (! $today || ! ($today['enabled'] ?? false)) {
            return false;
        }

        $start = $today['start'] ?? '09:00';
        $end = $today['end'] ?? '18:00';
        $current = $now->format('H:i');

        return $current >= $start && $current <= $end;
    }

    /**
     * Regenerate HMAC token.
     */
    public function regenerateHmacToken(): void
    {
        $this->update(['hmac_token' => Str::random(32)]);
    }

    /**
     * Optional link to an Engagement PlatformIntegration record.
     * Returns a self-relation stub when Engagement module is disabled,
     * so callers can still reference ->platformIntegration without errors.
     */
    public function platformIntegration(): BelongsTo
    {
        $integrationModel = '\\Modules\\Engagement\\Models\\PlatformIntegration';

        if (! class_exists($integrationModel)) {
            return $this->belongsTo(self::class, 'platform_integration_id');
        }

        return $this->belongsTo($integrationModel, 'platform_integration_id');
    }

    /**
     * Build the install snippet HTML for the configured CMS platform.
     * The token is interpolated into a script block tailored to each platform.
     */
    public function getInstallSnippet(): string
    {
        $token = (string) ($this->website_token ?? '');
        $base = url('/');
        $cms = $this->cms_type ?? 'custom';

        $genericSnippet = <<<HTML
<!-- Helpdesk Chat Widget -->
<script>
  window.helpdeskSettings = { websiteToken: "{$token}", baseUrl: "{$base}" };
  (function (d, t) {
    var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
    g.src = "{$base}/build-helpdesklivechat/widget.js";
    g.defer = true; g.async = true;
    s.parentNode.insertBefore(g, s);
  })(document, "script");
</script>
HTML;

        return match ($cms) {
            'prestashop' => <<<HTML
<!-- PrestaShop: pega este código en hook displayBeforeBodyClosingTag.tpl o en footer.tpl -->
<!-- Próximamente módulo nativo descargable que rellena customer/cart/orders automáticamente -->
<script>
  window.helpdeskSettings = {
    websiteToken: "{$token}",
    baseUrl: "{$base}"
    {literal},
    customer: typeof prestashop !== 'undefined' && prestashop.customer ? {
      id: prestashop.customer.id,
      email: prestashop.customer.email,
      name: prestashop.customer.firstname + ' ' + prestashop.customer.lastname,
      platform: 'prestashop'
    } : null{/literal}
  };
  (function (d, t) {
    var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
    g.src = "{$base}/build-helpdesklivechat/widget.js";
    g.defer = true; g.async = true;
    s.parentNode.insertBefore(g, s);
  })(document, "script");
</script>
HTML,
            'shopify' => <<<HTML
<!-- Shopify: pega este código en theme.liquid antes de </body> -->
<!-- Próximamente App Embed Block oficial -->
<script>
  window.helpdeskSettings = {
    websiteToken: "{$token}",
    baseUrl: "{$base}",
    customer: {% if customer %}{
      id: {{ customer.id | json }},
      email: {{ customer.email | json }},
      name: {{ customer.first_name | append: ' ' | append: customer.last_name | json }},
      platform: 'shopify'
    }{% else %}null{% endif %}
  };
  (function (d, t) {
    var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
    g.src = "{$base}/build-helpdesklivechat/widget.js";
    g.defer = true; g.async = true;
    s.parentNode.insertBefore(g, s);
  })(document, "script");
</script>
HTML,
            'woocommerce' => <<<HTML
<!-- WooCommerce/WordPress: pega en footer.php (child theme) o usa hook wp_footer -->
<!-- Próximamente plugin WordPress descargable -->
<script>
  window.helpdeskSettings = {
    websiteToken: "{$token}",
    baseUrl: "{$base}"<?php if ( is_user_logged_in() ) : \$u = wp_get_current_user(); ?>,
    customer: {
      id: <?php echo (int) \$u->ID; ?>,
      email: <?php echo wp_json_encode( \$u->user_email ); ?>,
      name: <?php echo wp_json_encode( \$u->display_name ); ?>,
      platform: 'woocommerce'
    }<?php endif; ?>
  };
  (function (d, t) {
    var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
    g.src = "{$base}/build-helpdesklivechat/widget.js";
    g.defer = true; g.async = true;
    s.parentNode.insertBefore(g, s);
  })(document, "script");
</script>
HTML,
            'magento' => <<<HTML
<!-- Magento 2: incluye en default.xml o en una plantilla .phtml de footer -->
<!-- Próximamente extensión Magento descargable -->
{$genericSnippet}
HTML,
            'wordpress' => <<<HTML
<!-- WordPress puro: añade al child-theme functions.php con add_action('wp_footer', ...) -->
<!-- Próximamente plugin WordPress descargable -->
{$genericSnippet}
HTML,
            default => $genericSnippet,
        };
    }
}
