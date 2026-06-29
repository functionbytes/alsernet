<?php

namespace Modules\Attention\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionDepartment;
use Modules\Attention\Models\AttentionSlaPolicy;
use Modules\Attention\Models\AttentionType;
use Tests\TestCase;

class AttentionStatsAndBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super-settings');
    }

    /** @test */
    public function it_can_get_dashboard_stats()
    {
        // Crear datos de prueba
        Attention::factory()->count(10)->create(['status' => AttentionStatus::RECEIVED]);
        Attention::factory()->count(5)->create(['status' => AttentionStatus::IN_PROCESS]);
        Attention::factory()->count(3)->create(['status' => AttentionStatus::RESOLVED]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/attentions/stats/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'today',
                    'this_week',
                    'this_month',
                    'by_status',
                    'by_type',
                    'average_satisfaction',
                    'average_resolution_time',
                    'sla_compliance_rate',
                    'top_categories',
                    'top_departments',
                ],
            ]);

        $this->assertEquals(18, $response->json('data.total'));
    }

    /** @test */
    public function it_can_get_stats_by_type()
    {
        $type = AttentionType::factory()->create();
        Attention::factory()->count(5)->create(['type_id' => $type->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/attentions/stats/by-type');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['type', 'count', 'avg_resolution_time', 'avg_satisfaction'],
                ],
            ]);
    }

    /** @test */
    public function it_can_get_stats_by_status()
    {
        Attention::factory()->count(3)->create(['status' => AttentionStatus::RECEIVED]);
        Attention::factory()->count(2)->create(['status' => AttentionStatus::IN_PROCESS]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/attentions/stats/by-status');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['status', 'count', 'percentage'],
                ],
            ]);
    }

    /** @test */
    public function it_can_get_sla_status_for_attention()
    {
        $policy = AttentionSlaPolicy::factory()->create();
        $attention = Attention::factory()->create(['sla_policy_id' => $policy->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/attentions/{$attention->radicado}/sla-status");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'sla_policy',
                    'time_elapsed',
                    'time_limit',
                    'percentage_used',
                    'status',
                ],
            ]);
    }

    /** @test */
    public function it_can_get_sla_breaches()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/attentions/sla-breaches');

        $response->assertOk();
    }

    /** @test */
    public function it_can_bulk_assign_attentions()
    {
        $department = AttentionDepartment::factory()->create();
        $attentions = Attention::factory()->count(3)->create(['status' => AttentionStatus::RECEIVED]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/attentions/bulk-assign', [
                'attention_ids' => $attentions->pluck('id')->toArray(),
                'department_id' => $department->id,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['success', 'failed', 'total'],
            ]);

        $this->assertEquals(3, $response->json('data.success'));
    }

    /** @test */
    public function it_can_bulk_close_attentions()
    {
        $attentions = Attention::factory()->count(3)->create([
            'status' => AttentionStatus::RESOLVED,
            'resolution' => 'Test resolution',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/attentions/bulk-close', [
                'attention_ids' => $attentions->pluck('id')->toArray(),
                'reason' => 'Bulk closure test',
            ]);

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.success'));
    }

    /** @test */
    public function it_can_bulk_delete_attentions()
    {
        $attentions = Attention::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/attentions/bulk-delete', [
                'attention_ids' => $attentions->pluck('id')->toArray(),
            ]);

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.success'));

        // Verificar soft delete
        foreach ($attentions as $attention) {
            $this->assertSoftDeleted('attentions', ['id' => $attention->id]);
        }
    }

    /** @test */
    public function it_requires_super_admin_for_bulk_delete()
    {
        $attentions = Attention::factory()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/attentions/bulk-delete', [
                'attention_ids' => $attentions->pluck('id')->toArray(),
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function it_can_request_export()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/attentions/export', [
                'format' => 'excel',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['token', 'download_url', 'expires_at'],
            ]);
    }

    /** @test */
    public function it_can_download_export()
    {
        // Primero solicitar export
        $exportResponse = $this->actingAs($this->adminUser)
            ->postJson('/api/attentions/export', ['format' => 'excel']);

        $token = $exportResponse->json('data.token');

        // Luego descargar
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/attentions/export/{$token}");

        // Puede ser 200 si está listo o 202 si está procesando
        $this->assertContains($response->status(), [200, 202]);
    }
}
