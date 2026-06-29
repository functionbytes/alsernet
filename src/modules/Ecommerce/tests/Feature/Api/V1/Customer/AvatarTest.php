<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Customer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Models\Customer;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_upload_avatar(): void
    {
        Storage::fake('public');
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/v1/me/avatar', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertIsString($response->json('data.avatarUrl'));
    }

    public function test_avatar_upload_rejects_non_image(): void
    {
        Storage::fake('public');
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/v1/me/avatar', ['file' => $file]);

        $response->assertUnprocessable();
    }

    public function test_customer_can_delete_avatar(): void
    {
        Storage::fake('public');
        $customer = Customer::factory()->create(['avatar' => 'ecommerce/avatars/test.jpg']);
        Storage::disk('public')->put('ecommerce/avatars/test.jpg', 'fake-content');
        Sanctum::actingAs($customer, ['*']);

        $response = $this->deleteJson('/api/v1/me/avatar');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($customer->fresh()->avatar);
    }

    public function test_avatar_upload_requires_auth(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('avatar.jpg');

        $this->postJson('/api/v1/me/avatar', ['file' => $file])
            ->assertUnauthorized();
    }

    public function test_avatar_delete_requires_auth(): void
    {
        $this->deleteJson('/api/v1/me/avatar')
            ->assertUnauthorized();
    }
}
