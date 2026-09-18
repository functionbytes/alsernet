<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\HelpdeskChatFlow\Services\BotMessageDispatcher;
use Modules\HelpdeskChatFlow\Services\ChatFlowHsmDelivery;
use Tests\TestCase;

class BotMessageDispatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Build a dispatcher whose HSM delivery is inert by default (no template),
     * so existing tests exercise the normal text/carousel paths.
     */
    private function dispatcher(OutboundMessageService $outbound, ?ChatFlowHsmDelivery $hsm = null): BotMessageDispatcher
    {
        if ($hsm === null) {
            $hsm = Mockery::mock(ChatFlowHsmDelivery::class);
            $hsm->shouldReceive('shouldUseTemplate')->andReturn(false);
        }

        return new BotMessageDispatcher($outbound, $hsm);
    }

    private function conversation(string $channel = 'whatsapp'): Conversation
    {
        $conversation = new Conversation;
        $conversation->setRawAttributes(['id' => 1, 'channel' => $channel, 'external_sender_id' => '34600111222']);

        return $conversation;
    }

    private function item(string $body, array $metadata = []): ConversationItem
    {
        $item = new ConversationItem;
        $item->setRawAttributes([
            'id' => 5,
            'conversation_id' => 1,
            'body' => $body,
            'metadata' => json_encode($metadata),
        ]);

        return $item;
    }

    public function test_delivers_text_to_supported_external_channel(): void
    {
        $conversation = $this->conversation('whatsapp');
        $item = $this->item("¿Qué necesitas?\n1. Ventas\n2. Soporte");

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->with($conversation)->andReturn(true);
        $outbound->shouldReceive('setTyping')->andReturn(true);
        $outbound->shouldReceive('sendReply')
            ->once()
            ->with($conversation, "¿Qué necesitas?\n1. Ventas\n2. Soporte")
            ->andReturn('wamid.123');

        $this->dispatcher($outbound)->deliver($conversation, $item);
    }

    public function test_sends_native_buttons_when_channel_supports_them(): void
    {
        $conversation = $this->conversation('whatsapp');
        $item = $this->item("¿Qué necesitas?\n1. Ventas\n2. Soporte", [
            'bot_options' => ['Ventas', 'Soporte'],
            'bot_prompt' => '¿Qué necesitas?',
        ]);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(true);
        $outbound->shouldReceive('setTyping')->andReturn(true);
        $outbound->shouldReceive('sendOptions')
            ->once()
            ->with($conversation, '¿Qué necesitas?', ['Ventas', 'Soporte'])
            ->andReturn('wamid.opt');
        // Native buttons delivered → must NOT also send the numbered text.
        $outbound->shouldNotReceive('sendReply');

        $this->dispatcher($outbound)->deliver($conversation, $item);
    }

    public function test_falls_back_to_numbered_text_when_native_buttons_unavailable(): void
    {
        $conversation = $this->conversation('whatsapp');
        $item = $this->item("Elige:\n1. A\n2. B\n3. C\n4. D", [
            'bot_options' => ['A', 'B', 'C', 'D'], // exceeds WhatsApp's 3-button limit
            'bot_prompt' => 'Elige:',
        ]);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(true);
        $outbound->shouldReceive('setTyping')->andReturn(true);
        $outbound->shouldReceive('sendOptions')->once()->andReturn(null); // can't render natively
        $outbound->shouldReceive('sendReply')
            ->once()
            ->with($conversation, "Elige:\n1. A\n2. B\n3. C\n4. D")
            ->andReturn('wamid.txt');

        $this->dispatcher($outbound)->deliver($conversation, $item);
    }

    public function test_sends_carousel_natively_without_text_fallback(): void
    {
        $conversation = $this->conversation('facebook');
        $cards = [
            ['title' => 'Camisa', 'subtitle' => '29,90 €', 'image_url' => 'https://cdn/1.jpg'],
            ['title' => 'Pantalón', 'subtitle' => '39,90 €', 'image_url' => 'https://cdn/2.jpg'],
        ];
        $item = $this->item("Productos\n1. Camisa\n2. Pantalón", ['cards' => $cards]);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(true);
        $outbound->shouldReceive('setTyping')->andReturn(true);
        $outbound->shouldReceive('sendCarousel')->once()->with($conversation, $cards)->andReturn('mid.car');
        // Native carousel delivered (no selection options) → no extra text.
        $outbound->shouldNotReceive('sendReply');

        $this->dispatcher($outbound)->deliver($conversation, $item);
    }

    public function test_carousel_falls_back_to_numbered_text_when_unavailable(): void
    {
        $conversation = $this->conversation('whatsapp');
        $cards = [['title' => 'Camisa'], ['title' => 'Pantalón']];
        $item = $this->item("Productos\n1. Camisa\n2. Pantalón", ['cards' => $cards]);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(true);
        $outbound->shouldReceive('setTyping')->andReturn(true);
        $outbound->shouldReceive('sendCarousel')->once()->andReturn(null); // channel can't render
        $outbound->shouldReceive('sendReply')
            ->once()
            ->with($conversation, "Productos\n1. Camisa\n2. Pantalón")
            ->andReturn('wamid.txt');

        $this->dispatcher($outbound)->deliver($conversation, $item);
    }

    public function test_sends_file_attachment_natively(): void
    {
        $conversation = $this->conversation('whatsapp');
        $item = $this->item('Tu factura', [
            'attachment' => ['url' => 'https://cdn/factura.pdf', 'type' => 'document', 'caption' => 'Tu factura'],
        ]);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(true);
        $outbound->shouldReceive('setTyping')->andReturn(true);
        $outbound->shouldReceive('sendAttachment')
            ->once()
            ->with($conversation, 'document', 'https://cdn/factura.pdf', 'Tu factura')
            ->andReturn('wamid.file');
        $outbound->shouldNotReceive('sendReply');

        $this->dispatcher($outbound)->deliver($conversation, $item);
    }

    public function test_does_nothing_for_web_conversations(): void
    {
        $conversation = $this->conversation('web');
        $item = $this->item('Hola');

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(false);
        $outbound->shouldNotReceive('sendReply');
        $outbound->shouldNotReceive('sendAttachment');

        $this->dispatcher($outbound)->deliver($conversation, $item);
    }

    public function test_sends_image_attachment_then_text_for_rich_message(): void
    {
        $conversation = $this->conversation('instagram');
        $item = $this->item('Oferta especial', ['image_url' => 'https://cdn/x.jpg']);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(true);
        $outbound->shouldReceive('setTyping')->andReturn(true);
        $outbound->shouldReceive('sendAttachment')
            ->once()
            ->with($conversation, 'image', 'https://cdn/x.jpg')
            ->andReturn('mid.1');
        $outbound->shouldReceive('sendReply')
            ->once()
            ->with($conversation, 'Oferta especial')
            ->andReturn('mid.2');

        $this->dispatcher($outbound)->deliver($conversation, $item);
    }

    public function test_does_not_resend_raw_image_url_as_text(): void
    {
        $conversation = $this->conversation('facebook');
        // rich_message with only an image: body equals the image URL.
        $item = $this->item('https://cdn/x.jpg', ['image_url' => 'https://cdn/x.jpg']);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(true);
        $outbound->shouldReceive('setTyping')->andReturn(true);
        $outbound->shouldReceive('sendAttachment')->once()->andReturn('mid.1');
        $outbound->shouldNotReceive('sendReply');

        $this->dispatcher($outbound)->deliver($conversation, $item);
    }

    public function test_sends_whatsapp_hsm_template_for_outbound_outside_window(): void
    {
        $conversation = $this->conversation('whatsapp');
        $item = $this->item('Tu pedido va en camino', [
            'whatsapp_template' => 'order_update',
            'template_vars' => ['Ada', '123'],
        ]);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(true);
        // Template path → no typing indicator and no free-text send.
        $outbound->shouldNotReceive('setTyping');
        $outbound->shouldNotReceive('sendReply');

        $hsm = Mockery::mock(ChatFlowHsmDelivery::class);
        $hsm->shouldReceive('shouldUseTemplate')->once()->with($conversation, Mockery::type('array'))->andReturn(true);
        $hsm->shouldReceive('send')
            ->once()
            ->with($conversation, Mockery::on(fn ($m) => ($m['whatsapp_template'] ?? null) === 'order_update'))
            ->andReturn('wamid.hsm');

        $this->dispatcher($outbound, $hsm)->deliver($conversation, $item);
    }

    public function test_throws_when_hsm_send_fails_instead_of_sending_free_text(): void
    {
        $conversation = $this->conversation('whatsapp');
        $item = $this->item('Hola de nuevo', ['whatsapp_template' => 'order_update']);

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(true);
        // Outside the 24h window a free-text send is rejected by WhatsApp, so the
        // HSM template is the only valid route: on failure we must NOT fall back.
        $outbound->shouldNotReceive('setTyping');
        $outbound->shouldNotReceive('sendReply');

        $hsm = Mockery::mock(ChatFlowHsmDelivery::class);
        $hsm->shouldReceive('shouldUseTemplate')->once()->andReturn(true);
        $hsm->shouldReceive('send')->once()->andReturn(null); // template send failed

        // Throwing lets DeliverBotMessageJob retry the template delivery.
        $this->expectException(\RuntimeException::class);

        $this->dispatcher($outbound, $hsm)->deliver($conversation, $item);
    }

    public function test_skips_empty_body_without_image(): void
    {
        $conversation = $this->conversation('whatsapp');
        $item = $this->item('   ');

        $outbound = Mockery::mock(OutboundMessageService::class);
        $outbound->shouldReceive('supports')->once()->andReturn(true);
        $outbound->shouldReceive('setTyping')->andReturn(true);
        $outbound->shouldNotReceive('sendReply');

        $this->dispatcher($outbound)->deliver($conversation, $item);
    }
}
