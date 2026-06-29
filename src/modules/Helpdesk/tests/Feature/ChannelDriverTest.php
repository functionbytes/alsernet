<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Services\Channels\MetaGraphChannelDriver;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Tests\TestCase;

class ChannelDriverTest extends TestCase
{
    public function test_facebook_and_instagram_extend_the_shared_meta_driver(): void
    {
        $this->assertInstanceOf(MetaGraphChannelDriver::class, new FacebookMessengerService);
        $this->assertInstanceOf(MetaGraphChannelDriver::class, new InstagramService);
    }

    public function test_facebook_send_text_posts_to_graph_api(): void
    {
        config([
            'helpdesk.integrations.facebook.enabled' => true,
            'helpdesk.integrations.facebook.page_access_token' => 'fb-token',
            'helpdesk.integrations.facebook.app_secret' => 'fb-secret',
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['message_id' => 'mid.fb'], 200)]);

        $id = (new FacebookMessengerService)->sendText('PSID1', 'Hola');

        $this->assertSame('mid.fb', $id);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com/v25.0/me/messages')
            && $request['recipient']['id'] === 'PSID1'
            && $request['message']['text'] === 'Hola');
    }

    public function test_instagram_send_text_uses_its_own_api_version(): void
    {
        config([
            'helpdesk.integrations.instagram.enabled' => true,
            'helpdesk.integrations.instagram.access_token' => 'ig-token',
            'helpdesk.integrations.instagram.business_account_id' => 'biz-1',
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['message_id' => 'mid.ig'], 200)]);

        $id = (new InstagramService)->sendText('IGSID1', 'Hello');

        $this->assertSame('mid.ig', $id);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com/v19.0/me/messages')
            && $request['recipient']['id'] === 'IGSID1');
    }

    public function test_send_attachment_builds_attachment_envelope(): void
    {
        config([
            'helpdesk.integrations.facebook.enabled' => true,
            'helpdesk.integrations.facebook.page_access_token' => 'fb-token',
            'helpdesk.integrations.facebook.app_secret' => 'fb-secret',
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['message_id' => 'mid.att'], 200)]);

        (new FacebookMessengerService)->sendAttachment('PSID1', 'image', 'https://x/y.jpg');

        Http::assertSent(fn ($request) => $request['message']['attachment']['type'] === 'image'
            && $request['message']['attachment']['payload']['url'] === 'https://x/y.jpg');
    }

    public function test_disabled_channel_does_not_send(): void
    {
        config(['helpdesk.integrations.facebook.enabled' => false]);

        Http::fake();

        $id = (new FacebookMessengerService)->sendText('PSID1', 'Hola');

        $this->assertNull($id);
        Http::assertNothingSent();
    }

    public function test_signature_verification_is_shared(): void
    {
        config(['helpdesk.integrations.facebook.app_secret' => 'shared-secret']);

        $body = '{"object":"page"}';
        $valid = 'sha256='.hash_hmac('sha256', $body, 'shared-secret');

        $fb = new FacebookMessengerService;
        $this->assertTrue($fb->verifySignature($body, $valid));
        $this->assertFalse($fb->verifySignature($body, 'sha256=bad'));
        $this->assertFalse($fb->verifySignature($body, 'md5='.$valid));

        // Instagram comparte el app_secret de Facebook.
        $this->assertTrue((new InstagramService)->verifySignature($body, $valid));
    }
}
