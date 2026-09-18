<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Http\Requests\UpdateLivechatSettingsRequest;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Nwidart\Modules\Facades\Module;

class LivechatSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.settings.view')->only(['index', 'platformIntegrations']);
        $this->middleware('can:helpdesk.livechat.settings.update')->only('update');
    }

    /**
     * AJAX endpoint: list Engagement PlatformIntegration records by platform.
     * Returns [] when Engagement module is disabled or unknown platform requested.
     */
    public function platformIntegrations(Request $request): JsonResponse
    {
        if (! Module::find('Engagement')?->isEnabled()) {
            return response()->json([]);
        }

        $integrationModel = '\\Modules\\Engagement\\Models\\PlatformIntegration';
        if (! class_exists($integrationModel)) {
            return response()->json([]);
        }

        $platform = (string) $request->query('platform', '');
        $allowed = ['prestashop', 'shopify', 'woocommerce', 'magento', 'wordpress'];

        if (! in_array($platform, $allowed, true)) {
            return response()->json([]);
        }

        $rows = $integrationModel::query()
            ->where('platform', $platform)
            ->orderBy('name')
            ->get(['id', 'name', 'store_url']);

        return response()->json($rows);
    }

    public function index(): View
    {
        $web = $this->getActiveWeb();
        $settings = $this->extractSettings($web);

        $positions = [
            'bottom-right' => 'Abajo Derecha',
            'bottom-left' => 'Abajo Izquierda',
            'top-right' => 'Arriba Derecha',
            'top-left' => 'Arriba Izquierda',
        ];

        return view('helpdesklivechat::settings.livechat.index', [
            'backups' => $settings,
            'positions' => $positions,
        ]);
    }

    public function update(UpdateLivechatSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Tracking settings live in the Setting key-value table (global, not
        // per-Web inbox) so they stay configurable even before any inbox exists.
        $trackingKeys = [
            'heartbeat_interval_seconds',
            'heartbeat_cooldown_seconds',
            'sdk_batch_interval_ms',
            'sdk_batch_size',
        ];
        foreach ($trackingKeys as $key) {
            if (array_key_exists($key, $validated)) {
                Setting::set("livechat.tracking.{$key}", $validated[$key], 'livechat');
                unset($validated[$key]);
            }
        }

        // Normalise no_agents_message → offline_message
        if (isset($validated['no_agents_message'])) {
            $validated['offline_message'] = $validated['no_agents_message'];
            unset($validated['no_agents_message']);
        }

        // Map form field `position` → `widget_position` (legacy column)
        if (isset($validated['position'])) {
            $validated['widget_position'] = $validated['position'];
            unset($validated['position']);
        }

        // Map form field `primary_color` → `widget_color` (legacy column)
        if (isset($validated['primary_color'])) {
            $validated['widget_color'] = $validated['primary_color'];
            unset($validated['primary_color']);
        }

        // Boolean checkboxes are absent from the request when unchecked;
        // resolve them explicitly so the model always receives a value.
        $validated['enable_file_upload'] = $request->boolean('enable_file_upload');
        $validated['enable_emoji'] = $request->boolean('enable_emoji');
        $validated['allowed_file_types'] = $request->input('allowed_file_types', []);

        // Apply to all web channel inboxes
        $webs = Web::query()->get();

        if ($webs->isEmpty()) {
            // No inbox yet — persist to Setting as fallback for future Web creation
            foreach ($validated as $key => $value) {
                Setting::set("livechat.{$key}", $value, 'livechat');
            }
        } else {
            foreach ($webs as $web) {
                $web->update($validated);
            }
        }

        // Invalidate the per-Web widget config cache so the new tracking
        // values are exposed to the widget on the next page load.
        foreach (Web::query()->pluck('id') as $webId) {
            Cache::forget("widget_config_{$webId}");
        }
        Cache::forget('helpdesklivechat:heartbeat_cooldown');

        return back()->with('success', 'Configuración de LiveChat actualizada correctamente');
    }

    /**
     * Get the first active Web channel, falling back to a new unsaved instance
     * so the view always has something to read from.
     */
    private function getActiveWeb(): ?Web
    {
        return Web::query()->first();
    }

    /**
     * Extract all settings for the view from a Web model (or Setting fallback).
     *
     * @return array<string, mixed>
     */
    private function extractSettings(?Web $web): array
    {
        /** @var Setting $secretSetting */
        $secretSetting = Setting::firstOrCreate(
            ['key' => 'livechat.secret_key'],
            ['value' => Str::random(40), 'group' => 'livechat']
        );
        $secretKey = $secretSetting->value;

        $defaults = [
            // Identity — website_token exposed for the install snippet
            'website_token' => null,

            // Home screen
            'show_avatars' => true,
            'show_help_center' => true,
            'hide_suggested_articles' => false,
            'show_tickets_section' => true,
            'enable_send_message' => true,
            'enable_create_ticket' => true,
            'enable_search_help' => true,

            // Chat screen
            'welcome_message' => 'Hola! ¿Cómo podemos ayudarte?',
            'input_placeholder' => 'Escribe tu mensaje...',
            'offline_message' => 'Nuestros agentes no están disponibles en este momento, pero puedes enviar mensajes.',
            'queue_message' => 'Uno de nuestros agentes estará contigo en breve. Eres el número :number en la cola.',

            // Launcher
            'position' => 'bottom-right',
            'side_spacing' => 16,
            'bottom_spacing' => 16,
            'hide_launcher' => false,

            // Style
            'primary_color' => '#90bb13',
            'secondary_color' => '#ffffff',
            'header_title' => 'Chat de Soporte',

            // Feature flags
            'show_timestamps' => true,
            'typing_indicator' => true,
            'sound_notifications' => true,
            'enable_email_transcripts' => false,
            'enable_file_upload' => true,
            'enable_emoji' => true,
            'allowed_file_types' => ['images', 'documents'],

            // Automation
            'enable_auto_transfer' => false,
            'auto_transfer_minutes' => 5,
            'enable_auto_inactive' => false,
            'auto_inactive_minutes' => 10,
            'enable_auto_close' => false,
            'auto_close_minutes' => 15,

            // Security
            'trusted_domains' => '',
            'enforce_identity_verification' => false,
            'secret_key' => $secretKey,

            // Forms
            'pre_chat_form_enabled' => false,
            'pre_chat_info' => '',
            'post_chat_form_enabled' => false,
            'post_chat_info' => '',

            // Chat page
            'chat_page_title' => '',
            'chat_page_subtitle' => '',

            // Tracking — read from Setting (global, not per-Web)
            'heartbeat_interval_seconds' => (int) (Setting::get('livechat.tracking.heartbeat_interval_seconds') ?? 15),
            'heartbeat_cooldown_seconds' => (int) (Setting::get('livechat.tracking.heartbeat_cooldown_seconds') ?? 5),
            'sdk_batch_interval_ms' => (int) (Setting::get('livechat.tracking.sdk_batch_interval_ms') ?? 1500),
            'sdk_batch_size' => (int) (Setting::get('livechat.tracking.sdk_batch_size') ?? 10),
        ];

        if ($web === null) {
            return $defaults;
        }

        return array_merge($defaults, [
            // Identity
            'website_token' => $web->website_token,

            // Home screen
            'show_avatars' => (bool) ($web->show_avatars ?? $defaults['show_avatars']),
            'show_help_center' => (bool) ($web->show_help_center ?? $defaults['show_help_center']),
            'hide_suggested_articles' => (bool) ($web->hide_suggested_articles ?? $defaults['hide_suggested_articles']),
            'show_tickets_section' => (bool) ($web->show_tickets_section ?? $defaults['show_tickets_section']),
            'enable_send_message' => (bool) ($web->enable_send_message ?? $defaults['enable_send_message']),
            'enable_create_ticket' => (bool) ($web->enable_create_ticket ?? $defaults['enable_create_ticket']),
            'enable_search_help' => (bool) ($web->enable_search_help ?? $defaults['enable_search_help']),

            // Chat screen
            'welcome_message' => $web->welcome_message ?? $defaults['welcome_message'],
            'input_placeholder' => $web->input_placeholder ?? $defaults['input_placeholder'],
            'offline_message' => $web->offline_message ?? $defaults['offline_message'],
            'queue_message' => $web->queue_message ?? $defaults['queue_message'],

            // Launcher — map widget_position back to form field `position`
            'position' => $web->widget_position ?? $defaults['position'],
            'side_spacing' => $web->side_spacing ?? $defaults['side_spacing'],
            'bottom_spacing' => $web->bottom_spacing ?? $defaults['bottom_spacing'],
            'hide_launcher' => (bool) ($web->hide_launcher ?? $defaults['hide_launcher']),

            // Style — map widget_color back to form field `primary_color`
            'primary_color' => $web->widget_color ?? $defaults['primary_color'],
            'secondary_color' => $web->secondary_color ?? $defaults['secondary_color'],
            'header_title' => $web->header_title ?? $defaults['header_title'],

            // Feature flags
            'show_timestamps' => (bool) ($web->show_timestamps ?? $defaults['show_timestamps']),
            'typing_indicator' => (bool) ($web->typing_indicator ?? $defaults['typing_indicator']),
            'sound_notifications' => (bool) ($web->sound_notifications ?? $defaults['sound_notifications']),
            'enable_email_transcripts' => (bool) ($web->enable_email_transcripts ?? $defaults['enable_email_transcripts']),
            'enable_file_upload' => (bool) ($web->enable_file_upload ?? $defaults['enable_file_upload']),
            'enable_emoji' => (bool) ($web->enable_emoji ?? $defaults['enable_emoji']),
            'allowed_file_types' => $web->allowed_file_types ?? $defaults['allowed_file_types'],

            // Automation
            'enable_auto_transfer' => (bool) ($web->enable_auto_transfer ?? $defaults['enable_auto_transfer']),
            'auto_transfer_minutes' => $web->auto_transfer_minutes ?? $defaults['auto_transfer_minutes'],
            'enable_auto_inactive' => (bool) ($web->enable_auto_inactive ?? $defaults['enable_auto_inactive']),
            'auto_inactive_minutes' => $web->auto_inactive_minutes ?? $defaults['auto_inactive_minutes'],
            'enable_auto_close' => (bool) ($web->enable_auto_close ?? $defaults['enable_auto_close']),
            'auto_close_minutes' => $web->auto_close_minutes ?? $defaults['auto_close_minutes'],

            // Security
            'trusted_domains' => $web->trusted_domains ?? $defaults['trusted_domains'],
            'enforce_identity_verification' => (bool) ($web->enforce_identity_verification ?? $defaults['enforce_identity_verification']),
            'secret_key' => $secretKey,

            // Forms
            'pre_chat_form_enabled' => (bool) $web->pre_chat_form_enabled,
            'pre_chat_info' => $web->pre_chat_info ?? $defaults['pre_chat_info'],
            'post_chat_form_enabled' => (bool) ($web->post_chat_form_enabled ?? $defaults['post_chat_form_enabled']),
            'post_chat_info' => $web->post_chat_info ?? $defaults['post_chat_info'],

            // Chat page
            'chat_page_title' => $web->chat_page_title ?? $defaults['chat_page_title'],
            'chat_page_subtitle' => $web->chat_page_subtitle ?? $defaults['chat_page_subtitle'],
        ]);
    }
}
