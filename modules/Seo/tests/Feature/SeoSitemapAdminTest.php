<?php

namespace Modules\Seo\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Sitemap\Builder\SitemapBuilder;
use Tests\TestCase;

/**
 * Uses DatabaseTransactions (not RefreshDatabase) because the project test DB
 * has a broken Core products migration that breaks migrate:fresh.
 *
 * sitemap_generations is recreated manually since SitemapTest drops it.
 * Gate::before() grants all permissions to the in-memory user (id=9999) so
 * Spatie is never queried for that user, avoiding DB dependency.
 */
class SeoSitemapAdminTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        activity()->disableLogging();

        config([
            'sitemap.models' => [],
            'sitemap.page_models' => [],
            'sitemap.post_models' => [],
            'sitemap.post_callbacks' => [],
            'sitemap.static_urls' => [],
            'sitemap.cache_enabled' => false,
        ]);

        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('sitemap_generations');
        Schema::create('sitemap_generations', function ($table) {
            $table->id();
            $table->string('status', 20);
            $table->unsignedInteger('url_count')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->text('error_message')->nullable();
            $table->string('source', 20)->default('command');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('seo_static_urls');
        Schema::create('seo_static_urls', function ($table) {
            $table->id();
            $table->string('url')->unique();
            $table->decimal('priority', 2, 1)->default(0.5);
            $table->enum('changefreq', ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])->default('weekly');
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('seo_metas');
        Schema::create('seo_metas', function ($table) {
            $table->id();
            $table->string('seoable_type');
            $table->unsignedBigInteger('seoable_id');
            $table->string('canonical_url', 500)->nullable();
            $table->string('robots', 100)->default('index,follow');
            $table->unsignedInteger('gsc_clicks')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();

        // In-memory user — actingAs() sets the guard directly without DB queries.
        $this->user = new User([
            'firstname' => 'Test',
            'lastname' => 'Admin',
            'email' => 'sitemap-test@test.test',
            'password' => 'hashed',
        ]);
        $this->user->id = 9999;
        $this->user->exists = true;

        // Grant all permissions to the in-memory user without querying Spatie tables.
        Gate::before(fn ($user) => $user->id === 9999 ? true : null);
    }

    protected function tearDown(): void
    {
        // Remove any mock bindings so they don't leak into subsequent tests.
        $this->app->offsetUnset(SitemapBuilder::class);

        $path = public_path('sitemap.xml');
        if (file_exists($path)) {
            @unlink($path);
        }

        try {
            parent::tearDown();
        } catch (\Throwable) {
            // DatabaseTransactions savepoint conflicts are benign in MariaDB
        }
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function test_index_requires_auth(): void
    {
        $this->get(route('setting.seo.sitemap.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_index_returns_view(): void
    {
        $this->actingAs($this->user)
            ->get(route('setting.seo.sitemap.index'))
            ->assertOk()
            ->assertViewIs('Seo::settings.sitemap.index')
            ->assertViewHas('sitemaps')
            ->assertViewHas('history');
    }

    // ── generate ──────────────────────────────────────────────────────────────

    public function test_generate_requires_auth(): void
    {
        $this->post(route('setting.seo.sitemap.generate'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_generate_creates_sitemap_and_records_success(): void
    {
        Http::fake([
            'https://www.google.com/*' => Http::response('', 200),
            'https://www.bing.com/*' => Http::response('', 200),
        ]);

        $this->actingAs($this->user)
            ->post(route('setting.seo.sitemap.generate'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFileExists(public_path('sitemap.xml'));
        $this->assertDatabaseHas('sitemap_generations', ['status' => 'success', 'source' => 'admin']);
    }

    public function test_generate_records_failure_on_exception(): void
    {
        Http::fake();

        $this->app->bind(SitemapBuilder::class, function () {
            $mock = $this->createMock(SitemapBuilder::class);
            $mock->method('clear')->willReturnSelf();
            $mock->method('add')->willReturnSelf();
            $mock->method('addModel')->willReturnSelf();
            $mock->method('generate')->willThrowException(new \RuntimeException('disk full'));
            $mock->method('getItems')->willReturn([]);

            return $mock;
        });

        $this->actingAs($this->user)
            ->post(route('setting.seo.sitemap.generate'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('sitemap_generations', ['status' => 'failed', 'source' => 'admin']);
    }

    // ── clear-cache ───────────────────────────────────────────────────────────

    public function test_clear_cache_requires_auth(): void
    {
        $this->post(route('setting.seo.sitemap.clear-cache'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_clear_cache_removes_sitemap_key(): void
    {
        Cache::put('sitemap-xml', '<xml/>', 3600);

        $this->actingAs($this->user)
            ->post(route('setting.seo.sitemap.clear-cache'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull(Cache::get('sitemap-xml'));
    }

    // ── verify-urls ───────────────────────────────────────────────────────────

    public function test_verify_urls_requires_auth(): void
    {
        $this->getJson(route('setting.seo.sitemap.verify-urls'))
            ->assertUnauthorized();
    }

    public function test_verify_urls_returns_json_structure(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $this->actingAs($this->user)
            ->getJson(route('setting.seo.sitemap.verify-urls'))
            ->assertOk()
            ->assertJsonStructure(['status', 'results', 'summary' => ['total', 'ok', 'broken']]);
    }

    // ── calculate-priorities ──────────────────────────────────────────────────

    public function test_calculate_priorities_requires_auth(): void
    {
        $this->getJson(route('setting.seo.sitemap.calculate-priorities'))
            ->assertUnauthorized();
    }

    public function test_calculate_priorities_returns_priorities_key(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('setting.seo.sitemap.calculate-priorities'))
            ->assertOk()
            ->assertJsonStructure(['priorities']);
    }

    // ── permissions ───────────────────────────────────────────────────────────

    public function test_index_forbidden_without_settings_view(): void
    {
        // Override the gate for this test: explicitly deny the required permission.
        Gate::define('Seo.settings.view', fn () => false);

        $noPermUser = new User(['firstname' => 'No', 'lastname' => 'Perm', 'email' => 'noperm@test.test', 'password' => 'x']);
        $noPermUser->id = 9998;
        $noPermUser->exists = true;

        $this->actingAs($noPermUser)
            ->get(route('setting.seo.sitemap.index'))
            ->assertForbidden();
    }

    public function test_generate_forbidden_without_settings_update(): void
    {
        Gate::define('Seo.settings.update', fn () => false);

        $noPermUser = new User(['firstname' => 'No', 'lastname' => 'Perm', 'email' => 'noperm2@test.test', 'password' => 'x']);
        $noPermUser->id = 9998;
        $noPermUser->exists = true;

        $this->actingAs($noPermUser)
            ->post(route('setting.seo.sitemap.generate'))
            ->assertForbidden();
    }

    public function test_clear_cache_forbidden_without_settings_update(): void
    {
        Gate::define('Seo.settings.update', fn () => false);

        $noPermUser = new User(['firstname' => 'No', 'lastname' => 'Perm', 'email' => 'noperm3@test.test', 'password' => 'x']);
        $noPermUser->id = 9998;
        $noPermUser->exists = true;

        $this->actingAs($noPermUser)
            ->post(route('setting.seo.sitemap.clear-cache'))
            ->assertForbidden();
    }
}
