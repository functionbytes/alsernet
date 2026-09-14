<?php

namespace Modules\Helpdesk\Tests\Feature\Outbound;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Jobs\DownloadConversationAttachmentsJob;
use Modules\Helpdesk\Jobs\SendOutboundMessageJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;
use Tests\TestCase;

class MetaResilienceTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    // ─── 1. Tope de tamaño en la descarga de media (anti-OOM) ──────────────────

    public function test_media_download_rechaza_ficheros_que_superan_el_maximo(): void
    {
        Storage::fake('public');
        config([
            'helpdesk.attachments.disk' => 'public',
            'helpdesk.attachments.max_download_bytes' => 100,
        ]);
        // Cuerpo de 1000 bytes con un máximo de 100 → debe rechazarse.
        Http::fake(['*' => Http::response(str_repeat('x', 1000), 200)]);

        $customer = Customer::factory()->create();
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1],
        );
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'facebook',
            'status_id' => $status->id,
        ]);
        $item = ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'author_id' => $customer->id,
            'type' => 'message',
            'attachment_urls' => null,
        ]);

        (new DownloadConversationAttachmentsJob(
            $item->id,
            [['type' => 'image', 'original_url' => 'https://8.8.8.8/enorme.jpg']],
            'facebook',
        ))->handle();

        // No se almacenó ningún fichero ni se reescribió el adjunto del ítem.
        $this->assertCount(0, Storage::disk('public')->allFiles());
        $this->assertEmpty($item->fresh()->attachment_urls ?? []);
    }

    // ─── 2. Rate-limit de envío a Meta ─────────────────────────────────────────

    public function test_send_outbound_job_lleva_rate_limit(): void
    {
        $job = new SendOutboundMessageJob(1, 1, 'Hola');

        $middleware = $job->middleware();

        $this->assertNotEmpty($middleware);
        $this->assertInstanceOf(RateLimited::class, $middleware[0]);
        $this->assertGreaterThan(now(), $job->retryUntil());
    }

    // ─── 3. Detección de token de Meta caducado (code 190) ─────────────────────

    public function test_whatsapp_marca_token_invalido_ante_error_190(): void
    {
        Cache::forget('helpdesk:meta:token-invalid:whatsapp');
        config([
            'helpdesk.integrations.whatsapp.enabled' => true,
            'helpdesk.integrations.whatsapp.access_token' => 'token',
            'helpdesk.integrations.whatsapp.phone_number_id' => '12345',
            'helpdesk.integrations.whatsapp.api_url' => 'https://graph.facebook.com/v19.0',
        ]);
        Http::fake([
            '*' => Http::response(['error' => ['code' => 190, 'type' => 'OAuthException', 'message' => 'expired']], 401),
        ]);

        $result = (new WhatsAppBusinessService)->sendText('5211234567890', 'Hola');

        $this->assertNull($result);
        $this->assertTrue(Cache::has('helpdesk:meta:token-invalid:whatsapp'));
    }

    public function test_facebook_marca_token_invalido_ante_error_190(): void
    {
        Cache::forget('helpdesk:meta:token-invalid:facebook-messenger');
        config([
            'helpdesk.integrations.facebook.enabled' => true,
            'helpdesk.integrations.facebook.page_access_token' => 'token',
            'helpdesk.integrations.facebook.app_secret' => 'secret',
        ]);
        Http::fake([
            '*' => Http::response(['error' => ['code' => 190, 'type' => 'OAuthException', 'message' => 'expired']], 401),
        ]);

        $result = (new FacebookMessengerService)->sendText('psid_123', 'Hola');

        $this->assertNull($result);
        $this->assertTrue(Cache::has('helpdesk:meta:token-invalid:facebook-messenger'));
    }
}
