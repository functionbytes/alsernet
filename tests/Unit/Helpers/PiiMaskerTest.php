<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PiiMasker;
use PHPUnit\Framework\TestCase;

class PiiMaskerTest extends TestCase
{
    public function test_email_masks_the_local_part_and_keeps_the_domain(): void
    {
        $this->assertSame('an*@example.com', PiiMasker::email('ana@example.com'));
        $this->assertSame('jo******@corp.test', PiiMasker::email('john.doe@corp.test'));
    }

    public function test_email_returns_stars_for_short_local_part(): void
    {
        $this->assertSame('**@example.com', PiiMasker::email('a@example.com'));
    }

    public function test_email_returns_stars_for_value_without_at_sign(): void
    {
        $this->assertSame('***', PiiMasker::email('not-an-email'));
    }

    public function test_email_returns_empty_string_for_null_or_empty(): void
    {
        $this->assertSame('', PiiMasker::email(null));
        $this->assertSame('', PiiMasker::email(''));
    }

    public function test_phone_keeps_only_the_last_four_digits(): void
    {
        $this->assertSame('******7890', PiiMasker::phone('1234567890'));
        $this->assertSame('********7890', PiiMasker::phone('+34 123 4567890'));
    }

    public function test_phone_returns_stars_when_fewer_than_four_digits(): void
    {
        $this->assertSame('***', PiiMasker::phone('12'));
    }

    public function test_phone_returns_empty_string_for_null_or_empty(): void
    {
        $this->assertSame('', PiiMasker::phone(null));
        $this->assertSame('', PiiMasker::phone(''));
    }
}
