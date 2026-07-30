<?php

namespace Modules\HelpdeskSla\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskSla\Models\ConversationSlaBreach;
use Tests\TestCase;

class PruneResolvedSlaBreachesCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_resolved_breach_older_than_retention_is_deleted(): void
    {
        config()->set('helpdesksla.breach_retention_days', 365);

        $old = $this->makeBreach(resolved: true, resolvedAt: now()->subDays(400));

        $this->artisan('helpdesksla:prune-breaches')->assertSuccessful();

        $this->assertDatabaseMissing('helpdesk_conversation_sla_breaches', ['id' => $old->id], 'helpdesk');
    }

    public function test_resolved_breach_within_retention_is_kept(): void
    {
        config()->set('helpdesksla.breach_retention_days', 365);

        $recent = $this->makeBreach(resolved: true, resolvedAt: now()->subDays(10));

        $this->artisan('helpdesksla:prune-breaches')->assertSuccessful();

        $this->assertDatabaseHas('helpdesk_conversation_sla_breaches', ['id' => $recent->id], 'helpdesk');
    }

    public function test_unresolved_old_breach_is_kept(): void
    {
        config()->set('helpdesksla.breach_retention_days', 365);

        $unresolved = $this->makeBreach(resolved: false, resolvedAt: null, breachedAt: now()->subDays(400));

        $this->artisan('helpdesksla:prune-breaches')->assertSuccessful();

        $this->assertDatabaseHas('helpdesk_conversation_sla_breaches', ['id' => $unresolved->id], 'helpdesk');
    }

    private function makeBreach(bool $resolved, ?Carbon $resolvedAt, ?Carbon $breachedAt = null): ConversationSlaBreach
    {
        $conversation = Conversation::factory()->create();

        return ConversationSlaBreach::create([
            'conversation_id' => $conversation->id,
            'sla_type' => ConversationSlaBreach::TYPE_FIRST_RESPONSE,
            'breached_at' => $breachedAt ?? now()->subDay(),
            'resolved' => $resolved,
            'resolved_at' => $resolvedAt,
        ]);
    }
}
