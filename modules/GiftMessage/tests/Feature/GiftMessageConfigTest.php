<?php

namespace Modules\GiftMessage\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Modules\GiftMessage\Database\Seeders\GiftMessagePermissionsSeeder;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Models\GiftMessageGeneration;
use Modules\GiftMessage\Services\GiftMessageOrderService;
use Tests\TestCase;

class GiftMessageConfigTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GiftMessagePermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'giftmessage.view',
            'giftmessage.create',
            'giftmessage.update',
            'giftmessage.delete',
        ]);

        // GiftMessageConfig::current() looks up id=1 via firstOrCreate(), pero 'id' no es
        // fillable, asi que una fila ausente se crearia con un id autoincremental en vez
        // de 1, rompiendo el lookup del singleton en la siguiente llamada a current(). Se
        // usa firstOrCreate (no forceCreate) porque en este proyecto los tests corren
        // contra la misma BD 'webadmin' (no una BD de test aislada) y ya puede existir
        // una fila real con id=1; forceCreate chocaria con una UniqueConstraintViolation.
        GiftMessageConfig::query()->firstOrCreate(['id' => 1]);
    }

    public function test_settings_index_shows_the_config_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.giftmessage.index'))
            ->assertOk()
            ->assertSee('Imagen de fondo')
            ->assertSee('Posicion del texto')
            ->assertSee('Tipografia');
    }

    public function test_user_without_update_permission_cannot_view_settings(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('giftmessage.view');

        $this->actingAs($user)
            ->get(route('settings.giftmessage.index'))
            ->assertForbidden();
    }

    public function test_panel_index_no_longer_shows_the_config_sections(): void
    {
        $this->actingAs($this->admin)
            ->get(route('giftmessage.index'))
            ->assertOk()
            ->assertDontSee('Imagenes base')
            ->assertDontSee('Fuentes y tamanos')
            ->assertSee('Buscar pedidos');
    }

    public function test_index_shows_the_panel(): void
    {
        $this->actingAs($this->admin)
            ->get(route('giftmessage.index'))
            ->assertOk();
    }

    public function test_index_does_not_preload_any_order(): void
    {
        // El listado solo se pinta tras buscar (via AJAX): entrar en la
        // pantalla no debe consultar los pedidos con mensaje regalo.
        $this->mock(GiftMessageOrderService::class, function ($mock) {
            $mock->shouldNotReceive('ordersWithGiftMessage');
        });

        $this->actingAs($this->admin)
            ->get(route('giftmessage.index'))
            ->assertOk()
            ->assertSee('Todavia no hay pedidos en pantalla');
    }

    public function test_preview_metrics_match_what_the_pdf_would_use(): void
    {
        GiftMessageConfig::current()->update([
            'card_t1_font' => 'helvetica',
            'card_t1_size' => 14,
            'card_t1_w' => 71.64,
            'card_t1_h' => 55.71,
        ]);

        // Un mensaje con emoji fuerza DejaVu Sans en el PDF: la vista previa del
        // editor tiene que ensenar esa misma fuente, no la configurada.
        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.preview.metrics'), [
                'message' => 'Feliz cumpleanos 🎉',
                'order' => '29394',
            ])
            ->assertOk();

        $this->assertSame('dejavusans', $response->json('card.t1.font'));
        $this->assertSame(14, $response->json('card.t1.font_size'));
        $this->assertGreaterThan(1.2, $response->json('card.t1.line_height'));

        // Y un mensaje que no cabe se reduce, igual que al generar el PDF.
        $largo = str_repeat('Muchas felicidades de parte de toda la familia. ', 20);

        $reducido = $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.preview.metrics'), ['message' => $largo, 'order' => '29394'])
            ->assertOk();

        $this->assertLessThan(14, $reducido->json('card.t1.font_size'));
    }

    public function test_preview_metrics_use_the_box_size_on_screen(): void
    {
        GiftMessageConfig::current()->update([
            'card_t1_font' => 'helvetica',
            'card_t1_size' => 14,
            'card_t1_w' => 71.64,
            'card_t1_h' => 55.71,
        ]);

        $mensaje = str_repeat('Felicidades y muchos abrazos de toda la familia. ', 6);

        // La caja que manda es la que el usuario tiene en pantalla, aunque no la
        // haya guardado: si la achica, la letra debe encoger ya.
        $grande = $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.preview.metrics'), ['message' => $mensaje])
            ->json('card.t1.font_size');

        $pequena = $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.preview.metrics'), [
                'message' => $mensaje,
                'boxes' => ['card' => ['t1' => ['w' => 30, 'h' => 20]]],
            ])
            ->json('card.t1.font_size');

        $this->assertLessThan($grande, $pequena);
    }

    public function test_saving_the_t1_content_only_touches_its_own_piece(): void
    {
        GiftMessageConfig::current()->update(['env_t1_content' => 'message', 'card_t1_content' => 'message']);

        $this->actingAs($this->admin)
            ->post(route('settings.giftmessage.content.update'), [
                'scope' => 'envelope',
                'env_t1_content' => 'recipient',
            ])
            ->assertRedirect(route('settings.giftmessage.index'));

        $config = GiftMessageConfig::current()->fresh();

        $this->assertSame('recipient', $config->env_t1_content);
        $this->assertSame('message', $config->card_t1_content);
    }

    public function test_preview_metrics_use_the_recipient_when_the_piece_prints_the_name(): void
    {
        GiftMessageConfig::current()->update([
            'env_t1_content' => 'recipient',
            'env_t1_font' => 'helvetica',
            'env_t1_size' => 14,
        ]);

        // El nombre cabe de sobra aunque el mensaje sea larguisimo: la vista
        // previa del sobre tiene que medir el nombre, no el mensaje.
        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.preview.metrics'), [
                'message' => str_repeat('Muchas felicidades de parte de toda la familia. ', 40),
                'recipient' => 'Jorge Da Silva',
                'order' => '29394',
            ])
            ->assertOk();

        $this->assertSame(14, $response->json('envelope.t1.font_size'));
        $this->assertLessThan(14, $response->json('card.t1.font_size'));
    }

    public function test_the_test_pdf_uses_the_screen_state_and_leaves_no_trace(): void
    {
        GiftMessageConfig::current()->update([
            'card_t1_align' => 'center',
            'card_t1_valign' => 'middle',
            'card_t1_size' => 14,
        ]);

        $antes = GiftMessageGeneration::query()->count();

        $captured = null;
        View::composer('giftmessage::pdf.page', function ($view) use (&$captured) {
            $captured = $view->getData();
        });

        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.preview.pdf'), [
                'scope' => 'card',
                'message' => 'Feliz cumpleanos',
                'order' => '29394',
                // Lo que el usuario tiene en pantalla y todavia no ha guardado.
                'styles' => ['t1' => ['align' => 'left', 'valign' => 'top', 'size' => 20]],
                'boxes' => ['t1' => ['x' => 5, 'y' => 5, 'w' => 80, 'h' => 40]],
            ]);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        // Manda la pantalla, no lo guardado.
        $this->assertSame('left', $captured['pages'][0]['t1']['align']);
        $this->assertSame('top', $captured['pages'][0]['t1']['valign']);
        $this->assertSame(20.0, $captured['pages'][0]['t1']['font_size']);

        // Y no deja rastro: ni en el historial ni en la configuracion guardada.
        $this->assertSame($antes, GiftMessageGeneration::query()->count());
        $this->assertSame('center', GiftMessageConfig::current()->fresh()->card_t1_align);
    }

    public function test_the_test_pdf_needs_the_update_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('giftmessage.view');

        $this->actingAs($user)
            ->postJson(route('settings.giftmessage.preview.pdf'), ['scope' => 'card', 'message' => 'Hola'])
            ->assertForbidden();
    }

    public function test_user_without_permission_cannot_view_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('giftmessage.index'))
            ->assertForbidden();
    }

    public function test_uploading_images_updates_the_config(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('settings.giftmessage.images.store'), [
                'envelope_image' => UploadedFile::fake()->image('envelope.jpg'),
                'card_image' => UploadedFile::fake()->image('card.jpg'),
            ])
            ->assertRedirect(route('settings.giftmessage.index'));

        $config = GiftMessageConfig::current()->fresh();

        $this->assertStringStartsWith('giftmessage/images/envelope_', $config->envelope_image);
        $this->assertStringStartsWith('giftmessage/images/card_', $config->card_image);

        $this->assertDatabaseHas('gift_message_configs', [
            'id' => $config->id,
            'envelope_image' => $config->envelope_image,
            'card_image' => $config->card_image,
        ]);

        Storage::disk('public')->assertExists($config->envelope_image);
        Storage::disk('public')->assertExists($config->card_image);
    }

    public function test_saving_fonts_updates_the_config(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.giftmessage.typography.update'), [
                'env_t1_font' => 'times',
                'env_t1_size' => 16,
                'env_t2_font' => 'courier',
                'env_t2_size' => 10,
                'card_t1_font' => 'dejavusans',
                'card_t1_size' => 18,
                'card_t2_font' => 'dejavuserif',
                'card_t2_size' => 9,
            ])
            ->assertRedirect(route('settings.giftmessage.index'));

        $this->assertDatabaseHas('gift_message_configs', [
            'env_t1_font' => 'times',
            'env_t1_size' => 16,
            'env_t2_font' => 'courier',
            'env_t2_size' => 10,
            'card_t1_font' => 'dejavusans',
            'card_t1_size' => 18,
            'card_t2_font' => 'dejavuserif',
            'card_t2_size' => 9,
        ]);
    }

    public function test_saving_fonts_stores_colors_and_opacity(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.giftmessage.typography.update'), [
                'env_t1_color' => '#90BB13',
                'env_t1_opacity' => 40,
                'card_t2_color' => '#ff0000',
                'card_t2_opacity' => 0,
            ])
            ->assertRedirect(route('settings.giftmessage.index'));

        $this->assertDatabaseHas('gift_message_configs', [
            'env_t1_color' => '#90BB13',
            'env_t1_opacity' => 40,
            'card_t2_color' => '#ff0000',
            'card_t2_opacity' => 0,
        ]);
    }

    public function test_saving_fonts_rejects_an_invalid_color(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.typography.update'), ['env_t1_color' => 'rojo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('env_t1_color');
    }

    public function test_saving_fonts_rejects_an_opacity_above_one_hundred(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.typography.update'), ['env_t1_opacity' => 140])
            ->assertStatus(422)
            ->assertJsonValidationErrors('env_t1_opacity');
    }

    public function test_saving_positions_updates_the_config_via_ajax(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.positions.save'), [
                'scope' => 'envelope',
                't1_x' => 15.5, 't1_y' => 20.25, 't1_w' => 70, 't1_h' => 30.5,
                't2_x' => 30, 't2_y' => 40, 't2_w' => 25, 't2_h' => 8,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('gift_message_configs', [
            'env_t1_x' => 15.5,
            'env_t1_y' => 20.25,
            'env_t1_w' => 70,
            'env_t1_h' => 30.5,
            'env_t2_x' => 30,
            'env_t2_y' => 40,
            'env_t2_w' => 25,
            'env_t2_h' => 8,
        ]);
    }

    public function test_saving_positions_rejects_invalid_scope(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.positions.save'), [
                'scope' => 'invalid',
                't1_x' => 10, 't1_y' => 10, 't1_w' => 10, 't1_h' => 10,
                't2_x' => 10, 't2_y' => 10, 't2_w' => 10, 't2_h' => 10,
            ])
            ->assertStatus(422);
    }

    public function test_saving_positions_rejects_a_box_without_size(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.giftmessage.positions.save'), [
                'scope' => 'envelope',
                't1_x' => 10, 't1_y' => 10, 't1_w' => 0, 't1_h' => 10,
                't2_x' => 10, 't2_y' => 10, 't2_w' => 10, 't2_h' => 10,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('t1_w');
    }

    public function test_user_without_update_permission_cannot_upload_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->givePermissionTo('giftmessage.view');

        $this->actingAs($user)
            ->post(route('settings.giftmessage.images.store'), [
                'envelope_image' => UploadedFile::fake()->image('envelope.jpg'),
            ])
            ->assertForbidden();
    }
}
