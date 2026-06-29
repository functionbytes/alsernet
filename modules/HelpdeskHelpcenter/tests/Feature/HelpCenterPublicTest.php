<?php

namespace Modules\HelpdeskHelpcenter\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;
use Tests\TestCase;

class HelpCenterPublicTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_public_index_is_accessible(): void
    {
        $this->get(route('public.helpcenter.index'))
            ->assertOk()
            ->assertViewIs('helpdeskhelpcenter::public.helpcenter.index');
    }

    public function test_public_index_shows_published_articles(): void
    {
        HelpCenterArticle::factory()->published()->count(3)->create();
        HelpCenterArticle::factory()->unpublished()->count(2)->create();

        $response = $this->get(route('public.helpcenter.index'))
            ->assertOk();

        $response->assertViewHas('articles', function ($articles) {
            return $articles->total() === 3;
        });
    }

    public function test_public_index_filters_by_search_query(): void
    {
        HelpCenterArticle::factory()->published()->create(['title' => 'How to reset your password']);
        HelpCenterArticle::factory()->published()->create(['title' => 'Getting started guide']);

        $response = $this->get(route('public.helpcenter.index', ['q' => 'reset']))
            ->assertOk();

        $response->assertViewHas('articles', function ($articles) {
            return $articles->total() === 1;
        });
    }

    // ─── search ───────────────────────────────────────────────────────────────

    public function test_public_search_returns_results(): void
    {
        HelpCenterArticle::factory()->published()->create([
            'title' => 'How to configure your account',
        ]);

        $this->getJson(route('public.helpcenter.search', ['q' => 'configure']))
            ->assertOk()
            ->assertJsonStructure(['articles' => [['id', 'title', 'slug', 'description']]])
            ->assertJsonCount(1, 'articles');
    }

    public function test_public_search_returns_empty_for_short_query(): void
    {
        HelpCenterArticle::factory()->published()->count(3)->create();

        $this->getJson(route('public.helpcenter.search', ['q' => 'ab']))
            ->assertOk()
            ->assertJsonCount(0, 'articles');
    }

    public function test_public_search_returns_empty_when_no_matches(): void
    {
        HelpCenterArticle::factory()->published()->create(['title' => 'Getting started with billing']);

        $this->getJson(route('public.helpcenter.search', ['q' => 'nonexistentterminology']))
            ->assertOk()
            ->assertJsonCount(0, 'articles');
    }

    public function test_public_search_excludes_unpublished_articles(): void
    {
        HelpCenterArticle::factory()->unpublished()->create(['title' => 'Secret unpublished article']);

        $this->getJson(route('public.helpcenter.search', ['q' => 'Secret unpublished']))
            ->assertOk()
            ->assertJsonCount(0, 'articles');
    }

    // ─── show ─────────────────────────────────────────────────────────────────

    public function test_public_show_returns_published_article(): void
    {
        $article = HelpCenterArticle::factory()->published()->create();

        $this->get(route('public.helpcenter.show', $article->slug))
            ->assertOk()
            ->assertViewIs('helpdeskhelpcenter::public.helpcenter.show')
            ->assertViewHas('article');
    }

    public function test_public_show_increments_views_count(): void
    {
        $article = HelpCenterArticle::factory()->published()->create(['views_count' => 5]);

        $this->get(route('public.helpcenter.show', $article->slug))
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_helpcenter_articles', [
            'id' => $article->id,
            'views_count' => 6,
        ], 'helpdesk');
    }

    public function test_public_show_returns_404_for_unpublished_article(): void
    {
        $article = HelpCenterArticle::factory()->unpublished()->create();

        $this->get(route('public.helpcenter.show', $article->slug))
            ->assertNotFound();
    }

    public function test_public_show_returns_404_for_nonexistent_slug(): void
    {
        $this->get(route('public.helpcenter.show', 'this-slug-does-not-exist'))
            ->assertNotFound();
    }

    public function test_public_show_returns_related_articles(): void
    {
        $article = HelpCenterArticle::factory()->published()->create();
        HelpCenterArticle::factory()->published()->count(3)->create();

        $response = $this->get(route('public.helpcenter.show', $article->slug))
            ->assertOk();

        $response->assertViewHas('related');
    }

    // ─── categories on public index ───────────────────────────────────────────

    public function test_public_index_passes_categories_to_view(): void
    {
        HelpCenterCategory::factory()->count(2)->create(['is_section' => false, 'parent_id' => null]);

        $response = $this->get(route('public.helpcenter.index'))
            ->assertOk();

        $response->assertViewHas('categories', function ($categories) {
            return $categories->count() === 2;
        });
    }
}
