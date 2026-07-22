<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Http\Requests\StorePsRecommendationRequest;
use Tests\TestCase;

/**
 * La validación de `store` se movió de inline a StorePsRecommendationRequest.
 * La autorización replica la policy `view` de la conversación route-bound.
 */
class PsRecommendationTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        // La policy view() de la conversación consulta 'helpdesk.conversations.view';
        // sin sembrarlo, Spatie lanza PermissionDoesNotExist en vez de denegar.
        $this->seed(PermissionsSeeder::class);
    }

    public function test_rules_require_product_id_and_name(): void
    {
        $rules = (new StorePsRecommendationRequest)->rules();

        $this->assertTrue(Validator::make([], $rules)->fails(), 'Sin datos debe fallar.');
        $this->assertTrue(Validator::make(['product_id' => 5], $rules)->fails(), 'Sin product_name debe fallar.');
        $this->assertFalse(
            Validator::make(['product_id' => 5, 'product_name' => 'Zapatillas'], $rules)->fails(),
            'Con producto y nombre debe pasar.'
        );
    }

    public function test_store_is_forbidden_without_conversation_view_authorization(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $user = User::factory()->create(); // sin permisos

        $this->actingAs($user)
            ->postJson(
                route('manager.helpdesk.conversations.ps.recommendations.store', $conversation->id),
                ['product_id' => 5, 'product_name' => 'Zapatillas']
            )
            ->assertForbidden();
    }
}
