<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Listeners;

use Modules\Helpdesk\Events\ConversationMarkedAsSpam;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Listeners\AddSpamSenderToBlacklist;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

class AddSpamSenderToBlacklistTest extends TestCase
{
    use SharesHelpdeskPdo;

    private AddSpamSenderToBlacklist $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new AddSpamSenderToBlacklist;
    }

    public function test_adds_the_conversation_customer_email_to_the_blacklist(): void
    {
        $customer = Customer::firstOrCreate(['email' => 'spammer@example.com'], ['name' => 'Spammer']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->listener->handle(new ConversationMarkedAsSpam($conversation, 42));

        $rule = TicketEmailBlacklist::where('value', 'spammer@example.com')->first();

        $this->assertNotNull($rule);
        $this->assertSame('email', $rule->type);
        $this->assertTrue($rule->is_active);
        $this->assertSame(42, $rule->added_by);
        $this->assertStringContainsString((string) $conversation->id, $rule->reason);
    }

    public function test_does_not_duplicate_an_existing_rule(): void
    {
        TicketEmailBlacklist::create(['type' => 'email', 'value' => 'already-blocked@example.com']);

        $customer = Customer::firstOrCreate(['email' => 'already-blocked@example.com'], ['name' => 'Repeat offender']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->listener->handle(new ConversationMarkedAsSpam($conversation));

        $this->assertSame(
            1,
            TicketEmailBlacklist::where('value', 'already-blocked@example.com')->count()
        );
    }

    public function test_does_nothing_when_customer_has_no_email(): void
    {
        // SharesHelpdeskPdo no envuelve en transacción/rollback — se compara
        // el conteo antes/después en vez de asumir la tabla vacía (otros
        // tests de esta clase dejan filas reales tras de sí).
        $before = TicketEmailBlacklist::count();

        $customer = Customer::factory()->create(['email' => null]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->listener->handle(new ConversationMarkedAsSpam($conversation));

        $this->assertSame($before, TicketEmailBlacklist::count());
    }
}
