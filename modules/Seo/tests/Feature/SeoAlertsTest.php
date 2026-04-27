<?php

namespace Modules\Seo\Tests\Feature;

use Modules\Seo\Models\SeoAlert;
use Modules\Seo\Tests\TestCase;

class SeoAlertsTest extends TestCase
{
    public function test_raise_creates_alert(): void
    {
        $alert = SeoAlert::raise(
            SeoAlert::TYPE_SCORE_DROP,
            SeoAlert::SEVERITY_WARNING,
            'Caída de score',
            'El score bajó 20 puntos',
            'https://example.com/page',
            ['meta_id' => 42, 'previous' => 90, 'current' => 70]
        );

        $this->assertDatabaseHas('seo_alerts', [
            'id' => $alert->id,
            'type' => 'score_drop',
            'severity' => 'warning',
            'url' => 'https://example.com/page',
        ]);
        $this->assertEquals(42, $alert->context['meta_id']);
    }

    public function test_raise_deduplicates_within_24h(): void
    {
        SeoAlert::raise('score_drop', 'warning', 'Primera', 'msg1', '/same-url', ['v' => 1]);
        SeoAlert::raise('score_drop', 'warning', 'Segunda', 'msg2', '/same-url', ['v' => 2]);

        $this->assertEquals(1, SeoAlert::count());

        $alert = SeoAlert::first();
        $this->assertEquals('Segunda', $alert->title);
    }

    public function test_raise_creates_new_alert_for_different_url(): void
    {
        SeoAlert::raise('score_drop', 'warning', 'A', 'msg', '/url-a');
        SeoAlert::raise('score_drop', 'warning', 'B', 'msg', '/url-b');

        $this->assertEquals(2, SeoAlert::count());
    }

    public function test_acknowledge_marks_alert_as_reviewed(): void
    {
        $alert = SeoAlert::raise('score_drop', 'info', 'Test', null, '/x');
        $this->assertNull($alert->acknowledged_at);

        $alert->acknowledge(99);
        $alert->refresh();

        $this->assertNotNull($alert->acknowledged_at);
        $this->assertEquals(99, $alert->acknowledged_by);
    }

    public function test_unacknowledged_scope_filters_correctly(): void
    {
        $acked = SeoAlert::raise('t1', 'info', 'acked', null, '/a');
        $acked->acknowledge();
        SeoAlert::raise('t1', 'info', 'open', null, '/b');

        $this->assertEquals(1, SeoAlert::unacknowledged()->count());
    }

    public function test_index_requires_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('settings.seo.alerts.index'))
            ->assertForbidden();
    }

    public function test_index_shows_alerts(): void
    {
        SeoAlert::raise('score_drop', 'warning', 'Visible alert', 'msg', '/p');

        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->get(route('settings.seo.alerts.index'))
            ->assertOk()
            ->assertSee('Visible alert');
    }

    public function test_acknowledge_endpoint(): void
    {
        $alert = SeoAlert::raise('score_drop', 'info', 'x', null, '/x');
        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->post(route('settings.seo.alerts.acknowledge', $alert))
            ->assertRedirect();

        $this->assertNotNull($alert->fresh()->acknowledged_at);
    }

    public function test_acknowledge_all_endpoint(): void
    {
        SeoAlert::raise('t1', 'info', 'a1', null, '/1');
        SeoAlert::raise('t2', 'info', 'a2', null, '/2');

        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->post(route('settings.seo.alerts.acknowledge-all'))
            ->assertRedirect();

        $this->assertEquals(0, SeoAlert::unacknowledged()->count());
    }
}
