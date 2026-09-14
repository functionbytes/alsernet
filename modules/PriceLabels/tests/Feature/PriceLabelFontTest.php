<?php

namespace Modules\PriceLabels\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\PriceLabels\Database\Seeders\PriceLabelsPermissionsSeeder;
use Modules\PriceLabels\Models\PriceLabelFont;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use Modules\PriceLabels\Services\PriceLabelFontService;
use Modules\PriceLabels\Services\PriceLabelPdfService;
use Tests\TestCase;

class PriceLabelFontTest extends TestCase
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
            'pricelabels.update',
            'pricelabels.settings.view',
            'pricelabels.settings.update',
        ]);
    }

    /**
     * TTF real del sistema: hace falta una fuente parseable de verdad porque
     * la subida valida el fichero con FontLib y el PDF la embebe.
     */
    private function realFontFile(string $name = 'montserrat.ttf'): UploadedFile
    {
        $source = '/System/Library/Fonts/Supplemental/Andale Mono.ttf';

        if (! file_exists($source)) {
            $this->markTestSkipped('No hay una fuente TTF del sistema disponible para la prueba.');
        }

        $temp = tempnam(sys_get_temp_dir(), 'font').'.ttf';
        copy($source, $temp);

        return new UploadedFile($temp, $name, 'font/ttf', null, true);
    }

    public function test_user_without_settings_permission_cannot_view_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.pricelabels.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.pricelabels.index'))
            ->assertOk();
    }

    public function test_admin_can_upload_a_font(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.pricelabels.fonts.store'), [
            'name' => 'Montserrat',
            'weight' => 'normal',
            'style' => 'normal',
            'font_file' => $this->realFontFile(),
        ]);

        $response->assertRedirect(route('settings.pricelabels.index'));

        $font = PriceLabelFont::query()->where('family', 'montserrat')->first();
        $this->assertNotNull($font);
        $this->assertSame('Montserrat', $font->name);
        $this->assertTrue(Storage::disk('public')->exists($font->file_path));
    }

    public function test_upload_rejects_a_file_that_is_not_a_font(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.pricelabels.fonts.store'), [
                'name' => 'Falsa',
                'weight' => 'normal',
                'style' => 'normal',
                'font_file' => UploadedFile::fake()->create('fake.ttf', 10),
            ])
            ->assertSessionHasErrors('font_file');

        $this->assertDatabaseMissing('price_label_fonts', ['family' => 'falsa']);
    }

    public function test_upload_rejects_a_name_reserved_by_a_builtin_font(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.pricelabels.fonts.store'), [
                'name' => 'Helvetica',
                'weight' => 'normal',
                'style' => 'normal',
                'font_file' => $this->realFontFile(),
            ])
            ->assertSessionHasErrors('family');
    }

    public function test_upload_rejects_a_duplicated_variant(): void
    {
        $this->actingAs($this->admin)->post(route('settings.pricelabels.fonts.store'), [
            'name' => 'Montserrat',
            'weight' => 'normal',
            'style' => 'normal',
            'font_file' => $this->realFontFile(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('settings.pricelabels.fonts.store'), [
                'name' => 'Montserrat',
                'weight' => 'normal',
                'style' => 'normal',
                'font_file' => $this->realFontFile(),
            ])
            ->assertSessionHasErrors('font_file');

        $this->assertSame(1, PriceLabelFont::query()->where('family', 'montserrat')->count());
    }

    public function test_admin_can_delete_a_font_and_its_file(): void
    {
        $this->actingAs($this->admin)->post(route('settings.pricelabels.fonts.store'), [
            'name' => 'Montserrat',
            'weight' => 'normal',
            'style' => 'normal',
            'font_file' => $this->realFontFile(),
        ]);

        $font = PriceLabelFont::query()->where('family', 'montserrat')->firstOrFail();
        $path = $font->file_path;

        $this->actingAs($this->admin)
            ->delete(route('settings.pricelabels.fonts.destroy', $font))
            ->assertRedirect(route('settings.pricelabels.index'));

        $this->assertDatabaseMissing('price_label_fonts', ['id' => $font->id]);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_uploaded_font_becomes_selectable_in_templates(): void
    {
        $service = app(PriceLabelFontService::class);

        $this->assertArrayNotHasKey('montserrat', $service->familyOptions());

        $this->actingAs($this->admin)->post(route('settings.pricelabels.fonts.store'), [
            'name' => 'Montserrat',
            'weight' => 'normal',
            'style' => 'normal',
            'font_file' => $this->realFontFile(),
        ]);

        $fresh = app()->make(PriceLabelFontService::class);
        $this->assertArrayHasKey('montserrat', $fresh->familyOptions());
        $this->assertContains('montserrat', $fresh->allowedFamilies());
    }

    public function test_template_accepts_a_custom_font_family(): void
    {
        $this->actingAs($this->admin)->post(route('settings.pricelabels.fonts.store'), [
            'name' => 'Montserrat',
            'weight' => 'normal',
            'style' => 'normal',
            'font_file' => $this->realFontFile(),
        ]);

        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('pricelabels.positions.save', $template), [
                'orientation' => 'vertical',
                'positions' => ['referencia' => ['1' => ['x' => 10, 'y' => 10]]],
                'fields' => ['referencia' => ['font_family' => 'montserrat']],
            ])
            ->assertOk();

        $template->refresh();
        $this->assertSame('montserrat', $template->fields['referencia']['font_family']);
    }

    public function test_template_rejects_an_unknown_font_family(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('pricelabels.positions.save', $template), [
                'orientation' => 'vertical',
                'positions' => ['referencia' => ['1' => ['x' => 10, 'y' => 10]]],
                'fields' => ['referencia' => ['font_family' => 'inexistente']],
            ])
            ->assertSessionHasErrors('fields.referencia.font_family');
    }

    public function test_uploaded_font_is_embedded_in_the_generated_pdf(): void
    {
        $this->actingAs($this->admin)->post(route('settings.pricelabels.fonts.store'), [
            'name' => 'Montserrat',
            'weight' => 'normal',
            'style' => 'normal',
            'font_file' => $this->realFontFile(),
        ]);

        $template = PriceLabelTemplate::factory()->create([
            'fields' => [
                'referencia' => ['color' => '#000000', 'font_family' => 'montserrat', 'font_size' => 20],
            ],
        ]);

        $pdf = app(PriceLabelPdfService::class)->generate(
            $template,
            [['referencia' => 'REF-123', 'descripcion' => 'Producto', 'pvprp' => '10', 'pvp' => '8']],
            'vertical'
        );

        $output = $pdf->output();

        // El nombre interno de la fuente solo aparece en el PDF si DomPDF la
        // resolvio via @font-face y la embebio, en vez de caer al fallback.
        $this->assertStringContainsString('AndaleMono', $output);
    }
}
