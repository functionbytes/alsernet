<?php

namespace Modules\HelpdeskSocial\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialInboxApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('helpdesksocial.view');
    }

    public function test_can_list_inbox_comments(): void
    {
        $account = SocialAccount::factory()->create();
        SocialComment::factory()->count(5)->create(['social_account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/helpdesk/social/inbox');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_inbox_by_status(): void
    {
        $account = SocialAccount::factory()->create();
        SocialComment::factory()->create(['social_account_id' => $account->id, 'status' => 'pending']);
        SocialComment::factory()->create(['social_account_id' => $account->id, 'status' => 'replied']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/helpdesk/social/inbox?status=pending');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_mark_comment_as_spam(): void
    {
        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create(['social_account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/helpdesk/social/inbox/{$comment->id}/spam");

        $response->assertOk();
        $this->assertTrue($comment->fresh()->is_spam);
    }

    public function test_can_get_inbox_stats(): void
    {
        $account = SocialAccount::factory()->create();
        SocialComment::factory()->count(3)->create(['social_account_id' => $account->id]);
        SocialComment::factory()->create(['social_account_id' => $account->id, 'status' => 'replied']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/helpdesk/social/inbox/stats');

        $response->assertOk()
            ->assertJsonPath('total', 4);
    }
}
