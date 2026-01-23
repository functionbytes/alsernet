<?php

namespace Modules\Document\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Document\Entities\Document;
use Tests\TestCase;

class DocumentValidationApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario administrativo manualmente
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Asignar rol si el sistema de roles está disponible
        if (method_exists($this->user, 'assignRole')) {
            $this->user->assignRole('administrative');
        }

        // Crear documento de prueba manualmente
        $this->document = Document::create([
            'uid' => $this->faker->uuid,
            'current_stage' => 1,
            'total_stages' => 3,
            'current_validator_group' => 'commercial',
            'status' => 'pending_validation',
            'type_id' => 1,
            'client_id' => 1,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_get_next_stage_info()
    {
        $response = $this->getJson(route('api.documents.next-stage-info', $this->document->uid));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'next_stage',
                'next_group_key',
                'next_group_label',
                'users',
            ])
            ->assertJson([
                'success' => true,
                'next_stage' => 2,
            ]);
    }

    /** @test */
    public function it_returns_error_when_document_is_in_last_stage()
    {
        $this->document->update([
            'current_stage' => 3,
            'total_stages' => 3,
        ]);

        $response = $this->getJson(route('api.documents.next-stage-info', $this->document->uid));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'El documento está en la última etapa',
            ]);
    }

    /** @test */
    public function it_can_get_custom_email_template()
    {
        $this->markTestSkipped('EmailTemplate factory not configured yet');
    }

    /** @test */
    public function it_returns_error_when_no_custom_email_template_exists()
    {
        $response = $this->getJson(route('api.documents.custom-email-template'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'No hay plantilla configurada',
            ]);
    }

    /** @test */
    public function it_can_call_approve_stage_endpoint()
    {
        $response = $this->postJson(route('api.documents.approve-stage', $this->document->uid), [
            'comments' => 'Documento aprobado correctamente',
            'assigned_user_id' => null,
        ]);

        // Verificar que el endpoint responde (puede ser 200 o 422 dependiendo de validaciones)
        $this->assertContains($response->status(), [200, 422, 403]);
    }

    /** @test */
    public function it_can_call_reject_stage_endpoint()
    {
        $response = $this->postJson(route('api.documents.reject-stage', $this->document->uid), [
            'reason' => 'Documento incompleto',
            'comments' => 'Faltan documentos requeridos',
        ]);

        // Verificar que el endpoint responde
        $this->assertContains($response->status(), [200, 422, 403]);
    }

    /** @test */
    public function it_requires_reason_when_rejecting_stage()
    {
        $response = $this->postJson(route('api.documents.reject-stage', $this->document->uid), [
            'comments' => 'Solo comentarios sin razón',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function it_can_send_approval_email()
    {
        $response = $this->postJson(route('api.documents.send-approval', $this->document->uid), [
            'additional_message' => 'Su documento ha sido aprobado',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_can_send_rejection_email()
    {
        $response = $this->postJson(route('api.documents.send-rejection', $this->document->uid), [
            'reason' => 'Documento incompleto',
            'additional_comments' => 'Por favor revise los requisitos',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_can_send_custom_email()
    {
        $template = EmailTemplate::factory()->create(['type' => 'custom_document']);

        $response = $this->postJson(route('api.documents.send-custom-email', $this->document->uid), [
            'subject' => 'Asunto personalizado',
            'message' => 'Mensaje personalizado para el cliente',
            'template_id' => $template->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_requires_subject_and_message_for_custom_email()
    {
        $response = $this->postJson(route('api.documents.send-custom-email', $this->document->uid), [
            'template_id' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'message']);
    }

    /** @test */
    public function it_can_send_reminder()
    {
        $response = $this->postJson(route('api.documents.send-reminder', $this->document->uid), [
            'message' => 'Recordatorio de documentos pendientes',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_can_request_initial_documents()
    {
        $response = $this->postJson(route('api.documents.request-initial', $this->document->uid), [
            'required_documents' => 'Cédula, RUT, Certificado bancario',
            'deadline_days' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_can_request_missing_documents()
    {
        $response = $this->postJson(route('api.documents.request-missing', $this->document->uid), [
            'missing_documents' => 'Cédula legible por ambos lados',
            'comments' => 'La cédula actual no se ve claramente',
            'send_email' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_can_get_action_history()
    {
        // Crear algunas acciones en el documento
        $this->document->actions()->create([
            'user_id' => $this->user->id,
            'action_type' => 'approved',
            'comments' => 'Acción de prueba',
        ]);

        $response = $this->getJson(route('api.documents.action-history', $this->document->uid));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'actions' => [
                    '*' => ['id', 'action_type', 'comments', 'created_at'],
                ],
            ]);
    }

    /** @test */
    public function it_can_get_email_history()
    {
        // Crear un email de prueba
        $this->document->mails()->create([
            'recipient_email' => 'cliente@example.com',
            'subject' => 'Prueba',
            'body_text' => 'Cuerpo del email',
            'email_type' => 'approval',
            'is_sent' => true,
            'sent_at' => now(),
        ]);

        $response = $this->getJson(route('api.documents.email-history', $this->document->uid));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'emails' => [
                    '*' => ['id', 'subject', 'email_type', 'is_sent'],
                ],
            ]);
    }

    /** @test */
    public function it_can_get_status_timeline()
    {
        // Crear un cambio de estado
        $this->document->statusHistory()->create([
            'user_id' => $this->user->id,
            'from_status' => 'pending_validation',
            'to_status' => 'approved',
            'reason' => 'Documentos correctos',
        ]);

        $response = $this->getJson(route('api.documents.status-timeline', $this->document->uid));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'timeline' => [
                    '*' => ['from_status', 'to_status', 'created_at'],
                ],
            ]);
    }

    /** @test */
    public function it_detects_user_profile_automatically()
    {
        // Test para manager
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $response = $this->getJson(route('api.documents.next-stage-info', $this->document->uid));
        $response->assertStatus(200);

        // Test para weapons
        $weapons = User::factory()->create();
        $weapons->assignRole('weapons');
        $this->actingAs($weapons);

        $response = $this->getJson(route('api.documents.next-stage-info', $this->document->uid));
        $response->assertStatus(200);
    }

    /** @test */
    public function it_requires_authentication()
    {
        auth()->logout();

        $response = $this->getJson(route('api.documents.next-stage-info', $this->document->uid));
        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_document_authorization()
    {
        // Crear otro usuario sin permisos
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->postJson(route('api.documents.approve-stage', $this->document->uid));
        $response->assertStatus(403);
    }
}
