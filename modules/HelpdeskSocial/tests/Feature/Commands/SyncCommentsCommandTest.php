<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskSocial\Jobs\SyncSocialCommentsJob;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Tests\TestCase;

class SyncCommentsCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sync_command_dispatches_jobs_for_active_accounts(): void
    {
        Queue::fake();

        SocialAccount::factory()->count(2)->create(['is_active' => true, 'comments_enabled' => true]);
        SocialAccount::factory()->create(['is_active' => false]);

        $this->artisan('helpdesk-social:sync-comments')
            ->assertSuccessful();

        Queue::assertPushed(SyncSocialCommentsJob::class, 2);
    }

    public function test_sync_command_skips_inactive_accounts(): void
    {
        Queue::fake();

        SocialAccount::factory()->create(['is_active' => false]);

        $this->artisan('helpdesk-social:sync-comments')
            ->assertSuccessful();

        Queue::assertNotPushed(SyncSocialCommentsJob::class);
    }
}
