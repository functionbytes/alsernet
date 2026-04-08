<?php

namespace Modules\Reviews\Tests\Unit\Rules;

use Illuminate\Http\Request;
use Modules\Reviews\Rules\ValidReviewDateRange;
use Tests\TestCase;

class ValidReviewDateRangeTest extends TestCase
{
    public function test_passes_with_valid_date_range(): void
    {
        $this->mockRequest([
            'from_date' => '2024-01-01',
            'to_date' => '2024-06-01',
        ]);

        $rule = new ValidReviewDateRange('from_date');

        $this->assertTrue($rule->passes('to_date', '2024-06-01'));
    }

    public function test_passes_when_from_date_is_missing(): void
    {
        $this->mockRequest([
            'to_date' => '2024-06-01',
        ]);

        $rule = new ValidReviewDateRange('from_date');

        $this->assertTrue($rule->passes('to_date', '2024-06-01'));
    }

    public function test_passes_when_to_date_is_missing(): void
    {
        $this->mockRequest([
            'from_date' => '2024-01-01',
        ]);

        $rule = new ValidReviewDateRange('from_date');

        $this->assertTrue($rule->passes('to_date', null));
    }

    public function test_fails_when_from_date_is_after_to_date(): void
    {
        $this->mockRequest([
            'from_date' => '2024-06-01',
            'to_date' => '2024-01-01',
        ]);

        $rule = new ValidReviewDateRange('from_date');

        $this->assertFalse($rule->passes('to_date', '2024-01-01'));
    }

    public function test_fails_when_from_date_equals_to_date(): void
    {
        $this->mockRequest([
            'from_date' => '2024-06-01',
            'to_date' => '2024-06-01',
        ]);

        $rule = new ValidReviewDateRange('from_date');

        $this->assertFalse($rule->passes('to_date', '2024-06-01'));
    }

    public function test_fails_when_range_exceeds_max_years(): void
    {
        $this->mockRequest([
            'from_date' => '2022-01-01',
            'to_date' => '2025-01-01',
        ]);

        $rule = new ValidReviewDateRange('from_date', maxYears: 2);

        $this->assertFalse($rule->passes('to_date', '2025-01-01'));
    }

    public function test_passes_when_range_is_exactly_max_years(): void
    {
        $this->mockRequest([
            'from_date' => '2022-01-01',
            'to_date' => '2023-12-31',
        ]);

        $rule = new ValidReviewDateRange('from_date', maxYears: 2);

        $this->assertTrue($rule->passes('to_date', '2023-12-31'));
    }

    public function test_fails_with_invalid_date_format(): void
    {
        $this->mockRequest([
            'from_date' => 'invalid-date',
            'to_date' => '2024-06-01',
        ]);

        $rule = new ValidReviewDateRange('from_date');

        $this->assertFalse($rule->passes('to_date', '2024-06-01'));
    }

    public function test_supports_custom_max_years(): void
    {
        $this->mockRequest([
            'from_date' => '2020-01-01',
            'to_date' => '2024-01-01',
        ]);

        $rule = new ValidReviewDateRange('from_date', maxYears: 5);

        $this->assertTrue($rule->passes('to_date', '2024-01-01'));
    }

    public function test_returns_correct_error_message(): void
    {
        $rule = new ValidReviewDateRange('from_date', maxYears: 2);

        $message = $rule->message();

        $this->assertStringContainsString('2 años', $message);
        $this->assertStringContainsString('fecha inicial antes de fecha final', $message);
    }

    public function test_error_message_reflects_custom_max_years(): void
    {
        $rule = new ValidReviewDateRange('from_date', maxYears: 5);

        $message = $rule->message();

        $this->assertStringContainsString('5 años', $message);
    }

    private function mockRequest(array $data): void
    {
        $request = Request::create('/test', 'POST', $data);
        app()->instance('request', $request);
    }
}
