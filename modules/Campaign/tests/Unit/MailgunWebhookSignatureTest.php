<?php

namespace Modules\Campaign\Tests\Unit;

use Modules\Campaign\Support\MailgunWebhookSignature;
use PHPUnit\Framework\TestCase;

class MailgunWebhookSignatureTest extends TestCase
{
    private function sign(string $secret, string $timestamp, string $token): string
    {
        return hash_hmac('sha256', $timestamp.$token, $secret);
    }

    public function test_fail_open_when_no_secret_is_configured(): void
    {
        $this->assertTrue(MailgunWebhookSignature::valid(null, []));
        $this->assertTrue(MailgunWebhookSignature::valid('', ['signature' => 'whatever']));
    }

    public function test_accepts_a_correctly_signed_payload(): void
    {
        $secret = 'signing-key-123';
        $timestamp = '1700000000';
        $token = 'abc-token';

        $this->assertTrue(MailgunWebhookSignature::valid($secret, [
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => $this->sign($secret, $timestamp, $token),
        ]));
    }

    public function test_rejects_a_tampered_signature(): void
    {
        $secret = 'signing-key-123';

        $this->assertFalse(MailgunWebhookSignature::valid($secret, [
            'timestamp' => '1700000000',
            'token' => 'abc-token',
            'signature' => 'deadbeef',
        ]));
    }

    public function test_rejects_when_signature_is_missing_but_secret_is_set(): void
    {
        $this->assertFalse(MailgunWebhookSignature::valid('signing-key-123', [
            'timestamp' => '1700000000',
            'token' => 'abc-token',
        ]));
    }

    public function test_rejects_when_the_secret_differs(): void
    {
        $timestamp = '1700000000';
        $token = 'abc-token';

        $this->assertFalse(MailgunWebhookSignature::valid('server-secret', [
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => $this->sign('attacker-secret', $timestamp, $token),
        ]));
    }
}
