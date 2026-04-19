<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;
use Modules\Seo\Http\Requests\UpdateSeoSettingsRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unified SEO settings editor. All values are stored in the core_settings
 * table under the `seo.*` namespace, so admins can tune the module without
 * editing .env. The matching config/env fallback still applies via seo_setting().
 */
class SeoSettingsController extends Controller
{
    /**
     * Mapping from form field → Setting key → config fallback path.
     * Used to both prefill the form and persist changes.
     *
     * @var array<string, array{0: string, 1: ?string}>
     */
    private const FIELDS = [
        // IndexNow
        'indexnow_enabled' => ['seo.indexnow.enabled', 'seohelper.indexnow.enabled'],
        'indexnow_key' => ['seo.indexnow.key', 'seohelper.indexnow.key'],
        'indexnow_auto_submit' => ['seo.indexnow.auto_submit', 'seohelper.indexnow.auto_submit'],

        // Webhooks
        'slack_webhook_url' => ['seo.webhooks.slack_url', 'Seo.webhooks.slack_url'],
        'discord_webhook_url' => ['seo.webhooks.discord_url', 'Seo.webhooks.discord_url'],
        'webhook_signing_secret' => ['seo.webhooks.signing_secret', 'Seo.webhooks.signing_secret'],
        'notify_score_drop' => ['seo.webhooks.notify_score_drop', 'Seo.webhooks.notify_score_drop'],
        'notify_redirect_chain' => ['seo.webhooks.notify_redirect_chain', 'Seo.webhooks.notify_redirect_chain'],
        'notify_orphans' => ['seo.webhooks.notify_orphans', 'Seo.webhooks.notify_orphans'],
        'score_drop_threshold' => ['seo.notifications.score_drop_threshold', 'Seo.notifications.score_drop_threshold'],
        'notifications_email' => ['seo.notifications.email', 'Seo.notifications.email'],

        // PageSpeed
        'pagespeed_api_key' => ['seo.pagespeed_api_key', 'Seo.pagespeed_api_key'],

        // Performance
        'preconnect' => ['seo.performance.preconnect', 'Seo.performance.preconnect'],
        'dns_prefetch' => ['seo.performance.dns_prefetch', 'Seo.performance.dns_prefetch'],
        'html_cache_seconds' => ['seo.performance.html_cache_seconds', 'Seo.performance.html_cache_seconds'],
        'enable_security_headers' => ['seo.performance.enable_security_headers', 'Seo.performance.enable_security_headers'],

        // Web Vitals
        'web_vitals_enabled' => ['seo.web_vitals.enabled', 'seohelper.web_vitals.enabled'],
        'web_vitals_sample_rate' => ['seo.web_vitals.sample_rate', 'seohelper.web_vitals.sample_rate'],
        'web_vitals_retention_days' => ['seo.web_vitals.retention_days', 'seohelper.web_vitals.retention_days'],

        // llms.txt
        'llms_txt_enabled' => ['seo.llms_txt.enabled', 'seohelper.llms_txt.enabled'],

        // GTM
        'gtm_container_id' => ['seo.gtm.container_id', 'Seo.gtm.container_id'],
    ];

    /**
     * @var array<string>
     */
    private const BOOLEAN_FIELDS = [
        'indexnow_enabled', 'indexnow_auto_submit',
        'notify_score_drop', 'notify_redirect_chain', 'notify_orphans',
        'enable_security_headers', 'web_vitals_enabled', 'llms_txt_enabled',
    ];

    public function __construct()
    {
        $this->middleware('can:Seo.settings.view')->only('edit');
        $this->middleware('can:Seo.settings.update')->only('update');
    }

    public function edit(): View
    {
        $settings = [];

        foreach (self::FIELDS as $field => [$settingKey, $configKey]) {
            $value = Setting::get($settingKey);
            if ($value === null || $value === '') {
                $value = $configKey ? config($configKey) : null;
            }
            $settings[$field] = $value;
        }

        return view('Seo::settings.seo-settings.edit', compact('settings'));
    }

    public function update(UpdateSeoSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::FIELDS as $field => [$settingKey, $configKey]) {
            if (in_array($field, self::BOOLEAN_FIELDS, true)) {
                // Booleans: store "1" or "0" to distinguish from unset
                Setting::set($settingKey, $request->boolean($field) ? '1' : '0');

                continue;
            }

            if (! array_key_exists($field, $data)) {
                continue;
            }

            Setting::set($settingKey, (string) ($data[$field] ?? ''));
        }

        return redirect()
            ->route('setting.seo.settings.edit')
            ->with('success', 'Configuración SEO guardada correctamente.');
    }

    /**
     * Download all seo.* settings as a JSON file. Useful for migrating the
     * SEO module config between environments (dev → staging → prod).
     */
    public function export(): StreamedResponse
    {
        $payload = [
            'exported_at' => now()->toIso8601String(),
            'app_url' => config('app.url'),
            'version' => 1,
            'settings' => [],
        ];

        foreach (self::FIELDS as $field => [$settingKey]) {
            $payload['settings'][$settingKey] = Setting::get($settingKey);
        }

        // Also include robots.txt and llms.txt content
        $payload['settings']['seo.robots_txt'] = Setting::get('seo.robots_txt');
        $payload['settings']['seo.llms_txt'] = Setting::get('seo.llms_txt');

        $filename = 'seo-config-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(
            fn () => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Restore seo.* settings from a previously exported JSON file.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'config_file' => ['required', 'file', 'mimes:json,txt', 'max:512'],
        ]);

        $content = file_get_contents($request->file('config_file')->getPathname());
        $decoded = json_decode($content ?: '', true);

        if (! is_array($decoded) || ! isset($decoded['settings']) || ! is_array($decoded['settings'])) {
            return back()->with('error', 'Archivo inválido. Debe ser un JSON exportado por este módulo.');
        }

        $imported = 0;
        foreach ($decoded['settings'] as $key => $value) {
            if (! str_starts_with($key, 'seo.')) {
                continue;
            }
            Setting::set($key, (string) ($value ?? ''));
            $imported++;
        }

        return redirect()
            ->route('setting.seo.settings.edit')
            ->with('success', "Configuración SEO importada: {$imported} valores aplicados.");
    }
}
