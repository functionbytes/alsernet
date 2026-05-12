<?php

namespace Modules\HelpdeskHelpcenter\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\HelpdeskHelpcenter\Database\Seeders\HelpdeskHelpcenterPermissionsSeeder;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticleTranslation;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HelpCenterTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $superAdmin;

    private User $superNoTranslate;

    private HelpCenterArticle $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->seed(HelpdeskHelpcenterPermissionsSeeder::class);

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');
        $this->superAdmin->givePermissionTo([
            'helpdesk.helpcenter.view',
            'helpdesk.helpcenter.articles.view',
            'helpdesk.helpcenter.articles.update',
            'helpdesk.helpcenter.articles.translate',
        ]);

        // User with view-only permissions and no role: cannot translate.
        $this->superNoTranslate = User::factory()->create();
        $this->superNoTranslate->givePermissionTo([
            'helpdesk.helpcenter.view',
            'helpdesk.helpcenter.articles.view',
        ]);

        $this->article = HelpCenterArticle::factory()->published()->create();
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_super_admin_can_view_translations_index(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('manager.helpcenter.articles.translations.index', $this->article))
            ->assertOk()
            ->assertViewIs('helpdeskhelpcenter::settings.translations.index');
    }

    // ─── store translation ────────────────────────────────────────────────────

    public function test_super_admin_can_store_translation(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('manager.helpcenter.articles.translations.update', [$this->article, 'en']), [
                'title' => 'English title',
                'slug' => 'english-title',
                'body' => 'English body content.',
                'is_published' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_helpcenter_article_translations', [
            'article_id' => $this->article->id,
            'locale' => 'en',
            'title' => 'English title',
        ], 'helpdesk');
    }

    public function test_user_without_translate_permission_cannot_store_translation(): void
    {
        $this->actingAs($this->superNoTranslate)
            ->post(route('manager.helpcenter.articles.translations.update', [$this->article, 'en']), [
                'title' => 'English title',
                'slug' => 'english-title',
                'body' => 'English body content.',
            ])
            ->assertForbidden();
    }

    // ─── delete translation ───────────────────────────────────────────────────

    public function test_super_admin_can_delete_translation(): void
    {
        HelpCenterArticleTranslation::create([
            'article_id' => $this->article->id,
            'locale' => 'en',
            'title' => 'English title',
            'slug' => 'english-title',
            'body' => 'English body content.',
            'is_published' => false,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('manager.helpcenter.articles.translations.destroy', [$this->article, 'en']))
            ->assertRedirect();

        $this->assertDatabaseMissing('helpdesk_helpcenter_article_translations', [
            'article_id' => $this->article->id,
            'locale' => 'en',
        ], 'helpdesk');
    }
}
