<?php

namespace Modules\PriceLabels\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\PriceLabels\Database\Seeders\PriceLabelsPermissionsSeeder;
use Modules\PriceLabels\Models\PriceLabelGeneration;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PriceLabelGenerationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PriceLabelsPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'pricelabels.view',
            'pricelabels.create',
            'pricelabels.update',
            'pricelabels.delete',
        ]);
    }

    public function test_generating_a_pdf_records_it_in_history(): void
    {
        Storage::fake('public');

        $template = PriceLabelTemplate::factory()->create([
            'image_vertical' => 'pricelabels/backgrounds/fake.jpg',
        ]);

        $excel = $this->makeExcelFile();

        $this->actingAs($this->admin)
            ->post(route('pricelabels.generate', $template), [
                'type' => 'vertical',
                'excel_file' => $excel,
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('price_label_generations', [
            'price_label_template_id' => $template->id,
            'template_name' => $template->name,
            'type' => 'vertical',
            'rows_count' => 1,
        ]);

        $generation = PriceLabelGeneration::query()->first();
        Storage::disk('public')->assertExists($generation->file_path);
    }

    public function test_history_index_lists_generations(): void
    {
        PriceLabelGeneration::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('pricelabels.history.index'))
            ->assertOk();
    }

    public function test_history_index_filters_by_template(): void
    {
        $template = PriceLabelTemplate::factory()->create();
        PriceLabelGeneration::factory()->create(['price_label_template_id' => $template->id]);
        PriceLabelGeneration::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('pricelabels.history.index', ['template_id' => $template->id]));

        $response->assertOk();
        $response->assertViewHas('generations', function ($generations) use ($template) {
            return $generations->total() === 1 && $generations->first()->price_label_template_id === $template->id;
        });
    }

    public function test_download_serves_the_stored_pdf(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pricelabels/generated/test.pdf', '%PDF-1.7 fake content');

        $generation = PriceLabelGeneration::factory()->create([
            'file_path' => 'pricelabels/generated/test.pdf',
            'file_name' => 'test.pdf',
        ]);

        $this->actingAs($this->admin)
            ->get(route('pricelabels.history.download', $generation))
            ->assertOk();
    }

    public function test_destroy_removes_row_and_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pricelabels/generated/test.pdf', 'fake content');

        $generation = PriceLabelGeneration::factory()->create([
            'file_path' => 'pricelabels/generated/test.pdf',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('pricelabels.history.destroy', $generation))
            ->assertRedirect(route('pricelabels.history.index'));

        $this->assertDatabaseMissing('price_label_generations', ['id' => $generation->id]);
        Storage::disk('public')->assertMissing('pricelabels/generated/test.pdf');
    }

    public function test_bulk_delete_removes_multiple_generations(): void
    {
        Storage::fake('public');

        $generations = PriceLabelGeneration::factory()->count(2)->create();
        foreach ($generations as $generation) {
            Storage::disk('public')->put($generation->file_path, 'fake content');
        }

        $this->actingAs($this->admin)
            ->postJson(route('pricelabels.history.bulk-action'), [
                'action' => 'delete',
                'ids' => $generations->pluck('id')->toArray(),
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('price_label_generations', 0);
    }

    public function test_user_without_permission_cannot_view_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pricelabels.history.index'))
            ->assertForbidden();
    }

    public function test_prune_command_deletes_only_old_generations(): void
    {
        Storage::fake('public');

        $old = PriceLabelGeneration::factory()->create(['file_path' => 'pricelabels/generated/old.pdf']);
        $old->created_at = now()->subDays(100);
        $old->save();
        Storage::disk('public')->put($old->file_path, 'old content');

        $recent = PriceLabelGeneration::factory()->create(['file_path' => 'pricelabels/generated/recent.pdf']);
        Storage::disk('public')->put($recent->file_path, 'recent content');

        $this->artisan('pricelabels:prune-generations')->assertSuccessful();

        $this->assertDatabaseMissing('price_label_generations', ['id' => $old->id]);
        $this->assertDatabaseHas('price_label_generations', ['id' => $recent->id]);
        Storage::disk('public')->assertMissing($old->file_path);
        Storage::disk('public')->assertExists($recent->file_path);
    }

    public function test_history_index_shows_stats(): void
    {
        PriceLabelGeneration::factory()->count(2)->create(['type' => 'vertical']);
        PriceLabelGeneration::factory()->create(['type' => 'horizontal']);

        $response = $this->actingAs($this->admin)->get(route('pricelabels.history.index'));

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 3 && $stats['vertical'] === 2 && $stats['horizontal'] === 1;
        });
    }

    private function makeExcelFile(): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['REFERENCIA', 'DESCRIPCION', 'PVP RECOM PROV', 'PVP'],
            ['REF-1', 'Producto de prueba', '10,00', '8,00'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'pricelabels').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'catalogo.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
