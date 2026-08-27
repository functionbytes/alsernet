<?php

namespace Modules\PriceLabels\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Modules\PriceLabels\Database\Seeders\PriceLabelsPermissionsSeeder;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use Tests\TestCase;

class PriceLabelPreviewPdfTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PriceLabelsPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['pricelabels.view', 'pricelabels.update']);
    }

    private function templateWithImage(): PriceLabelTemplate
    {
        $path = 'pricelabels/backgrounds/preview.jpg';
        Storage::disk('public')->put($path, file_get_contents(__DIR__.'/../fixtures/blank.png'));

        return PriceLabelTemplate::factory()->create(['image_vertical' => $path]);
    }

    public function test_user_without_permission_cannot_preview(): void
    {
        $template = PriceLabelTemplate::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('pricelabels.preview-pdf', $template), ['orientation' => 'vertical'])
            ->assertForbidden();
    }

    public function test_preview_returns_an_inline_pdf(): void
    {
        $template = $this->templateWithImage();

        $response = $this->actingAs($this->admin)
            ->post(route('pricelabels.preview-pdf', $template), ['orientation' => 'vertical']);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_preview_fails_when_the_orientation_has_no_image(): void
    {
        $template = PriceLabelTemplate::factory()->create(['image_vertical' => null]);

        $this->actingAs($this->admin)
            ->post(route('pricelabels.preview-pdf', $template), ['orientation' => 'vertical'])
            ->assertSessionHasErrors('orientation');
    }

    public function test_preview_uses_unsaved_overrides_without_persisting_them(): void
    {
        $template = $this->templateWithImage();
        $originalFields = $template->fields;
        $originalPositions = $template->positions_vertical;

        $this->actingAs($this->admin)
            ->post(route('pricelabels.preview-pdf', $template), [
                'orientation' => 'vertical',
                'positions' => ['referencia' => ['1' => ['x' => 333, 'y' => 444]]],
                'fields' => ['referencia' => ['color' => '#ff0000', 'font_size' => 40]],
            ])
            ->assertOk();

        // La previsualizacion no debe tocar la plantilla guardada.
        $template->refresh();
        $this->assertSame($originalFields, $template->fields);
        $this->assertSame($originalPositions, $template->positions_vertical);
    }

    public function test_preview_rejects_an_invalid_orientation(): void
    {
        $template = $this->templateWithImage();

        $this->actingAs($this->admin)
            ->post(route('pricelabels.preview-pdf', $template), ['orientation' => 'diagonal'])
            ->assertSessionHasErrors('orientation');
    }
}
