<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Customer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Events\AccountDeleting;
use Modules\Ecommerce\Models\Customer;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_delete_account(): void
    {
        $this->deleteJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_customer_can_request_account_deletion(): void
    {
        Event::fake();

        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->deleteJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_account_deletion_dispatches_account_deleting_event(): void
    {
        Event::fake();

        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $this->deleteJson('/api/v1/me');

        Event::assertDispatched(AccountDeleting::class, function (AccountDeleting $event) use ($customer): bool {
            return $event->customer->id === $customer->id;
        });
    }

    public function test_account_deletion_revokes_all_tokens(): void
    {
        Event::fake();

        $customer = Customer::factory()->create();
        $customer->createToken('test-token');
        $customer->createToken('other-token');

        $this->assertSame(2, $customer->tokens()->count());

        Sanctum::actingAs($customer, ['*']);

        $this->deleteJson('/api/v1/me');

        $this->assertSame(0, $customer->tokens()->count());
    }
}
