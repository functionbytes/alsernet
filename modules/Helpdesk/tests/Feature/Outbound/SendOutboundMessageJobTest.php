<?php

namespace Modules\Helpdesk\Tests\Feature\Outbound;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Modules\Helpdesk\Jobs\SendOutboundMessageJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\OutboundMessageService;
use Tests\TestCase;

class SendOutboundMessageJobTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private function makeConversation(): Conversation
    {
        $customer = Customer::factory()->create();
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1],
        );

        return Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'external_sender_id' => '5211234567890',
            'status_id' => $status->id,
        ]);
    }

    public function test_no_reenvia_el_texto_si_el_item_ya_tiene_external_id(): void
    {
        $conversation = $this->makeConversation();
        $item = ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => 1,
            'type' => 'message',
            'body' => 'Hola',
            'external_id' => 'wamid.ya_enviado',
        ]);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->andReturnTrue();
        $outbound->shouldReceive('sendReply')->never();
        $outbound->shouldReceive('sendAttachment')->never();

        (new SendOutboundMessageJob($conversation->id, $item->id, 'Hola'))->handle($outbound);

        $this->assertSame('wamid.ya_enviado', $item->fresh()->external_id);
    }

    public function test_envia_el_texto_y_persiste_external_id_cuando_no_estaba_enviado(): void
    {
        $conversation = $this->makeConversation();
        $item = ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => 1,
            'type' => 'message',
            'body' => 'Hola',
            'external_id' => null,
        ]);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->andReturnTrue();
        $outbound->shouldReceive('sendReply')->once()->andReturn('wamid.nuevo');

        (new SendOutboundMessageJob($conversation->id, $item->id, 'Hola'))->handle($outbound);

        $this->assertSame('wamid.nuevo', $item->fresh()->external_id);
    }

    public function test_marca_send_failed_cuando_el_envio_falla(): void
    {
        $conversation = $this->makeConversation();
        $item = ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => 1,
            'type' => 'message',
            'body' => 'Hola',
            'external_id' => null,
        ]);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->andReturnTrue();
        $outbound->shouldReceive('sendReply')->once()->andReturnNull();

        (new SendOutboundMessageJob($conversation->id, $item->id, 'Hola'))->handle($outbound);

        $fresh = $item->fresh();
        $this->assertTrue((bool) ($fresh->metadata['send_failed'] ?? false));
        $this->assertNull($fresh->external_id);
    }
}
