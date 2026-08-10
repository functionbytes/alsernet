<?php

namespace Modules\Forms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Modules\Forms\Models\Form;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Database\Factories\TicketCategoryFactory;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

class FormSubmissionReceiverTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['helpdesk'];

    private string $url = '/api/forms/webhooks/submission';

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
        string $secret = 'test-secret'
    ): TestResponse {
        $timestamp ??= time();
        $body = json_encode(['action' => 'submit', 'type' => $formKey, 'category' => $categorySlug, 'data' => $data]);
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
     * Crea la TicketCategory + el Form activo que la vincula a $formKey --
     * FormSubmissionReceiverController resuelve por Form::form_key, no
     * directamente por el slug de categoría (ver tarea #20).
     */
    private function makeForm(string $formKey, string $categorySlug, bool $active = true): Form
    {
        $category = TicketCategoryFactory::new()->create(['slug' => $categorySlug, 'active' => true]);

        return Form::create([
            'form_key' => $formKey,
            'name' => $categorySlug,
            'category_id' => $category->id,
            'active' => $active,
        ]);
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

    // ─── Resolución de Form / categoría / cliente / creación de ticket ────────

    public function test_unknown_form_key_returns_500_and_creates_nothing(): void
    {
        $response = $this->postSignedSubmission('formulario-inexistente', 'contacto-general', [
            'email' => 'a@example.com',
        ]);

        $response->assertStatus(500);
        $this->assertSame(0, Ticket::on('helpdesk')->count());
    }

    public function test_inactive_form_returns_500_and_creates_nothing(): void
    {
        $this->makeForm('workwithus', 'trabaja-con-nosotros', active: false);

        $response = $this->postSignedSubmission('workwithus', 'trabaja-con-nosotros', [
            'email' => 'a@example.com',
        ]);

        $response->assertStatus(500);
        $this->assertSame(0, Ticket::on('helpdesk')->count());
    }

    public function test_form_without_category_returns_500(): void
    {
        Form::create(['form_key' => 'orphan', 'name' => 'Orphan', 'category_id' => null, 'active' => true]);

        $response = $this->postSignedSubmission('orphan', '', ['email' => 'a@example.com']);

        $response->assertStatus(500);
        $this->assertSame(0, Ticket::on('helpdesk')->count());
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
        // El Form manda: el payload trae un 'category' distinto (desincronizado
        // a propósito) y el ticket debe usar igualmente la categoría real del Form.
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
        Setting::set('forms.integration_enabled', '0', 'integrations');
        $this->makeForm('contact', 'contacto-general');

        $response = $this->postSignedSubmission('contact', 'contacto-general', [
            'email' => 'a@example.com',
        ]);

        $response->assertStatus(503);
        $this->assertSame(0, Ticket::on('helpdesk')->count());
    }
}
