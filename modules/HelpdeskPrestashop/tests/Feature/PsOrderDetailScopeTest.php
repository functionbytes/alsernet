<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regresión de seguridad (auditoría): el detalle de pedido PS exigía el email
 * como query OPCIONAL — sin él, cualquier agente con orders.view leía el
 * detalle de cualquier pedido por id (IDOR). Ahora el email es obligatorio y
 * se exige compartir inbox con el cliente. Ambos chequeos cortan antes de
 * llamar al bridge, así que el test no necesita mockearlo.
 */
class PsOrderDetailScopeTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        foreach (['helpdesk.customers.update', 'helpdesk.customers.manage', 'helpdesk.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function test_detail_requires_email(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskprestashop.orders.view', 'helpdesk.customers.manage']);

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.ps.orders.detail', ['order' => 12345]))
            ->assertStatus(422);
    }

    public function test_detail_denies_customer_of_another_inbox(): void
    {
        $inboxA = Inbox::create(['name' => 'A', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
        $inboxB = Inbox::create(['name' => 'B', 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);

        $victim = Customer::factory()->create(['email' => 'ajeno@example.com']);
        Conversation::factory()->create(['customer_id' => $victim->id, 'inbox_id' => $inboxB->id]);

        // Agente restringido: solo orders.view, asignado al inbox A (no comparte
        // inbox con el cliente, que solo tiene conversaciones en el inbox B).
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskprestashop.orders.view');
        AgentInboxCapacity::create(['user_id' => $user->id, 'inbox_id' => $inboxA->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.ps.orders.detail', ['order' => 12345]).'?email=ajeno@example.com')
            ->assertForbidden();
    }
}
