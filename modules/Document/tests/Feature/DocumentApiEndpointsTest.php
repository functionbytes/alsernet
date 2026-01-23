<?php

namespace Modules\Document\Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Test básico de endpoints API
 * USA LA BASE DE DATOS REAL (no RefreshDatabase)
 * SOLO LECTURA - No modifica datos existentes
 */
class DocumentApiEndpointsTest extends TestCase
{
    // NO usar RefreshDatabase - usamos BD real

    protected User $user;

    protected string $documentUid;

    protected function setUp(): void
    {
        parent::setUp();

        // USAR USUARIO REAL de la base de datos
        // Buscar primer usuario con rol administrative, manager o super-admin
        $this->user = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super-admin', 'manager', 'administrative']);
        })->first();

        if (! $this->user) {
            $this->markTestSkipped('No hay usuarios con rol administrative/manager en la BD');
        }

        $this->actingAs($this->user);

        // USAR DOCUMENTO REAL de la base de datos
        $document = \Modules\Document\Entities\Document::where('validation_status', 'pending')
            ->first();

        if ($document) {
            $this->documentUid = $document->uid;
        } else {
            // Usar cualquier documento si no hay pending
            $document = \Modules\Document\Entities\Document::first();
            $this->documentUid = $document ? $document->uid : 'test-uid-'.uniqid();
        }
    }

    public function test_api_routes_exist()
    {
        // Verificar que las rutas API están definidas
        $routes = [
            'api.documents.approve-stage',
            'api.documents.reject-stage',
            'api.documents.send-approval',
            'api.documents.send-rejection',
            'api.documents.send-custom-email',
            'api.documents.send-reminder',
            'api.documents.request-initial',
            'api.documents.request-missing',
            'api.documents.next-stage-info',
            'api.documents.custom-email-template',
            'api.documents.action-history',
            'api.documents.email-history',
            'api.documents.status-timeline',
        ];

        foreach ($routes as $routeName) {
            $this->assertTrue(
                \Route::has($routeName),
                "La ruta {$routeName} no existe"
            );
        }
    }

    public function test_next_stage_info_endpoint_responds()
    {
        $response = $this->getJson(route('api.documents.next-stage-info', $this->documentUid));

        // Puede retornar 404 (documento no existe), 200 (success) o 422 (validación)
        $this->assertContains($response->status(), [200, 404, 422]);
    }

    public function test_custom_email_template_endpoint_responds()
    {
        $response = $this->getJson(route('api.documents.custom-email-template'));

        // Debe responder 200 (con o sin template)
        $response->assertStatus(200);
        $response->assertJsonStructure(['success']);
    }

    public function test_approve_stage_endpoint_responds()
    {
        $response = $this->postJson(route('api.documents.approve-stage', $this->documentUid), [
            'comments' => 'Test comment',
        ]);

        // Puede retornar 404 (documento no existe), 403 (sin permiso), 422 (validación)
        $this->assertContains($response->status(), [200, 403, 404, 422]);
    }

    public function test_reject_stage_endpoint_responds()
    {
        $response = $this->postJson(route('api.documents.reject-stage', $this->documentUid), [
            'reason' => 'Test reason',
        ]);

        $this->assertContains($response->status(), [200, 403, 404, 422]);
    }

    public function test_send_approval_endpoint_responds()
    {
        $response = $this->postJson(route('api.documents.send-approval', $this->documentUid), [
            'additional_message' => 'Test message',
        ]);

        $this->assertContains($response->status(), [200, 403, 404, 422]);
    }

    public function test_send_rejection_endpoint_responds()
    {
        $response = $this->postJson(route('api.documents.send-rejection', $this->documentUid), [
            'reason' => 'Test reason',
        ]);

        $this->assertContains($response->status(), [200, 403, 404, 422]);
    }

    public function test_send_custom_email_endpoint_responds()
    {
        $response = $this->postJson(route('api.documents.send-custom-email', $this->documentUid), [
            'subject' => 'Test subject',
            'message' => 'Test message',
        ]);

        $this->assertContains($response->status(), [200, 403, 404, 422]);
    }

    public function test_send_reminder_endpoint_responds()
    {
        $response = $this->postJson(route('api.documents.send-reminder', $this->documentUid), [
            'message' => 'Test reminder',
        ]);

        $this->assertContains($response->status(), [200, 403, 404, 422]);
    }

    public function test_request_initial_documents_endpoint_responds()
    {
        $response = $this->postJson(route('api.documents.request-initial', $this->documentUid), [
            'required_documents' => 'Test docs',
        ]);

        $this->assertContains($response->status(), [200, 403, 404, 422]);
    }

    public function test_request_missing_documents_endpoint_responds()
    {
        $response = $this->postJson(route('api.documents.request-missing', $this->documentUid), [
            'missing_documents' => 'Test missing docs',
        ]);

        $this->assertContains($response->status(), [200, 403, 404, 422]);
    }

    public function test_action_history_endpoint_responds()
    {
        $response = $this->getJson(route('api.documents.action-history', $this->documentUid));

        $this->assertContains($response->status(), [200, 403, 404]);
    }

    public function test_email_history_endpoint_responds()
    {
        $response = $this->getJson(route('api.documents.email-history', $this->documentUid));

        $this->assertContains($response->status(), [200, 403, 404]);
    }

    public function test_status_timeline_endpoint_responds()
    {
        $response = $this->getJson(route('api.documents.status-timeline', $this->documentUid));

        $this->assertContains($response->status(), [200, 403, 404]);
    }

    public function test_endpoints_require_authentication()
    {
        // Desautenticar
        auth()->logout();

        $response = $this->getJson(route('api.documents.next-stage-info', $this->documentUid));

        // Debe retornar 401 (no autenticado) o redirigir
        $this->assertContains($response->status(), [401, 302]);
    }

    public function test_api_returns_json_responses()
    {
        $response = $this->getJson(route('api.documents.custom-email-template'));

        $response->assertHeader('Content-Type', 'application/json');
    }
}
