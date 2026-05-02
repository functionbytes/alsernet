<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialInboxWebTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('helpdesksocial.view');
    }

    public function test_authenticated_user_can_reply_to_comment(): void
    {
        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create(['social_account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->post("/panel/helpdesk/social/inbox/{$comment->id}/reply", [
                'body' => 'Gracias por tu mensaje. Te ayudamos en breve.',
            ]);

        $response->assertRedirect(route('helpdesksocial.inbox.index'))
            ->assertSessionHas('success');

        $comment->refresh();
        $this->assertSame('replied', $comment->status);
        $this->assertSame('manual', $comment->reply_type);
        $this->assertSame('Gracias por tu mensaje. Te ayudamos en breve.', $comment->reply_body);
        $this->assertSame($this->user->id, $comment->replied_by_user_id);
    }

    public function test_reply_requires_body(): void
    {
        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create(['social_account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->post("/panel/helpdesk/social/inbox/{$comment->id}/reply", []);

        $response->assertSessionHasErrors(['body']);
    }

    public function test_authenticated_user_can_assign_comment(): void
    {
        $agent = User::factory()->create();
        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create(['social_account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->post("/panel/helpdesk/social/inbox/{$comment->id}/assign", [
                'user_id' => $agent->id,
            ]);

        $response->assertRedirect(route('helpdesksocial.inbox.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_social_comments', [
            'id' => $comment->id,
            'assigned_to_user_id' => $agent->id,
        ]);
    }

    public function test_assign_requires_valid_user_id(): void
    {
        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create(['social_account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->post("/panel/helpdesk/social/inbox/{$comment->id}/assign", [
                'user_id' => 99999,
            ]);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_authenticated_user_can_mark_comment_as_spam(): void
    {
        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create(['social_account_id' => $account->id, 'is_spam' => false]);

        $response = $this->actingAs($this->user)
            ->post("/panel/helpdesk/social/inbox/{$comment->id}/spam");

        $response->assertRedirect(route('helpdesksocial.inbox.index'))
            ->assertSessionHas('success');

        $comment->refresh();
        $this->assertTrue($comment->is_spam);
        $this->assertSame('spam', $comment->status);
    }

    public function test_authenticated_user_can_escalate_comment(): void
    {
        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create(['social_account_id' => $account->id]);

        $response = $this->actingAs($this->user)
            ->post("/panel/helpdesk/social/inbox/{$comment->id}/escalate");

        $response->assertRedirect(route('helpdesksocial.inbox.index'))
            ->assertSessionHas('success');

        $comment->refresh();
        $this->assertSame('escalated', $comment->status);
    }

    public function test_guest_cannot_access_inbox_actions(): void
    {
        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create(['social_account_id' => $account->id]);

        $this->post("/panel/helpdesk/social/inbox/{$comment->id}/reply", ['body' => 'test'])
            ->assertRedirect('/login');
    }
}
