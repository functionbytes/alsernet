<?php

namespace Modules\Seo\Tests\Feature;

use Modules\Seo\Models\Seo404Log;
use Modules\Seo\Tests\TestCase;

class Seo404LogTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Authentication guard
    // -------------------------------------------------------------------------

    public function test_404_index_requires_auth(): void
    {
        $this->get(route('setting.seo.404-logs.index'))
            ->assertRedirect(route('auth.login'));
    }

    // -------------------------------------------------------------------------
    // Index listing
    // -------------------------------------------------------------------------

    public function test_404_index_requires_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('setting.seo.404-logs.index'))
            ->assertForbidden();
    }

    public function test_404_index_shows_logs(): void
    {
        $user = $this->createUser(['Seo.redirects.index']);

        Seo404Log::create([
            'path' => '/test-missing-page',
            'hit_count' => 5,
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now(),
            'has_redirect' => false,
        ]);

        $this->actingAs($user)
            ->get(route('setting.seo.404-logs.index'))
            ->assertOk()
            ->assertViewIs('Seo::settings.404-logs.index')
            ->assertSee('/test-missing-page');
    }

    public function test_404_index_passes_logs_and_stats_to_view(): void
    {
        $user = $this->createUser(['Seo.redirects.index']);

        $this->actingAs($user)
            ->get(route('setting.seo.404-logs.index'))
            ->assertOk()
            ->assertViewHas(['logs', 'stats']);
    }

    // -------------------------------------------------------------------------
    // Seo404Log::recordHit static method
    // -------------------------------------------------------------------------

    public function test_record_hit_creates_new_log(): void
    {
        Seo404Log::recordHit('/new-missing', 'https://example.com', '127.0.0.1');

        $this->assertDatabaseHas('seo_404_logs', [
            'path' => '/new-missing',
            'hit_count' => 1,
        ]);
    }

    public function test_record_hit_increments_existing_log(): void
    {
        Seo404Log::recordHit('/existing-missing');
        Seo404Log::recordHit('/existing-missing');

        $log = Seo404Log::where('path', '/existing-missing')->first();

        $this->assertEquals(2, $log->hit_count);
    }

    public function test_record_hit_stores_referer_and_ip(): void
    {
        Seo404Log::recordHit('/some-path', 'https://referrer.com', '10.0.0.1');

        $this->assertDatabaseHas('seo_404_logs', [
            'path' => '/some-path',
            'referer' => 'https://referrer.com',
            'ip' => '10.0.0.1',
        ]);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function test_destroy_requires_auth(): void
    {
        $log = Seo404Log::create([
            'path' => '/auth-check',
            'hit_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->delete(route('setting.seo.404-logs.destroy', $log))
            ->assertRedirect(route('auth.login'));
    }

    public function test_destroy_deletes_log(): void
    {
        $user = $this->createUser(['Seo.redirects.index']);

        $log = Seo404Log::create([
            'path' => '/delete-me',
            'hit_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('setting.seo.404-logs.destroy', $log))
            ->assertRedirect();

        $this->assertDatabaseMissing('seo_404_logs', ['id' => $log->id]);
    }

    // -------------------------------------------------------------------------
    // Clear (truncate)
    // -------------------------------------------------------------------------

    public function test_clear_requires_auth(): void
    {
        $this->post(route('setting.seo.404-logs.clear'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_clear_truncates_all_logs(): void
    {
        $user = $this->createUser(['Seo.redirects.index']);

        Seo404Log::create(['path' => '/a', 'hit_count' => 1, 'first_seen_at' => now(), 'last_seen_at' => now()]);
        Seo404Log::create(['path' => '/b', 'hit_count' => 2, 'first_seen_at' => now(), 'last_seen_at' => now()]);

        $this->actingAs($user)
            ->post(route('setting.seo.404-logs.clear'))
            ->assertRedirect();

        $this->assertEquals(0, Seo404Log::count());
    }

    // -------------------------------------------------------------------------
    // Mark resolved
    // -------------------------------------------------------------------------

    public function test_mark_resolved_updates_has_redirect(): void
    {
        $user = $this->createUser(['Seo.redirects.index']);

        $log = Seo404Log::create([
            'path' => '/unresolved',
            'hit_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'has_redirect' => false,
        ]);

        $this->actingAs($user)
            ->patchJson(route('setting.seo.404-logs.mark-resolved', $log))
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertTrue($log->fresh()->has_redirect);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_without_redirect_scope_filters_correctly(): void
    {
        Seo404Log::create(['path' => '/no-redirect', 'hit_count' => 1, 'first_seen_at' => now(), 'last_seen_at' => now(), 'has_redirect' => false]);
        Seo404Log::create(['path' => '/has-redirect', 'hit_count' => 1, 'first_seen_at' => now(), 'last_seen_at' => now(), 'has_redirect' => true]);

        $results = Seo404Log::withoutRedirect()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('/no-redirect', $results->first()->path);
    }

    public function test_order_by_hits_scope_sorts_descending(): void
    {
        Seo404Log::create(['path' => '/low-hits', 'hit_count' => 2, 'first_seen_at' => now(), 'last_seen_at' => now()]);
        Seo404Log::create(['path' => '/high-hits', 'hit_count' => 99, 'first_seen_at' => now(), 'last_seen_at' => now()]);

        $first = Seo404Log::orderByHits()->first();

        $this->assertEquals('/high-hits', $first->path);
    }
}
