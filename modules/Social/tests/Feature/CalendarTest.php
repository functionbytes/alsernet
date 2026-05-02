<?php

namespace Modules\Social\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function it_can_view_calendar_index(): void
    {
        $response = $this->get(route('admin.social.calendar.index'));

        $response->assertStatus(200);
        $response->assertViewIs('social::calendar.index');
    }

    /** @test */
    public function it_returns_calendar_events_as_json(): void
    {
        Post::factory()->count(5)->create();

        $response = $this->get(route('admin.social.calendar.events'));

        $response->assertStatus(200);
        $response->assertJsonCount(5);
    }

    /** @test */
    public function it_can_update_post_scheduled_date_via_drag_drop(): void
    {
        $post = Post::factory()->scheduled()->create([
            'scheduled_at' => now()->addDays(1),
        ]);

        $newDate = now()->addDays(3);

        $response = $this->putJson(route('admin.social.calendar.update-event', $post), [
            'start' => $newDate->toIso8601String(),
            'all_day' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);

        $post->refresh();
        $this->assertEquals($newDate->startOfMinute(), $post->scheduled_at->startOfMinute());
    }

    /** @test */
    public function it_cannot_reschedule_published_posts(): void
    {
        $post = Post::factory()->published()->create();

        $newDate = now()->addDays(3);

        $response = $this->putJson(route('admin.social.calendar.update-event', $post), [
            'start' => $newDate->toIso8601String(),
            'all_day' => false,
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['success' => false]);
    }

    /** @test */
    public function it_can_perform_quick_actions_on_posts(): void
    {
        $post = Post::factory()->draft()->create();

        $response = $this->postJson(route('admin.social.calendar.quick-action', $post), [
            'action' => 'duplicate',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);

        // Check duplicate was created
        $this->assertEquals(2, Post::where('content', $post->content)->count());
    }

    /** @test */
    public function it_can_delete_post_via_quick_action(): void
    {
        $post = Post::factory()->draft()->create();

        $response = $this->postJson(route('admin.social.calendar.quick-action', $post), [
            'action' => 'delete',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);

        $this->assertDatabaseMissing('social_posts', [
            'id' => $post->id,
        ]);
    }

    /** @test */
    public function it_filters_events_by_social_account(): void
    {
        $account1 = SocialAccount::factory()->create();
        $account2 = SocialAccount::factory()->create();

        Post::factory()->count(3)->create(['social_account_id' => $account1->id]);
        Post::factory()->count(2)->create(['social_account_id' => $account2->id]);

        $response = $this->get(route('admin.social.calendar.events', [
            'social_account_id' => $account1->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    /** @test */
    public function it_filters_events_by_status(): void
    {
        Post::factory()->count(3)->scheduled()->create();
        Post::factory()->count(2)->published()->create();

        $response = $this->get(route('admin.social.calendar.events', [
            'status' => ['scheduled'],
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }
}
