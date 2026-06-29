<?php

namespace Modules\Attention\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionDepartment;
use Modules\Attention\Models\AttentionType;
use Modules\Attention\Services\AttentionBulkActionService;
use Modules\Attention\Services\AttentionExportService;
use Modules\Attention\Services\AttentionStatisticsService;
use Tests\TestCase;

class AttentionServicesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function export_service_can_generate_token()
    {
        $token = AttentionExportService::generateExportToken();

        $this->assertIsString($token);
        $this->assertEquals(32, strlen($token));
    }

    /** @test */
    public function export_service_can_export_to_excel()
    {
        $attentions = Attention::factory()->count(5)->make();

        $filename = AttentionExportService::exportToExcel($attentions);

        $this->assertNotNull($filename);
        $this->assertStringContainsString('.xlsx', $filename);
        Storage::disk('local')->assertExists("exports/{$filename}");
    }

    /** @test */
    public function export_service_can_export_to_csv()
    {
        $attentions = Attention::factory()->count(5)->make();

        $filename = AttentionExportService::exportToCsv($attentions);

        $this->assertNotNull($filename);
        $this->assertStringContainsString('.csv', $filename);
        Storage::disk('local')->assertExists("exports/{$filename}");
    }

    /** @test */
    public function export_service_can_clean_old_exports()
    {
        // Crear archivo antiguo
        Storage::disk('local')->put('exports/old_file.xlsx', 'content');

        $cleaned = AttentionExportService::cleanOldExports();

        $this->assertIsInt($cleaned);
    }

    /** @test */
    public function bulk_action_service_can_assign_multiple_attentions()
    {
        $department = AttentionDepartment::factory()->create();
        $attentions = Attention::factory()->count(3)->create();

        $result = AttentionBulkActionService::bulkAssign(
            $attentions->pluck('id')->toArray(),
            $department->id
        );

        $this->assertEquals(3, $result['success']);
        $this->assertEquals(0, $result['failed']);

        foreach ($attentions as $attention) {
            $attention->refresh();
            $this->assertEquals($department->id, $attention->department_id);
        }
    }

    /** @test */
    public function bulk_action_service_can_close_multiple_attentions()
    {
        $attentions = Attention::factory()->count(3)->create([
            'status' => AttentionStatus::RESOLVED,
        ]);

        $result = AttentionBulkActionService::bulkClose(
            $attentions->pluck('id')->toArray(),
            'Test closure reason'
        );

        $this->assertEquals(3, $result['success']);

        foreach ($attentions as $attention) {
            $attention->refresh();
            $this->assertEquals(AttentionStatus::CLOSED, $attention->status);
        }
    }

    /** @test */
    public function bulk_action_service_can_delete_multiple_attentions()
    {
        $attentions = Attention::factory()->count(3)->create();

        $result = AttentionBulkActionService::bulkDelete(
            $attentions->pluck('id')->toArray()
        );

        $this->assertEquals(3, $result['success']);

        foreach ($attentions as $attention) {
            $this->assertSoftDeleted('attentions', ['id' => $attention->id]);
        }
    }

    /** @test */
    public function statistics_service_can_get_dashboard_stats()
    {
        Attention::factory()->count(10)->create();

        $stats = AttentionStatisticsService::getDashboardStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('today', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        $this->assertEquals(10, $stats['total']);
    }

    /** @test */
    public function statistics_service_can_get_stats_by_type()
    {
        $type = AttentionType::factory()->create();
        Attention::factory()->count(5)->create(['type_id' => $type->id]);

        $stats = AttentionStatisticsService::getStatsByType();

        $this->assertNotEmpty($stats);
        $this->assertArrayHasKey('type', $stats[0]);
        $this->assertArrayHasKey('count', $stats[0]);
    }

    /** @test */
    public function statistics_service_can_calculate_average_satisfaction()
    {
        Attention::factory()->create(['satisfaction_rating' => 5]);
        Attention::factory()->create(['satisfaction_rating' => 4]);
        Attention::factory()->create(['satisfaction_rating' => 3]);

        $avg = AttentionStatisticsService::getAverageSatisfaction();

        $this->assertEquals(4.0, $avg);
    }

    /** @test */
    public function statistics_service_can_calculate_sla_compliance_rate()
    {
        // Crear attentions con y sin breaches
        Attention::factory()->count(8)->create(); // Sin breaches

        $rate = AttentionStatisticsService::getSlaComplianceRate();

        $this->assertIsFloat($rate);
        $this->assertGreaterThanOrEqual(0, $rate);
        $this->assertLessThanOrEqual(100, $rate);
    }

    /** @test */
    public function statistics_service_can_get_top_categories()
    {
        $categories = AttentionStatisticsService::getTopCategories(5);

        $this->assertInstanceOf(Collection::class, $categories);
    }

    /** @test */
    public function statistics_service_can_get_time_trend()
    {
        Attention::factory()->count(5)->create();

        $trend = AttentionStatisticsService::getTimeTrend('day', 7);

        $this->assertIsArray($trend);
        $this->assertCount(7, $trend);
    }
}
