<?php

namespace Modules\GiftMessage\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Modules\GiftMessage\Database\Seeders\GiftMessagePermissionsSeeder;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Models\GiftMessageGeneration;
use Tests\TestCase;

class GiftMessageGenerationTest extends TestCase
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

        // Fuerza la fila singleton para que el config que arma cada test sea el
        // mismo que el controller/service ve al llamar a current() en la request.
        // firstOrCreate (no forceCreate): en este proyecto los tests corren contra
        // la misma BD 'webadmin', donde ya puede existir una fila real con id=1.
        GiftMessageConfig::query()->firstOrCreate(['id' => 1]);
    }

    public function test_generating_envelope_pdf_records_it_in_history(): void
    {
        Storage::fake('public');

        $backgroundPath = UploadedFile::fake()->image('envelope-bg.jpg')->store('giftmessage/images', 'public');
        GiftMessageConfig::current()->update(['envelope_image' => $backgroundPath]);

        $response = $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'envelope',
                'rows' => [[
                    'id_order' => 1,
                    'gift_message' => 'Feliz cumpleanos',
                    'firstname' => 'Maria',
                    'lastname' => 'Garcia',
                    'id_gestion' => '73230',
                    'npedidocli' => '41234',
                ]],
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('gift_message_generations', [
            'type' => 'envelope',
            'rows_count' => 1,
        ]);

        $generation = GiftMessageGeneration::query()->latest()->first();
        Storage::disk('public')->assertExists($generation->file_path);
    }

    public function test_envelope_pdf_prints_the_gift_message_and_not_the_recipient_name(): void
    {
        Storage::fake('public');

        $captured = null;
        View::composer('giftmessage::pdf.page', function ($view) use (&$captured) {
            $captured = $view->getData();
        });

        $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'envelope',
                'rows' => [[
                    'id_order' => 1,
                    'gift_message' => 'Feliz comunion Jaime',
                    'firstname' => 'Jorge',
                    'lastname' => 'Da Silva Orallo',
                    'id_gestion' => '102204020',
                    'npedidocli' => '29394',
                ]],
            ])
            ->assertOk();

        $this->assertNotNull($captured, 'La vista del PDF no llego a renderizarse.');
        $this->assertSame('Feliz comunion Jaime', $captured['pages'][0]['t1']['html']);
        $this->assertStringNotContainsString('Jorge', $captured['pages'][0]['t1']['html']);
        $this->assertSame('29394', $captured['pages'][0]['t2']['text']);
    }

    public function test_envelope_pdf_uses_the_envelope_config_columns_not_the_card_ones(): void
    {
        Storage::fake('public');

        GiftMessageConfig::current()->update([
            'env_t1_y' => 10, 'env_t1_size' => 30, 'env_t1_font' => 'times',
            'card_t1_y' => 90, 'card_t1_size' => 8, 'card_t1_font' => 'courier',
        ]);

        $captured = null;
        View::composer('giftmessage::pdf.page', function ($view) use (&$captured) {
            $captured = $view->getData();
        });

        $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'envelope',
                'rows' => [[
                    'id_order' => 1,
                    'gift_message' => 'Hola',
                    'firstname' => 'Ana',
                    'lastname' => 'Lopez',
                    'id_gestion' => '73230',
                    'npedidocli' => '41234',
                ]],
            ])
            ->assertOk();

        // 10% de los 110mm de alto del sobre.
        $this->assertSame(11.0, $captured['pages'][0]['t1']['top']);
        $this->assertSame(30, $captured['pages'][0]['t1']['font_size']);
        $this->assertStringContainsString('Times', $captured['pages'][0]['t1']['font_family']);
    }

    public function test_pdf_boxes_use_the_configured_width_and_height(): void
    {
        Storage::fake('public');

        GiftMessageConfig::current()->update([
            'env_t1_x' => 10, 'env_t1_y' => 20, 'env_t1_w' => 50, 'env_t1_h' => 30,
        ]);

        $captured = null;
        View::composer('giftmessage::pdf.page', function ($view) use (&$captured) {
            $captured = $view->getData();
        });

        $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'envelope',
                'rows' => [[
                    'id_order' => 1,
                    'gift_message' => 'Hola',
                    'firstname' => 'Ana',
                    'lastname' => 'Lopez',
                    'id_gestion' => '73230',
                    'npedidocli' => '41234',
                ]],
            ])
            ->assertOk();

        // El sobre mide 220x110mm: 10% / 20% / 50% / 30%.
        $this->assertSame(22.0, $captured['pages'][0]['t1']['left']);
        $this->assertSame(22.0, $captured['pages'][0]['t1']['top']);
        $this->assertSame(110.0, $captured['pages'][0]['t1']['width']);
        $this->assertSame(33.0, $captured['pages'][0]['t1']['height']);
    }

    public function test_t2_prints_the_erp_npedidocli_and_not_the_gestion_id(): void
    {
        Storage::fake('public');

        $captured = null;
        View::composer('giftmessage::pdf.page', function ($view) use (&$captured) {
            $captured = $view->getData();
        });

        $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'card',
                'rows' => [[
                    'id_order' => 833253,
                    'gift_message' => 'Feliz comunion',
                    'firstname' => 'Jorge',
                    'lastname' => 'Da Silva',
                    'id_gestion' => '102204020',
                    'npedidocli' => '41234',
                ]],
            ])
            ->assertOk();

        $this->assertSame('41234', $captured['pages'][0]['t2']['text']);
    }

    public function test_pdf_receives_the_configured_color_and_opacity(): void
    {
        Storage::fake('public');

        GiftMessageConfig::current()->update([
            'env_t1_color' => '#90bb13',
            'env_t1_opacity' => 35,
            'env_t2_color' => '#ff0000',
            'env_t2_opacity' => 100,
        ]);

        $captured = null;
        View::composer('giftmessage::pdf.page', function ($view) use (&$captured) {
            $captured = $view->getData();
        });

        $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'envelope',
                'rows' => [[
                    'id_order' => 1,
                    'gift_message' => 'Hola',
                    'firstname' => 'Ana',
                    'lastname' => 'Lopez',
                    'id_gestion' => '73230',
                    'npedidocli' => '41234',
                ]],
            ])
            ->assertOk();

        $this->assertSame('#90bb13', $captured['pages'][0]['t1']['color']);
        $this->assertSame(0.35, $captured['pages'][0]['t1']['opacity']);
        $this->assertSame('#ff0000', $captured['pages'][0]['t2']['color']);
        $this->assertSame(1.0, $captured['pages'][0]['t2']['opacity']);
    }

    public function test_pdf_falls_back_to_black_when_the_stored_color_is_invalid(): void
    {
        Storage::fake('public');

        GiftMessageConfig::current()->update(['env_t1_color' => 'roto']);

        $captured = null;
        View::composer('giftmessage::pdf.page', function ($view) use (&$captured) {
            $captured = $view->getData();
        });

        $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'envelope',
                'rows' => [[
                    'id_order' => 1,
                    'gift_message' => 'Hola',
                    'firstname' => 'Ana',
                    'lastname' => 'Lopez',
                    'id_gestion' => '73230',
                    'npedidocli' => '41234',
                ]],
            ])
            ->assertOk();

        $this->assertSame('#000000', $captured['pages'][0]['t1']['color']);
    }

    public function test_generating_card_pdf_with_emoji_message_does_not_fail(): void
    {
        Storage::fake('public');
        Http::fake(['*' => Http::response(str_repeat('x', 200), 200)]);

        $response = $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'card',
                'rows' => [[
                    'id_order' => 1,
                    'gift_message' => 'Feliz cumple 🎂',
                    'firstname' => 'Juan',
                    'lastname' => 'Perez',
                    'id_gestion' => '73231',
                ]],
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_generating_card_pdf_does_not_fail_when_the_twemoji_cdn_is_unreachable(): void
    {
        Storage::fake('public');
        Http::fake(fn () => throw new ConnectionException('cdnjs unreachable'));

        $response = $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'card',
                'rows' => [[
                    'id_order' => 1,
                    'gift_message' => 'Feliz cumple 🎂',
                    'firstname' => 'Juan',
                    'lastname' => 'Perez',
                    'id_gestion' => '73231',
                ]],
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_history_index_lists_generations(): void
    {
        GiftMessageGeneration::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('giftmessage.history.index'))
            ->assertOk();
    }

    public function test_history_index_filters_by_type(): void
    {
        GiftMessageGeneration::factory()->count(2)->envelope()->create();
        $card = GiftMessageGeneration::factory()->card()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('giftmessage.history.index', ['type' => 'card']));

        $response->assertOk();

        // Nada de contar el total: en este proyecto los tests corren contra la
        // BD de desarrollo, que ya trae generaciones reales. Se comprueba que el
        // filtro deja pasar la tarjeta creada y ninguna de las otras.
        $response->assertViewHas('generations', function ($generations) use ($card) {
            return $generations->contains(fn ($row) => $row->id === $card->id)
                && $generations->every(fn ($row) => $row->type === 'card');
        });
    }

    public function test_download_serves_the_stored_pdf(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('giftmessage/generated/test.pdf', '%PDF-1.7 fake content');

        $generation = GiftMessageGeneration::factory()->create([
            'file_path' => 'giftmessage/generated/test.pdf',
            'file_name' => 'test.pdf',
        ]);

        $this->actingAs($this->admin)
            ->get(route('giftmessage.history.download', $generation))
            ->assertOk();
    }

    public function test_view_serves_the_stored_pdf_inline(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('giftmessage/generated/test.pdf', '%PDF-1.7 fake content');

        $generation = GiftMessageGeneration::factory()->create([
            'file_path' => 'giftmessage/generated/test.pdf',
            'file_name' => 'test.pdf',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('giftmessage.history.view', $generation));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_destroy_removes_row_and_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('giftmessage/generated/test.pdf', 'fake content');

        $generation = GiftMessageGeneration::factory()->create([
            'file_path' => 'giftmessage/generated/test.pdf',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('giftmessage.history.destroy', $generation))
            ->assertRedirect(route('giftmessage.history.index'));

        $this->assertDatabaseMissing('gift_message_generations', ['id' => $generation->id]);
        Storage::disk('public')->assertMissing('giftmessage/generated/test.pdf');
    }

    public function test_bulk_delete_removes_multiple_generations(): void
    {
        Storage::fake('public');

        $generations = GiftMessageGeneration::factory()->count(2)->create();
        foreach ($generations as $generation) {
            Storage::disk('public')->put($generation->file_path, 'fake content');
        }

        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.history.bulk-action'), [
                'action' => 'delete',
                'ids' => $generations->pluck('id')->toArray(),
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'deleted' => 2]);

        foreach ($generations as $generation) {
            $this->assertDatabaseMissing('gift_message_generations', ['id' => $generation->id]);
        }
    }

    public function test_bulk_delete_ignores_ids_that_no_longer_exist(): void
    {
        Storage::fake('public');

        $generation = GiftMessageGeneration::factory()->create();
        Storage::disk('public')->put($generation->file_path, 'fake content');

        // Un id ya borrado (por otro usuario, con el listado abierto) no puede
        // tumbar la seleccion entera: se ignora y el resto se borra igual.
        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.history.bulk-action'), [
                'action' => 'delete',
                'ids' => [$generation->id, 999999999],
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'deleted' => 1]);

        $this->assertDatabaseMissing('gift_message_generations', ['id' => $generation->id]);
    }

    public function test_regenerating_one_order_creates_a_single_page_pdf(): void
    {
        Storage::fake('public');

        $generation = GiftMessageGeneration::factory()->card()->create([
            'rows_count' => 2,
            'order_numbers' => ['29394', '29389'],
            'rows' => [
                ['id_order' => 833253, 'npedidocli' => '29394', 'id_gestion' => '102204020', 'gift_message' => 'Feliz cumpleanos'],
                ['id_order' => 833248, 'npedidocli' => '29389', 'id_gestion' => '102204015', 'gift_message' => 'Enhorabuena'],
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('giftmessage.history.regenerate', $generation), ['order_number' => '29389'])
            ->assertOk()
            ->assertJson(['success' => true, 'order_number' => '29389']);

        // El PDF nuevo lleva solo esa fila y la generacion original no se toca.
        $new = GiftMessageGeneration::query()->latest('id')->first();

        $this->assertNotSame($generation->id, $new->id);
        $this->assertSame(1, $new->rows_count);
        $this->assertSame(['29389'], $new->order_numbers);
        $this->assertSame('card', $new->type);
        $this->assertSame('Enhorabuena', $new->rows[0]['gift_message']);
        $this->assertStringContainsString((string) $new->id, $response->json('view_url'));
        $this->assertDatabaseHas('gift_message_generations', ['id' => $generation->id]);
    }

    public function test_regenerating_rejects_an_order_that_is_not_in_the_pdf(): void
    {
        $generation = GiftMessageGeneration::factory()->card()->create([
            'order_numbers' => ['29394'],
            'rows' => [['npedidocli' => '29394', 'gift_message' => 'Hola']],
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.history.regenerate', $generation), ['order_number' => '11111'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Ese pedido no forma parte de este PDF.']);
    }

    public function test_regenerating_an_old_generation_falls_back_to_the_bridge(): void
    {
        Storage::fake('public');
        Config::set('giftmessage.bridge_url', 'https://ps.test/modules/alsernetbridge/api.php');
        Config::set('giftmessage.bridge_secret', 'test-secret');

        Http::fake([
            '*' => Http::response(['ok' => true, 'data' => ['orders' => [[
                'id_order' => 833253,
                'npedidocli' => '29394',
                'gift_message' => 'Mensaje recuperado del bridge',
            ]]]], 200),
        ]);

        // Generacion anterior a la columna `rows`: no guarda el mensaje.
        $generation = GiftMessageGeneration::factory()->card()->create([
            'order_numbers' => ['29394'],
            'rows' => null,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.history.regenerate', $generation), ['order_number' => '29394'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $new = GiftMessageGeneration::query()->latest('id')->first();

        $this->assertSame('Mensaje recuperado del bridge', $new->rows[0]['gift_message']);
    }

    public function test_regenerating_needs_the_create_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('giftmessage.view');

        $generation = GiftMessageGeneration::factory()->card()->create([
            'order_numbers' => ['29394'],
            'rows' => [['npedidocli' => '29394', 'gift_message' => 'Hola']],
        ]);

        $this->actingAs($user)
            ->postJson(route('giftmessage.history.regenerate', $generation), ['order_number' => '29394'])
            ->assertForbidden();
    }

    public function test_user_without_permission_cannot_view_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('giftmessage.history.index'))
            ->assertForbidden();
    }

    public function test_prune_command_deletes_only_old_generations(): void
    {
        Storage::fake('public');

        $old = GiftMessageGeneration::factory()->create(['file_path' => 'giftmessage/generated/old.pdf']);
        $old->created_at = now()->subDays(100);
        $old->save();
        Storage::disk('public')->put($old->file_path, 'old content');

        $recent = GiftMessageGeneration::factory()->create(['file_path' => 'giftmessage/generated/recent.pdf']);
        Storage::disk('public')->put($recent->file_path, 'recent content');

        $this->artisan('giftmessage:prune-generations')->assertSuccessful();

        $this->assertDatabaseMissing('gift_message_generations', ['id' => $old->id]);
        $this->assertDatabaseHas('gift_message_generations', ['id' => $recent->id]);
        Storage::disk('public')->assertMissing($old->file_path);
        Storage::disk('public')->assertExists($recent->file_path);
    }
}
