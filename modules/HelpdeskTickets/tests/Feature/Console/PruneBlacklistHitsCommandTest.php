<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Console;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklistHit;
use Tests\TestCase;

class PruneBlacklistHitsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_deletes_hits_older_than_the_given_days(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'email', 'value' => 'prune-test@spam.com']);

        $old = TicketEmailBlacklistHit::create(['blacklist_id' => $rule->id, 'from_email' => 'prune-test@spam.com']);
        $old->forceFill(['created_at' => now()->subDays(200)])->save();

        $recent = TicketEmailBlacklistHit::create(['blacklist_id' => $rule->id, 'from_email' => 'prune-test@spam.com']);

        $this->artisan('helpdesk:prune-blacklist-hits', ['--days' => 180])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertNotNull(TicketEmailBlacklistHit::find($recent->id));
    }

    public function test_respects_custom_days_option(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'email', 'value' => 'prune-test-2@spam.com']);

        $hit = TicketEmailBlacklistHit::create(['blacklist_id' => $rule->id, 'from_email' => 'prune-test-2@spam.com']);
        $hit->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->artisan('helpdesk:prune-blacklist-hits', ['--days' => 5])->assertSuccessful();

        $this->assertModelMissing($hit);
    }

    public function test_keeps_hits_within_retention_window(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'email', 'value' => 'prune-test-3@spam.com']);

        $hit = TicketEmailBlacklistHit::create(['blacklist_id' => $rule->id, 'from_email' => 'prune-test-3@spam.com']);

        $this->artisan('helpdesk:prune-blacklist-hits', ['--days' => 180])->assertSuccessful();

        $this->assertNotNull(TicketEmailBlacklistHit::find($hit->id));
    }
}
