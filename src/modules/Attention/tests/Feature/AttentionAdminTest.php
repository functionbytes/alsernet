<?php

namespace Modules\Attention\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Enums\ResponseType;
use Modules\Attention\Tests\TestCase;

class AttentionAdminTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_admin_can_list_attentions()
    {
        // Arrange
        $this->actingAsAdmin();
        $this->createAttention(['customer_email' => 'user1@example.com']);
        $this->createAttention(['customer_email' => 'user2@example.com']);
        $this->createAttention(['customer_email' => 'user3@example.com']);

        // Act
        $response = $this->getJson('/api/settings/peticiones');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'uid',
                        'radicado',
                        'status',
                        'subject',
                        'customer_firstname',
                        'customer_lastname',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_guest_cannot_access_admin_endpoints()
    {
        // Act
        $response = $this->getJson('/api/settings/peticiones');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function test_admin_can_view_detail()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention();

        // Act
        $response = $this->getJson("/api/settings/peticiones/{$attention->radicado}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'uid',
                    'radicado',
                    'status',
                    'type',
                    'category',
                    'department',
                    'assigned_user',
                    'subject',
                    'description',
                    'customer_firstname',
                    'customer_email',
                    'notes',
                    'actions',
                ],
            ])
            ->assertJsonPath('data.radicado', $attention->radicado);
    }

    /** @test */
    public function test_admin_can_assign_to_department()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention();
        $department = $this->createDepartment();

        // Act
        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/assign-department", [
            'department_id' => $department->id,
            'comment' => 'Asignado a departamento',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'peticiones asignado a departamento correctamente',
            ]);

        $attention->refresh();
        $this->assertEquals($department->id, $attention->department_id);

        // Verificar que se registró la acción
        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'department_assigned',
        ]);
    }

    /** @test */
    public function test_admin_can_assign_to_user()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention();
        $user = $this->createUser();

        // Act
        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/assign-user", [
            'user_id' => $user->id,
            'comment' => 'Asignado a usuario',
        ]);

        // Assert
        $response->assertStatus(200);

        $attention->refresh();
        $this->assertEquals($user->id, $attention->assigned_user_id);

        // Verificar que se registró la acción
        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'assigned',
        ]);
    }

    /** @test */
    public function test_admin_can_change_status()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention([
            'status' => AttentionStatus::RECEIVED,
        ]);

        // Act
        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/change-status", [
            'status' => AttentionStatus::IN_PROCESS->value,
            'comment' => 'Iniciando proceso',
        ]);

        // Assert
        $response->assertStatus(200);

        $attention->refresh();
        $this->assertEquals(AttentionStatus::IN_PROCESS, $attention->status);

        // Verificar que se registró el cambio de estado
        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'status_changed',
        ]);
    }

    /** @test */
    public function test_admin_can_resolve()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention([
            'status' => AttentionStatus::IN_PROCESS,
        ]);

        // Act
        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/resolve", [
            'resolution' => 'Se ha resuelto la solicitud satisfactoriamente',
            'response_type' => ResponseType::EMAIL->value,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'peticiones resuelto correctamente',
            ]);

        $attention->refresh();
        $this->assertEquals(AttentionStatus::RESOLVED, $attention->status);
        $this->assertEquals('Se ha resuelto la solicitud satisfactoriamente', $attention->resolution);
        $this->assertEquals(ResponseType::EMAIL, $attention->response_type);
        $this->assertNotNull($attention->resolved_at);

        // Verificar que se registró la acción
        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'resolved',
        ]);
    }

    /** @test */
    public function test_admin_can_close()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention([
            'status' => AttentionStatus::RESOLVED,
        ]);

        // Act
        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/close", [
            'comment' => 'Cerrando caso',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'peticiones cerrado correctamente',
            ]);

        $attention->refresh();
        $this->assertEquals(AttentionStatus::CLOSED, $attention->status);
        $this->assertNotNull($attention->closed_at);

        // Verificar que se registró la acción
        $this->assertDatabaseHas('attention_actions', [
            'attention_id' => $attention->id,
            'action' => 'closed',
        ]);
    }

    /** @test */
    public function test_admin_can_add_note()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention();

        // Act
        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/notes", [
            'content' => 'Esta es una nota interna importante',
        ]);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Nota agregada correctamente',
            ]);

        $this->assertDatabaseHas('attention_notes', [
            'attention_id' => $attention->id,
            'content' => 'Esta es una nota interna importante',
        ]);
    }

    /** @test */
    public function test_admin_can_list_notes()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention();
        $user = auth()->user();

        $attention->addNote('Primera nota', $user->id);
        $attention->addNote('Segunda nota', $user->id);

        // Act
        $response = $this->getJson("/api/settings/peticiones/{$attention->radicado}/notes");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'user',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_admin_can_view_actions_history()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention();
        $user = auth()->user();

        $attention->logAction('test_action_1', 'Primera acción', $user->id);
        $attention->logAction('test_action_2', 'Segunda acción', $user->id);

        // Act
        $response = $this->getJson("/api/settings/peticiones/{$attention->radicado}/actions");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'action',
                        'description',
                        'user',
                        'created_at',
                    ],
                ],
            ]);
    }

    /** @test */
    public function test_admin_can_view_emails_history()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention();

        // Act
        $response = $this->getJson("/api/settings/peticiones/{$attention->radicado}/emails");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function test_admin_can_view_stats()
    {
        // Arrange
        $this->actingAsAdmin();
        $this->createAttention(['status' => AttentionStatus::RECEIVED]);
        $this->createAttention(['status' => AttentionStatus::IN_PROCESS]);
        $this->createAttention(['status' => AttentionStatus::RESOLVED]);

        // Act
        $response = $this->getJson('/api/settings/peticiones/stats/overview');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'by_status',
                    'by_type',
                    'recent',
                ],
            ]);
    }

    /** @test */
    public function test_admin_can_filter_by_status()
    {
        // Arrange
        $this->actingAsAdmin();
        $this->createAttention(['status' => AttentionStatus::RECEIVED]);
        $this->createAttention(['status' => AttentionStatus::RECEIVED]);
        $this->createAttention(['status' => AttentionStatus::IN_PROCESS]);

        // Act
        $response = $this->getJson('/api/settings/peticiones?status=received');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_admin_can_search_attentions()
    {
        // Arrange
        $this->actingAsAdmin();
        $this->createAttention(['subject' => 'Solicitud de información']);
        $this->createAttention(['subject' => 'Queja sobre servicio']);
        $this->createAttention(['subject' => 'Solicitud de certificado']);

        // Act
        $response = $this->getJson('/api/settings/peticiones?search=solicitud');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_resolve_requires_resolution_text()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention();

        // Act
        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/resolve", [
            'response_type' => ResponseType::EMAIL->value,
            // Falta resolution
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resolution']);
    }

    /** @test */
    public function test_resolve_requires_response_type()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention();

        // Act
        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/resolve", [
            'resolution' => 'Resuelto',
            // Falta response_type
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['response_type']);
    }

    /** @test */
    public function test_admin_can_update_attention_details()
    {
        // Arrange
        $this->actingAsAdmin();
        $attention = $this->createAttention();

        // Act
        $response = $this->patchJson("/api/settings/peticiones/{$attention->radicado}", [
            'subject' => 'Nuevo asunto actualizado',
            'description' => 'Nueva descripción actualizada',
        ]);

        // Assert
        $response->assertStatus(200);

        $attention->refresh();
        $this->assertEquals('Nuevo asunto actualizado', $attention->subject);
        $this->assertEquals('Nueva descripción actualizada', $attention->description);
    }
}
