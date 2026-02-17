<?php

namespace Modules\Template\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Template\Models\Template;
use Modules\Template\Services\TemplateManager;
use Modules\Template\Services\TemplateService;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Ensure the settings table exists and has a default template value
        DB::table('settings')->updateOrInsert(
            ['key' => 'template'],
            ['value' => 'default']
        );
    }

    // -------------------------------------------------------------------------
    // Authentication guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_is_redirected_to_login_on_index(): void
    {
        $this->get(route('settings.templates.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_create(): void
    {
        $this->get(route('settings.templates.create'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_store(): void
    {
        $this->post(route('settings.templates.store'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_edit(): void
    {
        $template = Template::factory()->create();

        $this->get(route('settings.templates.edit', $template))
            ->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_update(): void
    {
        $template = Template::factory()->create();

        $this->put(route('settings.templates.update', $template))
            ->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_destroy(): void
    {
        $template = Template::factory()->create();

        $this->delete(route('settings.templates.destroy', $template))
            ->assertRedirect(route('auth.login'));
    }

    // -------------------------------------------------------------------------
    // GET /settings/templates (index)
    // -------------------------------------------------------------------------

    public function test_index_returns_ok_for_authenticated_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.templates.index'))
            ->assertOk();
    }

    public function test_index_passes_templates_and_active_template_to_view(): void
    {
        $manager = $this->createMock(TemplateManager::class);
        $manager->method('getTemplates')->willReturn([]);
        $manager->method('getActiveTemplateName')->willReturn('default');

        $this->app->instance(TemplateManager::class, $manager);

        $this->actingAs($this->user)
            ->get(route('settings.templates.index'))
            ->assertOk()
            ->assertViewHas('templates')
            ->assertViewHas('activeTemplate');
    }

    // -------------------------------------------------------------------------
    // GET /settings/templates/create
    // -------------------------------------------------------------------------

    public function test_create_returns_ok_for_authenticated_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.templates.create'))
            ->assertOk();
    }

    public function test_create_view_receives_templates_list(): void
    {
        Template::factory()->count(2)->create();

        $this->actingAs($this->user)
            ->get(route('settings.templates.create'))
            ->assertOk()
            ->assertViewHas('templates');
    }

    // -------------------------------------------------------------------------
    // POST /settings/templates (store)
    // -------------------------------------------------------------------------

    public function test_store_creates_template_and_redirects_to_show(): void
    {
        $service = $this->createMock(TemplateService::class);
        $created = Template::factory()->make(['id' => 99, 'slug' => 'new-template']);
        $service->method('create')->willReturn($created);

        $this->app->instance(TemplateService::class, $service);

        $response = $this->actingAs($this->user)
            ->post(route('settings.templates.store'), [
                'name' => 'New Template',
                'content' => '<p>Content here</p>',
                'status' => 'inactive',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_store_validates_name_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.templates.store'), [
                'content' => '<p>Content here</p>',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_content_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.templates.store'), [
                'name' => 'My Template',
            ])
            ->assertSessionHasErrors(['content']);
    }

    public function test_store_validates_slug_is_unique(): void
    {
        Template::factory()->create(['slug' => 'existing-slug']);

        $this->actingAs($this->user)
            ->post(route('settings.templates.store'), [
                'name' => 'Irrelevant Name',
                'slug' => 'existing-slug',
                'content' => '<p>Content</p>',
            ])
            ->assertSessionHasErrors(['slug']);
    }

    public function test_store_validates_status_must_be_valid(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.templates.store'), [
                'name' => 'My Template',
                'content' => '<p>Content</p>',
                'status' => 'published',
            ])
            ->assertSessionHasErrors(['status']);
    }

    // -------------------------------------------------------------------------
    // GET /settings/templates/{template}/edit
    // -------------------------------------------------------------------------

    public function test_edit_returns_ok_for_existing_template(): void
    {
        $template = Template::factory()->create();

        $this->actingAs($this->user)
            ->get(route('settings.templates.edit', $template))
            ->assertOk()
            ->assertViewHas('template');
    }

    public function test_edit_returns_404_for_nonexistent_template(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.templates.edit', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // PUT /settings/templates/{template} (update)
    // -------------------------------------------------------------------------

    public function test_update_modifies_template_and_redirects_to_show(): void
    {
        $template = Template::factory()->create(['name' => 'Old Name']);

        $service = $this->createMock(TemplateService::class);
        $updated = $template->fresh();
        $service->method('update')->willReturn($updated);

        $this->app->instance(TemplateService::class, $service);

        $response = $this->actingAs($this->user)
            ->put(route('settings.templates.update', $template), [
                'name' => 'Updated Name',
                'content' => '<p>Updated content</p>',
                'status' => 'inactive',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_update_validates_name_is_required(): void
    {
        $template = Template::factory()->create();

        $this->actingAs($this->user)
            ->put(route('settings.templates.update', $template), [
                'content' => '<p>Some content</p>',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_update_validates_content_is_required(): void
    {
        $template = Template::factory()->create();

        $this->actingAs($this->user)
            ->put(route('settings.templates.update', $template), [
                'name' => 'Some Name',
            ])
            ->assertSessionHasErrors(['content']);
    }

    public function test_update_allows_same_slug_for_the_template(): void
    {
        $template = Template::factory()->create(['slug' => 'my-template']);

        $service = $this->createMock(TemplateService::class);
        $service->method('update')->willReturn($template->fresh());

        $this->app->instance(TemplateService::class, $service);

        $this->actingAs($this->user)
            ->put(route('settings.templates.update', $template), [
                'name' => 'My Template',
                'slug' => 'my-template',
                'content' => '<p>Content</p>',
            ])
            ->assertSessionHasNoErrors();
    }

    // -------------------------------------------------------------------------
    // DELETE /settings/templates/{template} (destroy)
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_template_and_redirects_to_index(): void
    {
        $template = Template::factory()->inactive()->create();

        $this->actingAs($this->user)
            ->delete(route('settings.templates.destroy', $template))
            ->assertRedirect(route('settings.templates.index'));

        $this->assertSoftDeleted('templates', ['id' => $template->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_template(): void
    {
        $this->actingAs($this->user)
            ->delete(route('settings.templates.destroy', 99999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // POST /settings/templates/activate (AJAX)
    // -------------------------------------------------------------------------

    public function test_activate_returns_json_success_for_existing_slug(): void
    {
        $template = Template::factory()->inactive()->create();

        $this->actingAs($this->user)
            ->withoutMiddleware('preventDemo')
            ->postJson(route('settings.templates.activate'), [
                'template' => $template->slug,
            ])
            ->assertOk()
            ->assertJson(['error' => false]);
    }

    public function test_activate_returns_422_for_nonexistent_slug(): void
    {
        $this->actingAs($this->user)
            ->withoutMiddleware('preventDemo')
            ->postJson(route('settings.templates.activate'), [
                'template' => 'slug-that-does-not-exist',
            ])
            ->assertStatus(422);
    }

    public function test_activate_requires_template_field(): void
    {
        $this->actingAs($this->user)
            ->withoutMiddleware('preventDemo')
            ->postJson(route('settings.templates.activate'), [])
            ->assertStatus(422);
    }

    public function test_activate_marks_template_as_active_and_others_as_inactive(): void
    {
        $active = Template::factory()->active()->create();
        $target = Template::factory()->inactive()->create();

        $this->actingAs($this->user)
            ->withoutMiddleware('preventDemo')
            ->postJson(route('settings.templates.activate'), [
                'template' => $target->slug,
            ])
            ->assertOk()
            ->assertJson(['error' => false]);

        $this->assertDatabaseHas('templates', ['id' => $target->id, 'status' => 'active']);
        $this->assertDatabaseHas('templates', ['id' => $active->id, 'status' => 'inactive']);
    }

    public function test_unauthenticated_user_cannot_activate_template(): void
    {
        $template = Template::factory()->create();

        $this->postJson(route('settings.templates.activate'), [
            'template' => $template->slug,
        ])->assertUnauthorized();
    }
}
