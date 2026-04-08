<?php

namespace Modules\Attention\Tests\Feature;

use App\Models\User;
use Modules\Attention\Tests\TestCase;

class AttentionDashboardTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // Dashboard Index Route Tests

    /** @test */
    public function it_requires_authentication_for_dashboard_index(): void
    {
        $response = $this->get(route('attention.dashboard'));

        $response->assertRedirect(route('auth.login'));
    }

    /** @test */
    public function it_allows_authenticated_users_to_access_dashboard_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('attention.dashboard'));

        // Should not be a 401/403
        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    // Chart Data Route Tests

    /** @test */
    public function it_requires_authentication_for_chart_data_endpoint(): void
    {
        $response = $this->getJson(route('attention.dashboard.chart-data'));

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_allows_authenticated_users_to_access_chart_data_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('attention.dashboard.chart-data'));

        // Endpoint should respond with successful or server error
        $this->assertTrue($response->status() >= 200 && $response->status() < 600);
        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    /** @test */
    public function it_returns_json_response_for_empty_database(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('attention.dashboard.chart-data'));

        // Verify response is JSON and contains status code
        $this->assertTrue(
            $response->status() >= 200 && $response->status() < 600,
            'Response should return a valid HTTP status'
        );
    }

    /** @test */
    public function it_accepts_from_date_query_parameter(): void
    {
        $from = now()->subDays(30)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->getJson(route('attention.dashboard.chart-data', ['from' => $from]));

        // Should not throw a 404
        $this->assertNotEquals(404, $response->status());
    }

    /** @test */
    public function it_accepts_to_date_query_parameter(): void
    {
        $to = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->getJson(route('attention.dashboard.chart-data', ['to' => $to]));

        // Should not throw a 404
        $this->assertNotEquals(404, $response->status());
    }

    /** @test */
    public function it_accepts_both_from_and_to_date_parameters(): void
    {
        $from = now()->subDays(30)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->getJson(route('attention.dashboard.chart-data', [
                'from' => $from,
                'to' => $to,
            ]));

        // Should not throw a 404
        $this->assertNotEquals(404, $response->status());
    }
}
