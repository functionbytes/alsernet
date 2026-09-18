<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentAssignSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    /**
     * @return array{0: User, 1: Conversation, 2: Document}
     */
    private function scenario(): array
    {
        // La caché de permisos Spatie (Redis) no hace rollback entre tests y queda
        // desincronizada con la BD (DatabaseTransactions); la olvidamos para que
        // findOrCreate/can() lean el estado real.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // CustomerPolicy / sharesInboxWith consultan estos permisos: deben existir
        // o Spatie lanza PermissionDoesNotExist (en prod están sembrados).
        Permission::findOrCreate('helpdesk.manage', 'web');
        Permission::findOrCreate('helpdesk.customers.manage', 'web');
        Permission::findOrCreate('helpdesk.documents.manage', 'web');
        Permission::findOrCreate('helpdesk.customers.view', 'web');

        $inbox = Inbox::create(['name' => 'Inbox A', 'channel_type' => 'web', 'is_active' => true]);

        $agent = User::factory()->create();
        $agent->givePermissionTo(['helpdesk.documents.manage', 'helpdesk.customers.view']);
        AgentInboxCapacity::create([
            'user_id' => $agent->id,
            'inbox_id' => $inbox->id,
            'max_concurrent' => 10,
            'accepts_new' => true,
        ]);

        $customer = Customer::factory()->create();
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1],
        );
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $inbox->id,
            'status_id' => $status->id,
        ]);

        // Expediente objetivo (email/nombre/orden distintos al cliente: es el caso de
        // asignar manualmente un expediente que no auto-coincide).
        $document = new Document;
        $document->uid = (string) Str::uuid();
        $document->customer_email = 'rosa.jimenez.assign@example.test';
        $document->customer_firstname = 'ROSA';
        $document->customer_lastname = 'JIMENEZ';
        $document->order_reference = 'REFASSIGN999';
        $document->save();

        return [$agent, $conversation, $document];
    }

    private function ids(?array $results): array
    {
        return collect($results ?? [])->pluck('id')->all();
    }

    public function test_busqueda_por_correo_encuentra_el_expediente(): void
    {
        [$agent, $conversation, $document] = $this->scenario();

        $response = $this->actingAs($agent)
            ->getJson(route('manager.helpdesk.conversations.documents.search', $conversation).'?q=rosa.jimenez.assign')
            ->assertOk();

        $this->assertContains($document->id, $this->ids($response->json('results')));
    }

    public function test_busqueda_por_apellido_encuentra_el_expediente(): void
    {
        [$agent, $conversation, $document] = $this->scenario();

        $response = $this->actingAs($agent)
            ->getJson(route('manager.helpdesk.conversations.documents.search', $conversation).'?q=JIMENEZ')
            ->assertOk();

        $this->assertContains($document->id, $this->ids($response->json('results')));
    }

    public function test_busqueda_por_referencia_de_orden_encuentra_el_expediente(): void
    {
        [$agent, $conversation, $document] = $this->scenario();

        $response = $this->actingAs($agent)
            ->getJson(route('manager.helpdesk.conversations.documents.search', $conversation).'?q=REFASSIGN999')
            ->assertOk();

        $this->assertContains($document->id, $this->ids($response->json('results')));
    }

    public function test_asignar_vincula_el_expediente_a_la_conversacion(): void
    {
        [$agent, $conversation, $document] = $this->scenario();

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.conversations.documents.link', [$conversation, $document]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame($document->id, (int) ($conversation->fresh()->metadata['document_id'] ?? null));
    }
}
