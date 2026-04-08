<?php

namespace Modules\Seo\Tests\Feature;

use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Tests\TestCase;

class SeoDashboardTest extends TestCase
{
    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('setting.seo.dashboard'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_dashboard_requires_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('setting.seo.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_loads_for_authorized_user(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->get(route('setting.seo.dashboard'))
            ->assertOk()
            ->assertViewIs('Seo::settings.dashboard.index')
            ->assertViewHas(['metaStats', 'redirectStats', 'recentAudits']);
    }

    public function test_dashboard_shows_correct_meta_total(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        SeoMeta::insert([
            ['seoable_type' => 'App\\Models\\User', 'seoable_id' => 1, 'robots' => 'index,follow', 'created_at' => now(), 'updated_at' => now()],
            ['seoable_type' => 'App\\Models\\User', 'seoable_id' => 2, 'robots' => 'index,follow', 'created_at' => now(), 'updated_at' => now()],
            ['seoable_type' => 'App\\Models\\User', 'seoable_id' => 3, 'robots' => 'noindex', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($user)
            ->get(route('setting.seo.dashboard'))
            ->assertOk()
            ->assertViewHas('metaStats', fn ($stats) => $stats['total'] >= 3);
    }

    public function test_dashboard_passes_all_expected_view_variables(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $response = $this->actingAs($user)->get(route('setting.seo.dashboard'));

        $response->assertOk();
        $response->assertViewHas([
            'metaStats',
            'gradeDistribution',
            'redirectStats',
            'recentAudits',
            'scoreTrend',
            'topIssues',
            'worstPages',
            'duplicateTitles',
            'duplicateDescriptions',
        ]);
    }

    public function test_dashboard_meta_stats_contain_expected_keys(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $response = $this->actingAs($user)->get(route('setting.seo.dashboard'));

        $response->assertOk();

        $metaStats = $response->viewData('metaStats');

        $this->assertArrayHasKey('total', $metaStats);
        $this->assertArrayHasKey('indexable', $metaStats);
        $this->assertArrayHasKey('noindex', $metaStats);
        $this->assertArrayHasKey('missing_description', $metaStats);
        $this->assertArrayHasKey('avg_score', $metaStats);
    }
}
