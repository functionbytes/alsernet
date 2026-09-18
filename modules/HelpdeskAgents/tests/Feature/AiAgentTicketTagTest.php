<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAgents\Services\AiAgentFlowEngine;
use Modules\HelpdeskAgents\Services\PromptSanitizer;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

/**
 * Regression for a latent bug found via PHPStan: `addTicketTag()` called
 * `$ticket->tags()->syncWithoutDetaching()` but `tags` is a JSON array column
 * (cast array), not a relation — so the AI "add tag" flow action would fatal
 * ("Call to undefined method Ticket::tags()"). Latent because the AI runtime is
 * disabled by default. The fix appends to the array column with dedup.
 */
class AiAgentTicketTagTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function addTag(Ticket $ticket, string $tag): void
    {
        $engine = new AiAgentFlowEngine(new PromptSanitizer);
        $method = (new \ReflectionClass($engine))->getMethod('addTicketTag');
        $method->setAccessible(true);
        $method->invoke($engine, $ticket, ['tag' => $tag]);
    }

    public function test_add_ticket_tag_appends_to_the_json_column(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $ticket = Ticket::factory()->create(['tags' => ['urgent'], 'customer_id' => Customer::factory()->create()->id]);

        $this->addTag($ticket, 'vip');

        $this->assertSame(['urgent', 'vip'], $ticket->fresh()->tags);
    }

    public function test_add_ticket_tag_is_idempotent(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $ticket = Ticket::factory()->create(['tags' => ['vip'], 'customer_id' => Customer::factory()->create()->id]);

        $this->addTag($ticket, 'vip');

        $this->assertSame(['vip'], $ticket->fresh()->tags);
    }
}
