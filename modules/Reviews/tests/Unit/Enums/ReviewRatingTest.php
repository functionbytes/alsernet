<?php

namespace Modules\Reviews\Tests\Unit\Enums;

use Modules\Reviews\Enums\ReviewRating;
use PHPUnit\Framework\TestCase;

class ReviewRatingTest extends TestCase
{
    public function test_value_returns_correct_numeric_rating(): void
    {
        $this->assertSame(1, ReviewRating::ONE->value());
        $this->assertSame(2, ReviewRating::TWO->value());
        $this->assertSame(3, ReviewRating::THREE->value());
        $this->assertSame(4, ReviewRating::FOUR->value());
        $this->assertSame(5, ReviewRating::FIVE->value());
    }

    public function test_from_int_converts_numeric_to_enum(): void
    {
        $this->assertSame(ReviewRating::ONE, ReviewRating::fromInt(1));
        $this->assertSame(ReviewRating::TWO, ReviewRating::fromInt(2));
        $this->assertSame(ReviewRating::THREE, ReviewRating::fromInt(3));
        $this->assertSame(ReviewRating::FOUR, ReviewRating::fromInt(4));
        $this->assertSame(ReviewRating::FIVE, ReviewRating::fromInt(5));
    }

    public function test_from_int_throws_exception_for_invalid_rating(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid rating: 6');

        ReviewRating::fromInt(6);
    }

    public function test_from_int_throws_exception_for_zero_rating(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid rating: 0');

        ReviewRating::fromInt(0);
    }

    public function test_from_int_throws_exception_for_negative_rating(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ReviewRating::fromInt(-1);
    }

    public function test_stars_returns_correct_emoji_representation(): void
    {
        $this->assertSame('⭐', ReviewRating::ONE->stars());
        $this->assertSame('⭐⭐', ReviewRating::TWO->stars());
        $this->assertSame('⭐⭐⭐', ReviewRating::THREE->stars());
        $this->assertSame('⭐⭐⭐⭐', ReviewRating::FOUR->stars());
        $this->assertSame('⭐⭐⭐⭐⭐', ReviewRating::FIVE->stars());
    }

    public function test_all_enum_cases_exist(): void
    {
        $cases = ReviewRating::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(ReviewRating::ONE, $cases);
        $this->assertContains(ReviewRating::TWO, $cases);
        $this->assertContains(ReviewRating::THREE, $cases);
        $this->assertContains(ReviewRating::FOUR, $cases);
        $this->assertContains(ReviewRating::FIVE, $cases);
    }
}
