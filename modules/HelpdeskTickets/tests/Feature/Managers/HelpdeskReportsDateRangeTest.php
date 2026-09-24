<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HelpdeskReportsDateRangeTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Role::findOrCreate('super-settings', 'web');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('super-settings');
    }

    public function test_reports_index_with_valid_dates_returns_ok(): void
    {
        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.reports.index', [
                'from' => now()->subDays(7)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk();
    }

    public function test_reports_index_with_malformed_dates_falls_back_instead_of_500(): void
    {
        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.reports.index', [
                'from' => 'not-a-date',
                'to' => 'garbage-value',
            ]))
            ->assertOk();
    }

    public function test_reports_index_with_inverted_range_falls_back_instead_of_500(): void
    {
        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.reports.index', [
                'from' => now()->toDateString(),
                'to' => now()->subDays(30)->toDateString(),
            ]))
            ->assertOk();
    }

    public function test_reports_export_with_malformed_dates_falls_back_instead_of_500(): void
    {
        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.reports.export', [
                'from' => '9999-99-99',
                'to' => '<script>',
            ]))
            ->assertOk();
    }

    public function test_reports_show_backlog_aging_and_agent_metrics_and_export_them(): void
    {
        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.reports.index', ['from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk()
            ->assertSee('Antigüedad del backlog')
            ->assertSee('Rendimiento de agentes');

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.reports.export-agents', ['from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk();
        $this->assertStringContainsString('Resueltos/cerrados', $response->streamedContent());
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
