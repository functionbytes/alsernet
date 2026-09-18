<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Setting;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketAiService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Modal 27 "Etiquetado automático": confianza real (cuota de coincidencia de
 * palabras clave, nunca inventada) por sugerencia + el interruptor "aplicar
 * automáticamente si supera el 90%" (OFF por defecto).
 */
class TicketAiServiceTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketStatus $status;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        $this->withoutMiddleware(RoleMiddleware::class);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::factory()->create();

        Setting::set('tickets.ai_auto_apply_high_confidence', false);
    }

    // ─── suggestCategory() ───────────────────────────────────────────────────
    //
    // Texto 100% inventado (nada de palabras reales, ni de relleno) a
    // propósito: suggestCategory() consulta TODAS las categorías activas de
    // la BD compartida de test, no solo las de este test. Ya probado en la
    // práctica: "algo que no coincide con nada" SÍ coincidía con una
    // categoría real del entorno (confianza 0.5 en vez del null esperado)
    // porque el nombre/descripción de alguna categoría real contiene esas
    // palabras — mismo riesgo que la tasa de rebote ambiental de
    // CheckTicketMailReputationCommand (ver mockBounceRate()), aquí resuelto
    // sin relleno gramatical real en absoluto, no solo evitando nombres de
    // categoría plausibles.

    public function test_suggest_category_da_100_por_ciento_con_una_sola_categoria_coincidente(): void
    {
        TicketCategory::factory()->create(['name' => 'Zqfrenolix', 'description' => null, 'active' => true]);
        $ticket = $this->makeTicket(['description' => 'zqfrenolix']);

        $suggested = app(TicketAiService::class)->suggestCategory($ticket);

        $this->assertNotNull($suggested);
        $this->assertSame(1.0, $suggested['confidence']);
    }

    public function test_suggest_category_reparte_la_confianza_entre_categorias_que_compiten(): void
    {
        $a = TicketCategory::factory()->create(['name' => 'Zqfrenolix', 'description' => null, 'active' => true]);
        $b = TicketCategory::factory()->create(['name' => 'Wobbaturk Plimzorf', 'description' => null, 'active' => true]);
        // "zqfrenolix" (A: +1.0) + "wobbaturk" y "plimzorf" (B: +1.0 cada
        // una) => A 1.0, B 2.0.
        $ticket = $this->makeTicket(['description' => 'wobbaturk plimzorf zqfrenolix']);

        $suggested = app(TicketAiService::class)->suggestCategory($ticket);

        $this->assertSame($b->id, $suggested['id']);
        $this->assertSame(0.67, $suggested['confidence']);
        $this->assertNotSame($a->id, $suggested['id']);
    }

    public function test_suggest_category_es_null_sin_ninguna_coincidencia(): void
    {
        TicketCategory::factory()->create(['name' => 'Zqfrenolix', 'description' => null, 'active' => true]);
        $ticket = $this->makeTicket(['description' => 'jindleforpx']);

        $this->assertNull(app(TicketAiService::class)->suggestCategory($ticket));
    }

    // ─── suggestPriority() ───────────────────────────────────────────────────

    public function test_suggest_priority_urgent_con_una_sola_senal(): void
    {
        $ticket = $this->makeTicket(['description' => 'El sistema esta caido, es urgente.']);

        $suggested = app(TicketAiService::class)->suggestPriority($ticket);

        $this->assertSame('urgent', $suggested['priority']);
        $this->assertSame(1.0, $suggested['confidence']);
    }

    public function test_suggest_priority_reparte_confianza_entre_urgent_y_high(): void
    {
        // 'urgente' contiene 'urgent' como subcadena, así que cuenta DOS
        // veces para urgent (countKeywords no dedup a por palabra completa,
        // suma coincidencias de cada entrada de la lista) + 'problema' una
        // vez para high => urgent 2, high 1, total 3, confianza 2/3.
        $ticket = $this->makeTicket(['description' => 'tengo un problema urgente']);

        $suggested = app(TicketAiService::class)->suggestPriority($ticket);

        $this->assertSame('urgent', $suggested['priority']);
        $this->assertSame(0.67, $suggested['confidence']);
    }

    public function test_suggest_priority_es_null_sin_ninguna_senal(): void
    {
        $ticket = $this->makeTicket(['description' => 'todo bien, gracias']);

        $this->assertNull(app(TicketAiService::class)->suggestPriority($ticket));
    }

    // ─── autoClassify() ──────────────────────────────────────────────────────

    public function test_autoclassify_sin_autoaplicar_solo_deja_la_sugerencia(): void
    {
        $cat = TicketCategory::factory()->create(['name' => 'Zqfrenolix', 'description' => null, 'active' => true]);
        $ticket = $this->makeTicket(['description' => 'zqfrenolix']);

        app(TicketAiService::class)->autoClassify($ticket);
        $ticket->refresh();

        $this->assertNull($ticket->category_id);
        $this->assertSame($cat->id, $ticket->ai_suggested_category_id);
        $this->assertSame(1.0, (float) $ticket->ai_suggested_category_confidence);
    }

    public function test_autoclassify_con_autoaplicar_y_confianza_alta_aplica_directo(): void
    {
        Setting::set('tickets.ai_auto_apply_high_confidence', true);
        // 'caido' en vez de 'urgente': este último es justo el tipo de
        // palabra que también podría ser el nombre real de una categoría
        // (ver nota sobre BD compartida arriba) — 'caido' no compite con la
        // parte de categoría de esta prueba.
        $cat = TicketCategory::factory()->create(['name' => 'Zqfrenolix', 'description' => null, 'active' => true]);
        $ticket = $this->makeTicket(['description' => 'zqfrenolix caido']);

        app(TicketAiService::class)->autoClassify($ticket);
        $ticket->refresh();

        $this->assertSame($cat->id, $ticket->category_id);
        $this->assertNull($ticket->ai_suggested_category_id);
        $this->assertSame('urgent', $ticket->priority);
        $this->assertNull($ticket->ai_suggested_priority);
    }

    public function test_autoclassify_con_autoaplicar_pero_confianza_baja_no_aplica(): void
    {
        Setting::set('tickets.ai_auto_apply_high_confidence', true);
        // urgent 2 (urgente + su subcadena 'urgent') / high 1 (problema) =>
        // confianza 0.67, por debajo del umbral 0.90.
        $ticket = $this->makeTicket(['description' => 'tengo un problema urgente']);

        app(TicketAiService::class)->autoClassify($ticket);
        $ticket->refresh();

        $this->assertSame('normal', $ticket->priority);
        $this->assertSame('urgent', $ticket->ai_suggested_priority);
        $this->assertSame(0.67, (float) $ticket->ai_suggested_priority_confidence);
    }

    // ─── aplicar manualmente limpia también la confianza ────────────────────

    public function test_aplicar_la_sugerencia_de_categoria_limpia_su_confianza(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.update');
        $cat = TicketCategory::factory()->create(['name' => 'Zqfrenolix', 'description' => null, 'active' => true]);
        $ticket = $this->makeTicket([
            'ai_suggested_category_id' => $cat->id,
            'ai_suggested_category_confidence' => 0.8,
        ]);

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.apply-ai-suggestion', $ticket), ['field' => 'category'])
            ->assertOk();

        $ticket->refresh();
        $this->assertSame($cat->id, $ticket->category_id);
        $this->assertNull($ticket->ai_suggested_category_id);
        $this->assertNull($ticket->ai_suggested_category_confidence);
    }

    // ─── interruptor "aplicar automáticamente" ──────────────────────────────

    public function test_guarda_el_interruptor_de_autoaplicar(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.update');

        $this->actingAs($manager)
            ->patchJson(route('manager.helpdesk.tickets.ai-auto-apply.update'), ['enabled' => true])
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['enabled' => true]]);

        $this->assertTrue(filter_var(Setting::get('tickets.ai_auto_apply_high_confidence'), FILTER_VALIDATE_BOOLEAN));
    }

    public function test_guardar_el_interruptor_exige_permiso(): void
    {
        $agent = User::factory()->create();

        $this->actingAs($agent)
            ->patchJson(route('manager.helpdesk.tickets.ai-auto-apply.update'), ['enabled' => true])
            ->assertForbidden();
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        // 'subject' vacío: por defecto no debe aportar ninguna palabra al
        // texto que suggestCategory()/suggestPriority() analizan (ver nota
        // sobre BD compartida arriba) — cada test pone en 'description'
        // exactamente lo que quiere que el algoritmo vea.
        return Ticket::create(array_merge([
            'subject' => '',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
