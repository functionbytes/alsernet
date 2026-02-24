<?php

namespace Modules\Reviews\Tests\Feature;

use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Tests\TestCase;

class ReviewTemplateTest extends TestCase
{
    public function test_user_can_view_templates(): void
    {
        $user = $this->createUser(['reviews.manage-templates']);

        ReviewReplyTemplate::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->get(route('reviews.templates.index'));

        $response->assertOk()
            ->assertViewIs('reviews::templates.index');
    }

    public function test_user_can_create_template(): void
    {
        $user = $this->createUser(['reviews.manage-templates']);

        $response = $this->actingAs($user)
            ->postJson(route('reviews.templates.store'), [
                'name' => 'Thank you template',
                'body' => 'Thank you {reviewer_name} for your {rating}-star review!',
                'category' => 'general',
                'is_active' => true,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('review_reply_templates', [
            'name' => 'Thank you template',
            'body' => 'Thank you {reviewer_name} for your {rating}-star review!',
            'is_active' => true,
        ]);
    }

    public function test_user_can_update_template(): void
    {
        $user = $this->createUser(['reviews.manage-templates']);
        $template = ReviewReplyTemplate::factory()->create([
            'body' => 'Original content',
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('reviews.templates.update', $template), [
                'body' => 'Updated content with {reviewer_name}',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('review_reply_templates', [
            'id' => $template->id,
            'body' => 'Updated content with {reviewer_name}',
        ]);
    }

    public function test_user_can_delete_template(): void
    {
        $user = $this->createUser(['reviews.manage-templates']);
        $template = ReviewReplyTemplate::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson(route('reviews.templates.destroy', $template));

        $response->assertOk();

        $this->assertDatabaseMissing('review_reply_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_template_render_replaces_variables(): void
    {
        $template = ReviewReplyTemplate::factory()->create([
            'body' => 'Hello {reviewer_name}, thanks for {rating} stars at {business_name}!',
        ]);

        $variables = [
            '{reviewer_name}' => 'John',
            '{rating}' => 5,
            '{business_name}' => 'Cafe',
        ];

        $rendered = $template->render($variables);

        $this->assertStringContainsString('Hello John', $rendered);
        $this->assertStringContainsString('thanks for 5 stars', $rendered);
        $this->assertStringContainsString('at Cafe!', $rendered);
    }

    public function test_template_usage_count_increments(): void
    {
        $user = $this->createUser(['reviews.reply']);
        $review = $this->createReview();
        $template = ReviewReplyTemplate::factory()->create([
            'usage_count' => 0,
        ]);

        $service = app(\Modules\Reviews\Services\ReviewReplyService::class);
        $service->createFromTemplate($review, $template, $user);

        $this->assertSame(1, $template->fresh()->usage_count);
    }

    public function test_unauthorized_user_cannot_manage_templates(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->get(route('reviews.templates.index'));

        $response->assertForbidden();
    }

    public function test_template_validation_requires_name_and_body(): void
    {
        $user = $this->createUser(['reviews.manage-templates']);

        $response = $this->actingAs($user)
            ->postJson(route('reviews.templates.store'), [
                'name' => '',
                'body' => '',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'body']);
    }
}
