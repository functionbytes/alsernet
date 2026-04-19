<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Locales\Models\Locale;
use Modules\Page\Models\Page;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageVisualEditorSaveTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'page.update', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('page.update');

        Locale::firstOrCreate(['code' => 'es'], ['name' => 'Español']);
        $this->page = Page::factory()->create(['title' => 'Original', 'slug' => 'original']);
    }

    public function test_save_requires_authentication(): void
    {
        $this->postJson(route('pages.visual-save', $this->page), [
            'locale' => 'es',
            'content' => '<p>Test</p>',
        ])->assertStatus(401);
    }

    public function test_save_updates_translation_and_creates_version(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('pages.visual-save', $this->page), [
            'locale' => 'es',
            'content' => '<p>Contenido nuevo</p>',
            'title' => 'Titulo actualizado',
            'slug' => 'titulo-actualizado',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO desc',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('version.version_number', 1);

        $this->assertDatabaseHas('page_translations', [
            'page_id' => $this->page->id,
            'title' => 'Titulo actualizado',
            'slug' => 'titulo-actualizado',
        ]);
        $this->assertDatabaseHas('page_versions', [
            'page_id' => $this->page->id,
            'version_number' => 1,
            'title' => 'Titulo actualizado',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_save_requires_locale(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('pages.visual-save', $this->page), ['content' => '<p>x</p>'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_save_increments_version_number_on_subsequent_saves(): void
    {
        $payload = ['locale' => 'es', 'content' => '<p>a</p>', 'title' => 'A'];
        $this->actingAs($this->user)->postJson(route('pages.visual-save', $this->page), $payload);
        $this->actingAs($this->user)->postJson(route('pages.visual-save', $this->page), $payload);
        $response = $this->actingAs($this->user)->postJson(route('pages.visual-save', $this->page), $payload);

        $response->assertJsonPath('version.version_number', 3);
        $this->assertSame(3, $this->page->versions()->count());
    }

    public function test_get_draft_returns_empty_response_when_no_draft(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('pages.draft', $this->page))
            ->assertOk()
            ->assertJsonPath('success', false);
    }

    public function test_auto_save_requires_authentication(): void
    {
        $this->patchJson(route('pages.auto-save', $this->page), ['content' => '<p>x</p>'])
            ->assertStatus(401);
    }

    public function test_auto_save_accepts_minimal_payload(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson(route('pages.auto-save', $this->page), [
                'locale' => 'es',
                'content' => '<p>borrador</p>',
            ]);

        $response->assertOk();
    }

    public function test_save_rejects_slug_already_used_by_another_page_in_same_locale(): void
    {
        $other = Page::factory()->create();
        $localeId = Locale::where('code', 'es')->first()->id;
        $other->translations()->create([
            'locale_id' => $localeId,
            'slug' => 'ocupado',
            'title' => 'Otra',
        ]);

        $this->actingAs($this->user)
            ->postJson(route('pages.visual-save', $this->page), [
                'locale' => 'es',
                'slug' => 'ocupado',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_save_allows_same_page_to_keep_its_own_slug(): void
    {
        $localeId = Locale::where('code', 'es')->first()->id;
        $this->page->translations()->create([
            'locale_id' => $localeId,
            'slug' => 'mismo',
            'title' => 'x',
        ]);

        $this->actingAs($this->user)
            ->postJson(route('pages.visual-save', $this->page), [
                'locale' => 'es',
                'slug' => 'mismo',
                'content' => '<p>y</p>',
            ])
            ->assertOk();
    }

    public function test_save_rejects_nonexistent_template(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('pages.visual-save', $this->page), [
                'locale' => 'es',
                'template' => 'this-template-does-not-exist',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['template']);
    }

    public function test_save_prunes_versions_beyond_the_50_cap(): void
    {
        // Seed 50 versions (keep window exactly at the cap)
        for ($i = 1; $i <= 50; $i++) {
            $this->page->versions()->create([
                'version_number' => $i,
                'title' => 'v'.$i,
                'content' => '<p>'.$i.'</p>',
                'user_id' => $this->user->id,
            ]);
        }
        $this->assertSame(50, $this->page->versions()->count());

        // Save → creates version 51 + prunes the oldest.
        $this->actingAs($this->user)->postJson(route('pages.visual-save', $this->page), [
            'locale' => 'es',
            'content' => '<p>new</p>',
            'title' => 'v51',
        ])->assertOk();

        $this->page->refresh();
        $this->assertLessThanOrEqual(50, $this->page->versions()->count());
        $this->assertSame(51, (int) $this->page->versions()->max('version_number'));
        // The oldest (version_number = 1) should have been pruned.
        $this->assertFalse($this->page->versions()->where('version_number', 1)->exists());
    }

    public function test_save_returns_duplicate_flag_when_called_twice_in_quick_succession(): void
    {
        $payload = ['locale' => 'es', 'content' => '<p>x</p>', 'title' => 'A'];

        // Force the lock without releasing — simulates a request that is still
        // mid-flight. Use the same key format as the controller.
        Cache::add("ve:save:lock:{$this->page->id}:{$this->user->id}", 1, 5);

        $res = $this->actingAs($this->user)
            ->postJson(route('pages.visual-save', $this->page), $payload);

        $res->assertOk();
        $res->assertJsonPath('duplicate', true);
        $this->assertSame(0, $this->page->versions()->count());
    }

    public function test_expand_shortcode_returns_cache_hit_on_second_identical_call(): void
    {
        Cache::flush();
        $payload = ['shortcode' => '[button bc_url="#" bc_style="primary"]'];

        $first = $this->actingAs($this->user)
            ->postJson(route('pages.expand-shortcode', $this->page), $payload);
        $first->assertOk();
        $this->assertSame('miss', $first->headers->get('X-VE-Cache'));

        $second = $this->actingAs($this->user)
            ->postJson(route('pages.expand-shortcode', $this->page), $payload);
        $second->assertOk();
        $this->assertSame('hit', $second->headers->get('X-VE-Cache'));
        $this->assertSame($first->json('html'), $second->json('html'));
    }
}
