<?php

namespace Modules\Core\Tests\Unit\Services;

use Modules\Core\Services\PhoneNumberValidator;
use Modules\Core\Services\WhatsAppValidator;
use Tests\TestCase;

class WhatsAppValidatorTest extends TestCase
{
    private WhatsAppValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new WhatsAppValidator(new PhoneNumberValidator);
    }

    /** @test */
    public function test_validates_whatsapp_number(): void
    {
        // Colombian mobile numbers (+573xx) are considered WhatsApp-ready.
        $result = $this->validator->validate('3001234567');

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['whatsapp_ready']);
        $this->assertEquals('+573001234567', $result['normalized']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function test_rejects_landline(): void
    {
        // Colombian landline (7 digits, normalized to +571XXXXXXX) is not WhatsApp-ready.
        $result = $this->validator->validate('2345678');

        $this->assertTrue($result['valid']);
        $this->assertFalse($result['whatsapp_ready']);
    }
}
