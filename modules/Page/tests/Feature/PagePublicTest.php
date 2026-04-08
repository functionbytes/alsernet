<?php

namespace Modules\Page\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageTranslation;
use Tests\TestCase;

class PagePublicTest extends TestCase
{
    use RefreshDatabase;

    private function createPublishedPage(string $slug, array $attributes = []): Page
    {
        $page = Page::factory()->published()->create(array_merge(['slug' => $slug], $attributes));

        PageTranslation::create([
            'page_id' => $page->id,
            'locale' => 'es',
            'title' => $page->title,
            'slug' => $slug,
            'status' => PageStatus::Published->value,
            'published_at' => now()->subHour(),
        ]);

        return $page;
    }

    // ========== PUBLISHED PAGES ==========

    public function test_published_page_is_accessible(): void
    {
        $this->createPublishedPage('test-page');

        $response = $this->get('/test-page');

        $response->assertOk();
    }

    public function test_published_page_resolves_by_translation_slug(): void
    {
        $page = Page::factory()->published()->create(['slug' => 'about-us']);

        PageTranslation::create([
            'page_id' => $page->id,
            'locale' => 'es',
            'title' => 'Sobre nosotros',
            'slug' => 'sobre-nosotros',
            'status' => PageStatus::Published->value,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get('/sobre-nosotros');

        $response->assertOk();
    }

    // ========== DRAFT / PENDING PAGES ==========

    public function test_draft_page_returns_404(): void
    {
        Page::factory()->draft()->create(['slug' => 'draft-page']);

        $response = $this->get('/draft-page');

        $response->assertNotFound();
    }

    public function test_page_with_future_published_at_returns_404(): void
    {
        $page = Page::factory()->create([
            'slug' => 'future-page',
            'status' => PageStatus::Published->value,
            'published_at' => now()->addDay(),
        ]);

        PageTranslation::create([
            'page_id' => $page->id,
            'locale' => 'es',
            'title' => 'Future page',
            'slug' => 'future-page',
            'status' => PageStatus::Published->value,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get('/future-page');

        $response->assertNotFound();
    }

    public function test_nonexistent_page_slug_returns_404(): void
    {
        $response = $this->get('/this-slug-does-not-exist-anywhere');

        $response->assertNotFound();
    }

    // ========== HIERARCHICAL SLUG RESOLUTION ==========

    /**
     * Helper: create a published Page with a given slug and optional parent_id.
     * No PageTranslation is created so the hierarchical fallback path is exercised.
     */
    private function createHierarchicalPage(string $slug, ?int $parentId = null): Page
    {
        return Page::factory()->published()->create([
            'slug' => $slug,
            'parent_id' => $parentId,
        ]);
    }

    public function test_child_page_accessible_via_parent_child_slug(): void
    {
        $parent = $this->createHierarchicalPage('servicios');
        $this->createHierarchicalPage('ventanas', $parent->id);

        $response = $this->get('/servicios/ventanas');

        $response->assertOk();
    }

    public function test_orphan_page_not_matched_by_hierarchical_route(): void
    {
        // Child page exists but its parent has been deleted.
        $parent = $this->createHierarchicalPage('old-parent');
        $child = $this->createHierarchicalPage('orphan-child', $parent->id);

        $parent->forceDelete();
        $child->refresh(); // parent_id still set but parent no longer exists

        $response = $this->get('/old-parent/orphan-child');

        $response->assertNotFound();
    }

    public function test_three_level_hierarchy_resolves_correctly(): void
    {
        $grandparent = $this->createHierarchicalPage('productos');
        $parent = $this->createHierarchicalPage('ventanas', $grandparent->id);
        $this->createHierarchicalPage('pvc', $parent->id);

        $response = $this->get('/productos/ventanas/pvc');

        $response->assertOk();
    }

    public function test_wrong_parent_prefix_returns_404(): void
    {
        $realParent = $this->createHierarchicalPage('correct-parent');
        $this->createHierarchicalPage('child-page', $realParent->id);

        $response = $this->get('/wrong-parent/child-page');

        $response->assertNotFound();
    }

    // ========== TEMPLATE ==========

    public function test_homepage_template_page_renders_successfully(): void
    {
        $this->createPublishedPage('inicio-test', ['template' => 'homepage']);

        $response = $this->get('/inicio-test');

        $response->assertOk();
    }

    public function test_full_width_template_page_renders_successfully(): void
    {
        $this->createPublishedPage('full-width-test', ['template' => 'full-width']);

        $response = $this->get('/full-width-test');

        $response->assertOk();
    }
}
