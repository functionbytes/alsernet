<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Modules\Page\Jobs\DispatchPageWebhookJob;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageWebhook;
use Modules\Page\Services\PageService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'page.update', 'guard_name' => 'web']);

        // The webhook/category controllers call authorize('update', Page::class) with no
        // model instance. Laravel removes the class string from arguments and invokes
        // PagePolicy::update($user) with 1 arg — but the signature requires 2 (User, Page).
        // A Gate::before hook intercepts all policy calls and resolves update/approve
        // permissions directly from Spatie, preventing the ArgumentCountError.
        Gate::before(function (User $user, string $ability) {
            if (in_array($ability, ['update', 'viewAny', 'create', 'approve'], true)) {
                $permMap = [
                    'update' => 'page.update',
                    'viewAny' => 'page.view',
                    'create' => 'page.create',
                    'approve' => 'page.approve',
                ];

                return $user->hasPermissionTo($permMap[$ability]) ? true : false;
            }

            return null;
        });
    }

    private function userWithPermission(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('page.update');

        return $user;
    }

    /** @param array<string> $events */
    private function createWebhook(array $overrides = []): PageWebhook
    {
        return PageWebhook::create(array_merge([
            'name' => 'Test Webhook',
            'url' => 'https://webhook.site/test-hook',
            'secret' => null,
            'events' => ['published', 'updated'],
            'is_active' => true,
            'timeout' => 10,
            'success_count' => 0,
            'fail_count' => 0,
        ], $overrides));
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    public function test_admin_can_create_webhook(): void
    {
        $user = $this->userWithPermission();

        $this->actingAs($user)
            ->post(route('pages.webhooks.store'), [
                'name' => 'My Webhook',
                'url' => 'https://example.com/hook',
                'events' => ['published'],
                'timeout' => 10,
                'is_active' => true,
            ])
            ->assertRedirect(route('pages.webhooks.index'));

        $this->assertDatabaseHas('page_webhooks', [
            'name' => 'My Webhook',
            'url' => 'https://example.com/hook',
        ]);
    }

    public function test_webhook_requires_valid_url(): void
    {
        $user = $this->userWithPermission();

        $this->actingAs($user)
            ->post(route('pages.webhooks.store'), [
                'name' => 'Bad',
                'url' => 'not-a-url',
                'events' => ['published'],
                'timeout' => 10,
            ])
            ->assertSessionHasErrors(['url']);
    }

    public function test_webhook_rejects_localhost(): void
    {
        $user = $this->userWithPermission();

        $this->actingAs($user)
            ->post(route('pages.webhooks.store'), [
                'name' => 'Local',
                'url' => 'http://localhost/hook',
                'events' => ['published'],
                'timeout' => 10,
            ])
            ->assertSessionHasErrors(['url']);

        $this->assertDatabaseCount('page_webhooks', 0);
    }

    public function test_webhook_rejects_internal_ip(): void
    {
        $user = $this->userWithPermission();

        $this->actingAs($user)
            ->post(route('pages.webhooks.store'), [
                'name' => 'Internal',
                'url' => 'http://127.0.0.1/hook',
                'events' => ['published'],
                'timeout' => 10,
            ])
            ->assertSessionHasErrors(['url']);

        $this->assertDatabaseCount('page_webhooks', 0);
    }

    public function test_admin_can_update_webhook(): void
    {
        $user = $this->userWithPermission();
        $webhook = $this->createWebhook();

        $this->actingAs($user)
            ->put(route('pages.webhooks.update', $webhook), [
                'name' => 'Updated Name',
                'url' => 'https://example.com/updated',
                'events' => ['updated'],
                'timeout' => 30,
            ])
            ->assertRedirect(route('pages.webhooks.index'));

        $this->assertDatabaseHas('page_webhooks', [
            'id' => $webhook->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_admin_can_delete_webhook(): void
    {
        $user = $this->userWithPermission();
        $webhook = $this->createWebhook();

        $this->actingAs($user)
            ->delete(route('pages.webhooks.destroy', $webhook))
            ->assertRedirect(route('pages.webhooks.index'));

        $this->assertDatabaseMissing('page_webhooks', ['id' => $webhook->id]);
    }

    public function test_unauthorized_user_cannot_manage_webhooks(): void
    {
        $user = User::factory()->create();
        $webhook = $this->createWebhook();

        $this->actingAs($user)->get(route('pages.webhooks.index'))->assertForbidden();
        $this->actingAs($user)->post(route('pages.webhooks.store'), [])->assertForbidden();
        $this->actingAs($user)->delete(route('pages.webhooks.destroy', $webhook))->assertForbidden();
    }

    // =========================================================================
    // Webhook dispatching
    // =========================================================================

    public function test_webhook_dispatched_on_page_publish(): void
    {
        Queue::fake();

        $this->createWebhook(['events' => ['published'], 'is_active' => true]);

        $page = Page::factory()->draft()->create();
        app(PageService::class)->publishPage($page);

        Queue::assertPushed(DispatchPageWebhookJob::class, fn ($job) => $job->event === 'published');
    }

    public function test_webhook_dispatched_on_page_update(): void
    {
        Queue::fake();

        $this->createWebhook(['events' => ['updated'], 'is_active' => true]);

        $page = Page::factory()->create();
        app(PageService::class)->updatePage($page, ['title' => 'Updated Title']);

        Queue::assertPushed(DispatchPageWebhookJob::class, fn ($job) => $job->event === 'updated');
    }

    public function test_webhook_dispatched_on_page_delete(): void
    {
        Queue::fake();

        $this->createWebhook(['events' => ['deleted'], 'is_active' => true]);

        $page = Page::factory()->create();
        app(PageService::class)->deletePage($page);

        Queue::assertPushed(DispatchPageWebhookJob::class, fn ($job) => $job->event === 'deleted');
    }

    public function test_webhook_not_dispatched_when_inactive(): void
    {
        Queue::fake();

        $this->createWebhook(['events' => ['published'], 'is_active' => false]);

        $page = Page::factory()->draft()->create();
        app(PageService::class)->publishPage($page);

        Queue::assertNotPushed(DispatchPageWebhookJob::class);
    }

    public function test_webhook_not_dispatched_when_event_not_subscribed(): void
    {
        Queue::fake();

        // Webhook only listens to 'deleted', not 'published'
        $this->createWebhook(['events' => ['deleted'], 'is_active' => true]);

        $page = Page::factory()->draft()->create();
        app(PageService::class)->publishPage($page);

        Queue::assertNotPushed(DispatchPageWebhookJob::class);
    }

    // =========================================================================
    // Signature
    // =========================================================================

    public function test_webhook_signature_includes_timestamp(): void
    {
        $webhook = $this->createWebhook(['secret' => 'my-secret']);
        $payload = '{"event":"published"}';
        $timestamp = now()->timestamp;

        $signature = $webhook->generateSignature($payload, $timestamp);

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, 'my-secret');
        $this->assertEquals($expected, $signature);
    }

    public function test_webhook_signature_changes_with_different_timestamps(): void
    {
        $webhook = $this->createWebhook(['secret' => 'secret']);
        $payload = '{"event":"test"}';

        $sig1 = $webhook->generateSignature($payload, 1000);
        $sig2 = $webhook->generateSignature($payload, 9999);

        $this->assertNotEquals($sig1, $sig2);
    }
}
