<?php

namespace Modules\HelpdeskHelpcenter\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HelpCenterManagerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $superAdmin;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');
        $this->superAdmin->givePermissionTo([
            'helpdesk.helpcenter.view',
            'helpdesk.helpcenter.articles.view',
            'helpdesk.helpcenter.articles.create',
            'helpdesk.helpcenter.articles.update',
            'helpdesk.helpcenter.articles.delete',
            'helpdesk.helpcenter.articles.manage',
            'helpdesk.helpcenter.categories.view',
            'helpdesk.helpcenter.categories.create',
            'helpdesk.helpcenter.categories.update',
            'helpdesk.helpcenter.categories.delete',
        ]);

        // Sin permisos de manage/categorias a proposito (ver
        // test_regular_agent_cannot_view_helpcenter_index y los tests de
        // "sin permiso de manage"): representa un agente que solo puede
        // buscar articulos (ej. para adjuntarlos a una respuesta), no
        // administrar el Centro de Ayuda.
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo('helpdesk.helpcenter.articles.view');
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_manager_routes(): void
    {
        $this->get(route('manager.helpcenter.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_authenticated_super_admin_can_view_helpcenter_index(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('manager.helpcenter.index'))
            ->assertOk()
            ->assertViewIs('helpdeskhelpcenter::helpcenter.categories.index');
    }

    public function test_regular_agent_cannot_view_helpcenter_index(): void
    {
        $this->actingAs($this->agent)
            ->get(route('manager.helpcenter.index'))
            ->assertForbidden();
    }

    // ─── search articles ──────────────────────────────────────────────────────

    public function test_search_articles_returns_json_for_authenticated_user(): void
    {
        HelpCenterArticle::factory()->published()->count(3)->create();

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpcenter.articles.search'))
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_search_articles_filters_by_query(): void
    {
        HelpCenterArticle::factory()->published()->create([
            'title' => 'How to reset your password',
        ]);

        HelpCenterArticle::factory()->published()->create([
            'title' => 'Getting started with the dashboard',
        ]);

        $response = $this->actingAs($this->agent)
            ->getJson(route('manager.helpcenter.articles.search', ['q' => 'reset']))
            ->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsStringIgnoringCase('reset', $data[0]['title']);
    }

    public function test_search_articles_excludes_drafts(): void
    {
        HelpCenterArticle::factory()->draft()->create(['title' => 'Draft article']);
        HelpCenterArticle::factory()->published()->create(['title' => 'Published article']);

        $response = $this->actingAs($this->agent)
            ->getJson(route('manager.helpcenter.articles.search'))
            ->assertOk();

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertFalse($titles->contains('Draft article'));
        $this->assertTrue($titles->contains('Published article'));
    }

    public function test_guest_cannot_access_search_articles(): void
    {
        $this->getJson(route('manager.helpcenter.articles.search'))
            ->assertUnauthorized();
    }

    // ─── api categories ───────────────────────────────────────────────────────

    public function test_api_categories_returns_json(): void
    {
        HelpCenterCategory::factory()->count(2)->create(['is_section' => false, 'parent_id' => null]);

        $this->actingAs($this->superAdmin)
            ->getJson(route('manager.helpcenter.api.categories'))
            ->assertOk()
            ->assertJsonStructure(['categories' => [['id', 'name', 'description', 'is_section']]]);
    }

    public function test_api_categories_only_returns_root_categories(): void
    {
        $category = HelpCenterCategory::factory()->create(['is_section' => false, 'parent_id' => null]);
        HelpCenterCategory::factory()->section($category)->create();

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('manager.helpcenter.api.categories'))
            ->assertOk();

        $this->assertCount(1, $response->json('categories'));
    }

    public function test_guest_cannot_access_api_categories(): void
    {
        $this->getJson(route('manager.helpcenter.api.categories'))
            ->assertUnauthorized();
    }

    // ─── api delete category ──────────────────────────────────────────────────

    public function test_api_delete_category_removes_it(): void
    {
        $category = HelpCenterCategory::factory()->create(['is_section' => false, 'parent_id' => null]);

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('manager.helpcenter.api.categories.delete', $category->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('helpdesk_helpcenter_categories', ['id' => $category->id], 'helpdesk');
    }

    public function test_api_delete_category_with_sections_returns_422(): void
    {
        $category = HelpCenterCategory::factory()->create(['is_section' => false, 'parent_id' => null]);
        HelpCenterCategory::factory()->section($category)->create();

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('manager.helpcenter.api.categories.delete', $category->id))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_user_without_manage_permission_cannot_delete_category(): void
    {
        $category = HelpCenterCategory::factory()->create(['is_section' => false, 'parent_id' => null]);

        $this->actingAs($this->agent)
            ->deleteJson(route('manager.helpcenter.api.categories.delete', $category->id))
            ->assertForbidden();
    }

    // ─── api delete article ───────────────────────────────────────────────────

    public function test_api_delete_article_removes_it(): void
    {
        $article = HelpCenterArticle::factory()->create();

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('manager.helpcenter.api.articles.delete', $article->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('helpdesk_helpcenter_articles', ['id' => $article->id], 'helpdesk');
    }

    public function test_user_without_manage_permission_cannot_delete_article(): void
    {
        $article = HelpCenterArticle::factory()->create();

        $this->actingAs($this->agent)
            ->deleteJson(route('manager.helpcenter.api.articles.delete', $article->id))
            ->assertForbidden();
    }

    public function test_api_delete_nonexistent_article_returns_404(): void
    {
        $this->actingAs($this->superAdmin)
            ->deleteJson(route('manager.helpcenter.api.articles.delete', 999999))
            ->assertNotFound();
    }

    // ─── store article publish state ──────────────────────────────────────────

    public function test_store_article_without_draft_marks_it_published(): void
    {
        $category = HelpCenterCategory::factory()->create(['is_section' => false, 'parent_id' => null]);
        $section = HelpCenterCategory::factory()->section($category)->create();

        $this->actingAs($this->superAdmin)
            ->postJson(route('manager.helpcenter.articles.store'), [
                'title' => 'Publishing test article',
                'body' => 'Body content here.',
                'section_id' => $section->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_helpcenter_articles', [
            'title' => 'Publishing test article',
            'is_published' => 1,
            'draft' => 0,
        ], 'helpdesk');

        $article = HelpCenterArticle::where('title', 'Publishing test article')->first();
        $this->assertNotNull($article->published_at);
    }

    public function test_store_article_with_draft_stays_unpublished(): void
    {
        $category = HelpCenterCategory::factory()->create(['is_section' => false, 'parent_id' => null]);
        $section = HelpCenterCategory::factory()->section($category)->create();

        $this->actingAs($this->superAdmin)
            ->postJson(route('manager.helpcenter.articles.store'), [
                'title' => 'Draft test article',
                'body' => 'Body content here.',
                'section_id' => $section->id,
                'draft' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_helpcenter_articles', [
            'title' => 'Draft test article',
            'is_published' => 0,
            'draft' => 1,
        ], 'helpdesk');

        $article = HelpCenterArticle::where('title', 'Draft test article')->first();
        $this->assertNull($article->published_at);
    }

    public function test_update_article_publishing_sets_published_at(): void
    {
        $category = HelpCenterCategory::factory()->create(['is_section' => false, 'parent_id' => null]);
        $section = HelpCenterCategory::factory()->section($category)->create();
        $article = HelpCenterArticle::factory()->draft()->create();
        $article->categories()->attach($section->id, ['position' => 1]);

        $this->actingAs($this->superAdmin)
            ->postJson(route('manager.helpcenter.articles.update'), [
                'id' => $article->id,
                'title' => $article->title,
                'body' => $article->body,
                'section_id' => $section->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_helpcenter_articles', [
            'id' => $article->id,
            'is_published' => 1,
            'draft' => 0,
        ], 'helpdesk');

        $updated = HelpCenterArticle::find($article->id);
        $this->assertNotNull($updated->published_at);
    }

    public function test_update_article_to_draft_clears_published(): void
    {
        $category = HelpCenterCategory::factory()->create(['is_section' => false, 'parent_id' => null]);
        $section = HelpCenterCategory::factory()->section($category)->create();
        $article = HelpCenterArticle::factory()->published()->create();
        $article->categories()->attach($section->id, ['position' => 1]);

        $this->actingAs($this->superAdmin)
            ->postJson(route('manager.helpcenter.articles.update'), [
                'id' => $article->id,
                'title' => $article->title,
                'body' => $article->body,
                'section_id' => $section->id,
                'draft' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_helpcenter_articles', [
            'id' => $article->id,
            'is_published' => 0,
            'draft' => 1,
        ], 'helpdesk');

        $updated = HelpCenterArticle::find($article->id);
        $this->assertNull($updated->published_at);
    }
}
