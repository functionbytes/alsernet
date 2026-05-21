<?php

namespace Modules\Remarketing\Tests\Feature\Services;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Jobs\DispatchWebhookJob;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Webhook;
use Modules\Remarketing\Services\WebhookService;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use DatabaseTransactions;

    private WebhookService $service;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_webhooks')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->service = new WebhookService;
        $this->store = $this->createStore();
    }

    public function test_fire_dispatches_job_for_active_matching_webhook(): void
    {
        Queue::fake();

        $webhook = $this->createWebhook(['events' => ['order.created'], 'is_active' => true]);

        $this->service->fire('order.created', $this->store->id, ['order_id' => 1]);

        Queue::assertPushed(DispatchWebhookJob::class, 1);
    }

    public function test_fire_does_not_dispatch_for_inactive_webhook(): void
    {
        Queue::fake();

        $this->createWebhook(['events' => ['order.created'], 'is_active' => false]);

        $this->service->fire('order.created', $this->store->id, ['order_id' => 1]);

        Queue::assertNothingPushed();
    }

    public function test_fire_does_not_dispatch_when_event_does_not_match(): void
    {
        Queue::fake();

        $this->createWebhook(['events' => ['customer.created'], 'is_active' => true]);

        $this->service->fire('order.created', $this->store->id, ['order_id' => 1]);

        Queue::assertNothingPushed();
    }

    public function test_fire_dispatches_for_wildcard_event_webhook(): void
    {
        Queue::fake();

        $this->createWebhook(['events' => ['*'], 'is_active' => true]);

        $this->service->fire('any.event', $this->store->id, []);

        Queue::assertPushed(DispatchWebhookJob::class, 1);
    }

    public function test_fire_dispatches_multiple_matching_webhooks(): void
    {
        Queue::fake();

        $this->createWebhook(['events' => ['campaign.sent'], 'is_active' => true]);
        $this->createWebhook(['events' => ['campaign.sent'], 'is_active' => true]);

        $this->service->fire('campaign.sent', $this->store->id, []);

        Queue::assertPushed(DispatchWebhookJob::class, 2);
    }

    public function test_fire_ignores_webhooks_for_different_store(): void
    {
        Queue::fake();

        $otherStore = $this->createStore();
        Webhook::query()->create([
            'store_id' => $otherStore->id,
            'url' => 'https://other.example.com/hook',
            'events' => ['order.created'],
            'is_active' => true,
        ]);

        $this->service->fire('order.created', $this->store->id, []);

        Queue::assertNothingPushed();
    }

    private function createStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'platform' => 'manual',
            'name' => 'Test Store '.uniqid(),
            'domain' => 'test-'.uniqid().'.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ], $attributes));
    }

    private function createWebhook(array $attributes = []): Webhook
    {
        return Webhook::query()->create(array_merge([
            'store_id' => $this->store->id,
            'url' => 'https://example.com/webhook-'.uniqid(),
            'events' => ['order.created'],
            'is_active' => true,
        ], $attributes));
    }
}
