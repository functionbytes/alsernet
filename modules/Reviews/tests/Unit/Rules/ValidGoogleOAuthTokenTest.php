<?php

namespace Modules\Reviews\Tests\Unit\Rules;

use Modules\Reviews\Rules\ValidGoogleOAuthToken;
use Tests\TestCase;

class ValidGoogleOAuthTokenTest extends TestCase
{
    public function test_passes_with_valid_token_format(): void
    {
        $rule = new ValidGoogleOAuthToken;

        // Typical Google OAuth token patterns
        $validTokens = [
            'ya29.a0AfH6SMBxyz123abc-def456_ghi789.jkl012',
            '1/fFAGRNJru1FTz70BzhT3Zg',
            '1//0gVWZ_8qKxyz123abc456def789ghi012jkl345mno678pqr901stu234vwx567yza890bcd123',
        ];

        foreach ($validTokens as $token) {
            $this->assertTrue($rule->passes('access_token', $token));
        }
    }

    public function test_fails_with_short_token(): void
    {
        $rule = new ValidGoogleOAuthToken;

        $this->assertFalse($rule->passes('access_token', 'short'));
        $this->assertFalse($rule->passes('access_token', 'abc123'));
        $this->assertFalse($rule->passes('access_token', str_repeat('a', 19))); // 19 chars (minimum is 20)
    }

    public function test_passes_with_minimum_length_token(): void
    {
        $rule = new ValidGoogleOAuthToken;

        $minLengthToken = str_repeat('a', 20); // Exactly 20 chars

        $this->assertTrue($rule->passes('access_token', $minLengthToken));
    }

    public function test_fails_with_invalid_characters(): void
    {
        $rule = new ValidGoogleOAuthToken;

        $invalidTokens = [
            'token with spaces and more characters to reach minimum',
            'token@email.com'.str_repeat('x', 20),
            'token#hashtag'.str_repeat('x', 20),
            'token$dollar'.str_repeat('x', 20),
            'token%percent'.str_repeat('x', 20),
        ];

        foreach ($invalidTokens as $token) {
            $this->assertFalse($rule->passes('access_token', $token));
        }
    }

    public function test_passes_with_valid_characters(): void
    {
        $rule = new ValidGoogleOAuthToken;

        $validTokens = [
            str_repeat('a', 32), // alphanumeric
            'abc123-def456_ghi789.jkl012/mno345',
            'ABC.123-def_456/ghi_jklmnopqrst', // At least 20 chars
        ];

        foreach ($validTokens as $token) {
            $this->assertTrue($rule->passes('access_token', $token));
        }
    }

    public function test_fails_with_non_string_values(): void
    {
        $rule = new ValidGoogleOAuthToken;

        $this->assertFalse($rule->passes('access_token', null));
        $this->assertFalse($rule->passes('access_token', 123));
        $this->assertFalse($rule->passes('access_token', true));
        $this->assertFalse($rule->passes('access_token', []));
        $this->assertFalse($rule->passes('access_token', new \stdClass));
    }

    public function test_fails_with_empty_string(): void
    {
        $rule = new ValidGoogleOAuthToken;

        $this->assertFalse($rule->passes('access_token', ''));
    }

    public function test_returns_correct_error_message(): void
    {
        $rule = new ValidGoogleOAuthToken;

        $this->assertEquals(
            'El token de OAuth no tiene un formato válido',
            $rule->message()
        );
    }
}
