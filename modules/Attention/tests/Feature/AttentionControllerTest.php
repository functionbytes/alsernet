<?php

namespace Modules\Attention\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionCategory;
use Modules\Attention\Models\AttentionDepartment;
use Modules\Attention\Models\AttentionSede;
use Modules\Attention\Models\AttentionType;
use Tests\TestCase;

/**
 * Feature tests for AttentionController
 *
 * Pruebas de integración para el controlador principal de peticiones
 */
class AttentionControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;

    protected AttentionType $type;

    protected AttentionCategory $category;

    protected AttentionSede $sede;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->adminUser = User::factory()->create();
        $this->type = AttentionType::factory()->create();
        $this->category = AttentionCategory::factory()->create();
        $this->sede = AttentionSede::factory()->create();

        Storage::fake('public');
    }

    // ========================================================================
    // PUBLIC ENDPOINTS TESTS
    // ========================================================================

    /**
     * Test: Can submit a new peticiones (public endpoint)
     */
    public function test_can_submit_peticiones_as_citizen(): void
    {
        $data = [
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'sede_id' => $this->sede->id,
            'subject' => 'Solicitud de información sobre servicios',
            'description' => 'Me gustaría obtener información detallada sobre los servicios que ofrecen en la sede principal.',
            'is_anonymous' => false,
            'customer_firstname' => 'Juan',
            'customer_lastname' => 'Pérez',
            'customer_email' => 'juan.perez@example.com',
            'customer_cellphone' => '3001234567',
            'customer_dni' => '1234567890',
            'customer_address' => 'Calle 123 #45-67',
            'response_type' => 'email',
        ];

        $response = $this->postJson('/api/peticiones', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'radicado',
                    'status',
                    'status_label',
                    'created_at',
                    'tracking_url',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Verify database
        $this->assertDatabaseHas('attentions', [
            'subject' => $data['subject'],
            'customer_email' => $data['customer_email'],
            'status' => AttentionStatus::RECEIVED->value,
        ]);
    }

    /**
     * Test: Can submit anonymous peticiones
     */
    public function test_can_submit_anonymous_peticiones(): void
    {
        $data = [
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'subject' => 'Solicitud anónima',
            'description' => 'Esta es una solicitud anónima de prueba.',
            'is_anonymous' => true,
        ];

        $response = $this->postJson('/api/peticiones', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('attentions', [
            'is_anonymous' => true,
            'customer_firstname' => null,
            'customer_email' => null,
        ]);
    }

    /**
     * Test: Validates required fields when submitting
     */
    public function test_validates_required_fields_on_submit(): void
    {
        $response = $this->postJson('/api/peticiones', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'type_id',
                'category_id',
                'subject',
                'description',
            ]);
    }

    /**
     * Test: Validates citizen info when not anonymous
     */
    public function test_validates_citizen_info_when_not_anonymous(): void
    {
        $data = [
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'subject' => 'Test subject',
            'description' => 'Test description with enough characters',
            'is_anonymous' => false,
            // Missing citizen info
        ];

        $response = $this->postJson('/api/peticiones', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'customer_firstname',
                'customer_lastname',
                'customer_email',
            ]);
    }

    /**
     * Test: Can submit with file attachments
     */
    public function test_can_submit_with_file_attachments(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $data = [
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'subject' => 'Solicitud con adjunto',
            'description' => 'Esta solicitud incluye un documento adjunto.',
            'customer_firstname' => 'Test',
            'customer_lastname' => 'User',
            'customer_email' => 'test@example.com',
            'attachments' => [$file],
        ];

        $response = $this->postJson('/api/peticiones', $data);

        $response->assertStatus(201);

        // Verify file was uploaded
        $attention = Attention::first();
        $this->assertCount(1, $attention->getMedia('attachments'));
    }

    /**
     * Test: Can track peticiones by radicado
     */
    public function test_can_track_peticiones_by_radicado(): void
    {
        $attention = Attention::factory()->create([
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/peticiones/{$attention->radicado}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'radicado' => $attention->radicado,
                    'status' => $attention->status->value,
                ],
            ]);
    }

    /**
     * Test: Returns 404 when radicado not found
     */
    public function test_returns_404_when_radicado_not_found(): void
    {
        $response = $this->getJson('/api/peticiones/INVALID-RADICADO');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    // ========================================================================
    // ADMIN ENDPOINTS TESTS
    // ========================================================================

    /**
     * Test: Admin can list all peticiones
     */
    public function test_admin_can_list_all_peticiones(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        Attention::factory(5)->create([
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/settings/peticiones');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    /**
     * Test: Can filter peticiones by status
     */
    public function test_can_filter_peticiones_by_status(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        Attention::factory()->create([
            'status' => AttentionStatus::RECEIVED,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        Attention::factory()->create([
            'status' => AttentionStatus::IN_PROCESS,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/settings/peticiones?status=received');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test: Can search peticiones
     */
    public function test_can_search_peticiones(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        Attention::factory()->create([
            'customer_email' => 'searchme@example.com',
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/settings/peticiones?search=searchme');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test: Admin can view peticiones detail
     */
    public function test_admin_can_view_peticiones_detail(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $attention = Attention::factory()->create([
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/settings/peticiones/{$attention->radicado}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'radicado',
                    'status',
                    'type',
                    'category',
                    'notes',
                    'actions',
                    'attachments',
                ],
            ]);
    }

    /**
     * Test: Admin can update peticiones
     */
    public function test_admin_can_update_peticiones(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $attention = Attention::factory()->create([
            'status' => AttentionStatus::RECEIVED,
            'subject' => 'Original subject',
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $updateData = [
            'subject' => 'Updated subject',
            'description' => 'Updated description with enough characters',
        ];

        $response = $this->patchJson("/api/settings/peticiones/{$attention->radicado}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('attentions', [
            'id' => $attention->id,
            'subject' => 'Updated subject',
        ]);
    }

    /**
     * Test: Cannot update closed peticiones
     */
    public function test_cannot_update_closed_peticiones(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $attention = Attention::factory()->create([
            'status' => AttentionStatus::CLOSED,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->patchJson("/api/settings/peticiones/{$attention->radicado}", [
            'subject' => 'New subject',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test: Can assign peticiones to department
     */
    public function test_can_assign_to_department(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $department = AttentionDepartment::factory()->create();
        $attention = Attention::factory()->create([
            'status' => AttentionStatus::RECEIVED,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/assign-department", [
            'department_id' => $department->id,
            'comment' => 'Assigned by test',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attentions', [
            'id' => $attention->id,
            'department_id' => $department->id,
            'status' => AttentionStatus::IN_PROCESS->value,
        ]);
    }

    /**
     * Test: Can assign peticiones to user
     */
    public function test_can_assign_to_user(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $assignedUser = User::factory()->create();
        $attention = Attention::factory()->create([
            'status' => AttentionStatus::RECEIVED,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/assign-user", [
            'user_id' => $assignedUser->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attentions', [
            'id' => $attention->id,
            'assigned_user_id' => $assignedUser->id,
        ]);
    }

    /**
     * Test: Can change peticiones status
     */
    public function test_can_change_status(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $attention = Attention::factory()->create([
            'status' => AttentionStatus::RECEIVED,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/change-status", [
            'status' => 'in_process',
            'comment' => 'Starting work',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attentions', [
            'id' => $attention->id,
            'status' => AttentionStatus::IN_PROCESS->value,
        ]);
    }

    /**
     * Test: Can resolve peticiones
     */
    public function test_can_resolve_peticiones(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $attention = Attention::factory()->create([
            'status' => AttentionStatus::IN_PROCESS,
            'customer_email' => 'test@example.com',
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/resolve", [
            'resolution' => 'Esta es la respuesta completa a su solicitud. Hemos procesado su petición y adjuntamos la información solicitada.',
            'response_type' => 'email',
            'send_notification' => false, // Disable for testing
        ]);

        $response->assertStatus(200);

        $attention->refresh();
        $this->assertEquals(AttentionStatus::RESOLVED, $attention->status);
        $this->assertNotNull($attention->resolution);
        $this->assertNotNull($attention->resolved_at);
    }

    /**
     * Test: Can close peticiones
     */
    public function test_can_close_peticiones(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $attention = Attention::factory()->create([
            'status' => AttentionStatus::RESOLVED,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/close", [
            'comment' => 'Closing after resolution',
        ]);

        $response->assertStatus(200);

        $attention->refresh();
        $this->assertEquals(AttentionStatus::CLOSED, $attention->status);
        $this->assertNotNull($attention->closed_at);
    }

    /**
     * Test: Can add note to peticiones
     */
    public function test_can_add_note(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $attention = Attention::factory()->create([
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/notes", [
            'content' => 'This is a test note with sufficient length',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('attention_notes', [
            'attention_id' => $attention->id,
            'user_id' => $this->adminUser->id,
        ]);
    }

    /**
     * Test: Can get notes list
     */
    public function test_can_get_notes(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $attention = Attention::factory()->create([
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $attention->addNote('Test note', $this->adminUser->id);

        $response = $this->getJson("/api/settings/peticiones/{$attention->radicado}/notes");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /**
     * Test: Can get action history
     */
    public function test_can_get_actions(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $attention = Attention::factory()->create([
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/settings/peticiones/{$attention->radicado}/actions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /**
     * Test: Can get statistics
     */
    public function test_can_get_statistics(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        Attention::factory(10)->create([
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/settings/peticiones/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'by_status',
                    'by_type',
                    'by_category',
                ],
            ]);
    }

    /**
     * Test: Can submit satisfaction survey
     */
    public function test_can_submit_satisfaction_survey(): void
    {
        $attention = Attention::factory()->create([
            'status' => AttentionStatus::RESOLVED,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson("/api/peticiones/{$attention->radicado}/satisfaction", [
            'rating' => 5,
            'comment' => 'Excelente servicio',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('attentions', [
            'id' => $attention->id,
            'satisfaction_rating' => 5,
        ]);
    }

    /**
     * Test: Cannot submit satisfaction for unresolved peticiones
     */
    public function test_cannot_submit_satisfaction_for_unresolved(): void
    {
        $attention = Attention::factory()->create([
            'status' => AttentionStatus::IN_PROCESS,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->postJson("/api/peticiones/{$attention->radicado}/satisfaction", [
            'rating' => 5,
        ]);

        $response->assertStatus(422);
    }

    // ========================================================================
    // AUTHORIZATION TESTS
    // ========================================================================

    /**
     * Test: Requires authentication for settings endpoints
     */
    public function test_requires_authentication_for_admin_endpoints(): void
    {
        $response = $this->getJson('/api/settings/peticiones');

        $response->assertStatus(401);
    }
}
