<?php

namespace Modules\Helpdesk\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Exceptions\WhatsAppHsmException;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\WhatsAppHsmService;
use Tests\TestCase;

/**
 * Unit coverage for WhatsAppHsmService — confirms it reads the REAL config keys
 * (helpdesk.integrations.whatsapp.*) and sends through the Graph API when
 * configured, only falling back to a mock response when credentials are absent.
 *
 * No DB: customer is attached as an in-memory relation; HTTP is faked.
 */
class WhatsAppHsmServiceTest extends TestCase
{
    private function makeConversation(?string $phone = '+34666123456'): Conversation
    {
        $customer = new Customer;
        $customer->forceFill(['whatsapp_phone' => $phone]);

        $conversation = new Conversation;
        $conversation->forceFill(['id' => 1]);
        $conversation->setRelation('customer', $customer);

        return $conversation;
    }

    private function configureWhatsApp(): void
    {
        config([
            'helpdesk.integrations.whatsapp.access_token' => 'test-token',
            'helpdesk.integrations.whatsapp.phone_number_id' => '123456789',
            'helpdesk.integrations.whatsapp.api_url' => 'https://graph.facebook.com/v19.0',
        ]);
    }

    public function test_mocks_when_credentials_missing(): void
    {
        config([
            'helpdesk.integrations.whatsapp.access_token' => null,
            'helpdesk.integrations.whatsapp.phone_number_id' => null,
        ]);
        Http::fake();

        $result = (new WhatsAppHsmService)->send($this->makeConversation(), 'welcome', []);

        $this->assertTrue($result['mocked']);
        $this->assertStringStartsWith('wa-mock-', $result['id']);
        Http::assertNothingSent();
    }

    public function test_sends_via_graph_api_when_configured(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.HBgABC']],
            ], 200),
        ]);

        $result = (new WhatsAppHsmService)->send(
            $this->makeConversation(),
            'order_update',
            ['Juan', '12345'],
        );

        $this->assertSame('wamid.HBgABC', $result['id']);
        $this->assertSame('sent', $result['status']);
        $this->assertArrayNotHasKey('mocked', $result);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://graph.facebook.com/v19.0/123456789/messages'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['template']['name'] === 'order_update'
                && $request['to'] === '+34666123456';
        });
    }

    public function test_throws_when_customer_has_no_phone(): void
    {
        $this->configureWhatsApp();
        Http::fake();

        $this->expectException(WhatsAppHsmException::class);

        (new WhatsAppHsmService)->send($this->makeConversation(phone: null), 'welcome', []);
    }

    public function test_throws_on_api_error(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Invalid template', 'code' => 132000],
            ], 400),
        ]);

        $this->expectException(WhatsAppHsmException::class);
        $this->expectExceptionMessage('Invalid template');

        (new WhatsAppHsmService)->send($this->makeConversation(), 'bad_template', []);
    }
}
