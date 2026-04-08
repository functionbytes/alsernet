<?php

namespace Modules\Optimize\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use Tests\TestCase;

/**
 * Feature tests for OptimizeController.
 *
 * Uses RefreshDatabase to run migrations on the SQLite in-memory DB and
 * roll back each test automatically. Cache is flushed manually.
 */
class OptimizeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_requires_auth(): void
    {
        $this->get(route('settings.optimize.index'))
            ->assertRedirect();
    }

    public function test_index_loads_settings_view(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.optimize.index'))
            ->assertOk()
            ->assertViewIs('optimize::settings.index')
            ->assertViewHasAll(['get', 'stats', 'ttl', 'skipPatterns']);
    }

    public function test_index_shows_zero_stats_when_no_activity(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('settings.optimize.index'));

        $stats = $response->viewData('stats');
        $this->assertSame(0, $stats['requests']);
        $this->assertSame(0, $stats['bytes_saved']);
    }

    public function test_index_shows_cached_stats(): void
    {
        Cache::put('optimize.stats.requests', 42, 300);
        Cache::put('optimize.stats.bytes_saved', 1024, 300);

        $response = $this->actingAs($this->user)
            ->get(route('settings.optimize.index'));

        $stats = $response->viewData('stats');
        $this->assertSame(42, $stats['requests']);
        $this->assertSame(1024, $stats['bytes_saved']);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_requires_auth(): void
    {
        $this->post(route('settings.optimize.update'))
            ->assertRedirect();
    }

    public function test_update_saves_enabled_setting(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.optimize.update'), ['enabled' => '1']);

        $this->assertSame('1', Setting::get('optimize.enabled'));
    }

    public function test_update_saves_disabled_when_checkbox_absent(): void
    {
        Setting::set('optimize.enabled', '1');

        $this->actingAs($this->user)
            ->post(route('settings.optimize.update'), []);

        $this->assertSame('0', Setting::get('optimize.enabled'));
    }

    public function test_update_saves_ttl(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.optimize.update'), ['response_cache_ttl' => '120']);

        $this->assertSame('120', Setting::get('optimize.response_cache_ttl'));
    }

    public function test_update_clamps_ttl_to_minimum_one(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.optimize.update'), ['response_cache_ttl' => '0']);

        $this->assertSame('1', Setting::get('optimize.response_cache_ttl'));
    }

    public function test_update_saves_skip_patterns(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.optimize.update'), ['skip_patterns' => "admin/*\napi/*"]);

        $this->assertSame("admin/*\napi/*", Setting::get('optimize.skip_patterns'));
    }

    public function test_update_flushes_response_cache(): void
    {
        $key = 'optimize.cache.'.md5('http://localhost/page|');
        Cache::tags(['optimize.response'])->put($key, '<p>old</p>', 60);

        $this->actingAs($this->user)
            ->post(route('settings.optimize.update'), []);

        $this->assertNull(Cache::tags(['optimize.response'])->get($key));
    }

    public function test_update_redirects_with_success_message(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.optimize.update'), [])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    // -------------------------------------------------------------------------
    // resetStats
    // -------------------------------------------------------------------------

    public function test_reset_stats_requires_auth(): void
    {
        $this->post(route('settings.optimize.reset-stats'))
            ->assertRedirect();
    }

    public function test_reset_stats_clears_counters(): void
    {
        Cache::put('optimize.stats.requests', 100, 300);
        Cache::put('optimize.stats.bytes_saved', 5000, 300);

        $this->actingAs($this->user)
            ->post(route('settings.optimize.reset-stats'));

        $this->assertNull(Cache::get('optimize.stats.requests'));
        $this->assertNull(Cache::get('optimize.stats.bytes_saved'));
    }

    public function test_reset_stats_flushes_response_cache(): void
    {
        $key = 'optimize.cache.'.md5('http://localhost/page|');
        Cache::tags(['optimize.response'])->put($key, '<p>cached</p>', 60);

        $this->actingAs($this->user)
            ->post(route('settings.optimize.reset-stats'));

        $this->assertNull(Cache::tags(['optimize.response'])->get($key));
    }

    public function test_reset_stats_redirects_with_success_message(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.optimize.reset-stats'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
