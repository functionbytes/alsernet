<?php

namespace Modules\Seo\Tests\Feature;

use Modules\Seo\Tests\TestCase;

class SeoOrphanTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Authentication guard
    // -------------------------------------------------------------------------

    public function test_orphans_index_requires_auth(): void
    {
        $this->get(route('setting.seo.orphans.index'))
            ->assertRedirect(route('auth.login'));
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_orphans_index_requires_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('setting.seo.orphans.index'))
            ->assertForbidden();
    }

    public function test_orphans_index_returns_view(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->get(route('setting.seo.orphans.index'))
            ->assertOk()
            ->assertViewIs('Seo::settings.orphans.index')
            ->assertViewHas('orphans');
    }

    public function test_orphans_view_data_is_array(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $response = $this->actingAs($user)->get(route('setting.seo.orphans.index'));

        $response->assertOk();
        $this->assertIsArray($response->viewData('orphans'));
    }

    // -------------------------------------------------------------------------
    // Generate (single)
    // -------------------------------------------------------------------------

    public function test_generate_requires_auth(): void
    {
        $this->postJson(route('setting.seo.orphans.generate'), [
            'model_class' => 'App\\Models\\User',
            'model_id' => 1,
        ])->assertUnauthorized();
    }

    public function test_generate_rejects_disallowed_model_class(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->postJson(route('setting.seo.orphans.generate'), [
                'model_class' => 'App\\Models\\User',
                'model_id' => 1,
            ])
            ->assertUnprocessable()
            ->assertJson(['status' => false]);
    }

    public function test_generate_validates_required_fields(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->postJson(route('setting.seo.orphans.generate'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['model_class', 'model_id']);
    }

    // -------------------------------------------------------------------------
    // Bulk generate
    // -------------------------------------------------------------------------

    public function test_bulk_generate_requires_auth(): void
    {
        $this->postJson(route('setting.seo.orphans.bulk-generate'), [
            'model_class' => 'App\\Models\\User',
        ])->assertUnauthorized();
    }

    public function test_bulk_generate_rejects_disallowed_model_class(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->postJson(route('setting.seo.orphans.bulk-generate'), [
                'model_class' => 'App\\Models\\User',
            ])
            ->assertUnprocessable()
            ->assertJson(['status' => false]);
    }

    public function test_bulk_generate_validates_model_class_is_required(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $this->actingAs($user)
            ->postJson(route('setting.seo.orphans.bulk-generate'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['model_class']);
    }
}
