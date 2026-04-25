<?php

namespace Modules\Ads\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ads\Enums\AdsStatus;
use Modules\Ads\Models\Ads;
use Tests\TestCase;

class AdsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'ads.view',
            'ads.create',
            'ads.update',
            'ads.delete',
        ]);
    }

    public function test_index_route_returns_ok(): void
    {
        $response = $this->actingAs($this->admin)->get(route('ads.index'));

        $response->assertOk();
    }

    public function test_create_route_returns_ok(): void
    {
        $response = $this->actingAs($this->admin)->get(route('ads.create'));

        $response->assertOk();
    }

    public function test_store_creates_ad(): void
    {
        $data = [
            'translations' => [
                ['locale' => 'es', 'name' => 'Test Ad'],
            ],
            'key' => 'test-ad',
            'status' => AdsStatus::PUBLISHED->value,
            'url' => 'https://example.com',
        ];

        $response = $this->actingAs($this->admin)->post(route('ads.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('ads', ['key' => 'test-ad', 'name' => 'Test Ad']);
        $this->assertDatabaseHas('ad_translations', ['locale' => 'es', 'name' => 'Test Ad']);
    }

    public function test_edit_route_returns_ok(): void
    {
        $ad = Ads::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('ads.edit', $ad));

        $response->assertOk();
    }

    public function test_update_modifies_ad(): void
    {
        $ad = Ads::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)->put(route('ads.update', $ad), [
            'translations' => [
                ['locale' => 'es', 'name' => 'New Name'],
            ],
            'key' => $ad->key,
            'status' => $ad->status->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ads', ['id' => $ad->id, 'name' => 'New Name']);
        $this->assertDatabaseHas('ad_translations', ['ad_id' => $ad->id, 'locale' => 'es', 'name' => 'New Name']);
    }

    public function test_destroy_deletes_ad(): void
    {
        $ad = Ads::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('ads.destroy', $ad));

        $response->assertRedirect();
        $this->assertDatabaseMissing('ads', ['id' => $ad->id]);
    }

    public function test_guest_cannot_access_admin_routes(): void
    {
        $response = $this->get(route('ads.index'));

        $response->assertRedirect(route('login'));
    }
}
