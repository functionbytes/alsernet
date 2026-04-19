<?php

namespace Modules\Seo\Tests\Feature;

use Modules\Seo\Models\SeoWebVital;
use Modules\Seo\Tests\TestCase;

class SeoWebVitalsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Beacon endpoint (public)
    // -------------------------------------------------------------------------

    public function test_beacon_stores_metric_sample(): void
    {
        $this->post(route('seo.web-vitals.store'), [
            'url' => 'https://example.com/pricing',
            'metric' => 'LCP',
            'value' => 2100,
            'rating' => 'good',
            'device' => 'mobile',
        ])->assertCreated();

        $this->assertDatabaseHas('seo_web_vitals', [
            'url_path' => '/pricing',
            'metric' => 'LCP',
            'rating' => 'good',
            'device' => 'mobile',
        ]);
    }

    public function test_beacon_rejects_invalid_metric(): void
    {
        $this->postJson(route('seo.web-vitals.store'), [
            'url' => 'https://example.com/',
            'metric' => 'FOO',
            'value' => 100,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['metric']);
    }

    public function test_beacon_rejects_negative_value(): void
    {
        $this->postJson(route('seo.web-vitals.store'), [
            'url' => 'https://example.com/',
            'metric' => 'LCP',
            'value' => -5,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);
    }

    public function test_beacon_derives_rating_from_value_when_missing(): void
    {
        $this->post(route('seo.web-vitals.store'), [
            'url' => 'https://example.com/slow',
            'metric' => 'LCP',
            'value' => 5000,  // > 4000 → poor
        ])->assertCreated();

        $this->assertDatabaseHas('seo_web_vitals', [
            'url_path' => '/slow',
            'rating' => 'poor',
        ]);
    }

    // -------------------------------------------------------------------------
    // Rating logic
    // -------------------------------------------------------------------------

    public function test_rate_classifies_lcp_good(): void
    {
        $this->assertEquals('good', SeoWebVital::rate('LCP', 2000));
        $this->assertEquals('good', SeoWebVital::rate('LCP', 2500));
    }

    public function test_rate_classifies_lcp_needs_improvement(): void
    {
        $this->assertEquals('needs-improvement', SeoWebVital::rate('LCP', 3000));
    }

    public function test_rate_classifies_lcp_poor(): void
    {
        $this->assertEquals('poor', SeoWebVital::rate('LCP', 4500));
    }

    public function test_rate_classifies_cls_with_decimal_thresholds(): void
    {
        $this->assertEquals('good', SeoWebVital::rate('CLS', 0.05));
        $this->assertEquals('needs-improvement', SeoWebVital::rate('CLS', 0.15));
        $this->assertEquals('poor', SeoWebVital::rate('CLS', 0.5));
    }

    public function test_rate_returns_unknown_for_invalid_metric(): void
    {
        $this->assertEquals('unknown', SeoWebVital::rate('XYZ', 100));
    }

    // -------------------------------------------------------------------------
    // p75 aggregation
    // -------------------------------------------------------------------------

    public function test_p75_returns_null_when_no_samples(): void
    {
        $this->assertNull(SeoWebVital::p75('LCP'));
    }

    public function test_p75_computes_percentile_across_samples(): void
    {
        // 4 samples: 1000, 2000, 3000, 4000 → p75 uses floor(4*0.75)=3 → 4000
        foreach ([1000, 2000, 3000, 4000] as $value) {
            SeoWebVital::create([
                'url' => 'https://example.com/',
                'url_path' => '/',
                'metric' => 'LCP',
                'value' => $value,
                'rating' => 'good',
                'device' => 'mobile',
                'captured_at' => now(),
            ]);
        }

        // p75 of [1000, 2000, 3000, 4000] returns an upper-quartile value.
        $p75 = SeoWebVital::p75('LCP');
        $this->assertGreaterThanOrEqual(3000, $p75);
        $this->assertLessThanOrEqual(4000, $p75);
    }

    public function test_p75_scoped_by_url_path(): void
    {
        SeoWebVital::create([
            'url' => 'https://a.com/x', 'url_path' => '/x', 'metric' => 'LCP',
            'value' => 1000, 'rating' => 'good', 'device' => 'desktop', 'captured_at' => now(),
        ]);
        SeoWebVital::create([
            'url' => 'https://a.com/y', 'url_path' => '/y', 'metric' => 'LCP',
            'value' => 5000, 'rating' => 'poor', 'device' => 'desktop', 'captured_at' => now(),
        ]);

        $this->assertEquals(1000, SeoWebVital::p75('LCP', '/x'));
        $this->assertEquals(5000, SeoWebVital::p75('LCP', '/y'));
    }

    // -------------------------------------------------------------------------
    // Dashboard (admin)
    // -------------------------------------------------------------------------

    public function test_dashboard_requires_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('setting.seo.web-vitals.index'))
            ->assertForbidden();
    }

    public function test_dashboard_renders_with_no_data(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->get(route('setting.seo.web-vitals.index'))
            ->assertOk()
            ->assertViewIs('Seo::settings.web-vitals.index');
    }

    public function test_dashboard_aggregates_p75_per_metric(): void
    {
        foreach (['LCP' => 2100, 'INP' => 150, 'CLS' => 0.05] as $metric => $value) {
            SeoWebVital::create([
                'url' => 'https://example.com/',
                'url_path' => '/',
                'metric' => $metric,
                'value' => $value,
                'rating' => 'good',
                'device' => 'mobile',
                'captured_at' => now(),
            ]);
        }

        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->get(route('setting.seo.web-vitals.index'))
            ->assertOk()
            ->assertViewHas('globalP75');
    }
}
