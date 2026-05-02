<?php

namespace Modules\Social\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Social\Enums\PostStatus;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;
use Tests\TestCase;

class PublishingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user and authenticate
        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function it_can_create_a_draft_post(): void
    {
        $account = SocialAccount::factory()->facebook()->create();

        $response = $this->post(route('admin.social.publishing.store'), [
            'social_account_id' => $account->id,
            'type' => 'text',
            'content' => 'This is a test post',
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('social_posts', [
            'content' => 'This is a test post',
            'status' => PostStatus::DRAFT->value,
        ]);
    }

    /** @test */
    public function it_can_schedule_a_post_for_future(): void
    {
        $account = SocialAccount::factory()->facebook()->create();
        $scheduledTime = now()->addHours(2);

        $response = $this->post(route('admin.social.publishing.store'), [
            'social_account_id' => $account->id,
            'type' => 'text',
            'content' => 'Scheduled post content',
            'status' => 'scheduled',
            'scheduled_at' => $scheduledTime->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('social_posts', [
            'content' => 'Scheduled post content',
            'status' => PostStatus::SCHEDULED->value,
        ]);
    }

    /** @test */
    public function it_can_update_an_existing_post(): void
    {
        $post = Post::factory()->draft()->create();

        $response = $this->put(route('admin.social.publishing.update', $post), [
            'social_account_id' => $post->social_account_id,
            'type' => 'text',
            'content' => 'Updated content',
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('social_posts', [
            'id' => $post->id,
            'content' => 'Updated content',
        ]);
    }

    /** @test */
    public function it_can_delete_a_post(): void
    {
        $post = Post::factory()->draft()->create();

        $response = $this->delete(route('admin.social.publishing.destroy', $post));

        $response->assertRedirect();
        $this->assertDatabaseMissing('social_posts', [
            'id' => $post->id,
        ]);
    }

    /** @test */
    public function it_can_duplicate_a_post(): void
    {
        $original = Post::factory()->published()->create([
            'content' => 'Original post',
        ]);

        $response = $this->post(route('admin.social.publishing.duplicate', $original));

        $response->assertRedirect();

        // Check that a new draft was created with same content
        $this->assertDatabaseHas('social_posts', [
            'content' => 'Original post',
            'status' => PostStatus::DRAFT->value,
        ]);

        // Should have 2 posts with same content
        $this->assertEquals(2, Post::where('content', 'Original post')->count());
    }

    /** @test */
    public function it_shows_validation_errors_for_invalid_post(): void
    {
        $response = $this->post(route('admin.social.publishing.store'), [
            'type' => 'text',
            // Missing required fields
        ]);

        $response->assertSessionHasErrors(['social_account_id', 'content']);
    }

    /** @test */
    public function it_can_view_posts_index(): void
    {
        Post::factory()->count(5)->create();

        $response = $this->get(route('admin.social.publishing.index'));

        $response->assertStatus(200);
        $response->assertViewIs('social::publishing.index');
    }

    /** @test */
    public function it_can_view_post_edit_form(): void
    {
        $post = Post::factory()->draft()->create();

        $response = $this->get(route('admin.social.publishing.edit', $post));

        $response->assertStatus(200);
        $response->assertViewIs('social::publishing.edit');
        $response->assertViewHas('post', $post);
    }
}
