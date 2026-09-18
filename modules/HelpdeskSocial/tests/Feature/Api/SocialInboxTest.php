<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialInboxTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'helpdesksocial.view',
            'helpdesksocial.accounts.manage',
        ]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_inbox(): void
    {
        SocialComment::factory()->count(5)->create();

        $response = $this->getJson('/api/helpdesk/social/inbox');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_inbox_by_platform(): void
    {
        SocialComment::factory()->count(2)->create(['platform' => 'facebook']);
        SocialComment::factory()->count(3)->create(['platform' => 'instagram']);

        $response = $this->getJson('/api/helpdesk/social/inbox?platform=facebook');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_inbox_by_status(): void
    {
        SocialComment::factory()->count(2)->create(['status' => 'pending']);
        SocialComment::factory()->count(3)->create(['status' => 'replied']);

        $response = $this->getJson('/api/helpdesk/social/inbox?status=pending');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_show_comment(): void
    {
        $comment = SocialComment::factory()->create();

        $response = $this->getJson("/api/helpdesk/social/inbox/{$comment->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $comment->id);
    }

    public function test_can_reply_to_comment(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['id' => 'reply_123']),
        ]);

        $comment = SocialComment::factory()->create(['status' => 'pending']);
        $account = SocialAccount::factory()->create([
            'platform' => $comment->platform,
            'is_active' => true,
            'page_access_token' => 'test_token_123',
        ]);
        $comment->update(['social_account_id' => $account->id]);

        $response = $this->postJson("/api/helpdesk/social/inbox/{$comment->id}/reply", [
            'body' => 'Test reply',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('helpdesk_social_comments', [
            'id' => $comment->id,
            'status' => 'replied',
            'reply_body' => 'Test reply',
        ]);
    }

    public function test_can_mark_comment_as_spam(): void
    {
        $comment = SocialComment::factory()->create();

        $response = $this->postJson("/api/helpdesk/social/inbox/{$comment->id}/spam");

        $response->assertOk();
        $this->assertDatabaseHas('helpdesk_social_comments', [
            'id' => $comment->id,
            'is_spam' => true,
            'status' => 'spam',
        ]);
    }

    public function test_can_escalate_comment(): void
    {
        $comment = SocialComment::factory()->create();

        $response = $this->postJson("/api/helpdesk/social/inbox/{$comment->id}/escalate");

        $response->assertOk();
        $this->assertDatabaseHas('helpdesk_social_comments', [
            'id' => $comment->id,
            'status' => 'escalated',
        ]);
    }

    public function test_can_assign_comment(): void
    {
        $comment = SocialComment::factory()->create();
        $agent = User::factory()->create();

        $response = $this->postJson("/api/helpdesk/social/inbox/{$comment->id}/assign", [
            'user_id' => $agent->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('helpdesk_social_comments', [
            'id' => $comment->id,
            'assigned_to_user_id' => $agent->id,
        ]);
    }

    public function test_can_get_inbox_stats(): void
    {
        SocialComment::factory()->count(3)->create(['status' => 'pending']);
        SocialComment::factory()->count(2)->create(['status' => 'replied']);
        SocialComment::factory()->count(1)->create(['status' => 'escalated', 'is_spam' => true]);

        $response = $this->getJson('/api/helpdesk/social/inbox/stats');

        $response->assertOk()
            ->assertJsonPath('total', 6)
            ->assertJsonPath('pending', 3)
            ->assertJsonPath('replied', 2)
            ->assertJsonPath('escalated', 1);
    }
}
