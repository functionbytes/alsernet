<?php

namespace Modules\Ads\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ads\Enums\AdsStatus;
use Modules\Ads\Models\Ads;
use Tests\TestCase;

class AdsModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_published_filters_published_and_not_expired(): void
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

        $results = Ads::query()->published()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($active));
    }

    public function test_scope_by_location(): void
    {
        $homeAd = Ads::factory()->create(['location' => 'home-banner']);
        Ads::factory()->create(['location' => 'sidebar']);

        $results = Ads::query()->byLocation('home-banner')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($homeAd));
    }

    public function test_trans_returns_translation_when_exists(): void
    {
        $ad = Ads::factory()->create(['name' => 'Base Name']);
        $ad->translations()->create(['locale' => 'en', 'name' => 'English Name']);

        $this->assertEquals('English Name', $ad->trans('name', 'en'));
    }

    public function test_trans_falls_back_to_base_field(): void
    {
        $ad = Ads::factory()->create(['name' => 'Base Name']);

        $this->assertEquals('Base Name', $ad->trans('name', 'fr'));
    }

    public function test_trans_uses_app_locale_by_default(): void
    {
        $ad = Ads::factory()->create(['name' => 'Base Name']);
        $ad->translations()->create(['locale' => app()->getLocale(), 'name' => 'Locale Name']);

        $this->assertEquals('Locale Name', $ad->trans('name'));
    }
}
