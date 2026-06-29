<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartCountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_count_endpoint_returns_zero_for_empty(): void
    {
        $response = $this->get('/api/v1/ecommerce/cart/count');

        $response->assertOk();
        $response->assertJsonStructure(['count']);
        $response->assertJson(['count' => 0]);
    }
}
