<?php

namespace Modules\Attention\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Tests\TestCase;

class AttentionTrackingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_track_by_radicado()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'uid',
                    'radicado',
                    'status',
                    'status_label',
                    'type',
                    'category',
                    'sede',
                    'subject',
                    'description',
                    'created_at',
                ],
            ])
            ->assertJsonPath('data.radicado', $attention->radicado)
            ->assertJsonPath('data.subject', $attention->subject);
    }

    /** @test */
    public function test_returns_404_for_invalid_radicado()
    {
        // Act
        $response = $this->getJson('/api/peticiones/peticiones-2026-999999');

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'peticiones no encontrado',
            ]);
    }

    /** @test */
    public function test_tracking_shows_current_status()
    {
        // Arrange
        $attention = $this->createAttention([
            'status' => AttentionStatus::IN_PROCESS,
        ]);

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.status', AttentionStatus::IN_PROCESS->value)
            ->assertJsonPath('data.status_label', 'En Proceso');
    }

    /** @test */
    public function test_tracking_includes_type_and_category()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type' => ['id', 'name', 'code'],
                    'category' => ['id', 'name'],
                ],
            ]);
    }

    /** @test */
    public function test_tracking_includes_sede_information()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'sede' => ['id', 'name', 'code'],
                ],
            ]);
    }

    /** @test */
    public function test_tracking_hides_sensitive_admin_data()
    {
        // Arrange
        $department = $this->createDepartment();
        $user = $this->createUser();

        $attention = $this->createAttention([
            'department_id' => $department->id,
            'assigned_user_id' => $user->id,
        ]);

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}");

        // Assert
        $response->assertStatus(200);

        // No debe incluir información administrativa sensible
        $responseData = $response->json('data');
        $this->assertArrayNotHasKey('department', $responseData);
        $this->assertArrayNotHasKey('assigned_user', $responseData);
        $this->assertArrayNotHasKey('department_id', $responseData);
        $this->assertArrayNotHasKey('assigned_user_id', $responseData);
    }

    /** @test */
    public function test_tracking_shows_resolution_when_resolved()
    {
        // Arrange
        $attention = $this->createAttention([
            'status' => AttentionStatus::RESOLVED,
            'resolution' => 'El caso ha sido resuelto satisfactoriamente',
            'resolved_at' => now(),
        ]);

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.resolution', $attention->resolution)
            ->assertJsonPath('data.status', AttentionStatus::RESOLVED->value);
    }

    /** @test */
    public function test_tracking_shows_documents_count()
    {
        // Arrange
        $attention = $this->createAttention();
        $attention->addDocument($this->createTestFile('doc1.pdf'));
        $attention->addDocument($this->createTestFile('doc2.pdf'));

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.documents_count', 2);
    }

    /** @test */
    public function test_tracking_protects_anonymous_user_data()
    {
        // Arrange
        $attention = $this->createAnonymousAttention();

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.is_anonymous', true)
            ->assertJsonPath('data.full_name', 'ANÓNIMO');

        $responseData = $response->json('data');
        $this->assertNull($responseData['customer_email'] ?? null);
        $this->assertNull($responseData['customer_cellphone'] ?? null);
    }

    /** @test */
    public function test_tracking_is_case_insensitive()
    {
        // Arrange
        $attention = $this->createAttention();
        $radicadoLower = strtolower($attention->radicado);

        // Act
        $response = $this->getJson("/api/peticiones/{$radicadoLower}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.radicado', $attention->radicado);
    }

    /** @test */
    public function test_tracking_includes_creation_date()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['created_at', 'updated_at'],
            ]);

        $createdAt = $response->json('data.created_at');
        $this->assertNotNull($createdAt);
    }

    /** @test */
    public function test_tracking_respects_throttle_limits()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act - Hacer más de 60 requests en 1 minuto
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson("/api/peticiones/{$attention->radicado}");
        }

        // Assert - El request 61 debe ser rechazado
        $response->assertStatus(429);
    }
}
