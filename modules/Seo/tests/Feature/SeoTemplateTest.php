<?php

namespace Modules\Seo\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Modules\Seo\Models\SeoTemplate;
use Modules\Seo\Tests\TestCase;

class SeoTemplateTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Authentication guard
    // -------------------------------------------------------------------------

    public function test_template_index_requires_auth(): void
    {
        $this->get(route('settings.seo.templates.index'))
            ->assertRedirect(route('auth.login'));
    }

    // -------------------------------------------------------------------------
    // Index listing
    // -------------------------------------------------------------------------

    public function test_template_index_requires_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('settings.seo.templates.index'))
            ->assertForbidden();
    }

    public function test_template_index_returns_view(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->get(route('settings.seo.templates.index'))
            ->assertOk()
            ->assertViewIs('Seo::settings.templates.index')
            ->assertViewHas(['templates', 'stats']);
    }

    // -------------------------------------------------------------------------
    // Store (create)
    // -------------------------------------------------------------------------

    public function test_store_requires_auth(): void
    {
        $this->post(route('settings.seo.templates.store'), ['name' => 'Test', 'robots' => 'index,follow'])
            ->assertRedirect(route('auth.login'));
    }

    public function test_store_requires_update_permission(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->post(route('settings.seo.templates.store'), ['name' => 'Test', 'robots' => 'index,follow'])
            ->assertForbidden();
    }

    public function test_store_creates_template(): void
    {
        $user = $this->createUser(['Seo.metas.update']);

        $data = [
            'name' => 'Test Template',
            'model_type' => null,
            'title_pattern' => '{title} | {site_name}',
            'description_pattern' => '{description}',
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image',
            'robots' => 'index,follow',
            'is_active' => true,
            'priority' => 10,
        ];

        $this->actingAs($user)
            ->post(route('settings.seo.templates.store'), $data)
            ->assertRedirect(route('settings.seo.templates.index'));

        $this->assertDatabaseHas('seo_templates', ['name' => 'Test Template', 'robots' => 'index,follow']);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->createUser(['Seo.metas.update']);

        $this->actingAs($user)
            ->post(route('settings.seo.templates.store'), [])
            ->assertSessionHasErrors(['name', 'robots']);
    }

    public function test_store_validates_og_type_enum(): void
    {
        $user = $this->createUser(['Seo.metas.update']);

        $this->actingAs($user)
            ->post(route('settings.seo.templates.store'), [
                'name' => 'Template',
                'robots' => 'index,follow',
                'og_type' => 'invalid-type',
            ])
            ->assertSessionHasErrors(['og_type']);
    }

    public function test_store_validates_twitter_card_enum(): void
    {
        $user = $this->createUser(['Seo.metas.update']);

        $this->actingAs($user)
            ->post(route('settings.seo.templates.store'), [
                'name' => 'Template',
                'robots' => 'index,follow',
                'twitter_card' => 'not-valid',
            ])
            ->assertSessionHasErrors(['twitter_card']);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function test_update_requires_auth(): void
    {
        $template = SeoTemplate::create(['name' => 'Old', 'robots' => 'index,follow', 'is_active' => true, 'priority' => 0]);

        $this->put(route('settings.seo.templates.update', $template), ['name' => 'New', 'robots' => 'index,follow'])
            ->assertRedirect(route('auth.login'));
    }

    public function test_update_modifies_template(): void
    {
        $user = $this->createUser(['Seo.metas.update']);

        $template = SeoTemplate::create([
            'name' => 'Old Name',
            'robots' => 'index,follow',
            'is_active' => true,
            'priority' => 0,
        ]);

        $this->actingAs($user)
            ->put(route('settings.seo.templates.update', $template), [
                'name' => 'New Name',
                'robots' => 'noindex',
            ])
            ->assertRedirect(route('settings.seo.templates.index'));

        $this->assertDatabaseHas('seo_templates', [
            'id' => $template->id,
            'name' => 'New Name',
            'robots' => 'noindex',
        ]);
    }

    // -------------------------------------------------------------------------
    // Toggle active
    // -------------------------------------------------------------------------

    public function test_toggle_active_requires_auth(): void
    {
        $template = SeoTemplate::create(['name' => 'T', 'robots' => 'index,follow', 'is_active' => true, 'priority' => 0]);

        $this->patch(route('settings.seo.templates.toggle-active', $template))
            ->assertRedirect(route('auth.login'));
    }

    public function test_toggle_active_switches_status_from_true_to_false(): void
    {
        $user = $this->createUser(['Seo.metas.update']);

        $template = SeoTemplate::create([
            'name' => 'Toggle Test',
            'robots' => 'index,follow',
            'is_active' => true,
            'priority' => 0,
        ]);

        $this->actingAs($user)
            ->patchJson(route('settings.seo.templates.toggle-active', $template))
            ->assertOk()
            ->assertJson(['status' => true, 'is_active' => false]);

        $this->assertFalse($template->fresh()->is_active);
    }

    public function test_toggle_active_switches_status_from_false_to_true(): void
    {
        $user = $this->createUser(['Seo.metas.update']);

        $template = SeoTemplate::create([
            'name' => 'Toggle Off',
            'robots' => 'index,follow',
            'is_active' => false,
            'priority' => 0,
        ]);

        $this->actingAs($user)
            ->patchJson(route('settings.seo.templates.toggle-active', $template))
            ->assertOk()
            ->assertJson(['status' => true, 'is_active' => true]);

        $this->assertTrue($template->fresh()->is_active);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function test_destroy_requires_auth(): void
    {
        $template = SeoTemplate::create(['name' => 'Del', 'robots' => 'index,follow', 'is_active' => true, 'priority' => 0]);

        $this->delete(route('settings.seo.templates.destroy', $template))
            ->assertRedirect(route('auth.login'));
    }

    public function test_destroy_removes_template(): void
    {
        $user = $this->createUser(['Seo.metas.update']);

        $template = SeoTemplate::create([
            'name' => 'Delete Me',
            'robots' => 'index,follow',
            'is_active' => true,
            'priority' => 0,
        ]);

        $this->actingAs($user)
            ->delete(route('settings.seo.templates.destroy', $template))
            ->assertRedirect();

        $this->assertDatabaseMissing('seo_templates', ['id' => $template->id]);
    }

    // -------------------------------------------------------------------------
    // Bulk apply
    // -------------------------------------------------------------------------

    public function test_bulk_apply_returns_error_for_inactive_template(): void
    {
        $user = $this->createUser(['Seo.metas.update']);

        $template = SeoTemplate::create([
            'name' => 'Inactive',
            'robots' => 'index,follow',
            'is_active' => false,
            'priority' => 0,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.seo.templates.bulk-apply', $template))
            ->assertUnprocessable()
            ->assertJson(['status' => false]);
    }

    public function test_bulk_apply_succeeds_for_active_template(): void
    {
        $user = $this->createUser(['Seo.metas.update']);

        $template = SeoTemplate::create([
            'name' => 'Active Template',
            'robots' => 'index,follow',
            'is_active' => true,
            'priority' => 0,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.seo.templates.bulk-apply', $template))
            ->assertOk()
            ->assertJson(['status' => true]);
    }

    // -------------------------------------------------------------------------
    // SeoTemplate::applyToModel unit-style test
    // -------------------------------------------------------------------------

    public function test_apply_to_model_replaces_title_variable(): void
    {
        $template = new SeoTemplate([
            'title_pattern' => '{title} | Test Site',
            'description_pattern' => '{description} - more info',
            'robots' => 'index,follow',
        ]);

        $model = new class extends Model
        {
            public string $title = 'My Page Title';

            public string $description = 'My page description';

            protected $guarded = [];
        };

        $applied = $template->applyToModel($model);

        $this->assertStringContainsString('My Page Title', $applied['title']);
        $this->assertStringContainsString('Test Site', $applied['title']);
        $this->assertStringContainsString('My page description', $applied['description']);
    }

    public function test_apply_to_model_respects_title_length_limit(): void
    {
        $template = new SeoTemplate([
            'title_pattern' => str_repeat('A', 100),
            'robots' => 'index,follow',
        ]);

        $model = new class extends Model
        {
            protected $guarded = [];
        };

        $applied = $template->applyToModel($model);

        $this->assertLessThanOrEqual(60, mb_strlen($applied['title']));
    }

    public function test_apply_to_model_includes_robots_when_set(): void
    {
        $template = new SeoTemplate([
            'robots' => 'noindex,nofollow',
        ]);

        $model = new class extends Model
        {
            protected $guarded = [];
        };

        $applied = $template->applyToModel($model);

        $this->assertArrayHasKey('robots', $applied);
        $this->assertEquals('noindex,nofollow', $applied['robots']);
    }
}
