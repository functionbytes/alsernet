<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Support;

use Modules\HelpdeskTickets\Support\TicketEmailChannelUrlGuard;
use Tests\TestCase;

class TicketEmailChannelUrlGuardTest extends TestCase
{
    public function test_rejects_loopback_host(): void
    {
        $this->assertFalse(TicketEmailChannelUrlGuard::isHostAllowed('127.0.0.1'));
        $this->assertFalse(TicketEmailChannelUrlGuard::isHostAllowed('localhost'));
    }

    public function test_rejects_link_local_metadata_ip(): void
    {
        $this->assertFalse(TicketEmailChannelUrlGuard::isHostAllowed('169.254.169.254'));
    }

    public function test_rejects_empty_host(): void
    {
        $this->assertFalse(TicketEmailChannelUrlGuard::isHostAllowed(''));
        $this->assertFalse(TicketEmailChannelUrlGuard::isHostAllowed(null));
    }

    public function test_allows_private_rfc1918_ip(): void
    {
        // Un servidor IMAP self-hosted en la red interna Docker es un
        // destino legítimo — solo loopback/link-local están bloqueados.
        $this->assertTrue(TicketEmailChannelUrlGuard::isHostAllowed('172.20.0.5'));
        $this->assertTrue(TicketEmailChannelUrlGuard::isHostAllowed('192.168.1.10'));
    }

    public function test_allows_public_ip_literal(): void
    {
        $this->assertTrue(TicketEmailChannelUrlGuard::isHostAllowed('8.8.8.8'));
    }

    public function test_rejects_unresolvable_host(): void
    {
        $this->assertFalse(TicketEmailChannelUrlGuard::isHostAllowed('this-host-does-not-exist.invalid'));
    }
}
