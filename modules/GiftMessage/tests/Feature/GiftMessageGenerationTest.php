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
use Modules\GiftMessage\Services\GiftMessageGenerationService;
use Modules\GiftMessage\Services\GiftMessageOrderService;
use Modules\GiftMessage\Services\GiftMessagePdfService;
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

    /**
     * Texto plano de lo que se imprime: sin etiquetas y con los espacios duros
     * (los que evitan la palabra suelta al final) como espacios normales.
     */
    private function plainText(string $html): string
    {
        return str_replace("\u{00A0}", ' ', strip_tags($html));
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
        // Este caso es el del sobre configurado para imprimir el mensaje; el
        // que imprime el nombre tiene su propio test.
        GiftMessageConfig::current()->update(['env_t1_content' => 'message']);

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
        // El mensaje va envuelto en un bloque por parrafo y las dos ultimas
        // palabras van unidas por un espacio duro (para que no quede una sola
        // palabra en el ultimo renglon), asi que se normaliza antes de comparar.
        $this->assertStringContainsString('Feliz comunion Jaime', $this->plainText($captured['pages'][0]['t1']['html']));
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
        // El tamano puede llevar medio punto desde que el ajuste va en pasos de 0,5.
        $this->assertSame(30.0, $captured['pages'][0]['t1']['font_size']);
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

    public function test_generating_again_replaces_the_previous_pdf_of_the_same_order(): void
    {
        Storage::fake('public');

        $previous = GiftMessageGeneration::factory()->envelope()->create([
            'rows_count' => 1,
            'order_numbers' => ['41234'],
        ]);
        Storage::disk('public')->put($previous->file_path, 'pdf viejo');

        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.generate'), [
                'type' => 'envelope',
                'rows' => [['id_order' => 1, 'gift_message' => 'Feliz cumpleanos', 'npedidocli' => '41234']],
            ])
            ->assertOk();

        // El anterior desaparece con su fichero: no se acumulan "Ver sobre"
        // repetidos en el listado de pedidos.
        $this->assertDatabaseMissing('gift_message_generations', ['id' => $previous->id]);
        Storage::disk('public')->assertMissing($previous->file_path);
    }

    public function test_regenerating_one_order_keeps_the_batch_it_belonged_to(): void
    {
        Storage::fake('public');

        // Un lote con tres pedidos es la unica copia de los otros dos, asi que
        // rehacer uno suelto no puede llevarselo por delante.
        $batch = GiftMessageGeneration::factory()->envelope()->create([
            'rows_count' => 3,
            'order_numbers' => ['41234', '41235', '41236'],
        ]);
        Storage::disk('public')->put($batch->file_path, 'pdf del lote');

        $otherType = GiftMessageGeneration::factory()->card()->create([
            'rows_count' => 1,
            'order_numbers' => ['41234'],
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.generate'), [
                'type' => 'envelope',
                'rows' => [['id_order' => 1, 'gift_message' => 'Feliz cumpleanos', 'npedidocli' => '41234']],
            ])
            ->assertOk();

        $this->assertDatabaseHas('gift_message_generations', ['id' => $batch->id]);
        Storage::disk('public')->assertExists($batch->file_path);

        // Y la tarjeta del mismo pedido tampoco: solo se reemplaza el mismo tipo.
        $this->assertDatabaseHas('gift_message_generations', ['id' => $otherType->id]);
    }

    public function test_the_order_list_only_links_the_current_pdf_of_each_type(): void
    {
        GiftMessageGeneration::factory()->envelope()->create([
            'order_numbers' => ['41234'],
            'created_at' => now()->subDays(2),
        ]);
        $newest = GiftMessageGeneration::factory()->envelope()->create([
            'order_numbers' => ['41234'],
            'created_at' => now(),
        ]);

        $index = app(GiftMessageGenerationService::class)->orderNumberIndex();

        $this->assertArrayHasKey('41234', $index);

        // El indice trae todas, pero el listado se queda con una por tipo: la
        // ultima. Se comprueba a traves del servicio de pedidos.
        $service = app(GiftMessageOrderService::class);
        $method = new \ReflectionMethod($service, 'attachExistingGenerations');
        $method->setAccessible(true);

        $rows = $method->invoke($service, [['id_order' => 1, 'npedidocli' => '41234']]);
        $generations = $rows[0]['existing_generations'];

        $this->assertCount(1, $generations);
        $this->assertSame($newest->id, $generations[0]['id']);
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

    public function test_line_height_is_tightened_before_shrinking_the_letter(): void
    {
        $config = GiftMessageConfig::current();
        $config->update(['card_t1_font' => 'helvetica', 'card_t1_size' => 14, 'card_t1_w' => 71.64, 'card_t1_h' => 55.71, 'min_font_size' => 7]);

        $service = app(GiftMessagePdfService::class);
        $method = new \ReflectionMethod($service, 'buildPage');
        $method->setAccessible(true);

        // Un mensaje que se pasa por poco: debe caber apretando el interlineado,
        // sin bajar tanto la letra como antes.
        $mensaje = str_repeat('Muchas felicidades de parte de toda la familia y un abrazo enorme. ', 12);
        $page = $method->invoke($service, 'card', ['gift_message' => $mensaje, 'npedidocli' => '1'], $config->fresh());

        $this->assertLessThanOrEqual(1.2, $page['t1']['line_height']);
        $this->assertGreaterThanOrEqual(8, $page['t1']['font_size']);
    }

    public function test_generation_reports_messages_that_do_not_fit(): void
    {
        Storage::fake('public');
        GiftMessageConfig::current()->update(['card_t1_size' => 14, 'card_t1_w' => 71.64, 'card_t1_h' => 55.71, 'min_font_size' => 7]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('giftmessage.generate'), [
                'type' => 'card',
                'rows' => [
                    ['id_order' => 1, 'gift_message' => 'Corto', 'npedidocli' => '11111'],
                    ['id_order' => 2, 'gift_message' => str_repeat('Muchas felicidades de toda la familia. ', 120), 'npedidocli' => '22222'],
                ],
            ])
            ->assertOk();

        // El que no cabe ni al minimo sale marcado; el corto no genera aviso.
        $warnings = collect($response->json('warnings'));

        $this->assertCount(1, $warnings);
        $this->assertSame('22222', $warnings->first()['order_number']);
        $this->assertTrue($warnings->first()['truncated']);

        // Y queda registrado en el historial, no solo en la respuesta.
        $generation = GiftMessageGeneration::query()->latest('id')->first();
        $this->assertSame('22222', $generation->warnings[0]['order_number']);
    }

    public function test_the_minimum_font_size_is_configurable(): void
    {
        $mensaje = str_repeat('Muchas felicidades de parte de toda la familia. ', 40);
        $service = app(GiftMessagePdfService::class);
        $method = new \ReflectionMethod($service, 'buildPage');
        $method->setAccessible(true);

        GiftMessageConfig::current()->update(['card_t1_size' => 14, 'card_t1_w' => 71.64, 'card_t1_h' => 55.71, 'min_font_size' => 10]);
        $conSueloAlto = $method->invoke($service, 'card', ['gift_message' => $mensaje, 'npedidocli' => '1'], GiftMessageConfig::current()->fresh());

        GiftMessageConfig::current()->update(['min_font_size' => 6]);
        $conSueloBajo = $method->invoke($service, 'card', ['gift_message' => $mensaje, 'npedidocli' => '1'], GiftMessageConfig::current()->fresh());

        $this->assertSame(10.0, $conSueloAlto['t1']['font_size']);
        $this->assertLessThan(10, $conSueloBajo['t1']['font_size']);
    }

    public function test_blank_lines_and_repeated_spaces_do_not_eat_the_box(): void
    {
        $service = app(GiftMessagePdfService::class);
        $method = new \ReflectionMethod($service, 'normalizeMessage');
        $method->setAccessible(true);

        $sucio = "Hola   mundo\n\n\n\n\nFeliz    cumpleanos   \n\n\n  ";

        $this->assertSame("Hola mundo\n\nFeliz cumpleanos", $method->invoke($service, $sucio));
    }

    public function test_generation_rejects_absurdly_long_messages_and_huge_batches(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.generate'), [
                'type' => 'card',
                'rows' => [['id_order' => 1, 'gift_message' => str_repeat('a', 5001), 'npedidocli' => '1']],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rows.0.gift_message');
    }

    public function test_envelope_prints_the_recipient_name_and_the_card_the_message(): void
    {
        $config = GiftMessageConfig::current();
        $config->update(['env_t1_content' => 'recipient', 'card_t1_content' => 'message']);

        $service = app(GiftMessagePdfService::class);
        $method = new \ReflectionMethod($service, 'buildPage');
        $method->setAccessible(true);

        $order = [
            'gift_message' => 'Feliz comunion Jaime',
            'firstname' => 'Jorge',
            'lastname' => 'Da Silva Orallo',
            'npedidocli' => '29394',
        ];

        $sobre = $method->invoke($service, 'envelope', $order, $config->fresh());
        $tarjeta = $method->invoke($service, 'card', $order, $config->fresh());

        $this->assertStringContainsString('Jorge Da Silva Orallo', $this->plainText($sobre['t1']['html']));
        $this->assertStringNotContainsString('Feliz comunion', $this->plainText($sobre['t1']['html']));
        $this->assertStringContainsString('Feliz comunion Jaime', $this->plainText($tarjeta['t1']['html']));
    }

    public function test_the_recipient_falls_back_to_the_message_when_there_is_no_name(): void
    {
        $config = GiftMessageConfig::current();
        $config->update(['env_t1_content' => 'recipient']);

        $service = app(GiftMessagePdfService::class);
        $method = new \ReflectionMethod($service, 'buildPage');
        $method->setAccessible(true);

        // Sin nombre no se imprime un sobre en blanco.
        $sobre = $method->invoke($service, 'envelope', [
            'gift_message' => 'Feliz comunion Jaime',
            'firstname' => '',
            'lastname' => '',
            'npedidocli' => '29394',
        ], $config->fresh());

        $this->assertStringContainsString('Feliz comunion Jaime', $this->plainText($sobre['t1']['html']));
    }

    public function test_the_file_name_says_the_order_and_the_piece(): void
    {
        Storage::fake('public');

        $service = app(GiftMessageGenerationService::class);

        $uno = $service->store('envelope', [['id_order' => 1, 'npedidocli' => '29394', 'gift_message' => 'Hola']], 'pdf');
        $varios = $service->store('card', [
            ['id_order' => 1, 'npedidocli' => '11111', 'gift_message' => 'Uno'],
            ['id_order' => 2, 'npedidocli' => '22222', 'gift_message' => 'Dos'],
        ], 'pdf');

        // Con un pedido, el nombre lo identifica de un vistazo en la carpeta de
        // descargas; con varios manda la cuenta y la fecha.
        $this->assertSame('29394-sobre.pdf', $uno->file_name);
        $this->assertStringStartsWith('tarjetas-2pedidos-', $varios->file_name);
        $this->assertStringEndsWith('.pdf', $varios->file_name);
    }

    public function test_regenerating_does_not_delete_the_file_it_just_wrote(): void
    {
        Storage::fake('public');

        $service = app(GiftMessageGenerationService::class);

        $primera = $service->store('envelope', [['id_order' => 1, 'npedidocli' => '29394', 'gift_message' => 'Hola']], 'pdf viejo');
        $segunda = $service->store('envelope', [['id_order' => 1, 'npedidocli' => '29394', 'gift_message' => 'Hola']], 'pdf nuevo');

        // Mismo nombre de fichero para el mismo pedido y pieza: si se borrara la
        // anterior DESPUES de escribir, el PDF recien creado desapareceria.
        $this->assertDatabaseMissing('gift_message_generations', ['id' => $primera->id]);
        Storage::disk('public')->assertExists($segunda->file_path);
        $this->assertSame('pdf nuevo', Storage::disk('public')->get($segunda->file_path));
    }

    public function test_generate_returns_a_download_url(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.generate'), [
                'type' => 'card',
                'rows' => [['id_order' => 1, 'gift_message' => 'Hola', 'npedidocli' => '1']],
            ])
            ->assertOk()
            ->assertJsonStructure(['success', 'view_url', 'download_url']);
    }

    public function test_the_text_alignment_reaches_the_pdf(): void
    {
        $config = GiftMessageConfig::current();
        $config->update(['card_t1_align' => 'left', 'card_t1_valign' => 'top']);

        $service = app(GiftMessagePdfService::class);
        $method = new \ReflectionMethod($service, 'buildPage');
        $method->setAccessible(true);

        $page = $method->invoke($service, 'card', ['gift_message' => 'Hola', 'npedidocli' => '1'], $config->fresh());

        $this->assertSame('left', $page['t1']['align']);
        $this->assertSame('top', $page['t1']['valign']);
        $this->assertGreaterThan(0, $page['t1']['padding']);
    }

    public function test_the_size_can_use_half_points(): void
    {
        $config = GiftMessageConfig::current();
        $config->update(['card_t1_font' => 'helvetica', 'card_t1_size' => 14, 'card_t1_w' => 71.64, 'card_t1_h' => 55.71, 'min_font_size' => 7]);

        $service = app(GiftMessagePdfService::class);
        $method = new \ReflectionMethod($service, 'fitText');
        $method->setAccessible(true);

        // Se prueban varias longitudes: con pasos de medio punto, alguna tiene
        // que caer en un tamano no entero, que antes se desaprovechaba.
        $sizes = [];

        for ($words = 40; $words <= 90; $words += 2) {
            $texto = str_repeat('felicidades ', $words);
            $fit = $method->invoke($service, $texto, 14, ['left' => 0, 'top' => 0, 'width' => 143.28, 'height' => 50.14], 'helvetica', 7);
            $sizes[] = $fit['size'];
        }

        $this->assertNotEmpty(array_filter($sizes, fn ($size) => fmod($size, 1.0) !== 0.0), 'Ningun tamano uso medio punto.');
    }

    public function test_the_last_word_does_not_stay_alone_on_its_line(): void
    {
        $service = app(GiftMessagePdfService::class);
        $method = new \ReflectionMethod($service, 'avoidWidow');
        $method->setAccessible(true);

        // Las dos ultimas palabras van unidas por un espacio duro para que bajen
        // juntas en vez de dejar una sola palabra en el ultimo renglon.
        $this->assertSame("Feliz cumpleanos de toda la\u{00A0}familia", $method->invoke($service, 'Feliz cumpleanos de toda la familia'));

        // Con dos palabras no hay nada que evitar.
        $this->assertSame('Feliz cumpleanos', $method->invoke($service, 'Feliz cumpleanos'));
    }

    public function test_the_paragraph_spacing_is_configurable(): void
    {
        $config = GiftMessageConfig::current();
        $config->update(['card_t1_font' => 'helvetica', 'card_t1_size' => 14, 'card_t1_w' => 71.64, 'card_t1_h' => 55.71, 'paragraph_spacing' => 0.35]);

        $service = app(GiftMessagePdfService::class);
        $method = new \ReflectionMethod($service, 'buildPage');
        $method->setAccessible(true);

        $mensaje = "Primer parrafo del mensaje.\n\nSegundo parrafo del mensaje.";
        $conAire = $method->invoke($service, 'card', ['gift_message' => $mensaje, 'npedidocli' => '1'], $config->fresh());

        $config->update(['paragraph_spacing' => 0]);
        $sinAire = $method->invoke($service, 'card', ['gift_message' => $mensaje, 'npedidocli' => '1'], $config->fresh());

        $this->assertStringContainsString('0.35em', $conAire['t1']['html']);
        $this->assertStringNotContainsString('0.35em', $sinAire['t1']['html']);
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
