<?php

namespace Modules\Document\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Guards de las acciones mutadoras del panel Document (endurecimiento de
 * seguridad, jul-2026):
 *  - las acciones send/assign/upload/downloadZip solo estaban tras el gate
 *    genérico view-documents-panel; ahora exigen perfil de gestión/supervisor
 *    o el permiso fino del grupo validador (denyUnlessCanDocument);
 *  - update() dejó de aplicar `data` como mass-assignment sobre todo el
 *    $fillable (status_id, validation_status…) y se limita a los campos de
 *    contacto del cliente.
 */
class DocumentActionAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private function supervisor(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']));

        return $user;
    }

    private function makeDocument(array $attrs = []): Document
    {
        return Document::create(array_merge([
            'customer_email' => 'cliente@example.com',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
        ], $attrs));
    }

    public function test_guest_cannot_call_send_approval(): void
    {
        $document = $this->makeDocument();

        $this->postJson(route('api.documents.send-approval', $document->uid))
            ->assertUnauthorized();
    }

    public function test_authenticated_user_without_document_access_cannot_call_send_approval(): void
    {
        // Sin rol supervisor ni permiso: lo frena el gate de ruta
        // view-documents-panel antes incluso de llegar al guard de acción.
        $document = $this->makeDocument();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.documents.send-approval', $document->uid))
            ->assertForbidden();
    }

    public function test_supervisor_passes_the_action_guard(): void
    {
        // El supervisor es un rol legítimo del panel: debe pasar el guard de
        // acción (getUserProfile no lo mapea a 'manager', así que sin la
        // excepción explícita quedaría bloqueado — regresión que blinda este test).
        $document = $this->makeDocument();

        $response = $this->actingAs($this->supervisor())
            ->postJson(route('api.documents.send-approval', $document->uid));

        // Puede fallar luego por falta de plantilla/estado, pero NUNCA con el
        // 403 "No tienes permiso para realizar esta acción" del guard.
        $this->assertNotSame(
            'No tienes permiso para realizar esta acción.',
            $response->json('message')
        );
    }

    public function test_update_ignores_non_whitelisted_fields(): void
    {
        Permission::firstOrCreate(['name' => 'helpdesk.documents.manage', 'guard_name' => 'web']);
        $pending = DocumentStatus::firstOrCreate(['key' => 'pending'], ['label' => 'Pendiente', 'is_active' => true, 'order' => 1]);
        $approved = DocumentStatus::firstOrCreate(['key' => 'approved'], ['label' => 'Aprobado', 'is_active' => true, 'order' => 2]);

        $document = $this->makeDocument(['status_id' => $pending->id, 'customer_firstname' => 'Original']);

        $this->actingAs($this->supervisor())
            ->postJson(route('api.documents.update'), [
                'uid' => $document->uid,
                'data' => [
                    'customer_firstname' => 'Editado',
                    'status_id' => $approved->id,          // debe ignorarse
                    'validation_status' => 'approved',     // debe ignorarse
                    'assigned_user_id' => 999,             // debe ignorarse
                ],
            ])
            ->assertOk();

        $document->refresh();
        // El modelo normaliza el nombre a mayúsculas (mutator): lo importante
        // es que el campo whitelisted SÍ se actualizó.
        $this->assertSame('EDITADO', $document->customer_firstname);
        $this->assertSame($pending->id, $document->status_id, 'status_id no debe cambiar vía update()');
        $this->assertNull($document->assigned_user_id, 'assigned_user_id no debe cambiar vía update()');
    }
}
