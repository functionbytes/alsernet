<?php

namespace Modules\Attention\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionCategory;
use Modules\Attention\Models\AttentionDepartment;
use Modules\Attention\Models\AttentionType;
use Tests\TestCase;

class AttentionWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected AttentionType $type;

    protected AttentionCategory $category;

    protected AttentionDepartment $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->type = AttentionType::factory()->create();
        $this->category = AttentionCategory::factory()->create();
        $this->department = AttentionDepartment::factory()->create();
    }

    /** @test */
    public function complete_attention_workflow()
    {
        Event::fake();

        // 1. Submit peticiones
        $response = $this->postJson('/api/peticiones/submit', [
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'customer_firstname' => 'John',
            'customer_lastname' => 'Doe',
            'customer_email' => 'john@example.com',
            'customer_cellphone' => '1234567890',
            'customer_dni' => '1234567890',
            'subject' => 'Test subject',
            'description' => 'Test description',
        ]);

        $response->assertCreated();
        $radicado = $response->json('data.radicado');

        // 2. Assign to department
        $response = $this->actingAs($this->user)
            ->postJson("/api/attentions/{$radicado}/assign-department", [
                'department_id' => $this->department->id,
            ]);

        $response->assertOk();

        // 3. Assign to user
        $response = $this->actingAs($this->user)
            ->postJson("/api/attentions/{$radicado}/assign-user", [
                'user_id' => $this->user->id,
            ]);

        $response->assertOk();

        // 4. Change status to in_process
        $response = $this->actingAs($this->user)
            ->postJson("/api/attentions/{$radicado}/change-status", [
                'status' => 'in_process',
            ]);

        $response->assertOk();

        // 5. Resolve
        $response = $this->actingAs($this->user)
            ->postJson("/api/attentions/{$radicado}/resolve", [
                'resolution' => 'Resolved successfully',
                'response_type' => 'email',
            ]);

        $response->assertOk();

        // 6. Close
        $response = $this->actingAs($this->user)
            ->postJson("/api/attentions/{$radicado}/close");

        $response->assertOk();

        // Verify final state
        $attention = Attention::where('radicado', $radicado)->first();
        $this->assertEquals(AttentionStatus::CLOSED, $attention->status);
        $this->assertNotNull($attention->resolved_at);
        $this->assertNotNull($attention->closed_at);
        $this->assertEquals($this->user->id, $attention->assigned_user_id);
    }

    /** @test */
    public function can_add_notes_throughout_workflow()
    {
        $attention = Attention::factory()->create([
            'assigned_user_id' => $this->user->id,
        ]);

        // Add note 1
        $response = $this->actingAs($this->user)
            ->postJson("/api/attentions/{$attention->uid}/notes", [
                'content' => 'First note',
                'is_internal' => true,
            ]);

        $response->assertCreated();

        // Add note 2
        $response = $this->actingAs($this->user)
            ->postJson("/api/attentions/{$attention->uid}/notes", [
                'content' => 'Second note',
                'is_internal' => false,
            ]);

        $response->assertCreated();

        // Get notes
        $response = $this->actingAs($this->user)
            ->getJson("/api/attentions/{$attention->uid}/notes");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_upload_and_delete_files()
    {
        $attention = Attention::factory()->create();

        // Upload file
        $response = $this->actingAs($this->user)
            ->postJson("/api/attentions/{$attention->uid}/files", [
                'files' => [
                    UploadedFile::fake()->create('document.pdf', 100),
                ],
            ]);

        $response->assertCreated();
        $mediaId = $response->json('data.0.id');

        // List files
        $response = $this->actingAs($this->user)
            ->getJson("/api/attentions/{$attention->uid}/files");

        $response->assertOk()
            ->assertJsonCount(1, 'data');

        // Delete file
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/attentions/{$attention->uid}/files/{$mediaId}");

        $response->assertOk();
    }
}
