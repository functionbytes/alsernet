<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Commands;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialMention;
use Modules\HelpdeskSocial\Tests\TestCase;

class PruneSocialContentCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_replied_comment_older_than_retention_is_deleted(): void
    {
        config()->set('helpdesksocial.comment_retention_days', 180);

        $old = $this->makeComment('replied', now()->subDays(200));

        $this->artisan('helpdesksocial:prune')->assertSuccessful();

        $this->assertDatabaseMissing('helpdesk_social_comments', ['id' => $old->id], 'helpdesk');
    }

    public function test_pending_comment_older_than_retention_is_kept(): void
    {
        config()->set('helpdesksocial.comment_retention_days', 180);

        $pending = $this->makeComment('pending', now()->subDays(200));

        $this->artisan('helpdesksocial:prune')->assertSuccessful();

        $this->assertDatabaseHas('helpdesk_social_comments', ['id' => $pending->id], 'helpdesk');
    }

    public function test_replied_comment_within_retention_is_kept(): void
    {
        config()->set('helpdesksocial.comment_retention_days', 180);

        $recent = $this->makeComment('replied', now()->subDays(10));

        $this->artisan('helpdesksocial:prune')->assertSuccessful();

        $this->assertDatabaseHas('helpdesk_social_comments', ['id' => $recent->id], 'helpdesk');
    }

    public function test_processed_mention_older_than_retention_is_deleted(): void
    {
        config()->set('helpdesksocial.comment_retention_days', 180);

        $old = SocialMention::factory()->create(['status' => 'replied', 'created_at' => now()->subDays(200)]);

        $this->artisan('helpdesksocial:prune')->assertSuccessful();

        $this->assertDatabaseMissing('helpdesk_social_mentions', ['id' => $old->id], 'helpdesk');
    }

    public function test_new_mention_older_than_retention_is_kept(): void
    {
        config()->set('helpdesksocial.comment_retention_days', 180);

        $new = SocialMention::factory()->create(['status' => 'new', 'created_at' => now()->subDays(200)]);

        $this->artisan('helpdesksocial:prune')->assertSuccessful();

        $this->assertDatabaseHas('helpdesk_social_mentions', ['id' => $new->id], 'helpdesk');
    }

    /**
     * `status` is not mass-assignable on SocialComment (only mutated via
     * forceFill in markAsReplied/markAsSpam/markAsEscalated), so both it and
     * `created_at` are set with a direct update on the `helpdesk` connection.
     */
    private function makeComment(string $status, Carbon $createdAt): SocialComment
    {
        $comment = SocialComment::factory()->create();

        DB::connection('helpdesk')->table('helpdesk_social_comments')
            ->where('id', $comment->id)
            ->update(['status' => $status, 'created_at' => $createdAt]);

        return $comment->fresh();
    }
}
