<?php

namespace Modules\Attention\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Attention\Exports\EmailHistoryExport;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionMail;
use Tests\TestCase;

class AttentionEmailExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super-settings');
    }

    /** @test */
    public function it_can_export_emails_to_excel()
    {
        Excel::fake();

        $attention = Attention::factory()->create();
        AttentionMail::factory()->count(5)->create(['attention_id' => $attention->id]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/emails/export', [
                'format' => 'excel',
            ]);

        $response->assertOk();

        Excel::assertDownloaded('historial_emails_*.xlsx', function (EmailHistoryExport $export) {
            return true;
        });
    }

    /** @test */
    public function it_can_export_emails_to_csv()
    {
        Excel::fake();

        $attention = Attention::factory()->create();
        AttentionMail::factory()->count(5)->create(['attention_id' => $attention->id]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/emails/export', [
                'format' => 'csv',
            ]);

        $response->assertOk();

        Excel::assertDownloaded('historial_emails_*.csv');
    }

    /** @test */
    public function it_can_filter_emails_by_attention()
    {
        $attention = Attention::factory()->create();
        AttentionMail::factory()->count(3)->create(['attention_id' => $attention->id]);
        AttentionMail::factory()->count(2)->create(); // Otros

        Excel::fake();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/emails/export', [
                'format' => 'excel',
                'attention_uid' => $attention->uid,
            ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_emails_by_type()
    {
        AttentionMail::factory()->count(3)->create(['email_type' => 'confirmation']);
        AttentionMail::factory()->count(2)->create(['email_type' => 'resolution']);

        Excel::fake();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/emails/export', [
                'format' => 'excel',
                'email_type' => 'confirmation',
            ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_emails_by_status()
    {
        AttentionMail::factory()->count(3)->create(['status' => 'sent']);
        AttentionMail::factory()->count(2)->create(['status' => 'failed']);

        Excel::fake();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/emails/export', [
                'format' => 'excel',
                'status' => 'sent',
            ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_emails_by_date_range()
    {
        AttentionMail::factory()->create(['created_at' => now()->subDays(10)]);
        AttentionMail::factory()->create(['created_at' => now()->subDays(5)]);
        AttentionMail::factory()->create(['created_at' => now()]);

        Excel::fake();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/emails/export', [
                'format' => 'excel',
                'date_from' => now()->subDays(7)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]);

        $response->assertOk();
    }

    /** @test */
    public function it_returns_404_when_no_emails_to_export()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/emails/export', [
                'format' => 'excel',
            ]);

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'No hay emails para exportar con los filtros aplicados.',
            ]);
    }

    /** @test */
    public function it_validates_export_format()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/emails/export', [
                'format' => 'invalid',
            ]);

        $response->assertUnprocessable();
    }
}
