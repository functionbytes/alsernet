<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarginAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_margin_analysis(): void
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get(route('ecommerce.reports.margin'));
        $response->assertOk();
    }

    public function test_admin_can_view_demand_forecast(): void
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get(route('ecommerce.reports.forecast'));
        $response->assertOk();
    }

    public function test_admin_can_view_customer_journey(): void
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get(route('ecommerce.reports.journey'));
        $response->assertOk();
    }
}
