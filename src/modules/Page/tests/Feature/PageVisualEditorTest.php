<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Models\Page;
use Modules\Template\Services\TemplateManager;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageVisualEditorTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'page.update', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('page.update');

        // Stub TemplateManager so preview tests render a minimal view
        // instead of depending on the active theme being fully configured.
        $this->mock(TemplateManager::class, function ($mock) {
            $mock->shouldReceive('getThemeViewPath')
                ->andReturnUsing(fn (string $view) => 'page::pages.partials.ve-preview-stub');
        });
    }

    // =========================================================================
    // Authentication guard
    // =========================================================================

    public function test_guest_cannot_access_visual_editor(): void
    {
        $page = Page::factory()->create();

        $this->get(route('pages.visual-editor', $page))
            ->assertRedirect();
    }

    public function test_guest_cannot_access_visual_preview(): void
    {
        $page = Page::factory()->create();

        $this->get(route('pages.visual-preview', $page))
            ->assertRedirect();
    }

    public function test_guest_cannot_post_visual_preview(): void
    {
        $page = Page::factory()->create();

        $this->post(route('pages.visual-preview.post', $page))
            ->assertRedirect();
    }

    // =========================================================================
    // Authorization — users without page.update are forbidden
    // =========================================================================

    public function test_user_without_permission_cannot_open_visual_editor(): void
    {
        $unauthorised = User::factory()->create();
        $page = Page::factory()->create();

        $this->actingAs($unauthorised)
            ->get(route('pages.visual-editor', $page))
            ->assertForbidden();
    }

    public function test_user_without_permission_cannot_access_visual_preview(): void
    {
        $unauthorised = User::factory()->create();
        $page = Page::factory()->create();

        $this->actingAs($unauthorised)
            ->get(route('pages.visual-preview', $page))
            ->assertForbidden();
    }

    // =========================================================================
    // GET /pages/{page}/visual-editor
    // =========================================================================

    public function test_authenticated_user_can_open_visual_editor(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('pages.visual-editor', $page))
            ->assertOk()
            ->assertViewIs('page::pages.visual-editor');
    }

    public function test_visual_editor_passes_page_to_view(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('pages.visual-editor', $page))
            ->assertOk()
            ->assertViewHas('page')
            ->assertViewHas('previewUrl')
            ->assertViewHas('saveUrl')
            ->assertViewHas('visualPreviewUrl');
    }

    public function test_visual_editor_view_has_correct_save_url(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('pages.visual-editor', $page));

        $response->assertOk();
        $this->assertEquals(
            route('pages.update', $page),
            $response->viewData('saveUrl')
        );
    }

    public function test_visual_editor_view_has_correct_visual_preview_url(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('pages.visual-editor', $page));

        $response->assertOk();
        $this->assertEquals(
            route('pages.visual-preview', $page),
            $response->viewData('visualPreviewUrl')
        );
    }

    // =========================================================================
    // GET /pages/{page}/visual-preview
    // =========================================================================

    public function test_visual_preview_returns_ok_for_authenticated_user(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('pages.visual-preview', $page))
            ->assertOk();
    }

    public function test_visual_preview_has_noindex_header(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('pages.visual-preview', $page))
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_visual_preview_has_no_cache_header(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('pages.visual-preview', $page));

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control') ?? '');
    }

    public function test_visual_preview_injects_postmessage_bridge(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('pages.visual-preview', $page))
            ->assertOk()
            ->assertSee('ve-element-selected', false);
    }

    public function test_visual_preview_contains_page_content(): void
    {
        $page = Page::factory()->create([
            'user_id' => $this->user->id,
            'content' => '<p>Contenido inicial de prueba</p>',
        ]);

        $this->actingAs($this->user)
            ->get(route('pages.visual-preview', $page))
            ->assertOk()
            ->assertSee('Contenido inicial de prueba', false);
    }

    // =========================================================================
    // POST /pages/{page}/visual-preview
    // =========================================================================

    public function test_visual_preview_post_accepts_content_parameter(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('pages.visual-preview.post', $page), [
                'content' => '<p>Nuevo contenido enviado</p>',
            ])
            ->assertOk()
            ->assertSee('Nuevo contenido enviado', false);
    }

    public function test_visual_preview_post_uses_page_content_when_no_content_param(): void
    {
        $page = Page::factory()->create([
            'user_id' => $this->user->id,
            'content' => '<p>Contenido guardado en base de datos</p>',
        ]);

        $this->actingAs($this->user)
            ->post(route('pages.visual-preview.post', $page))
            ->assertOk()
            ->assertSee('Contenido guardado en base de datos', false);
    }

    public function test_visual_preview_post_has_noindex_header(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('pages.visual-preview.post', $page), ['content' => '<p>test</p>'])
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
