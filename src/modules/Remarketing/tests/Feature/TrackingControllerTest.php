<?php

namespace Modules\Remarketing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Store;
use Tests\TestCase;

class TrackingControllerTest extends TestCase
{
    use DatabaseTransactions;

    private Store $store;

    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_messages')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $user = User::factory()->create();

        $this->store = Store::query()->create([
            'user_id' => $user->id,
            'platform' => 'manual',
            'name' => 'Test Store',
            'domain' => 'test-tracking.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ]);

        $this->campaign = Campaign::query()->create([
            'store_id' => $this->store->id,
            'name' => 'Test Campaign',
            'subject' => 'Test',
            'from_name' => 'Tester',
            'from_email' => 'test@example.com',
            'status' => 'sending',
            'settings' => [],
        ]);
    }

    // ─── open pixel ───────────────────────────────────────────────────────────

    public function test_open_returns_gif_pixel(): void
    {
        $message = $this->createMessage();

        $this->get(route('remarketing.public.open', $message->id).'?t='.$message->open_token)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif');
    }

    public function test_open_records_opened_at_on_first_call(): void
    {
        $message = $this->createMessage(['status' => 'sent']);

        $this->get(route('remarketing.public.open', $message->id).'?t='.$message->open_token);

        $message->refresh();
        $this->assertNotNull($message->opened_at);
        $this->assertEquals('opened', $message->status);
    }

    public function test_open_with_wrong_token_does_not_record(): void
    {
        $message = $this->createMessage(['status' => 'sent']);

        $this->get(route('remarketing.public.open', $message->id).'?t=wrongtoken');

        $message->refresh();
        $this->assertNull($message->opened_at);
        $this->assertEquals('sent', $message->status);
    }

    public function test_open_with_unknown_message_id_still_returns_gif(): void
    {
        $this->get(route('remarketing.public.open', 99999999))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif');
    }

    // ─── click redirect ───────────────────────────────────────────────────────

    public function test_click_with_valid_hmac_redirects_to_destination(): void
    {
        $message = $this->createMessage();
        $destination = 'https://example.com/product/123';
        $hash = substr(hash_hmac('sha256', $destination, $message->click_token), 0, 16);
        $encoded = base64_encode($destination);

        $this->get(route('remarketing.public.click', [$message->id, $hash]).'?u='.$encoded)
            ->assertRedirect($destination);
    }

    public function test_click_records_clicked_at_on_success(): void
    {
        $message = $this->createMessage(['status' => 'sent']);
        $destination = 'https://example.com/product/1';
        $hash = substr(hash_hmac('sha256', $destination, $message->click_token), 0, 16);
        $encoded = base64_encode($destination);

        $this->get(route('remarketing.public.click', [$message->id, $hash]).'?u='.$encoded);

        $message->refresh();
        $this->assertNotNull($message->clicked_at);
        $this->assertEquals('clicked', $message->status);
    }

    public function test_click_with_wrong_hmac_redirects_to_root(): void
    {
        $message = $this->createMessage();
        $encoded = base64_encode('https://example.com/product/1');

        $this->get(route('remarketing.public.click', [$message->id, 'badhash0badhash0']).'?u='.$encoded)
            ->assertRedirect('/');
    }

    public function test_click_with_unknown_message_id_redirects_to_root(): void
    {
        $encoded = base64_encode('https://example.com/product/1');

        $this->get(route('remarketing.public.click', [99999999, 'somehash']).'?u='.$encoded)
            ->assertRedirect('/');
    }

    public function test_click_without_destination_param_redirects_to_root(): void
    {
        $message = $this->createMessage();

        $this->get(route('remarketing.public.click', [$message->id, 'somehash']))
            ->assertRedirect('/');
    }

    public function test_click_with_non_url_destination_redirects_to_root(): void
    {
        $message = $this->createMessage();
        $encoded = base64_encode('javascript:alert(1)');
        $hash = substr(hash_hmac('sha256', 'javascript:alert(1)', $message->click_token), 0, 16);

        $this->get(route('remarketing.public.click', [$message->id, $hash]).'?u='.$encoded)
            ->assertRedirect('/');
    }

    private function createMessage(array $attributes = []): Message
    {
        return Message::query()->create(array_merge([
            'store_id' => $this->store->id,
            'campaign_id' => $this->campaign->id,
            'email' => 'test@example.com',
            'subject' => 'Test',
            'status' => 'sent',
            'is_holdout' => false,
            'open_token' => Str::random(64),
            'click_token' => Str::random(64),
        ], $attributes));
    }
}
