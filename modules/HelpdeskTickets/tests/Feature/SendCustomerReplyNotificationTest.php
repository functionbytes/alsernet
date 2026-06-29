<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Listeners\SendCustomerReplyNotification;
use Modules\HelpdeskTickets\Models\TicketItem;
use Tests\TestCase;

class SendCustomerReplyNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_listener_implements_should_queue(): void
    {
        $this->assertInstanceOf(
            ShouldQueue::class,
            new SendCustomerReplyNotification
        );
    }

    public function test_event_accepts_ticket_item(): void
    {
        $item = new TicketItem;
        $item->id = 1;

        $event = new MessageAdded($item);

        $this->assertInstanceOf(TicketItem::class, $event->item);
        $this->assertEquals(1, $event->item->id);
    }

    public function test_listener_skips_internal_messages(): void
    {
        Mail::fake();

        $item = new TicketItem;
        $item->is_internal = true;
        $item->user_id = 1;

        $listener = new SendCustomerReplyNotification;
        $listener->handle(new MessageAdded($item));

        Mail::assertNothingSent();
    }

    public function test_listener_skips_customer_messages_where_user_id_is_null(): void
    {
        Mail::fake();

        $item = new TicketItem;
        $item->is_internal = false;
        $item->user_id = null;

        $listener = new SendCustomerReplyNotification;
        $listener->handle(new MessageAdded($item));

        Mail::assertNothingSent();
    }

    public function test_message_added_event_stores_item_reference(): void
    {
        $item = new TicketItem;
        $item->id = 42;
        $item->is_internal = false;

        $event = new MessageAdded($item);

        $this->assertSame($item, $event->item);
        $this->assertEquals(42, $event->item->id);
    }
}
