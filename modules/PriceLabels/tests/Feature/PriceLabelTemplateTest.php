<?php

namespace Modules\PriceLabels\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\PriceLabels\Database\Seeders\PriceLabelsPermissionsSeeder;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PriceLabelTemplateTest extends TestCase
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

    public function test_guest_cannot_access_index(): void
    {
        $this->get(route('pricelabels.index'))->assertRedirect();
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pricelabels.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('pricelabels.index'))
            ->assertOk();
    }

    public function test_admin_can_create_template_with_images(): void
    {
        $response = $this->actingAs($this->admin)->post(route('pricelabels.store'), [
            'name' => 'Plantilla de prueba',
            'is_active' => '1',
            'orientation' => 'vertical',
            'label_text' => 'Precio recomendado:',
            'image_vertical' => UploadedFile::fake()->image('vertical.jpg'),
        ]);

        $template = PriceLabelTemplate::query()->where('name', 'Plantilla de prueba')->first();

        $response->assertRedirect(route('pricelabels.edit', $template));
        $this->assertNotNull($template);
        $this->assertNotNull($template->image_vertical);
        $this->assertNotEmpty($template->positions_vertical);
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('pricelabels.store'), [])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_field_styles(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('pricelabels.update', $template), [
            'name' => $template->name,
            'is_active' => '1',
            'orientation' => $template->orientation,
            'label_text' => $template->label_text,
            'fields' => [
                'referencia' => ['color' => '#ff0000', 'font_size' => 20, 'bold' => '1'],
            ],
        ]);

        $response->assertRedirect(route('pricelabels.edit', $template));

        $template->refresh();
        $this->assertSame('#ff0000', $template->fields['referencia']['color']);
        $this->assertSame(20, $template->fields['referencia']['font_size']);
        $this->assertTrue($template->fields['referencia']['bold']);
    }

    public function test_admin_can_save_positions(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('pricelabels.positions.save', $template), [
            'orientation' => 'vertical',
            'positions' => [
                'referencia' => [
                    '1' => ['x' => 120, 'y' => 60],
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $template->refresh();
        $this->assertSame(120, $template->positions_vertical['referencia']['1']['x']);
        $this->assertSame(60, $template->positions_vertical['referencia']['1']['y']);
    }

    public function test_admin_can_save_positions_together_with_field_style(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('pricelabels.positions.save', $template), [
            'orientation' => 'vertical',
            'positions' => [
                'referencia' => [
                    '1' => ['x' => 120, 'y' => 60],
                ],
            ],
            'fields' => [
                'referencia' => ['color' => '#ff0000', 'font_size' => 24, 'bold' => '1'],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $template->refresh();
        $this->assertSame(120, $template->positions_vertical['referencia']['1']['x']);
        $this->assertSame('#ff0000', $template->fields['referencia']['color']);
        $this->assertSame(24, $template->fields['referencia']['font_size']);
        $this->assertTrue($template->fields['referencia']['bold']);
    }

    public function test_save_positions_rejects_invalid_field_color(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)->post(route('pricelabels.positions.save', $template), [
            'orientation' => 'vertical',
            'positions' => [
                'referencia' => [
                    '1' => ['x' => 120, 'y' => 60],
                ],
            ],
            'fields' => [
                'referencia' => ['color' => 'not-a-color'],
            ],
        ])->assertSessionHasErrors('fields.referencia.color');
    }

    public function test_generate_fails_when_template_has_no_matching_image(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('pricelabels.generate', $template), [
                'types' => ['vertical'],
                'excel_file' => UploadedFile::fake()->create('catalog.xlsx', 10),
            ])
            ->assertSessionHasErrors('types');
    }

    public function test_admin_can_delete_template(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('pricelabels.destroy', $template))
            ->assertRedirect(route('pricelabels.index'));

        $this->assertSoftDeleted($template);
    }

    public function test_admin_can_duplicate_template_with_its_own_image_copy(): void
    {
        Storage::fake('public');

        $originalPath = 'pricelabels/backgrounds/original.jpg';
        Storage::disk('public')->put($originalPath, 'fake-image-bytes');

        $template = PriceLabelTemplate::factory()->create([
            'name' => 'Original',
            'image_vertical' => $originalPath,
        ]);

        $response = $this->actingAs($this->admin)->post(route('pricelabels.duplicate', $template));

        $copy = PriceLabelTemplate::query()->where('name', 'Original (copia)')->first();

        $this->assertNotNull($copy);
        $response->assertRedirect(route('pricelabels.edit', $copy));
        $this->assertNotSame($template->id, $copy->id);
        $this->assertNotSame($template->image_vertical, $copy->image_vertical);
        Storage::disk('public')->assertExists($copy->image_vertical);
        $this->assertSame($template->fields, $copy->fields);
    }

    public function test_new_template_default_positions_do_not_overlap_within_grid(): void
    {
        $response = $this->actingAs($this->admin)->post(route('pricelabels.store'), [
            'name' => 'Plantilla grid 2x3',
            'is_active' => '1',
            'orientation' => 'vertical',
            'vertical_rows' => 2,
            'vertical_columns' => 3,
        ]);

        $template = PriceLabelTemplate::query()->where('name', 'Plantilla grid 2x3')->first();
        $response->assertRedirect(route('pricelabels.edit', $template));

        $labelPositions = $template->positions_vertical['label'];
        $this->assertCount(6, $labelPositions);

        $unique = collect($labelPositions)->map(fn ($pos) => $pos['x'].':'.$pos['y'])->unique();
        $this->assertCount(6, $unique);
    }

    public function test_admin_can_add_and_remove_custom_field(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)->post(route('pricelabels.fields.store', $template), [
            'label' => 'Promocion',
            'excel_column' => 'E',
            'type' => 'text',
        ])->assertRedirect(route('pricelabels.edit', $template));

        $template->refresh();
        $keys = collect($template->field_definitions)->pluck('key');
        $this->assertTrue($keys->contains('promocion'));

        $this->actingAs($this->admin)->delete(route('pricelabels.fields.destroy', [$template, 'promocion']))
            ->assertRedirect(route('pricelabels.edit', $template));

        $template->refresh();
        $this->assertFalse(collect($template->field_definitions)->pluck('key')->contains('promocion'));
    }

    public function test_cannot_add_field_with_already_used_excel_column(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)->post(route('pricelabels.fields.store', $template), [
            'label' => 'Duplicado',
            'excel_column' => 'A',
            'type' => 'text',
        ])->assertSessionHasErrors('excel_column');

        $this->assertNull($template->fresh()->field_definitions);
    }

    public function test_edit_view_hides_horizontal_style_columns_for_vertical_only_template(): void
    {
        $template = PriceLabelTemplate::factory()->create(['orientation' => 'vertical']);

        $this->actingAs($this->admin)
            ->get(route('pricelabels.edit', $template))
            ->assertOk()
            ->assertSee('Fuente (vertical)')
            ->assertDontSee('Fuente (horizontal)');
    }

    public function test_admin_can_view_standalone_positions_editor(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('pricelabels.positions.edit', $template))
            ->assertOk()
            ->assertDontSee('Datos generales')
            ->assertSee('Volver a la edicion completa');
    }

    public function test_positions_editor_defaults_to_first_available_orientation(): void
    {
        $template = PriceLabelTemplate::factory()->create(['orientation' => 'both']);

        $this->actingAs($this->admin)
            ->get(route('pricelabels.positions.edit', $template))
            ->assertOk()
            ->assertSee('Previsualizacion vertical')
            ->assertDontSee('Previsualizacion horizontal');
    }

    public function test_positions_editor_shows_only_the_selected_orientation(): void
    {
        $template = PriceLabelTemplate::factory()->create(['orientation' => 'both']);

        $this->actingAs($this->admin)
            ->get(route('pricelabels.positions.edit', $template).'?orientation=horizontal')
            ->assertOk()
            ->assertSee('Previsualizacion horizontal')
            ->assertDontSee('Previsualizacion vertical');
    }

    public function test_positions_editor_hides_orientation_tabs_for_single_orientation_template(): void
    {
        $template = PriceLabelTemplate::factory()->create(['orientation' => 'vertical']);

        $this->actingAs($this->admin)
            ->get(route('pricelabels.positions.edit', $template))
            ->assertOk()
            ->assertDontSee('nav-pills', false);
    }

    public function test_user_without_permission_cannot_view_positions_editor(): void
    {
        $user = User::factory()->create();
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($user)
            ->get(route('pricelabels.positions.edit', $template))
            ->assertForbidden();
    }

    public function test_admin_can_download_excel_template_with_correct_headers(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('pricelabels.excel-template', $template));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $path = tempnam(sys_get_temp_dir(), 'pricelabels-download').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $sheet = (new \PhpOffice\PhpSpreadsheet\Reader\Xlsx)->load($path)->getActiveSheet();

        $this->assertSame('REFERENCIA', $sheet->getCell('A1')->getValue());
        $this->assertSame('DESCRIPCION', $sheet->getCell('B1')->getValue());
        $this->assertSame('PVP', $sheet->getCell('D1')->getValue());

        unlink($path);
    }

    public function test_preview_excel_returns_parsed_row_count(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('pricelabels.preview-excel', $template), [
                'excel_file' => $this->makeExcelFile(),
            ]);

        $response->assertOk()->assertJson(['success' => true, 'rows_count' => 2]);
    }

    private function makeExcelFile(): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['REFERENCIA', 'DESCRIPCION', 'PVP RECOM PROV', 'PVP'],
            ['REF-1', 'Producto uno', '10,00', '8,00'],
            ['REF-2', 'Producto dos', '20,00', '15,00'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'pricelabels').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'catalogo.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
