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
        // Las acciones de escritura (reply/spam/escalate/assign/bulk) exigen
        // `manage`; la lectura sigue con `view`. El usuario de estas pruebas es
        // un agente completo, así que recibe ambos.
        $this->user->givePermissionTo(['helpdesksocial.view', 'helpdesksocial.manage']);
    }

    public function test_view_only_user_cannot_write(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('helpdesksocial.view');

        $account = SocialAccount::factory()->create();
        $comment = SocialComment::factory()->create(['social_account_id' => $account->id]);

        // Puede leer…
        $this->actingAs($viewer)->getJson('/api/helpdesk/social/inbox')->assertOk();

        // …pero no marcar spam, escalar ni asignar (antes bastaba `view`).
        $this->actingAs($viewer)
            ->postJson("/api/helpdesk/social/inbox/{$comment->id}/spam")
            ->assertForbidden();
        $this->actingAs($viewer)
            ->postJson("/api/helpdesk/social/inbox/{$comment->id}/escalate")
            ->assertForbidden();
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
