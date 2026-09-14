<?php

namespace Modules\Core\Tests\Unit\Services;

use Modules\Core\Services\PhoneNumberValidator;
use Tests\TestCase;

class PhoneNumberValidatorTest extends TestCase
{
    private PhoneNumberValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new PhoneNumberValidator;
    }

    /** @test */
    public function test_validates_colombian_mobile(): void
    {
        $result = $this->validator->validate('3001234567');

        $this->assertTrue($result['valid']);
        $this->assertEquals('+573001234567', $result['normalized']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function test_validates_mexican_mobile(): void
    {
        // Mexican number in international format is accepted as-is by the
        // international E.164 branch (+52 10-digit number).
        $result = $this->validator->validate('+5215512345678');

        $this->assertTrue($result['valid']);
        $this->assertEquals('+5215512345678', $result['normalized']);
    }

    /** @test */
    public function test_rejects_invalid_format(): void
    {
        // A string that normalizes to digits but is not a recognized phone format.
        $result = $this->validator->validate('12345');

        $this->assertFalse($result['valid']);
        $this->assertNull($result['normalized']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function test_handles_international_prefix(): void
    {
        // Colombian mobile with +57 prefix should normalize the same as without.
        $withPrefix = $this->validator->validate('+573001234567');
        $withoutPrefix = $this->validator->validate('3001234567');

        $this->assertTrue($withPrefix['valid']);
        $this->assertEquals($withPrefix['normalized'], $withoutPrefix['normalized']);
    }

    /** @test */
    public function test_returns_error_on_empty_input(): void
    {
        $result = $this->validator->validate(null);

        $this->assertFalse($result['valid']);
        $this->assertEquals('Phone number is empty', $result['error']);
    }
}
