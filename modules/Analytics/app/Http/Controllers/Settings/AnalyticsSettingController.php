<?php

namespace Modules\Analytics\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Analytics\Analytics;
use Modules\Analytics\Forms\AnalyticsSettingForm;
use Modules\Analytics\Http\Requests\Settings\AnalyticsSettingRequest;
use Modules\Analytics\Period;

class AnalyticsSettingController extends Controller
{
    /**
     * Display analytics settings page.
     */
    public function index(): View
    {
        $this->authorize('analytics.settings.view');

        $pageTitle = __('analytics::analytics.settings.title');
        $breadcrumb = __('analytics::analytics.settings.title');

        // Get current settings — credentials are never sent to the browser.
        $settings = [
            'google_analytics_enable' => setting('google_analytics_enable', false),
            'google_analytics_property_id' => setting('google_analytics_property_id', ''),
            'google_analytics_measurement_id' => setting('google_analytics_measurement_id', ''),
            'analytics_cache_lifetime' => setting('analytics_cache_lifetime', 60),
            'analytics_dashboard_widgets' => setting('analytics_dashboard_widgets', ['general', 'top_pages', 'top_browsers', 'top_referrers']),
            'analytics_reports_daily_enabled' => setting('analytics_reports_daily_enabled', false),
            'analytics_reports_weekly_enabled' => setting('analytics_reports_weekly_enabled', false),
            'analytics_reports_monthly_enabled' => setting('analytics_reports_monthly_enabled', false),
            'analytics_report_email' => setting('analytics_report_email', ''),
            'meta_pixel_id' => setting('meta_pixel_id', ''),
            'microsoft_clarity_id' => setting('microsoft_clarity_id', ''),
            'tiktok_pixel_id' => setting('tiktok_pixel_id', ''),
            'linkedin_insight_tag_id' => setting('linkedin_insight_tag_id', ''),
        ];

        // Decrypt credentials — metadata for display, raw JSON for textarea.
        $credentialsInfo = $this->decryptCredentialsInfo();
        $credentialsJson = $this->decryptStoredCredentials() ?? '';

        // Get form configuration
        $formFields = AnalyticsSettingForm::getFields();
        $widgetTypes = AnalyticsSettingForm::getWidgetTypes();
        $dateRanges = AnalyticsSettingForm::getDateRanges();

        return view('analytics::settings.index', compact(
            'pageTitle',
            'breadcrumb',
            'settings',
            'credentialsInfo',
            'credentialsJson',
            'formFields',
            'widgetTypes',
            'dateRanges'
        ));
    }

    /**
     * Update analytics settings.
     */
    public function update(AnalyticsSettingRequest $request): JsonResponse
    {
        $this->authorize('analytics.settings.update');

        try {
            $validated = $request->validated();

            // Encrypt credentials before persisting — only when a new value is submitted.
            $credentialsRaw = $validated['google_analytics_credentials'] ?? '';

            $settingsToUpdate = [
                'google_analytics_enable' => $validated['google_analytics_enable'] ?? false,
                'google_analytics_property_id' => $validated['google_analytics_property_id'] ?? '',
                'google_analytics_measurement_id' => $validated['google_analytics_measurement_id'] ?? '',
                'analytics_cache_lifetime' => $validated['analytics_cache_lifetime'] ?? 60,
                'analytics_dashboard_widgets' => $validated['analytics_dashboard_widgets'] ?? [],
                'analytics_reports_daily_enabled' => $validated['analytics_reports_daily_enabled'] ?? false,
                'analytics_reports_weekly_enabled' => $validated['analytics_reports_weekly_enabled'] ?? false,
                'analytics_reports_monthly_enabled' => $validated['analytics_reports_monthly_enabled'] ?? false,
                'analytics_report_email' => $validated['analytics_report_email'] ?? '',
                'meta_pixel_id' => $validated['meta_pixel_id'] ?? '',
                'microsoft_clarity_id' => $validated['microsoft_clarity_id'] ?? '',
                'tiktok_pixel_id' => $validated['tiktok_pixel_id'] ?? '',
                'linkedin_insight_tag_id' => $validated['linkedin_insight_tag_id'] ?? '',
            ];

            // Only include credentials when a non-empty value is submitted.
            if ($credentialsRaw !== '') {
                $settingsToUpdate['google_analytics_credentials'] = encrypt($credentialsRaw);
            }

            updateSettings($settingsToUpdate);

            // Clear analytics cache when settings are updated (tags only supported on Redis/Memcached).
            if (\Cache::supportsTags()) {
                \Cache::tags(['analytics'])->flush();
            }

            return response()->json([
                'status' => true,
                'message' => __('analytics::analytics.success.settings_updated'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating analytics settings', [
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => __('analytics::analytics.errors.update_failed'),
            ], 500);
        }
    }

    /**
     * Validate Google Analytics credentials.
     *
     * Credentials are submitted directly from the form textarea (plain JSON) — no decrypt needed here.
     */
    public function validateCredentials(Request $request): JsonResponse
    {
        $this->authorize('analytics.settings.view');

        $propertyId = $request->input('property_id');
        $credentials = $request->input('credentials');

        if (empty($propertyId) || empty($credentials)) {
            return response()->json([
                'status' => false,
                'message' => __('analytics::analytics.validation.property_id_required'),
            ], 422);
        }

        try {
            // Validate JSON format
            $decoded = json_decode($credentials, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(__('analytics::analytics.validation.json_invalid', ['error' => json_last_error_msg()]));
            }

            // Check required fields
            $requiredFields = ['type', 'project_id', 'private_key', 'client_email'];
            foreach ($requiredFields as $field) {
                if (! isset($decoded[$field])) {
                    throw new \Exception(__('analytics::analytics.validation.invalid_field', ['field' => $field]));
                }
            }

            // Validate type
            if ($decoded['type'] !== 'service_account') {
                throw new \Exception(__('analytics::analytics.validation.type_must_be_service_account'));
            }

            // Try to initialize Analytics client
            try {
                $analytics = new Analytics($propertyId, $credentials);
                $analytics->getClient();

                return response()->json([
                    'status' => true,
                    'message' => __('analytics::analytics.success.credentials_validated'),
                ]);
            } catch (\Exception) {
                throw new \Exception(__('analytics::analytics.validation.connection_check_failed'));
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test Analytics connection using persisted (encrypted) credentials.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $this->authorize('analytics.settings.view');

        try {
            $propertyId = setting('google_analytics_property_id');

            $credentials = $this->decryptStoredCredentials();

            if (empty($propertyId) || $credentials === null) {
                throw new \Exception(__('analytics::analytics.errors.not_configured'));
            }

            $analytics = new Analytics($propertyId, $credentials);
            $period = Period::days(1);

            // Try to fetch some basic data
            $data = $analytics
                ->dateRange($period)
                ->metrics(['sessions'])
                ->get();

            return response()->json([
                'status' => true,
                'message' => __('analytics::analytics.success.connection_successful'),
                'data' => [
                    'sessions' => $data->getTotals()['sessions'] ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error testing Analytics connection', [
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => __('analytics::analytics.errors.connection_check_failed'),
            ], 500);
        }
    }

    /**
     * Clear analytics cache.
     */
    public function clearCache(): JsonResponse
    {
        $this->authorize('analytics.data.clear_cache');

        try {
            if (\Cache::supportsTags()) {
                \Cache::tags(['analytics'])->flush();
            }

            return response()->json([
                'status' => true,
                'message' => __('analytics::analytics.success.cache_cleared'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing analytics cache', [
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => __('analytics::analytics.errors.cache_clear_failed'),
            ], 500);
        }
    }

    /**
     * Decrypt stored credentials JSON string.
     * Returns null when not configured or when the stored value cannot be decrypted
     * (e.g. legacy plaintext value from before encryption was introduced).
     */
    private function decryptStoredCredentials(): ?string
    {
        $stored = setting('google_analytics_credentials', '');

        if (empty($stored)) {
            return null;
        }

        try {
            return decrypt($stored);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Return only non-sensitive metadata about the stored credentials
     * so the view can display configuration status without exposing private_key.
     *
     * @return array{configured: bool, project_id: string|null, client_email: string|null}
     */
    private function decryptCredentialsInfo(): array
    {
        $json = $this->decryptStoredCredentials();

        if ($json === null) {
            return ['configured' => false, 'project_id' => null, 'client_email' => null];
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return ['configured' => false, 'project_id' => null, 'client_email' => null];
        }

        return [
            'configured' => true,
            'project_id' => $decoded['project_id'] ?? null,
            'client_email' => $decoded['client_email'] ?? null,
        ];
    }
}
