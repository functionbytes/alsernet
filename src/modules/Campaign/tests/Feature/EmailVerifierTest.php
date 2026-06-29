<?php

namespace Modules\Campaign\Tests\Feature;

use Modules\Campaign\Services\EmailVerifier;
use Tests\TestCase;

class EmailVerifierTest extends TestCase
{
    public function test_syntax_check_valid_email(): void
    {
        $verifier = new EmailVerifier;
        $result = $verifier->verify('valid@example.com');

        $this->assertSame(EmailVerifier::VALID, $result['status']);
        $this->assertSame(1.0, $result['score']);
        $this->assertSame('syntax', $result['provider']);
    }

    public function test_syntax_check_invalid_email(): void
    {
        $verifier = new EmailVerifier;
        $result = $verifier->verify('not-an-email');

        $this->assertSame(EmailVerifier::INVALID, $result['status']);
        $this->assertSame(0.0, $result['score']);
    }

    public function test_verify_many_returns_array(): void
    {
        $verifier = new EmailVerifier;
        $results = $verifier->verifyMany(['a@example.com', 'bad', 'b@example.com']);

        $this->assertCount(3, $results);
        $this->assertSame(EmailVerifier::VALID, $results[0]['status']);
        $this->assertSame(EmailVerifier::INVALID, $results[1]['status']);
    }
}
