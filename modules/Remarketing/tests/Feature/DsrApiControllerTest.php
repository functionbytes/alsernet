<?php

namespace Modules\Remarketing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Store;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regresión del IDOR cross-tenant en los endpoints DSR (GDPR export/delete).
 *
 * Antes del fix, un usuario con permiso `remarketing.customers.manage` podía
 * exportar/anonimizar el customer de CUALQUIER tienda por su id. Ahora se exige
 * ownership de la tienda (o `remarketing.manage`).
 */
class DsrApiControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_customers') || ! Schema::hasTable('remarketing_stores')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas en la BD de testing.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['remarketing.dsr.export', 'remarketing.dsr.delete', 'remarketing.customers.manage'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
    }

    private function userWithDsrPermissions(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'remarketing.dsr.export',
            'remarketing.dsr.delete',
            'remarketing.customers.manage',
        ]);

        return $user;
    }

    private function makeStore(User $owner): Store
    {
        return Store::create([
            'user_id' => $owner->id,
            'platform' => 'shopify',
            'name' => 'Tienda '.Str::random(6),
            'domain' => Str::lower(Str::random(8)).'.myshopify.com',
            'webhook_token' => Str::random(64),
            'status' => 'active',
        ]);
    }

    private function makeCustomer(Store $store): Customer
    {
        $email = Str::lower(Str::random(8)).'@example.com';

        return Customer::create([
            'store_id' => $store->id,
            'email' => $email,
            'email_hash' => hash('sha256', $email),
            'status' => 'subscribed',
        ]);
    }

    public function test_dsr_export_is_forbidden_for_customer_of_another_store(): void
    {
        $owner = $this->userWithDsrPermissions();
        $attacker = $this->userWithDsrPermissions();

        $customer = $this->makeCustomer($this->makeStore($owner));

        Sanctum::actingAs($attacker);

        $this->postJson(route('api.remarketing.dsr.export'), ['customer_id' => $customer->id])
            ->assertForbidden();
    }

    public function test_dsr_delete_is_forbidden_and_keeps_data_for_another_store(): void
    {
        $owner = $this->userWithDsrPermissions();
        $attacker = $this->userWithDsrPermissions();

        $customer = $this->makeCustomer($this->makeStore($owner));

        Sanctum::actingAs($attacker);

        $this->postJson(route('api.remarketing.dsr.delete'), ['customer_id' => $customer->id])
            ->assertForbidden();

        // El customer ajeno NO fue anonimizado ni borrado.
        $this->assertDatabaseHas('remarketing_customers', [
            'id' => $customer->id,
            'email' => $customer->email,
        ]);
    }

    public function test_dsr_export_is_allowed_for_own_customer(): void
    {
        $owner = $this->userWithDsrPermissions();
        $customer = $this->makeCustomer($this->makeStore($owner));

        Sanctum::actingAs($owner);

        $this->postJson(route('api.remarketing.dsr.export'), ['customer_id' => $customer->id])
            ->assertOk()
            ->assertJsonPath('customer.id', $customer->id);
    }
}
