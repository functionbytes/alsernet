<?php

namespace Modules\Notification\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Modules\Notification\Services\SmsService;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    private SmsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.twilio.sid', 'ACtest');
        config()->set('services.twilio.token', 'secret-token');
        config()->set('services.twilio.from', '+10000000000');

        $this->service = new SmsService;
    }

    public function test_send_returns_true_on_success(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);

        $this->assertTrue($this->service->send('+34123456789', 'Hola'));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.twilio.com')
            && $request['To'] === '+34123456789'
            && $request['Body'] === 'Hola');
    }

    public function test_send_returns_false_on_failure(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'invalid number'], 400)]);

        $this->assertFalse($this->service->send('+34123456789', 'Hola'));
    }

    public function test_is_valid_phone_number(): void
    {
        $this->assertTrue((bool) $this->service->isValidPhoneNumber('+34123456789'));
        $this->assertFalse((bool) $this->service->isValidPhoneNumber('123-not-a-phone'));
    }

    public function test_format_phone_number_adds_country_code(): void
    {
        $this->assertSame('+34600123123', $this->service->formatPhoneNumber('600 123 123', '+34'));
    }
}
