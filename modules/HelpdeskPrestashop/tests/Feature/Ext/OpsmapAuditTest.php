<?php

namespace Modules\HelpdeskPrestashop\Tests\Feature\Ext;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Database\Seeders\HelpdeskPrestashopPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Pieza 40 "Auditoría de acciones": toda escritura aceptada contra la tienda
 * desde el panel deja rastro en el log 'helpdeskprestashop' sin tocar los
 * controladores; el vale no se duplica; la pantalla y el CSV exigen
 * helpdeskprestashop.ops.view. El puente se simula con Http::fake.
 */
class OpsmapAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private string $apiUrl = 'https://ps.test/modules/alsernetbridge/api.php';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'helpdeskprestashop.api_url' => $this->apiUrl,
            'helpdeskprestashop.webhook_secret' => 'test-secret-for-hmac',
        ]);

        $this->seed(HelpdeskPrestashopPermissionsSeeder::class);

        foreach (['helpdesk.customers.view', 'helpdesk.customers.update', 'helpdesk.customers.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function agent(string ...$extra): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_merge(['helpdesk.customers.view', 'helpdesk.customers.update', 'helpdesk.customers.manage'], $extra));

        return $user;
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['email' => 'audit-'.uniqid().'@example.com']);
    }

    private function auditFor(User $user): ?Activity
    {
        return Activity::query()
            ->where('log_name', 'helpdeskprestashop')
            ->where('causer_type', $user->getMorphClass())
            ->where('causer_id', $user->id)
            ->latest('id')
            ->first();
    }

    public function test_accepted_order_note_is_audited_with_conversation_and_without_the_note_text(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['added' => true]])]);

        $user = $this->agent('helpdeskprestashop.orders.manage');
        $customer = $this->customer();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($user)
            ->withHeader('X-Ps-Conversation', (string) $conversation->id)
            ->postJson(route('manager.helpdesk.ps.orders.note', [$customer, 829575]), ['note' => 'Texto privado del cliente'])
            ->assertOk();

        $activity = $this->auditFor($user);
        $this->assertNotNull($activity);
        $this->assertSame('ps.orders.note', $activity->description);
        $this->assertSame($customer->id, (int) $activity->subject_id);

        $props = $activity->properties->toArray();
        $this->assertSame($conversation->id, $props['conversation_id']);
        $this->assertSame(829575, (int) $props['params']['order']);
        $this->assertSame(['note'], $props['fields']);
        $this->assertStringNotContainsString('Texto privado', json_encode($props));
    }

    public function test_conversation_of_another_customer_is_not_linked(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['added' => true]])]);

        $user = $this->agent('helpdeskprestashop.orders.manage');
        $customer = $this->customer();
        $foreign = Conversation::factory()->create(['customer_id' => $this->customer()->id]);

        $this->actingAs($user)
            ->withHeader('X-Ps-Conversation', (string) $foreign->id)
            ->postJson(route('manager.helpdesk.ps.orders.note', [$customer, 829575]), ['note' => 'Nota'])
            ->assertOk();

        $this->assertArrayNotHasKey('conversation_id', $this->auditFor($user)->properties->toArray());
    }

    public function test_rejected_or_forbidden_actions_are_not_audited(): void
    {
        Http::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.ps.orders.note', [$this->customer(), 829575]), ['note' => 'Nota'])
            ->assertForbidden();

        $this->assertNull($this->auditFor($user));
    }

    public function test_voucher_is_audited_once(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => [
            'created' => true, 'id' => 9, 'code' => 'GES-ABCDEF12', 'amount' => 10.0, 'date_to' => '2026-10-24 23:59:59',
        ]])]);

        $user = $this->agent('helpdeskprestashop.vouchers.create');

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.customers.ps.vouchers.store', $this->customer()), ['amount' => 10, 'validity_days' => 30, 'reason' => 'retraso'])
            ->assertOk();

        $this->assertSame(1, Activity::query()
            ->where('log_name', 'helpdeskprestashop')
            ->where('causer_id', $user->id)
            ->where('causer_type', $user->getMorphClass())
            ->count());
    }

    public function test_audit_screen_and_export_require_ops_view(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('manager.helpdesk.ps.ext.opsmap.audit'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('manager.helpdesk.ps.ext.opsmap.audit.export'))
            ->assertForbidden();
    }

    public function test_audit_screen_shows_the_action_and_exports_csv(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['added' => true]])]);

        $agent = $this->agent('helpdeskprestashop.orders.manage');
        $customer = $this->customer();

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.ps.orders.note', [$customer, 829575]), ['note' => 'Nota'])
            ->assertOk();

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('helpdeskprestashop.ops.view');

        $this->actingAs($viewer)
            ->get(route('manager.helpdesk.ps.ext.opsmap.audit', ['days' => 7]))
            ->assertOk()
            ->assertSee('Añadió una nota al pedido', false)
            ->assertSee('#829575', false);

        $csv = $this->actingAs($viewer)
            ->get(route('manager.helpdesk.ps.ext.opsmap.audit.export', ['days' => 7]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Añadió una nota al pedido #829575', $csv);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function test_export_neutralizes_formulas_in_customer_names(): void
    {
        Http::fake([$this->apiUrl => Http::response(['ok' => true, 'data' => ['added' => true]])]);

        $agent = $this->agent('helpdeskprestashop.orders.manage');
        $customer = Customer::factory()->create(['name' => '=HYPERLINK("http://x.invalid")', 'email' => 'audit-'.uniqid().'@example.com']);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.ps.orders.note', [$customer, 829575]), ['note' => 'Nota'])
            ->assertOk();

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('helpdeskprestashop.ops.view');

        $csv = $this->actingAs($viewer)
            ->get(route('manager.helpdesk.ps.ext.opsmap.audit.export', ['days' => 7, 'agent' => $agent->id]))
            ->assertOk()
            ->streamedContent();

        // El nombre sale con comilla simple delante: Excel lo muestra como
        // texto en vez de ejecutarlo como fórmula.
        $this->assertStringContainsString(";\"'=HYPERLINK(", $csv);
    }
}
