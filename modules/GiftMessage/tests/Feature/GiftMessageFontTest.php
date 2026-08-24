<?php

namespace Modules\GiftMessage\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\GiftMessage\Database\Seeders\GiftMessagePermissionsSeeder;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Models\GiftMessageFont;
use Modules\GiftMessage\Services\GiftMessageFontService;
use Tests\TestCase;

class GiftMessageFontTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GiftMessagePermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['giftmessage.view', 'giftmessage.update']);

        GiftMessageConfig::query()->firstOrCreate(['id' => 1]);
    }

    /**
     * Una fuente real minima: el request rechaza cualquier fichero que FontLib no
     * sepa parsear, asi que no vale un UploadedFile::fake() con bytes aleatorios.
     */
    private function realFontFile(string $name = 'custom.ttf'): UploadedFile
    {
        $source = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');

        $this->assertFileExists($source, 'No se encuentra la fuente de DomPDF para la prueba.');

        $copy = tempnam(sys_get_temp_dir(), 'gmfont').'.ttf';
        copy($source, $copy);

        return new UploadedFile($copy, $name, 'font/ttf', null, true);
    }

    public function test_uploading_a_font_stores_the_file_and_the_record(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('settings.giftmessage.fonts.store'), [
                'name' => 'Montserrat',
                'weight' => 'normal',
                'style' => 'normal',
                'font_file' => $this->realFontFile(),
            ])
            ->assertRedirect(route('settings.giftmessage.index'));

        $font = GiftMessageFont::query()->where('family', 'montserrat')->firstOrFail();

        $this->assertSame('Montserrat', $font->name);
        $this->assertSame($this->admin->id, $font->created_by);
        Storage::disk('public')->assertExists($font->file_path);
    }

    public function test_uploaded_font_becomes_selectable_in_the_typography_form(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('settings.giftmessage.fonts.store'), [
                'name' => 'Montserrat',
                'weight' => 'normal',
                'style' => 'normal',
                'font_file' => $this->realFontFile(),
            ])
            ->assertRedirect(route('settings.giftmessage.index'));

        $this->actingAs($this->admin)
            ->post(route('settings.giftmessage.typography.update'), ['env_t1_font' => 'montserrat'])
            ->assertRedirect(route('settings.giftmessage.index'));

        $this->assertDatabaseHas('gift_message_configs', ['env_t1_font' => 'montserrat']);
    }

    public function test_typography_rejects_a_font_family_that_was_never_uploaded(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.typography.update'), ['env_t1_font' => 'inventada'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('env_t1_font');
    }

    public function test_uploading_a_file_that_is_not_a_font_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('settings.giftmessage.fonts.store'), [
                'name' => 'Falsa',
                'weight' => 'normal',
                'style' => 'normal',
                'font_file' => UploadedFile::fake()->create('falsa.ttf', 10),
            ])
            ->assertSessionHasErrors('font_file');

        $this->assertDatabaseCount('gift_message_fonts', 0);
    }

    public function test_the_same_variant_cannot_be_uploaded_twice(): void
    {
        Storage::fake('public');

        $payload = fn () => [
            'name' => 'Montserrat',
            'weight' => 'bold',
            'style' => 'normal',
            'font_file' => $this->realFontFile(),
        ];

        $this->actingAs($this->admin)->post(route('settings.giftmessage.fonts.store'), $payload());
        $this->actingAs($this->admin)
            ->post(route('settings.giftmessage.fonts.store'), $payload())
            ->assertSessionHasErrors('font_file');

        $this->assertDatabaseCount('gift_message_fonts', 1);
    }

    public function test_a_builtin_family_name_is_reserved(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('settings.giftmessage.fonts.store'), [
                'name' => 'helvetica',
                'weight' => 'normal',
                'style' => 'normal',
                'font_file' => $this->realFontFile(),
            ])
            ->assertSessionHasErrors('family');
    }

    public function test_deleting_a_font_removes_the_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post(route('settings.giftmessage.fonts.store'), [
            'name' => 'Montserrat',
            'weight' => 'normal',
            'style' => 'normal',
            'font_file' => $this->realFontFile(),
        ]);

        $font = GiftMessageFont::query()->firstOrFail();
        $path = $font->file_path;

        $this->actingAs($this->admin)
            ->delete(route('settings.giftmessage.fonts.destroy', $font))
            ->assertRedirect(route('settings.giftmessage.index'));

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('gift_message_fonts', ['id' => $font->id]);
    }

    public function test_a_user_without_update_permission_cannot_upload_fonts(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->givePermissionTo('giftmessage.view');

        $this->actingAs($user)
            ->post(route('settings.giftmessage.fonts.store'), [
                'name' => 'Montserrat',
                'weight' => 'normal',
                'style' => 'normal',
                'font_file' => $this->realFontFile(),
            ])
            ->assertForbidden();
    }

    public function test_font_face_css_points_to_the_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post(route('settings.giftmessage.fonts.store'), [
            'name' => 'Montserrat',
            'weight' => 'bold',
            'style' => 'italic',
            'font_file' => $this->realFontFile(),
        ]);

        $css = app(GiftMessageFontService::class)->fontFaceCss(forPdf: true);

        $this->assertStringContainsString("font-family: 'montserrat'", $css);
        $this->assertStringContainsString('font-weight: bold', $css);
        $this->assertStringContainsString('font-style: italic', $css);
        $this->assertStringContainsString('file://', $css);
        $this->assertStringContainsString("format('truetype')", $css);
    }
}
