<?php

namespace Modules\Reviews\Tests\Unit\Rules;

use Modules\Reviews\Rules\ValidStarRating;
use Tests\TestCase;

class ValidStarRatingTest extends TestCase
{
    public function test_passes_with_valid_rating_values(): void
    {
        $rule = new ValidStarRating;

        $this->assertTrue($rule->passes('rating', 'ONE'));
        $this->assertTrue($rule->passes('rating', 'TWO'));
        $this->assertTrue($rule->passes('rating', 'THREE'));
        $this->assertTrue($rule->passes('rating', 'FOUR'));
        $this->assertTrue($rule->passes('rating', 'FIVE'));
    }

    public function test_fails_with_invalid_rating_values(): void
    {
        $rule = new ValidStarRating;

        $this->assertFalse($rule->passes('rating', 'ONE_STAR'));
        $this->assertFalse($rule->passes('rating', 'ZERO'));
        $this->assertFalse($rule->passes('rating', 'SIX'));
        $this->assertFalse($rule->passes('rating', 'invalid'));
        $this->assertFalse($rule->passes('rating', ''));
    }

    public function test_fails_with_numeric_values(): void
    {
        $rule = new ValidStarRating;

        $this->assertFalse($rule->passes('rating', 1));
        $this->assertFalse($rule->passes('rating', 5));
        $this->assertFalse($rule->passes('rating', '1'));
        $this->assertFalse($rule->passes('rating', '5'));
    }

    public function test_fails_with_lowercase_values(): void
    {
        $rule = new ValidStarRating;

        $this->assertFalse($rule->passes('rating', 'one'));
        $this->assertFalse($rule->passes('rating', 'five'));
    }

    public function test_fails_with_non_string_values(): void
    {
        $rule = new ValidStarRating;

        $this->assertFalse($rule->passes('rating', null));
        $this->assertFalse($rule->passes('rating', true));
        $this->assertFalse($rule->passes('rating', []));
        $this->assertFalse($rule->passes('rating', new \stdClass));
    }

    public function test_returns_correct_error_message(): void
    {
        $rule = new ValidStarRating;

        $message = $rule->message();

        $this->assertStringContainsString('ONE', $message);
        $this->assertStringContainsString('TWO', $message);
        $this->assertStringContainsString('THREE', $message);
        $this->assertStringContainsString('FOUR', $message);
        $this->assertStringContainsString('FIVE', $message);
    }
}
