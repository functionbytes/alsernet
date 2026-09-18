<?php

namespace Modules\HelpdeskAnalytics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskAnalytics\Database\Seeders\HelpdeskAnalyticsPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression tests for the `analytics.integration_enabled` admin toggle
 * (Settings → Integraciones → HelpdeskAnalytics).
 *
 * AnalyticsController::data() is the single JSON feed that backs the whole
 * dashboard (overview, channels, agents, trends, heatmap, customers). It must
 * short-circuit to an empty/unavailable payload — without touching any
 * aggregate query — as soon as helpdesk_analytics_enabled() is false.
 */
class AnalyticsIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskAnalyticsPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('helpdeskanalytics.view');
    }

    public function test_data_feed_is_disabled_when_integration_toggle_is_off(): void
    {
        Setting::set('analytics.integration_enabled', '0', 'analytics');

        $this->actingAs($this->user)
            ->getJson(route('helpdeskanalytics.data'))
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'available' => false,
                'message' => 'La integración de Analytics está deshabilitada.',
            ]);
    }

    public function test_data_feed_builds_full_dashboard_payload_when_integration_toggle_is_on(): void
    {
        Setting::set('analytics.integration_enabled', '1', 'analytics');

        $this->actingAs($this->user)
            ->getJson(route('helpdeskanalytics.data'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('available', true)
            ->assertJsonStructure(['overview', 'channels', 'agents', 'trends', 'heatmap', 'customers']);
    }

    public function test_data_feed_defaults_to_enabled_when_no_setting_is_stored(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('helpdeskanalytics.data'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('available', true)
            ->assertJsonStructure(['overview']);
    }

    /**
     * Un rango multi-año haría que trends() itere día a día en PHP y que
     * heatmap()/channelDistribution() escaneen toda la tabla de conversaciones
     * sin límite: se rechaza cualquier rango mayor a 366 días (mitigación DoS).
     */
    public function test_data_feed_rejects_a_date_range_wider_than_366_days(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('helpdeskanalytics.data', ['from' => '2000-01-01', 'to' => '2030-01-01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    }

    public function test_data_feed_accepts_a_historical_range_within_366_days(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('helpdeskanalytics.data', ['from' => '2024-01-01', 'to' => '2024-06-01']))
            ->assertOk()
            ->assertJsonPath('available', true);
    }
}
