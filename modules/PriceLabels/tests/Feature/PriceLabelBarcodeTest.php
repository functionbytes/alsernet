<?php

namespace Modules\PriceLabels\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\PriceLabels\Database\Seeders\PriceLabelsPermissionsSeeder;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use Modules\PriceLabels\Services\PriceLabelBarcodeService;
use Modules\PriceLabels\Services\PriceLabelPdfService;
use Tests\TestCase;

class PriceLabelBarcodeTest extends TestCase
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
        ]);
    }

    public function test_create_and_edit_views_render(): void
    {
        $this->actingAs($this->admin)->get(route('pricelabels.create'))->assertOk();

        $template = PriceLabelTemplate::factory()->create();
        $this->actingAs($this->admin)->get(route('pricelabels.edit', $template))->assertOk();
        $this->actingAs($this->admin)->get(route('pricelabels.positions.edit', $template))->assertOk();
    }

    public function test_service_generates_a_code128_data_uri(): void
    {
        $uri = app(PriceLabelBarcodeService::class)->pngDataUri('barcode', 'REF-123', 'C128');

        $this->assertNotNull($uri);
        $this->assertStringStartsWith('data:image/png;base64,', $uri);
    }

    public function test_service_generates_a_qr_data_uri(): void
    {
        $uri = app(PriceLabelBarcodeService::class)->pngDataUri('qr', 'https://example.com', 'C128');

        $this->assertNotNull($uri);
        $this->assertStringStartsWith('data:image/png;base64,', $uri);
    }

    public function test_service_returns_null_for_a_value_invalid_in_that_symbology(): void
    {
        // EAN-13 exige 12/13 digitos: un texto libre no es codificable.
        $this->assertNull(app(PriceLabelBarcodeService::class)->pngDataUri('barcode', 'NO-ES-EAN', 'EAN13'));
    }

    public function test_service_returns_null_for_an_empty_value(): void
    {
        $this->assertNull(app(PriceLabelBarcodeService::class)->pngDataUri('barcode', '   ', 'C128'));
    }

    public function test_admin_can_add_a_barcode_field(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('pricelabels.fields.store', $template), [
                'label' => 'Codigo EAN',
                'excel_column' => 'F',
                'type' => 'barcode',
                'barcode_type' => 'EAN13',
            ])
            ->assertRedirect(route('pricelabels.edit', $template));

        $template->refresh();
        $definition = collect($template->field_definitions)->firstWhere('key', 'codigo_ean');

        $this->assertNotNull($definition);
        $this->assertSame('barcode', $definition['type']);
        $this->assertSame('EAN13', $definition['barcode_type']);
    }

    public function test_adding_a_barcode_field_requires_a_symbology(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('pricelabels.fields.store', $template), [
                'label' => 'Codigo',
                'excel_column' => 'F',
                'type' => 'barcode',
            ])
            ->assertSessionHasErrors('barcode_type');
    }

    public function test_admin_can_add_a_qr_field_without_symbology(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('pricelabels.fields.store', $template), [
                'label' => 'Enlace QR',
                'excel_column' => 'G',
                'type' => 'qr',
            ])
            ->assertRedirect(route('pricelabels.edit', $template));

        $template->refresh();
        $this->assertSame('qr', collect($template->field_definitions)->firstWhere('key', 'enlace_qr')['type']);
    }

    public function test_unknown_field_type_is_rejected(): void
    {
        $template = PriceLabelTemplate::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('pricelabels.fields.store', $template), [
                'label' => 'Raro',
                'excel_column' => 'H',
                'type' => 'hologram',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_barcode_field_is_rendered_as_an_image_in_the_pdf(): void
    {
        $template = PriceLabelTemplate::factory()->create([
            'field_definitions' => [
                ['key' => 'ean', 'label' => 'EAN', 'excel_column' => 'A', 'type' => 'barcode', 'barcode_type' => 'EAN13', 'order' => 1],
            ],
        ]);

        $pdf = app(PriceLabelPdfService::class)->generate(
            $template,
            [['ean' => '8412345678905']],
            'vertical'
        );

        // DomPDF convierte el data URI en un XObject de imagen dentro del PDF.
        $this->assertStringContainsString('/Image', $pdf->output());
    }

    public function test_invalid_barcode_value_falls_back_to_text_instead_of_breaking(): void
    {
        $template = PriceLabelTemplate::factory()->create([
            'field_definitions' => [
                ['key' => 'ean', 'label' => 'EAN', 'excel_column' => 'A', 'type' => 'barcode', 'barcode_type' => 'EAN13', 'order' => 1],
            ],
        ]);

        $pdf = app(PriceLabelPdfService::class)->generate(
            $template,
            [['ean' => 'VALOR-INVALIDO']],
            'vertical'
        );

        $this->assertNotEmpty($pdf->output());
    }
}
