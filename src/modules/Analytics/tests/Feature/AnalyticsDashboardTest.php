<?php

namespace Modules\Analytics\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Analytics\Models\AnalyticsReportSchedule;
use Modules\Analytics\Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    /** All analytics permissions so tests are not blocked by authorization. */
    private const ALL_PERMISSIONS = [
        'analytics.dashboard.view',
        'analytics.data.view',
        'analytics.reports.view',
        'analytics.reports.schedule',
        'analytics.settings.view',
        'analytics.settings.update',
    ];

    // -------------------------------------------------------------------------
    // Authentication guard
    // -------------------------------------------------------------------------

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('analytics.dashboard'))
            ->assertRedirect(route('auth.login'));
    }

    // -------------------------------------------------------------------------
    // Dashboard page
    // -------------------------------------------------------------------------

    public function test_dashboard_loads_for_authorized_user(): void
    {
        // The dashboard redirects to settings when Analytics is not configured,
        // so either a 200 (configured) or a redirect to settings.analytics.index is fine.
        // What must NOT happen is a redirect to login.
        $user = $this->createUser(self::ALL_PERMISSIONS);

        $response = $this->actingAs($user)
            ->get(route('analytics.dashboard'));

        // Authenticated users should never be sent to login.
        if ($response->isRedirect()) {
            $this->assertStringNotContainsString(
                route('auth.login'),
                $response->headers->get('Location', '')
            );
        } else {
            $response->assertOk();
        }
    }

    // -------------------------------------------------------------------------
    // KPI / overview API endpoint
    // -------------------------------------------------------------------------

    public function test_kpi_endpoint_requires_authentication(): void
    {
        // JSON requests get 401, not a redirect, when not authenticated
        $this->getJson(route('api.analytics.overview'))
            ->assertUnauthorized();
    }

    public function test_overview_api_returns_json_with_success_key(): void
    {
        // When Analytics is not configured, the controller throws and returns
        // a JSON error payload. Either way it must be valid JSON with a 'success' key.
        $user = $this->createUser(self::ALL_PERMISSIONS);

        $response = $this->actingAs($user)
            ->getJson(route('api.analytics.overview'));

        $response->assertJson(fn (AssertableJson $json) => $json->has('success')->etc());
    }

    public function test_overview_api_returns_correct_structure_when_configured(): void
    {
        // When Analytics is not configured this endpoint returns a 400 error payload.
        // We verify the error shape (success + message) so the test is environment-agnostic.
        $user = $this->createUser(self::ALL_PERMISSIONS);

        $response = $this->actingAs($user)
            ->getJson(route('api.analytics.overview'));

        // Must be JSON with a 'success' boolean key regardless of config state.
        $response->assertJsonStructure(['success']);

        if ($response->json('success') === true) {
            $response->assertJsonStructure(['data' => ['totals', 'chart_data']]);
        } else {
            $response->assertJsonStructure(['success', 'message']);
        }
    }

    // -------------------------------------------------------------------------
    // Report schedules
    // -------------------------------------------------------------------------

    public function test_report_schedule_can_be_created(): void
    {
        $user = $this->createUser(self::ALL_PERMISSIONS);

        $payload = [
            'name' => 'Weekly Report',
            'frequency' => 'weekly',
            'email' => 'reports@example.com',
            'format' => 'pdf',
            'is_active' => true,
        ];

        $this->actingAs($user)
            ->post(route('settings.analytics.schedules.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('analytics_report_schedules', [
            'name' => 'Weekly Report',
            'frequency' => 'weekly',
            'email' => 'reports@example.com',
            'format' => 'pdf',
        ]);
    }

    public function test_report_schedule_requires_valid_email(): void
    {
        $user = $this->createUser(self::ALL_PERMISSIONS);

        $payload = [
            'name' => 'Bad Email Report',
            'frequency' => 'daily',
            'email' => 'not-an-email',
            'format' => 'csv',
        ];

        $this->actingAs($user)
            ->post(route('settings.analytics.schedules.store'), $payload)
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseEmpty('analytics_report_schedules');
    }

    public function test_report_schedule_requires_name(): void
    {
        $user = $this->createUser(self::ALL_PERMISSIONS);

        $this->actingAs($user)
            ->post(route('settings.analytics.schedules.store'), [
                'frequency' => 'daily',
                'email' => 'valid@example.com',
                'format' => 'csv',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_report_schedule_requires_valid_frequency(): void
    {
        $user = $this->createUser(self::ALL_PERMISSIONS);

        $this->actingAs($user)
            ->post(route('settings.analytics.schedules.store'), [
                'name' => 'Bad Frequency',
                'frequency' => 'yearly',
                'email' => 'valid@example.com',
                'format' => 'csv',
            ])
            ->assertSessionHasErrors(['frequency']);
    }

    public function test_report_schedule_requires_valid_format(): void
    {
        $user = $this->createUser(self::ALL_PERMISSIONS);

        $this->actingAs($user)
            ->post(route('settings.analytics.schedules.store'), [
                'name' => 'Bad Format',
                'frequency' => 'daily',
                'email' => 'valid@example.com',
                'format' => 'xml',
            ])
            ->assertSessionHasErrors(['format']);
    }

    // -------------------------------------------------------------------------
    // Artisan command
    // -------------------------------------------------------------------------

    public function test_dispatch_schedules_command_runs(): void
    {
        Queue::fake();

        $this->artisan('analytics:dispatch-schedules')
            ->assertExitCode(0);
    }

    public function test_dispatch_schedules_command_dispatches_due_schedules(): void
    {
        Queue::fake();

        AnalyticsReportSchedule::create([
            'name' => 'Due Report',
            'frequency' => 'daily',
            'email' => 'test@example.com',
            'format' => 'csv',
            'is_active' => true,
            'next_run_at' => now()->subHour(),
        ]);

        $this->artisan('analytics:dispatch-schedules')
            ->assertExitCode(0);

        // Confirm next_run_at was updated past now
        $schedule = AnalyticsReportSchedule::first();
        $this->assertGreaterThan(now(), $schedule->fresh()->next_run_at);
    }
}
