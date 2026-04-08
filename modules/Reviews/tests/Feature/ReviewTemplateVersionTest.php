<?php

namespace Modules\Reviews\Tests\Feature;

use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Models\ReviewTemplateVersion;
use Modules\Reviews\Tests\TestCase;

/**
 * Tests for template versioning via ReviewTemplateController.
 *
 * Update route:   PUT  /settings/reviews/templates/{template}
 * Versions route: GET  /settings/reviews/templates/{template}/versions
 *
 * The form request (StoreReplyTemplateRequest) authorizes via reviews.templates.create.
 * The versions endpoint is authorized via the ReviewReplyTemplatePolicy view check,
 * which requires reviews.templates.view AND template->created_by === user->id.
 */
class ReviewTemplateVersionTest extends TestCase
{
    // =========================================================================
    // PUT /settings/reviews/templates/{template} — version creation
    // =========================================================================

    public function test_updating_template_creates_version(): void
    {
        $user = $this->createUser(['reviews.templates.create']);

        $template = ReviewReplyTemplate::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->put(route('settings.reviews.templates.update', $template), [
                'name' => 'Updated name',
                'body' => 'Updated body content',
                'category' => 'general',
                'language' => 'es',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('review_template_versions', [
            'review_reply_template_id' => $template->id,
            'version_number' => 1,
        ]);
    }

    // =========================================================================
    // Version number increments on successive updates
    // =========================================================================

    public function test_version_number_increments(): void
    {
        $user = $this->createUser(['reviews.templates.create']);

        $template = ReviewReplyTemplate::factory()->create(['created_by' => $user->id]);

        $payload = [
            'name' => 'Template',
            'body' => 'First update',
            'category' => 'general',
            'language' => 'es',
            'is_active' => true,
        ];

        $this->actingAs($user)->put(route('settings.reviews.templates.update', $template), $payload)->assertRedirect();
        $this->actingAs($user)->put(route('settings.reviews.templates.update', $template), array_merge($payload, ['body' => 'Second update']))->assertRedirect();

        $this->assertEquals(2, ReviewTemplateVersion::where('review_reply_template_id', $template->id)->max('version_number'));
    }

    // =========================================================================
    // GET /settings/reviews/templates/{template}/versions
    // =========================================================================

    public function test_user_can_view_template_versions(): void
    {
        $user = $this->createUser(['reviews.templates.view']);

        $template = ReviewReplyTemplate::factory()->create(['created_by' => $user->id]);

        ReviewTemplateVersion::create([
            'review_reply_template_id' => $template->id,
            'content' => 'Version one',
            'language' => 'es',
            'version_number' => 1,
            'created_by' => $user->id,
        ]);

        ReviewTemplateVersion::create([
            'review_reply_template_id' => $template->id,
            'content' => 'Version two',
            'language' => 'es',
            'version_number' => 2,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('settings.reviews.templates.versions', $template));

        $response->assertOk();

        $this->assertCount(2, $response->json('data'));
    }

    // =========================================================================
    // GET /settings/reviews/templates/{template}/versions — unauthenticated
    // =========================================================================

    public function test_unauthorized_user_cannot_view_versions(): void
    {
        $owner = $this->createUser(['reviews.templates.view']);
        $template = ReviewReplyTemplate::factory()->create(['created_by' => $owner->id]);

        $response = $this->getJson(route('settings.reviews.templates.versions', $template));

        $response->assertUnauthorized();
    }
}
