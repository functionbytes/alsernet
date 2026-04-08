<?php

namespace Modules\Helpdesk\Tests\Feature\Portal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Mail\PortalMagicLinkMail;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketStatus;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_login_page_is_accessible(): void
    {
        $this->get(route('portal.login'))
            ->assertOk();
    }

    public function test_login_sends_magic_link_email(): void
    {
        Mail::fake();

        $customer = $this->createCustomer(['email' => 'alice@example.com']);

        $this->post(route('portal.login.submit'), ['email' => $customer->email])
            ->assertRedirect();

        Mail::assertQueued(PortalMagicLinkMail::class, function (PortalMagicLinkMail $mail) use ($customer): bool {
            return $mail->customer->id === $customer->id;
        });
    }

    public function test_login_with_unknown_email_does_not_reveal_existence(): void
    {
        Mail::fake();

        $response = $this->post(route('portal.login.submit'), ['email' => 'nobody@example.com']);

        // Same redirect back with the generic status message — no error about user not found
        $response->assertRedirect();
        $response->assertSessionHas('status', 'If this email is registered, a login link has been sent.');

        Mail::assertNothingQueued();
    }

    public function test_magic_link_authenticates_customer(): void
    {
        $customer = $this->createCustomer();
        $token = $customer->generatePortalToken();

        $this->get(route('portal.authenticate', ['token' => $token]))
            ->assertRedirect(route('portal.tickets'));

        // Token is consumed after use
        $customer->refresh();
        $this->assertNull($customer->portal_token);
    }

    public function test_expired_token_redirects_to_login_with_error(): void
    {
        $customer = $this->createCustomer([
            'portal_token' => 'expired-token-abc',
            'portal_token_expires_at' => now()->subHour(),
        ]);

        $this->get(route('portal.authenticate', ['token' => 'expired-token-abc']))
            ->assertRedirect(route('portal.login'))
            ->assertSessionHas('error');
    }

    public function test_invalid_token_redirects_to_login_with_error(): void
    {
        $this->get(route('portal.authenticate', ['token' => 'completely-bogus-token']))
            ->assertRedirect(route('portal.login'))
            ->assertSessionHas('error');
    }

    public function test_rate_limiting_on_login(): void
    {
        Mail::fake();

        $customer = $this->createCustomer(['email' => 'rate@example.com']);

        // Hit the endpoint 5 times — all should pass (limit is 5)
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('portal.login.submit'), ['email' => $customer->email]);
        }

        // 6th attempt should be rate-limited
        $response = $this->post(route('portal.login.submit'), ['email' => $customer->email]);

        $response->assertSessionHasErrors(['email']);
        $errors = $response->getSession()->get('errors')->getBag('default');
        $this->assertStringContainsString('Too many attempts', $errors->first('email'));
    }

    public function test_authenticated_customer_sees_their_tickets(): void
    {
        $customer = $this->createCustomer();

        Ticket::create([
            'subject' => 'My portal ticket',
            'description' => 'Created via portal.',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'portal',
        ]);

        $response = $this->withSession(['portal_customer_id' => $customer->id])
            ->get(route('portal.tickets'));

        $response->assertOk();
    }

    public function test_unauthenticated_customer_is_redirected_from_tickets(): void
    {
        $this->get(route('portal.tickets'))
            ->assertRedirect(route('portal.login'));
    }

    public function test_customer_cannot_see_other_customers_ticket(): void
    {
        $customer = $this->createCustomer(['email' => 'owner@example.com']);
        $otherCustomer = $this->createCustomer(['email' => 'other@example.com']);

        $otherTicket = Ticket::create([
            'subject' => 'Other customer ticket',
            'description' => 'This belongs to another customer.',
            'customer_id' => $otherCustomer->id,
            'customer_name' => $otherCustomer->name,
            'customer_email' => $otherCustomer->email,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'portal',
        ]);

        // Acting as $customer, try to view $otherCustomer's ticket
        $this->withSession(['portal_customer_id' => $customer->id])
            ->get(route('portal.tickets.show', $otherTicket->ticket_number))
            ->assertNotFound();
    }

    public function test_login_requires_valid_email(): void
    {
        $response = $this->post(route('portal.login.submit'), ['email' => 'not-an-email']);

        $response->assertSessionHasErrors(['email']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Test Customer',
            'email' => 'customer'.uniqid().'@example.com',
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
