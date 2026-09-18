<?php

namespace Modules\GiftMessage\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\GiftMessage\Database\Seeders\GiftMessagePermissionsSeeder;
use Modules\GiftMessage\Services\GiftMessageOrderService;
use Tests\TestCase;

class GiftMessageOrderSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GiftMessagePermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('giftmessage.view');
    }

    public function test_search_by_gestion_returns_matching_orders(): void
    {
        $fakeRows = [
            ['id_order' => 100, 'gift_message' => 'Feliz cumple', 'firstname' => 'Ana', 'lastname' => 'Lopez', 'id_gestion' => '73230'],
        ];

        $this->mock(GiftMessageOrderService::class, function ($mock) use ($fakeRows) {
            $mock->shouldReceive('searchByIds')
                ->once()
                ->with(['73230', '73231'])
                ->andReturn($fakeRows);
        });

        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.orders.search'), [
                'ids' => '73230,73231',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'rows' => $fakeRows]);
    }

    public function test_search_accepts_prestashop_order_ids(): void
    {
        $fakeRows = [
            ['id_order' => 833253, 'gift_message' => 'Feliz comunion', 'firstname' => 'Jorge', 'lastname' => 'Da Silva', 'id_gestion' => '102204020'],
        ];

        $this->mock(GiftMessageOrderService::class, function ($mock) use ($fakeRows) {
            $mock->shouldReceive('searchByIds')
                ->once()
                ->with(['833253'])
                ->andReturn($fakeRows);
        });

        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.orders.search'), [
                'ids' => '833253',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'rows' => $fakeRows]);
    }

    public function test_search_accepts_gestion_and_order_ids_mixed(): void
    {
        $this->mock(GiftMessageOrderService::class, function ($mock) {
            $mock->shouldReceive('searchByIds')
                ->once()
                ->with(['102204020', '833248'])
                ->andReturn([]);
        });

        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.orders.search'), [
                'ids' => ' 102204020 , 833248 , abc , ',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'rows' => []]);
    }

    public function test_search_requires_ids(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.orders.search'), [])
            ->assertStatus(422);
    }
}
