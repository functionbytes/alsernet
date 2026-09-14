<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Bandeja global "Emails enviados" (todos los tickets), sobre TicketMail.
 *
 * Las rutas manager exigen role:super-admin|super-settings, pero ambos roles
 * pasan por alto TODAS las policies vía Gate::before (ver AuthServiceProvider/
 * DocumentsServiceProvider), lo que haría intestable la autorización por
 * email. Mismo patrón que BulkReplyTest/BulkTicketOperationsTest: se
 * desactiva el middleware de rol y se prueba con usuarios que solo tienen
 * los permisos explícitamente concedidos.
 */
class TicketMailsControllerTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: EmailLog vive en la conexión default de la app
    // (mysql en este entorno, no mariadb/helpdesk) — sin declararla, cada
    // EmailLog::create() de este archivo escribe una fila REAL sin rollback
    // (mismo gotcha ya documentado y corregido en varios tests hermanos de
    // Modules\HelpdeskEmailActivity, p. ej. EmailLogControllerTest).
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TicketStatus $status;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        foreach ([
            'helpdesk.tickets.view',
            'helpdesk.tickets.update',
            'helpdesk.tickets.emails.view',
            'helpdesk.tickets.emails.send',
            'helpdesk.tickets.emails.resend',
            'helpdesk.tickets.emails.delete',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->withoutMiddleware(RoleMiddleware::class);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::factory()->create();
    }

    public function test_data_includes_trace_from_matching_email_log(): void
    {
        // TicketMail.message_id se guarda con <ángulos>; EmailLog.message_id
        // se guarda sin ellos (ver LogEmailQueued::ensureMessageId()) — el
        // cruce por message_id debe normalizar eso o nunca encuentra nada
        // (bug real que se coló hasta probarlo con un envío de verdad).
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();
        $mail = $this->createMail($ticket, ['message_id' => '<abc123@alvarez.mx>', 'status' => 'sent']);

        $log = EmailLog::create([
            'module' => 'HelpdeskTickets',
            'from_address' => 'soporte@alvarez.mx',
            'to_addresses' => ['cliente@example.com'],
            'subject' => $mail->subject,
            'message_id' => 'abc123@alvarez.mx',
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        $log->opens()->create(['ip' => '127.0.0.1', 'user_agent' => 'test', 'opened_at' => now()]);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.data', $mail))
            ->assertOk();

        $trace = $response->json('data.trace');
        $types = collect($trace)->pluck('type');

        $this->assertTrue($types->contains('queued'));
        $this->assertTrue($types->contains('sent'));
        $this->assertTrue($types->contains('opened'));
    }

    /**
     * Cubre TicketDetailDataController::data() (panel de detalle del ticket,
     * distinto endpoint del que prueba test_data_includes_trace_from_matching_
     * email_log de arriba, que es el modal de UN correo) — el widget lateral
     * "Último correo del ticket" (renderCorreoSidePane en tickets-app.js)
     * lee mail.clicks_count/last_clicked_human de aquí.
     */
    public function test_ticket_data_includes_clicks_summary_and_trace(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.view']);
        $ticket = $this->createTicket();
        $mail = $this->createMail($ticket, ['message_id' => '<ticketclick123@alvarez.mx>', 'status' => 'sent']);

        $log = EmailLog::create([
            'module' => 'HelpdeskTickets',
            'from_address' => 'soporte@alvarez.mx',
            'to_addresses' => ['cliente@example.com'],
            'subject' => $mail->subject,
            'message_id' => 'ticketclick123@alvarez.mx',
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        $link = $log->links()->create(['token' => Str::random(40), 'url' => 'https://example.com/x', 'created_at' => now()]);
        $link->clicks()->create(['ip' => '127.0.0.1', 'user_agent' => 'test', 'clicked_at' => now()]);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.data', $ticket))
            ->assertOk();

        $response->assertJsonPath('mail.clicks_count', 1);
        $this->assertNotNull($response->json('mail.last_clicked_human'));

        $types = collect($response->json('trace'))->pluck('type');
        $this->assertTrue($types->contains('clicked'));
    }

    public function test_data_includes_click_trace_from_matching_email_log(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();
        $mail = $this->createMail($ticket, ['message_id' => '<click123@alvarez.mx>', 'status' => 'sent']);

        $log = EmailLog::create([
            'module' => 'HelpdeskTickets',
            'from_address' => 'soporte@alvarez.mx',
            'to_addresses' => ['cliente@example.com'],
            'subject' => $mail->subject,
            'message_id' => 'click123@alvarez.mx',
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        $link = $log->links()->create(['token' => Str::random(40), 'url' => 'https://example.com/x', 'created_at' => now()]);
        $link->clicks()->create(['ip' => '127.0.0.1', 'user_agent' => 'test', 'clicked_at' => now()]);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.data', $mail))
            ->assertOk();

        $types = collect($response->json('data.trace'))->pluck('type');

        $this->assertTrue($types->contains('clicked'));
    }

    public function test_update_tags_adds_and_removes_a_tag(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.update']);
        $ticket = $this->createTicket();
        $mail = $this->createMail($ticket, ['tags' => ['pedido']]);

        $this->actingAs($manager)
            ->patchJson(route('manager.helpdesk.tickets.emails.tags', $mail), ['add' => 'urgente'])
            ->assertOk()
            ->assertJson(['success' => true, 'tags' => ['pedido', 'urgente']]);

        $this->actingAs($manager)
            ->patchJson(route('manager.helpdesk.tickets.emails.tags', $mail), ['remove' => 'pedido'])
            ->assertOk()
            ->assertJson(['success' => true, 'tags' => ['urgente']]);

        $this->assertDatabaseHas('helpdesk_ticket_mails', ['id' => $mail->id], 'helpdesk');
        $this->assertSame(['urgente'], $mail->fresh()->tags);
    }

    public function test_related_tickets_includes_other_tickets_from_same_customer(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();
        $otherTicket = $this->createTicket(['subject' => 'Otro ticket del mismo cliente']);
        $mail = $this->createMail($ticket);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.data', $mail))
            ->assertOk();

        $related = collect($response->json('data.related'))->pluck('id');

        $this->assertTrue($related->contains($otherTicket->id));
        $this->assertFalse($related->contains($ticket->id));
    }

    public function test_templates_lists_only_macros_with_a_reply_action(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);

        Macro::create([
            'name' => 'Con reply',
            'actions' => [['type' => 'reply', 'body' => 'Hola {{customer_name}}']],
            'is_shared' => true,
            'is_active' => true,
        ]);
        Macro::create([
            'name' => 'Sin reply',
            'actions' => [['type' => 'set_priority', 'value' => 'high']],
            'is_shared' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.templates'))
            ->assertOk();

        $names = collect($response->json('templates'))->pluck('name');

        $this->assertTrue($names->contains('Con reply'));
        $this->assertFalse($names->contains('Sin reply'));
    }

    /**
     * El asunto es una clave opcional dentro de la acción 'reply' de una
     * macro (Macro::actionSpecs()['reply']['optional']) — cuando está
     * configurado se interpola con las mismas variables del ticket que ya
     * usa 'body' (mismo TicketVariableInterpolator, ver también
     * TicketVariableInterpolatorTest).
     */
    public function test_templates_includes_interpolated_subject_when_the_macro_has_one_configured(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket(['subject' => 'Factura duplicada']);

        Macro::create([
            'name' => 'Con asunto',
            'actions' => [['type' => 'reply', 'subject' => 'Re: {{ticket_subject}}', 'body' => 'Hola {{customer_name}}']],
            'is_shared' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.templates', ['ticket_id' => $ticket->id]))
            ->assertOk();

        $template = collect($response->json('templates'))->firstWhere('name', 'Con asunto');

        $this->assertNotNull($template, 'la plantilla con asunto debe estar en la respuesta');
        $this->assertSame('Re: Factura duplicada', $template['subject']);
    }

    /**
     * Macros anteriores a este campo no tienen 'subject' en su acción
     * 'reply' — el composer (ticket-detail.js) depende de que la API
     * devuelva null (no que falte la clave) para saber que debe caer al
     * nombre de la macro como aproximación.
     */
    public function test_templates_subject_is_null_when_the_macro_does_not_have_one_configured(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);

        Macro::create([
            'name' => 'Sin asunto',
            'actions' => [['type' => 'reply', 'body' => 'Hola {{customer_name}}']],
            'is_shared' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.templates'))
            ->assertOk();

        $template = collect($response->json('templates'))->firstWhere('name', 'Sin asunto');

        $this->assertNotNull($template, 'la plantilla sin asunto también debe estar en la respuesta');
        $this->assertArrayHasKey('subject', $template);
        $this->assertNull($template['subject']);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson(route('manager.helpdesk.tickets.emails.index'))
            ->assertUnauthorized();
    }

    /**
     * openTrackingStats() cruza CONEXIONES en una sola query
     * (DB::connection('helpdesk')->...->join('email_logs', ...), que vive en
     * la conexión default/mysql) — bajo DatabaseTransactions cada conexión
     * abre su PROPIA transacción/sesión, así que una fila insertada vía el
     * modelo EmailLog (conexión mysql) nunca es visible desde la sesión
     * 'helpdesk' que ejecuta el JOIN, aunque ambas apunten a la misma BD
     * física — aislamiento de transacción estándar entre sesiones. Mismo
     * motivo por el que opened_rate (idéntico patrón, preexistente) tampoco
     * tenía ningún test sobre su valor real antes de este archivo. No hay
     * forma honesta de afirmar aquí "clicked_rate refleja mi fixture" sin
     * comprometer el rollback de la transacción — se prueba solo que el
     * endpoint expone la clave con un valor numérico válido; la fórmula en
     * sí (SUM(clicked)/matched) se verificó manualmente en Docker.
     */
    public function test_index_json_stats_include_clicked_rate_key(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);

        Cache::forget('helpdeskticketmails:stats');

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.index'))
            ->assertOk();

        // is_numeric (no assertIsFloat): json_encode() de un float entero
        // (0.0) puede serializarse como "0" según serialize_precision, y
        // json_decode() lo devolvería como int, no float — un detalle de
        // codificación, no del contrato real de la clave.
        $this->assertIsNumeric($response->json('stats.clicked_rate'));
        $this->assertGreaterThanOrEqual(0.0, $response->json('stats.clicked_rate'));
    }

    /**
     * La bandeja global propia (vista HTML) se retiró — una petición de
     * NAVEGADOR (sin Accept: json) a este nombre de ruta ahora redirige al
     * log de emails unificado, filtrado por módulo. Una petición JSON (la
     * que sigue usando tickets-app.js/el composer del ticket) NO se ve
     * afectada — ver los tests de arriba, todos con getJson().
     */
    public function test_index_redirects_browser_requests_to_the_unified_email_log(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);

        $this->actingAs($manager)
            ->get(route('manager.helpdesk.tickets.emails.index'))
            ->assertRedirect(route('helpdeskemailactivity.index', ['module' => 'HelpdeskTickets']));
    }

    public function test_scheduled_page_renders_and_wires_the_json_api(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);

        $this->actingAs($manager)
            ->get(route('manager.helpdesk.tickets.scheduled'))
            ->assertOk()
            ->assertViewIs('helpdesktickets::managers.emails.scheduled')
            ->assertViewHas('dataUrl', route('manager.helpdesk.tickets.emails.index', ['view' => 'scheduled']))
            ->assertViewHas('bulkUrl', route('manager.helpdesk.tickets.emails.bulk'))
            // Selector de modo de vista (Lista/Compacta/Kanban): opera sobre
            // los datos ya cargados en el propio navegador (ver rows/render()
            // en el JS inline), así que solo se puede comprobar aquí que el
            // selector y sus 3 botones están en el HTML servido — el render
            // de cada modo (incluido el Kanban por franja horaria) es
            // responsabilidad del JS, no de este test de servidor.
            ->assertSee('id="sched-mode-switch"', false)
            ->assertSee('Compacta')
            ->assertSee('Kanban');
    }

    public function test_index_lists_only_outbound_mails_by_default(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();

        $outbound = $this->createMail($ticket, ['direction' => 'outbound', 'status' => 'sent', 'subject' => 'Outbound one']);
        $this->createMail($ticket, ['direction' => 'inbound', 'status' => 'received', 'subject' => 'Inbound one']);
        $this->createMail($ticket, ['direction' => 'outbound', 'status' => 'scheduled', 'scheduled_at' => now()->addHour(), 'subject' => 'Scheduled one']);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.index'))
            ->assertOk();

        // Se cuentan solo los correos de ESTE ticket. Antes se contaban todos
        // los del listado y se esperaba 1, lo que hacía depender el test de que
        // la tabla estuviese vacía: cualquier correo residual de otra ejecución
        // en la base compartida lo tumbaba. Lo que se quiere comprobar es el
        // filtro por dirección, no cuántas filas hay en total.
        $mine = collect($response->json('data'))->where('ticket_id', $ticket->id);

        $this->assertTrue($mine->pluck('id')->contains($outbound->id));
        $this->assertSame(1, $mine->count(), 'La vista por defecto solo lista los salientes ya enviados.');
    }

    public function test_index_view_scheduled_filters_by_status(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();

        $scheduled = $this->createMail($ticket, ['status' => 'scheduled', 'scheduled_at' => now()->addHour()]);
        $this->createMail($ticket, ['status' => 'sent']);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.index', ['view' => 'scheduled']))
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertSame([$scheduled->id], $ids->all());
    }

    public function test_index_outbound_view_excludes_internal_mails(): void
    {
        // Fase A: "Internos" es su propio tab — no debe mezclarse con "Enviados".
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();

        $external = $this->createMail($ticket, ['status' => 'sent', 'is_internal' => false]);
        $internal = $this->createMail($ticket, ['status' => 'sent', 'is_internal' => true]);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.index'))
            ->assertOk();

        // La bandeja global no está vacía en un entorno con datos reales —
        // se verifica presencia/ausencia, no un total exacto (mismo motivo
        // que las pruebas de filtro de abajo).
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($external->id));
        $this->assertFalse($ids->contains($internal->id));
    }

    public function test_index_internal_view_filters_by_is_internal(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();

        $internal = $this->createMail($ticket, ['status' => 'sent', 'is_internal' => true]);
        $this->createMail($ticket, ['status' => 'sent', 'is_internal' => false]);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.index', ['view' => 'internal']))
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertSame([$internal->id], $ids->all());
    }

    public function test_index_filters_by_origin(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $prestaTicket = $this->createTicket(['source' => 'presta']);
        $webTicket = $this->createTicket(['source' => 'web']);

        $prestaMail = $this->createMail($prestaTicket, ['status' => 'sent']);
        $webMail = $this->createMail($webTicket, ['status' => 'sent']);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.index', ['origin' => 'presta']))
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($prestaMail->id));
        $this->assertFalse($ids->contains($webMail->id));
    }

    public function test_index_filters_by_tag(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();

        $tagged = $this->createMail($ticket, ['status' => 'sent', 'tags' => ['urgente', 'pedido']]);
        $untagged = $this->createMail($ticket, ['status' => 'sent', 'tags' => ['otro']]);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.index', ['tag' => 'urgente']))
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($tagged->id));
        $this->assertFalse($ids->contains($untagged->id));
    }

    public function test_store_creates_internal_mail_when_flagged(): void
    {
        Queue::fake();

        // 'to' es una casilla interna de escalado (soporte-n2@...), no el
        // cliente del ticket — SEC-07 item 5 exige el permiso explícito
        // para poder fijar un destinatario distinto del cliente.
        $manager = $this->makeUser(['helpdesk.tickets.emails.send', 'helpdesk.tickets.view', 'helpdesk.tickets.emails.send_to_any']);
        $ticket = $this->createTicket();

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => 'soporte-n2@alvarez.mx',
                'subject' => 'Escalado a nivel 2',
                'body' => '<p>SLA en riesgo.</p>',
                'is_internal' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'Escalado a nivel 2',
            'is_internal' => true,
        ], 'helpdesk');
    }

    /**
     * La otra mitad de SEC-07 item 5: el caso positivo ya estaba cubierto
     * —con el permiso se puede fijar otro destinatario—, pero no el negativo,
     * que es el que impide que la dirección corporativa sirva para escribirle
     * a cualquiera. Ahora la regla vive en TicketMailRecipientResolver.
     */
    public function test_store_rechaza_un_destinatario_ajeno_sin_el_permiso(): void
    {
        Queue::fake();

        // Puede enviar correo del ticket, pero NO a una dirección cualquiera.
        $manager = $this->makeUser(['helpdesk.tickets.emails.send', 'helpdesk.tickets.view']);
        $ticket = $this->createTicket();

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => 'un-tercero@ajeno.test',
                'subject' => 'No debería salir',
                'body' => '<p>hola</p>',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'No debería salir',
        ], 'helpdesk');

        Queue::assertNothingPushed();
    }

    public function test_store_deja_rastro_al_escribir_a_un_tercero(): void
    {
        Queue::fake();

        $manager = $this->makeUser(['helpdesk.tickets.emails.send', 'helpdesk.tickets.view', 'helpdesk.tickets.emails.send_to_any']);
        $ticket = $this->createTicket();

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => 'un-tercero@ajeno.test',
                'subject' => 'Con permiso',
                'body' => '<p>hola</p>',
            ])
            ->assertCreated();

        // El permiso autoriza, pero no exime de dejar constancia: si no queda
        // en el historial, nadie puede auditar a quién se escribió.
        $this->assertDatabaseHas('helpdesk_ticket_histories', [
            'ticket_id' => $ticket->id,
            'action_type' => 'mail_sent_to_arbitrary_recipient',
            'new_value' => 'un-tercero@ajeno.test',
        ], 'helpdesk');
    }

    public function test_store_creates_mail_and_queues_it_immediately(): void
    {
        Queue::fake();

        $manager = $this->makeUser(['helpdesk.tickets.emails.send', 'helpdesk.tickets.view']);
        $ticket = $this->createTicket();

        // 'to' por defecto se fija al cliente del ticket (SEC-07 item 5) —
        // se usa el email real del cliente en vez de un literal arbitrario.
        $response = $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => $ticket->customer->email,
                'subject' => 'Nuevo email de prueba',
                'body' => '<p>Hola, este es un email de prueba.</p>',
            ])
            ->assertCreated();

        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'user_id' => $manager->id,
            'to' => $ticket->customer->email,
            'subject' => 'Nuevo email de prueba',
            'status' => 'sent',
        ], 'helpdesk');

        Queue::assertPushed(SendQueuedMailable::class);
    }

    public function test_store_schedules_instead_of_sending_when_scheduled_at_given(): void
    {
        Queue::fake();

        $manager = $this->makeUser(['helpdesk.tickets.emails.send', 'helpdesk.tickets.view']);
        $ticket = $this->createTicket();

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => $ticket->customer->email,
                'subject' => 'Seguimiento programado',
                'body' => '<p>Este correo se enviará más tarde.</p>',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'Seguimiento programado',
            'status' => 'scheduled',
        ], 'helpdesk');

        Queue::assertNothingPushed();
    }

    public function test_store_requires_view_permission_on_the_ticket(): void
    {
        // Sin helpdesk.tickets.view ni ser el assignee del ticket: TicketPolicy::view() deniega.
        $limitedAgent = $this->makeUser(['helpdesk.tickets.emails.send']);
        $ticket = $this->createTicket();

        $this->actingAs($limitedAgent)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => 'cliente@example.com',
                'subject' => 'No debería enviarse',
                'body' => '<p>...</p>',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'No debería enviarse',
        ], 'helpdesk');
    }

    public function test_resend_creates_a_new_mail_record_and_queues_it(): void
    {
        Queue::fake();

        $manager = $this->makeUser(['helpdesk.tickets.emails.resend']);
        $ticket = $this->createTicket();
        $original = $this->createMail($ticket, [
            'subject' => 'Original',
            'body_html' => '<p>Cuerpo original</p>',
            'to' => $ticket->customer->email,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.resend', $original))
            ->assertOk();

        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'Original',
            'in_reply_to' => $original->message_id,
            'to' => $ticket->customer->email,
        ], 'helpdesk');

        // El original sigue existiendo tal cual — reenviar crea una fila nueva.
        $this->assertSame(2, TicketMail::where('ticket_id', $ticket->id)->count());
        Queue::assertPushed(SendQueuedMailable::class);
    }

    public function test_bulk_resend_creates_new_mail_records_and_preserves_the_originals(): void
    {
        // Regresión: bulkResend() pasaba el registro original tal cual a
        // TicketMailDispatcher::send(), que llama markAsSent() sobre lo que
        // recibe — un reenvío masivo de un email "rebotado" lo mutaba en el
        // sitio a "enviado" y borraba el rastro del rebote original. Debe
        // comportarse igual que el reenvío individual: crear una fila nueva.
        Queue::fake();

        $manager = $this->makeUser(['helpdesk.tickets.emails.resend']);
        $ticket = $this->createTicket();
        $bounced = $this->createMail($ticket, [
            'subject' => 'Aviso rebotado',
            'to' => 'cliente@example.com',
            'status' => 'bounced',
        ]);

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.bulk'), [
                'mail_ids' => [$bounced->id],
                'action' => 'resend',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // El original conserva su estado "bounced" intacto...
        $this->assertSame('bounced', $bounced->fresh()->status);

        // ...y hay una fila nueva en estado "sent" para el reenvío.
        $this->assertSame(2, TicketMail::where('ticket_id', $ticket->id)->count());
        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'Aviso rebotado',
            'in_reply_to' => $bounced->message_id,
            'status' => 'sent',
        ], 'helpdesk');

        Queue::assertPushed(SendQueuedMailable::class);
    }

    public function test_bulk_cancel_scheduled_deletes_scheduled_mails(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.delete']);
        $ticket = $this->createTicket();
        $scheduled = $this->createMail($ticket, ['status' => 'scheduled', 'scheduled_at' => now()->addHour()]);

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.bulk'), [
                'mail_ids' => [$scheduled->id],
                'action' => 'cancel_scheduled',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('helpdesk_ticket_mails', ['id' => $scheduled->id], 'helpdesk');
    }

    public function test_bulk_skips_mails_the_user_cannot_act_on(): void
    {
        // Solo emails.view: TicketMailPolicy::delete() exige emails.delete o
        // .manage — sin ninguno, el cancelado se omite.
        $limitedAgent = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();
        $scheduled = $this->createMail($ticket, ['status' => 'scheduled', 'scheduled_at' => now()->addHour()]);

        $response = $this->actingAs($limitedAgent)
            ->postJson(route('manager.helpdesk.tickets.emails.bulk'), [
                'mail_ids' => [$scheduled->id],
                'action' => 'cancel_scheduled',
            ])
            ->assertStatus(403);

        $response->assertJsonPath('skipped_ids.0', $scheduled->id);
        $this->assertDatabaseHas('helpdesk_ticket_mails', ['id' => $scheduled->id, 'deleted_at' => null], 'helpdesk');
    }

    public function test_destroy_requires_delete_permission(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.delete']);
        $ticket = $this->createTicket();
        $mail = $this->createMail($ticket);

        $this->actingAs($manager)
            ->deleteJson(route('manager.helpdesk.tickets.emails.destroy', $mail))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('helpdesk_ticket_mails', ['id' => $mail->id], 'helpdesk');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<int, string>  $permissions
     */
    private function makeUser(array $permissions): User
    {
        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Email test ticket',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createMail(Ticket $ticket, array $overrides = []): TicketMail
    {
        return TicketMail::create(array_merge([
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'from' => 'soporte@alvarez.mx',
            'to' => 'cliente@example.com',
            'subject' => 'Test mail',
            'body_html' => '<p>Test</p>',
            'body_text' => 'Test',
            'status' => 'sent',
            'message_id' => '<'.uniqid().'@alvarez.mx>',
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
