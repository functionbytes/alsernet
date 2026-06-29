<?php

namespace Modules\Seo\Tests\Feature;

use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Tests\TestCase;

class SeoKeywordTrackingTest extends TestCase
{
    public function test_can_update_target_keyword_via_inline_update(): void
    {
        $meta = SeoMeta::factory()->create(['title' => 'Test Page', 'target_keyword' => null]);
        $user = $this->createUser(['Seo.metas.index', 'Seo.metas.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.seo.metas.inline-update', $meta), [
                'field' => 'target_keyword',
                'value' => 'test keyword',
            ])
            ->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertEquals('test keyword', $meta->fresh()->target_keyword);
    }

    public function test_inline_update_rejects_invalid_field(): void
    {
        $meta = SeoMeta::factory()->create();
        $user = $this->createUser(['Seo.metas.index', 'Seo.metas.update']);

        $this->actingAs($user)
            ->patchJson(route('settings.seo.metas.inline-update', $meta), [
                'field' => 'robots',  // not in allowed fields
                'value' => 'noindex',
            ])
            ->assertUnprocessable();
    }

    public function test_cannibalization_data_appears_in_dashboard(): void
    {
        SeoMeta::factory()->create(['target_keyword' => 'same keyword', 'title' => 'Page A']);
        SeoMeta::factory()->create(['target_keyword' => 'same keyword', 'title' => 'Page B']);

        $user = $this->createUser(['Seo.dashboard.view', 'Seo.metas.index']);

        $this->actingAs($user)
            ->get(route('settings.seo.dashboard'))
            ->assertOk()
            ->assertSee('same keyword');
    }
}
