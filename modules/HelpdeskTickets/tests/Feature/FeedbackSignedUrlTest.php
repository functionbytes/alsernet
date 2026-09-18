<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

/**
 * The public feedback pages resolve tickets by their sequential ticket_number,
 * so both routes must reject any URL without a valid Laravel signature —
 * otherwise anyone can read subjects and submit ratings for foreign tickets.
 */
class FeedbackSignedUrlTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );
    }

    public function test_show_rejects_unsigned_url(): void
    {
        $ticket = $this->createTicket();

        $this->get(route('helpdesk.feedback.show', $ticket->ticket_number))
            ->assertForbidden();
    }

    public function test_show_rejects_signature_for_another_ticket(): void
    {
        $ticketA = $this->createTicket();
        $ticketB = $this->createTicket();

        $signedForA = URL::signedRoute('helpdesk.feedback.show', ['ticketNumber' => $ticketA->ticket_number]);
        $tampered = str_replace($ticketA->ticket_number, $ticketB->ticket_number, $signedForA);

        $this->get($tampered)->assertForbidden();
    }

    public function test_show_allows_valid_signed_url(): void
    {
        $ticket = $this->createTicket();

        // actingAs: el layout público (layouts.theme) asume un usuario
        // autenticado (bug preexistente de la vista, ajeno a la firma).
        // La firma es lo que se valida aquí: sin ella la ruta devuelve 403.
        $this->actingAs(User::factory()->create())
            ->get(URL::signedRoute('helpdesk.feedback.show', ['ticketNumber' => $ticket->ticket_number]))
            ->assertOk()
            ->assertSee($ticket->ticket_number);
    }

    public function test_submit_rejects_unsigned_url(): void
    {
        $ticket = $this->createTicket();

        $this->post(route('helpdesk.feedback.submit', $ticket->ticket_number), [
            'rating' => 5,
        ])->assertForbidden();

        $this->assertNull($ticket->fresh()->rated_at);
    }

    public function test_submit_with_valid_signature_stores_rating(): void
    {
        $ticket = $this->createTicket();

        $this->post(
            URL::signedRoute('helpdesk.feedback.submit', ['ticketNumber' => $ticket->ticket_number]),
            ['rating' => 4, 'comment' => 'Muy buena atención']
        )->assertRedirect();

        $fresh = $ticket->fresh();
        $this->assertSame(4, $fresh->rating);
        $this->assertSame('Muy buena atención', $fresh->rating_comment);
        $this->assertNotNull($fresh->rated_at);
    }

    public function test_submit_does_not_overwrite_existing_rating(): void
    {
        $ticket = $this->createTicket(['rating' => 5, 'rated_at' => now()->subDay()]);

        $this->post(
            URL::signedRoute('helpdesk.feedback.submit', ['ticketNumber' => $ticket->ticket_number]),
            ['rating' => 1]
        )->assertRedirect();

        $this->assertSame(5, $ticket->fresh()->rating);
    }

    public function test_low_rating_stores_the_dissatisfaction_reason(): void
    {
        $ticket = $this->createTicket();

        $this->post(
            URL::signedRoute('helpdesk.feedback.submit', ['ticketNumber' => $ticket->ticket_number]),
            ['rating' => 1, 'reason' => 'slow', 'comment' => 'Muy lento']
        )->assertRedirect();

        $this->assertSame('slow', $ticket->fresh()->rating_reason);
    }

    public function test_reason_is_ignored_on_a_high_rating(): void
    {
        $ticket = $this->createTicket();

        $this->post(
            URL::signedRoute('helpdesk.feedback.submit', ['ticketNumber' => $ticket->ticket_number]),
            ['rating' => 5, 'reason' => 'slow']
        )->assertRedirect();

        $this->assertNull($ticket->fresh()->rating_reason);
    }

    public function test_invalid_reason_is_rejected(): void
    {
        $ticket = $this->createTicket();

        $this->post(
            URL::signedRoute('helpdesk.feedback.submit', ['ticketNumber' => $ticket->ticket_number]),
            ['rating' => 1, 'reason' => 'no-existe']
        )->assertSessionHasErrors(['reason']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Feedback test ticket',
            'description' => 'Test description.',
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
            'closed_at' => now(),
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
