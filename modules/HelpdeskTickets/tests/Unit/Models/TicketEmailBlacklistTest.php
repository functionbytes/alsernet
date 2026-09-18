<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Tests\TestCase;

class TicketEmailBlacklistTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_matches_exact_email(): void
    {
        TicketEmailBlacklist::create(['type' => 'email', 'value' => 'blocked@spam.com']);

        $this->assertNotNull(TicketEmailBlacklist::matches('blocked@spam.com'));
        $this->assertNull(TicketEmailBlacklist::matches('other@spam.com'));
    }

    public function test_matches_is_case_insensitive(): void
    {
        TicketEmailBlacklist::create(['type' => 'email', 'value' => 'Blocked@Spam.com']);

        $this->assertNotNull(TicketEmailBlacklist::matches('blocked@spam.com'));
    }

    public function test_matches_exact_domain(): void
    {
        TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);

        $this->assertNotNull(TicketEmailBlacklist::matches('anyone@spam.com'));
    }

    public function test_matches_subdomain_of_blocked_domain(): void
    {
        TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);

        $this->assertNotNull(TicketEmailBlacklist::matches('anyone@mail.spam.com'));
        $this->assertNotNull(TicketEmailBlacklist::matches('anyone@deep.mail.spam.com'));
    }

    public function test_does_not_match_unrelated_domain(): void
    {
        TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);

        $this->assertNull(TicketEmailBlacklist::matches('anyone@notspam.com'));
        $this->assertNull(TicketEmailBlacklist::matches('anyone@legit-spam.com'));
    }

    public function test_inactive_rule_does_not_match(): void
    {
        TicketEmailBlacklist::create(['type' => 'email', 'value' => 'blocked@spam.com', 'is_active' => false]);

        $this->assertNull(TicketEmailBlacklist::matches('blocked@spam.com'));
    }

    public function test_register_match_increments_counter_and_sets_timestamp(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'email', 'value' => 'blocked@spam.com']);

        $rule->registerMatch('blocked@spam.com', 'Oferta 1');
        $rule->registerMatch('blocked@spam.com', 'Oferta 2');
        $rule->refresh();

        $this->assertSame(2, $rule->matched_count);
        $this->assertNotNull($rule->last_matched_at);
    }

    public function test_register_match_creates_a_hit_record_per_call(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);

        $rule->registerMatch('a@spam.com', 'Primer intento');
        $rule->registerMatch('b@sub.spam.com', 'Segundo intento');

        $this->assertCount(2, $rule->hits()->get());
        $this->assertDatabaseHas('helpdesk_ticket_email_blacklist_hits', [
            'blacklist_id' => $rule->id,
            'from_email' => 'a@spam.com',
            'subject' => 'Primer intento',
        ], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_ticket_email_blacklist_hits', [
            'blacklist_id' => $rule->id,
            'from_email' => 'b@sub.spam.com',
            'subject' => 'Segundo intento',
        ], 'helpdesk');
    }

    public function test_register_match_stores_the_email_body_for_preview(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'email', 'value' => 'blocked@spam.com']);

        $rule->registerMatch('blocked@spam.com', 'Oferta', '<p>Hola</p>', 'Hola');

        $this->assertDatabaseHas('helpdesk_ticket_email_blacklist_hits', [
            'blacklist_id' => $rule->id,
            'body_html' => '<p>Hola</p>',
            'body_text' => 'Hola',
        ], 'helpdesk');
    }

    public function test_deleting_rule_cascades_to_its_hits(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'email', 'value' => 'blocked@spam.com']);
        $rule->registerMatch('blocked@spam.com', 'Oferta');

        $rule->delete();

        $this->assertDatabaseMissing('helpdesk_ticket_email_blacklist_hits', [
            'blacklist_id' => $rule->id,
        ], 'helpdesk');
    }
}
