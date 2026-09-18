<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Services\ChatFlowHsmDelivery;
use Tests\TestCase;

class ChatFlowHsmDeliveryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function conversation(string $channel): Conversation
    {
        $conversation = new Conversation;
        $conversation->setRawAttributes(['id' => 1, 'channel' => $channel]);

        return $conversation;
    }

    public function test_should_not_use_template_without_service(): void
    {
        $delivery = new ChatFlowHsmDelivery(null);

        $this->assertFalse($delivery->shouldUseTemplate(
            $this->conversation('whatsapp'),
            ['whatsapp_template' => 'order_update'],
        ));
    }

    public function test_should_not_use_template_on_non_whatsapp_channel(): void
    {
        $delivery = new ChatFlowHsmDelivery(Mockery::mock());

        $this->assertFalse($delivery->shouldUseTemplate(
            $this->conversation('facebook'),
            ['whatsapp_template' => 'order_update'],
        ));
    }

    public function test_should_not_use_template_when_meta_has_none(): void
    {
        $delivery = new ChatFlowHsmDelivery(Mockery::mock());

        $this->assertFalse($delivery->shouldUseTemplate(
            $this->conversation('whatsapp'),
            ['sent_by_chatflow' => true],
        ));
    }

    public function test_send_delegates_to_service_and_returns_id(): void
    {
        $conversation = $this->conversation('whatsapp');

        $service = Mockery::mock();
        $service->shouldReceive('send')
            ->once()
            ->with($conversation, 'order_update', ['Ada', '123'])
            ->andReturn(['id' => 'wamid.9', 'status' => 'sent']);

        $delivery = new ChatFlowHsmDelivery($service);

        $result = $delivery->send($conversation, [
            'whatsapp_template' => 'order_update',
            'template_vars' => ['Ada', '123'],
        ]);

        $this->assertSame('wamid.9', $result);
    }

    public function test_send_returns_null_when_service_throws(): void
    {
        $conversation = $this->conversation('whatsapp');

        $service = Mockery::mock();
        $service->shouldReceive('send')->once()->andThrow(new \RuntimeException('Meta API down'));

        $delivery = new ChatFlowHsmDelivery($service);

        $this->assertNull($delivery->send($conversation, ['whatsapp_template' => 'order_update']));
    }
}
