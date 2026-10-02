<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Events\SocialCommentReplied;
use Modules\HelpdeskSocial\Exceptions\SocialReplyException;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialAuditLog;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Services\SocialCommentReplyService;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialCommentReplyServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function makeComment(): SocialComment
    {
        $account = SocialAccount::factory()->create(['page_access_token' => 'tok']);

        return SocialComment::factory()->create([
            'social_account_id' => $account->id,
            'platform' => 'facebook',
            'status' => 'pending',
        ]);
    }

    public function test_replies_once_audits_and_dispatches_event(): void
    {
        Event::fake([SocialCommentReplied::class]);
        $client = Mockery::mock(SocialApiClientInterface::class);
        $client->shouldReceive('replyToComment')->once()->andReturn('reply_1');
        $this->app->instance(SocialApiClientInterface::class, $client);

        $comment = $this->makeComment();
        $service = app(SocialCommentReplyService::class);

        $service->reply($comment, 'Hola', null);

        $this->assertSame('replied', $comment->fresh()->status);
        $this->assertSame(1, SocialAuditLog::where('action', 'reply')->where('auditable_id', $comment->id)->count());
        Event::assertDispatched(SocialCommentReplied::class);

        try {
            $service->reply($comment, 'Otra vez', null);
            $this->fail('Second reply should be rejected.');
        } catch (SocialReplyException $e) {
            $this->assertSame(422, $e->status);
        }
    }

    public function test_rejects_when_lock_is_held(): void
    {
        $comment = $this->makeComment();
        $lock = Cache::lock("social-reply:{$comment->id}", 30);
        $lock->get();

        try {
            app(SocialCommentReplyService::class)->reply($comment, 'Hola', null);
            $this->fail('Expected lock rejection.');
        } catch (SocialReplyException $e) {
            $this->assertSame(409, $e->status);
        } finally {
            $lock->release();
        }
    }

    public function test_returns_422_when_account_was_soft_deleted(): void
    {
        $comment = $this->makeComment();
        $comment->socialAccount->delete();

        try {
            app(SocialCommentReplyService::class)->reply($comment->fresh(), 'Hola', null);
            $this->fail('Expected exception.');
        } catch (SocialReplyException $e) {
            $this->assertSame(422, $e->status);
        }
    }
}
