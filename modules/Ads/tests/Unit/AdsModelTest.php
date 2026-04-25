<?php

namespace Modules\Ads\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ads\Enums\AdsStatus;
use Modules\Ads\Models\Ads;
use Tests\TestCase;

class AdsModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_active_filters_published_and_not_expired(): void
    {
        $active = Ads::factory()->create([
            'status' => AdsStatus::PUBLISHED,
            'expired_at' => now()->addWeek(),
        ]);
        Ads::factory()->create([
            'status' => AdsStatus::DRAFT,
            'expired_at' => now()->addWeek(),
        ]);
        Ads::factory()->create([
            'status' => AdsStatus::PUBLISHED,
            'expired_at' => now()->subDay(),
        ]);

        $results = Ads::query()->active()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($active));
    }

    public function test_scope_for_location(): void
    {
        $homeAd = Ads::factory()->create(['location' => 'home-banner']);
        Ads::factory()->create(['location' => 'sidebar']);

        $results = Ads::query()->forLocation('home-banner')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($homeAd));
    }

    public function test_increment_clicks(): void
    {
        $ad = Ads::factory()->create(['clicked' => 5]);

        $ad->incrementClicks();

        $this->assertEquals(6, $ad->fresh()->clicked);
    }

    public function test_casts_status_to_enum(): void
    {
        $ad = Ads::factory()->create(['status' => AdsStatus::PUBLISHED->value]);

        $this->assertInstanceOf(AdsStatus::class, $ad->fresh()->status);
    }

    public function test_casts_open_in_new_tab_to_boolean(): void
    {
        $ad = Ads::factory()->create(['open_in_new_tab' => 1]);

        $this->assertTrue($ad->fresh()->open_in_new_tab);
    }
}
