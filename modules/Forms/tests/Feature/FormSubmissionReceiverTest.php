<?php

namespace Modules\Forms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Modules\Forms\Models\AlsernetForm;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Database\Factories\TicketCategoryFactory;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketMessage;
use Tests\TestCase;

class FormSubmissionReceiverTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['helpdesk'];

    private string $url = '/api/forms/webhooks/submission';

    /** Valor del interruptor de integración antes de que el test lo tocara. */
    private mixed $restoreIntegrationToggle = null;

    protected function tearDown(): void
    {
        if ($this->restoreIntegrationToggle !== null) {
            Setting::set('forms.integration_enabled', $this->restoreIntegrationToggle, 'integrations');
            $this->restoreIntegrationToggle = null;
        }

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['forms.webhook_secret' => 'test-secret']);
        Setting::set('forms.integration_enabled', '1', 'integrations');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $data
     */
    private function postSignedSubmission(
        string $formKey,
        string $categorySlug,
        array $data,
        ?string $idempotencyKey = 'submission-1',
        ?int $timestamp = null,
        string $secret = 'test-secret',
        array $attachments = []
    ): TestResponse {
        $timestamp ??= time();
        $payload = ['action' => 'submit', 'type' => $formKey, 'category' => $categorySlug, 'data' => $data];

        if ($attachments !== []) {
            $payload['attachments'] = $attachments;
        }

        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $timestamp.':'.$body, $secret);

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ALSERNET_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_ALSERNET_SIGNATURE' => $signature,
        ];

        if ($idempotencyKey !== null) {
            $server['HTTP_X_ALSERNET_IDEMPOTENCY_KEY'] = $idempotencyKey;
        }

        return $this->call('POST', $this->url, [], [], [], $server, $body);
    }

    /**
     * Crea la TicketCategory + el AlsernetForm activo que la vincula a $formKey --
     * FormSubmissionReceiverController resuelve por AlsernetForm::form_key, no
     * directamente por el slug de categoría (ver tarea #20).
     */
    /**
     * firstOrCreate/updateOrCreate en vez de create: aquí el form_key y el
     * slug viajan dentro del payload firmado, así que no se les puede poner
     * un sufijo único. Y ambos ('contact', 'contacto-general') existen ya
     * como datos reales en la BD compartida de test, con lo que crearlos
     * reventaba con UniqueConstraintViolationException. Reutilizarlos es
     * seguro: DatabaseTransactions revierte los cambios al terminar.
     */
    private function makeForm(string $formKey, string $categorySlug, bool $active = true): AlsernetForm
    {
        $category = TicketCategory::firstOrCreate(
            ['slug' => $categorySlug],
            TicketCategoryFactory::new()->make(['slug' => $categorySlug, 'active' => true])->getAttributes()
        );

        return AlsernetForm::updateOrCreate(
            ['form_key' => $formKey],
            ['name' => $categorySlug, 'category_id' => $category->id, 'active' => $active]
        );
    }

    // ─── Configuration guard ──────────────────────────────────────────────────

    public function test_returns_503_when_webhook_secret_not_configured(): void
    {
        config(['forms.webhook_secret' => '']);

        $response = $this->postSignedSubmission('contact', 'contacto-general', ['email' => 'a@example.com']);

        $response->assertStatus(503);
    }

    // ─── HMAC validation (delegado a VerifyAlsernetFormsHmac / HmacSigner) ─────

    public function test_returns_401_when_signature_is_invalid(): void
    {
        $ts = time();
        $body = json_encode(['action' => 'submit', 'type' => 'contact', 'category' => 'contacto-general', 'data' => []]);

        $response = $this->call('POST', $this->url, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ALSERNET_TIMESTAMP' => (string) $ts,
            'HTTP_X_ALSERNET_SIGNATURE' => 'totally-wrong-signature',
        ], $body);

        $response->assertUnauthorized();
    }

    public function test_returns_401_when_timestamp_is_too_old(): void
    {
        $response = $this->postSignedSubmission(
            'contact',
            'contacto-general',
            ['email' => 'a@example.com'],
            timestamp: time() - 400,
        );

        $response->assertUnauthorized();
    }

    // ─── Idempotencia ───────────────────────────────────────────────────────────

    public function test_missing_idempotency_key_returns_400(): void
    {
        $this->makeForm('contact', 'contacto-general');

        $response = $this->postSignedSubmission(
            'contact',
            'contacto-general',
            ['email' => 'a@example.com'],
            idempotencyKey: null,
        );

        $response->assertStatus(400);
    }

    /**
     * La protección anti-replay real: reenviar la petición TAL CUAL (misma
     * firma, que cubre timestamp+body) se rechaza en el middleware. Es lo que
     * distingue un ataque de un reintento legítimo del cron, que llega con la
     * misma idempotency key pero firma distinta — ver VerifyAlsernetFormsHmac.
     */
    public function test_replaying_the_exact_same_signature_is_rejected(): void
    {
        $this->makeForm('contact', 'contacto-general');

        $timestamp = time();
        $datos = ['email' => 'replay-firma@example.com', 'firstname' => 'Ana'];

        $this->postSignedSubmission('contact', 'contacto-general', $datos,
            idempotencyKey: 'firma-1', timestamp: $timestamp)->assertOk();

        // Misma firma byte a byte: mismo timestamp, mismo cuerpo.
        $this->postSignedSubmission('contact', 'contacto-general', $datos,
            idempotencyKey: 'firma-1', timestamp: $timestamp)->assertStatus(401);
    }

    public function test_replaying_the_same_idempotency_key_does_not_create_a_second_ticket(): void
    {
        $this->makeForm('contact', 'contacto-general');

        $first = $this->postSignedSubmission('contact', 'contacto-general', [
            'email' => 'replay@example.com',
            'firstname' => 'Ana',
        ], idempotencyKey: 'dup-key');
        $first->assertOk();
        $ticketNumber = $first->json('data.ticket_number');

        // Firma distinta a propósito (timestamp/body distintos, como un
        // reintento real del cron de alsernetforms), misma idempotency key.
        $second = $this->postSignedSubmission('contact', 'contacto-general', [
            'email' => 'replay@example.com',
            'firstname' => 'Ana',
        ], idempotencyKey: 'dup-key', timestamp: time() + 1);

        $second->assertOk();
        $second->assertJson(['deduplicated' => true]);
        $this->assertSame($ticketNumber, $second->json('data.ticket_number'));

        $this->assertSame(
            1,
            Ticket::on('helpdesk')->where('ticket_number', $ticketNumber)->count()
        );
    }

    // ─── Resolución de AlsernetForm / categoría / cliente / creación de ticket ────────

    public function test_unknown_form_key_returns_500_and_creates_nothing(): void
    {
        $ticketsAntes = Ticket::on('helpdesk')->count();
        $response = $this->postSignedSubmission('formulario-inexistente', 'contacto-general', [
            'email' => 'a@example.com',
        ]);

        $response->assertStatus(500);
        // Delta, no total: la BD de test es compartida y ya trae tickets reales.
        $this->assertSame($ticketsAntes, Ticket::on('helpdesk')->count());
    }

    public function test_inactive_form_returns_500_and_creates_nothing(): void
    {
        $ticketsAntes = Ticket::on('helpdesk')->count();
        $this->makeForm('workwithus', 'trabaja-con-nosotros', active: false);

        $response = $this->postSignedSubmission('workwithus', 'trabaja-con-nosotros', [
            'email' => 'a@example.com',
        ]);

        $response->assertStatus(500);
        // Delta, no total: la BD de test es compartida y ya trae tickets reales.
        $this->assertSame($ticketsAntes, Ticket::on('helpdesk')->count());
    }

    public function test_form_without_category_returns_500(): void
    {
        $ticketsAntes = Ticket::on('helpdesk')->count();
        AlsernetForm::create(['form_key' => 'orphan', 'name' => 'Orphan', 'category_id' => null, 'active' => true]);

        $response = $this->postSignedSubmission('orphan', '', ['email' => 'a@example.com']);

        $response->assertStatus(500);
        // Delta, no total: la BD de test es compartida y ya trae tickets reales.
        $this->assertSame($ticketsAntes, Ticket::on('helpdesk')->count());
    }

    public function test_missing_email_in_payload_returns_500(): void
    {
        $this->makeForm('contact', 'contacto-general');

        $response = $this->postSignedSubmission('contact', 'contacto-general', [
            'firstname' => 'Sin email',
        ]);

        $response->assertStatus(500);
    }

    public function test_valid_submission_creates_ticket_with_formulario_source(): void
    {
        $form = $this->makeForm('exchangesandreturns', 'devoluciones-cambios');
        Event::fake([TicketCreated::class]);

        $response = $this->postSignedSubmission('exchangesandreturns', 'devoluciones-cambios', [
            'email' => 'cliente@example.com',
            'firstname' => 'Laura',
            'lastname' => 'Gomez',
            'reason_label' => 'Producto en malas condiciones',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data' => ['id', 'ticket_number']]);

        $ticket = Ticket::on('helpdesk')->where('ticket_number', $response->json('data.ticket_number'))->first();
        $this->assertNotNull($ticket);
        $this->assertSame('formulario', $ticket->source);
        $this->assertSame($form->category_id, $ticket->category_id);
        $this->assertSame('exchangesandreturns', $ticket->custom_fields['form_key']);
        $this->assertSame('Producto en malas condiciones', $ticket->custom_fields['reason_label']);

        Event::assertDispatched(TicketCreated::class);
    }

    public function test_valid_submission_reuses_existing_customer_by_email(): void
    {
        $form = $this->makeForm('fitting', 'cita-fitting');
        $customer = Customer::on('helpdesk')->create([
            'name' => 'Cliente existente',
            'email' => 'existente@example.com',
        ]);

        $response = $this->postSignedSubmission('fitting', 'cita-fitting', [
            'email' => 'existente@example.com',
            'phone' => '600111222',
        ]);

        $response->assertOk();

        $ticket = Ticket::on('helpdesk')->where('ticket_number', $response->json('data.ticket_number'))->first();
        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertSame($form->category_id, $ticket->category_id);
        $this->assertSame(
            1,
            Customer::on('helpdesk')->where('email', 'existente@example.com')->count()
        );
    }

    public function test_mismatched_category_in_payload_does_not_block_creation(): void
    {
        // El AlsernetForm manda: el payload trae un 'category' distinto (desincronizado
        // a propósito) y el ticket debe usar igualmente la categoría real del AlsernetForm.
        $form = $this->makeForm('contact', 'contacto-general');

        $response = $this->postSignedSubmission('contact', 'categoria-vieja-en-prestashop', [
            'email' => 'a@example.com',
        ]);

        $response->assertOk();
        $ticket = Ticket::on('helpdesk')->where('ticket_number', $response->json('data.ticket_number'))->first();
        $this->assertSame($form->category_id, $ticket->category_id);
    }

    // ─── Integration toggle ───────────────────────────────────────────────────

    public function test_returns_503_and_creates_nothing_when_integration_disabled(): void
    {
        $ticketsAntes = Ticket::on('helpdesk')->count();

        /* Setting::set() escribe fuera de la transacción de DatabaseTransactions
           (y además cachea), así que apagar el interruptor aquí lo deja apagado
           en la base real al terminar: el sitio entero deja de crear tickets y
           nadie se entera hasta que alguien envía un formulario. Se restaura en
           tearDown pase lo que pase. */
        $this->restoreIntegrationToggle = Setting::get('forms.integration_enabled');

        Setting::set('forms.integration_enabled', '0', 'integrations');
        $this->makeForm('contact', 'contacto-general');

        $response = $this->postSignedSubmission('contact', 'contacto-general', [
            'email' => 'a@example.com',
        ]);

        $response->assertStatus(503);
        // Delta, no total: la BD de test es compartida y ya trae tickets reales.
        $this->assertSame($ticketsAntes, Ticket::on('helpdesk')->count());
    }

    // ─── Adjuntos ─────────────────────────────────────────────────────────────

    /**
     * El CV de "Trabaja con nosotros" y las fotos de "Segunda mano" viajan en
     * base64 dentro del payload. Antes se perdían: alsernetforms solo se los
     * pasaba al correo de negocio, y todos los formularios entregan por aquí.
     */
    public function test_base64_attachments_are_stored_on_the_ticket(): void
    {
        $this->makeForm('workwithus', 'trabaja-con-nosotros');

        $response = $this->postSignedSubmission(
            'workwithus',
            'trabaja-con-nosotros',
            ['email' => 'candidato@example.com', 'firstname' => 'Ana'],
            'submission-att-1',
            null,
            'test-secret',
            [[
                'name' => 'cv.txt',
                'mime' => 'text/plain',
                'size' => 11,
                'content_b64' => base64_encode('Curriculum'),
            ]]
        );

        $response->assertOk();

        $ticket = Ticket::on('helpdesk')->where('ticket_number', $response->json('data.ticket_number'))->firstOrFail();
        $adjuntos = TicketAttachment::on('helpdesk')
            ->whereIn('ticket_message_id', TicketMessage::on('helpdesk')->where('ticket_id', $ticket->id)->pluck('id'))
            ->get();

        $this->assertCount(1, $adjuntos);
        $this->assertSame('cv.txt', $adjuntos->first()->original_filename);
    }

    /**
     * Un base64 corrupto no debe tumbar la petición: devolver un 500 aquí haría
     * que el reintento del outbox abriera un ticket duplicado.
     */
    public function test_a_broken_attachment_does_not_lose_the_ticket(): void
    {
        $this->makeForm('workwithus', 'trabaja-con-nosotros');

        $response = $this->postSignedSubmission(
            'workwithus',
            'trabaja-con-nosotros',
            ['email' => 'candidato2@example.com'],
            'submission-att-2',
            null,
            'test-secret',
            [['name' => 'roto.txt', 'mime' => 'text/plain', 'size' => 5, 'content_b64' => '!!!no-es-base64!!!']]
        );

        $response->assertOk();
        $this->assertNotNull($response->json('data.ticket_number'));
    }
}
