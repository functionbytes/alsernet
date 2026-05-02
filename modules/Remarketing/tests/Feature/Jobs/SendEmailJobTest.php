<?php

namespace Modules\Remarketing\Tests\Feature\Jobs;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Mailrelay\Exceptions\ProviderException;
use Modules\Mailrelay\Services\ProviderManager;
use Modules\Remarketing\Jobs\SendEmailJob;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Suppression;
use Modules\Remarketing\Models\Template;
use Tests\TestCase;

class SendEmailJobTest extends TestCase
{
    use DatabaseTransactions;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_stores')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->store = $this->createStore();
    }

    public function test_handle_marks_message_suppressed_when_email_in_suppression_list(): void
    {
        $message = $this->createMessage();

        Suppression::query()->create([
            'store_id' => $this->store->id,
            'email' => $message->email,
            'reason' => 'bounce',
        ]);

        $mockProviders = $this->createMock(ProviderManager::class);
        $mockProviders->expects($this->never())->method('sendWithFailover');

        $job = new SendEmailJob($message);
        $job->handle($mockProviders);

        $message->refresh();
        $this->assertEquals('suppressed', $message->status);
    }

    public function test_handle_falls_back_to_laravel_mail_when_provider_fails(): void
    {
        // Mail::html() with a closure cannot be asserted via Mail::assertSent(),
        // but the status update to 'sent' and provider='fallback-laravel-mail'
        // confirms the fallback path was executed.
        $campaign = $this->createCampaign();
        $template = $this->createTemplate('<html><body>Hello {{firstName}}</body></html>');
        $campaign->update(['template_id' => $template->id]);

        $customer = $this->createCustomer();
        $message = $this->createMessage([
            'campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);

        $mockProviders = $this->createMock(ProviderManager::class);
        $mockProviders->method('sendWithFailover')
            ->willThrowException(new ProviderException('All providers failed'));

        // Intercept actual mail sending with a fake mailer
        Mail::fake();

        $job = new SendEmailJob($message);
        $job->handle($mockProviders);

        $message->refresh();
        $this->assertEquals('sent', $message->status);
        $this->assertEquals('fallback-laravel-mail', $message->provider);
        $this->assertNotNull($message->sent_at);
    }

    public function test_handle_marks_message_sent_with_provider_name(): void
    {
        $campaign = $this->createCampaign();
        $template = $this->createTemplate('<html><body>Test</body></html>');
        $campaign->update(['template_id' => $template->id]);

        $message = $this->createMessage(['campaign_id' => $campaign->id]);

        $mockProviders = $this->createMock(ProviderManager::class);
        $mockProviders->method('sendWithFailover')
            ->willReturn(['provider_name' => 'sendgrid', 'message_id' => 'msg-abc-123']);

        $job = new SendEmailJob($message);
        $job->handle($mockProviders);

        $message->refresh();
        $this->assertEquals('sent', $message->status);
        $this->assertEquals('sendgrid', $message->provider);
        $this->assertEquals('msg-abc-123', $message->provider_message_id);
        $this->assertNotNull($message->sent_at);
    }

    public function test_handle_injects_tracking_pixel_in_html(): void
    {
        $campaign = $this->createCampaign();
        $template = $this->createTemplate('<html><body>Content</body></html>');
        $campaign->update(['template_id' => $template->id]);

        $message = $this->createMessage([
            'campaign_id' => $campaign->id,
            'open_token' => 'pixel-token-abc',
        ]);

        $capturedHtml = null;
        $mockProviders = $this->createMock(ProviderManager::class);
        $mockProviders->method('sendWithFailover')
            ->willReturnCallback(function ($to, $subject, $html) use (&$capturedHtml) {
                $capturedHtml = $html;

                return ['provider_name' => 'test', 'message_id' => 'test-id'];
            });

        $job = new SendEmailJob($message);
        $job->handle($mockProviders);

        $this->assertNotNull($capturedHtml);
        $this->assertStringContainsString('/r/open/', $capturedHtml);
        $this->assertStringContainsString('pixel-token-abc', $capturedHtml);
        $this->assertStringContainsString('width="1" height="1"', $capturedHtml);
    }

    public function test_handle_rewrites_anchor_links_to_redirect_endpoint(): void
    {
        $campaign = $this->createCampaign();
        $template = $this->createTemplate('<html><body><a href="https://example.com/product">Buy Now</a></body></html>');
        $campaign->update(['template_id' => $template->id]);

        $message = $this->createMessage([
            'campaign_id' => $campaign->id,
            'click_token' => 'click-token-xyz',
        ]);

        $capturedHtml = null;
        $mockProviders = $this->createMock(ProviderManager::class);
        $mockProviders->method('sendWithFailover')
            ->willReturnCallback(function ($to, $subject, $html) use (&$capturedHtml) {
                $capturedHtml = $html;

                return ['provider_name' => 'test', 'message_id' => 'test-id'];
            });

        $job = new SendEmailJob($message);
        $job->handle($mockProviders);

        $this->assertNotNull($capturedHtml);
        $this->assertStringContainsString('/r/click/', $capturedHtml);
        $this->assertStringNotContainsString('href="https://example.com/product"', $capturedHtml);
    }

    private function createStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'user_id' => User::query()->first()?->id ?? 1,
            'platform' => 'manual',
            'name' => 'Test Store '.uniqid(),
            'domain' => 'test-'.uniqid().'.example.com',
            'status' => 'active',
        ], $attributes));
    }

    private function createCustomer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'store_id' => $this->store->id,
            'email' => 'job-customer-'.uniqid().'@example.com',
            'email_hash' => hash('sha256', uniqid()),
            'status' => 'subscribed',
            'consent_marketing' => true,
        ], $attributes));
    }

    private function createCampaign(array $attributes = []): Campaign
    {
        return Campaign::query()->create(array_merge([
            'store_id' => $this->store->id,
            'name' => 'Test Campaign '.uniqid(),
            'subject' => 'Test Subject',
            'from_name' => 'Sender',
            'from_email' => 'sender@example.com',
            'status' => 'sending',
        ], $attributes));
    }

    private function createTemplate(string $html): Template
    {
        return Template::query()->create([
            'store_id' => $this->store->id,
            'name' => 'Test Template '.uniqid(),
            'type' => 'email',
            'subject' => 'Test',
            'html_content' => $html,
            'is_global' => false,
        ]);
    }

    private function createMessage(array $attributes = []): Message
    {
        $customer = $this->createCustomer();

        return Message::query()->create(array_merge([
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'subject' => 'Test Email',
            'status' => 'queued',
            'open_token' => Str::random(32),
            'click_token' => Str::random(32),
        ], $attributes));
    }
}
