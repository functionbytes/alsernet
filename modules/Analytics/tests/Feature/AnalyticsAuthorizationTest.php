<?php

namespace Modules\Analytics\Tests\Feature;

use Modules\Analytics\Tests\TestCase;

class AnalyticsAuthorizationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function test_user_without_dashboard_permission_gets_403(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('analytics.dashboard'))
            ->assertForbidden();
    }

    public function test_user_with_correct_permission_can_access_dashboard(): void
    {
        $user = $this->createUser(['analytics.dashboard.view']);

        $response = $this->actingAs($user)
            ->get(route('analytics.dashboard'));

        // The dashboard may redirect to settings when Analytics is not configured;
        // either way the user must not receive a 403.
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Data endpoints (analytics.data.view)
    // -------------------------------------------------------------------------

    public function test_user_without_data_view_permission_gets_403_on_overview(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->getJson(route('api.analytics.overview'))
            ->assertForbidden();
    }

    public function test_user_without_data_view_permission_gets_403_on_top_pages(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->getJson(route('api.analytics.top-pages'))
            ->assertForbidden();
    }

    public function test_user_with_data_view_permission_can_access_overview(): void
    {
        $user = $this->createUser(['analytics.data.view']);

        $response = $this->actingAs($user)
            ->getJson(route('api.analytics.overview'));

        // When Analytics is not configured the controller returns a JSON error (400),
        // but it must not be a 403.
        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertJsonStructure(['success']);
    }

    // -------------------------------------------------------------------------
    // Settings (analytics.settings.view / analytics.settings.update)
    // -------------------------------------------------------------------------

    public function test_user_without_settings_view_gets_403_on_settings_index(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('settings.analytics.index'))
            ->assertForbidden();
    }

    public function test_user_without_settings_update_gets_403_on_settings_update(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->putJson(route('settings.analytics.update'), [
                'google_analytics_enable' => false,
            ])
            ->assertForbidden();
    }

    public function test_user_without_settings_update_gets_403_on_upload_json(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Schedules (analytics.reports.view / analytics.reports.schedule)
    // -------------------------------------------------------------------------

    public function test_guest_gets_redirect_to_login_on_schedules_index(): void
    {
        $this->get(route('settings.analytics.schedules.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_reports_view_gets_403_on_schedules_index(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('settings.analytics.schedules.index'))
            ->assertForbidden();
    }

    public function test_user_without_reports_schedule_gets_403_on_schedule_store(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('settings.analytics.schedules.store'), [
                'name' => 'Test Report',
                'frequency' => 'daily',
                'email' => 'test@example.com',
                'format' => 'pdf',
            ])
            ->assertForbidden();
    }

    public function test_user_with_reports_view_can_access_schedules_index(): void
    {
        $user = $this->createUser(['analytics.reports.view']);

        $this->actingAs($user)
            ->get(route('settings.analytics.schedules.index'))
            ->assertOk();
    }

    public function test_user_with_reports_schedule_can_create_schedule(): void
    {
        $user = $this->createUser(['analytics.reports.schedule']);

        $this->actingAs($user)
            ->post(route('settings.analytics.schedules.store'), [
                'name' => 'Weekly Report',
                'frequency' => 'weekly',
                'email' => 'reports@example.com',
                'format' => 'pdf',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('analytics_report_schedules', [
            'name' => 'Weekly Report',
            'email' => 'reports@example.com',
        ]);
    }
}
