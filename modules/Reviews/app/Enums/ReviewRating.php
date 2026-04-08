<?php

namespace Modules\Reviews\Enums;

enum ReviewRating: string
{
    case ONE = 'ONE';
    case TWO = 'TWO';
    case THREE = 'THREE';
    case FOUR = 'FOUR';
    case FIVE = 'FIVE';

    public function value(): int
    {
        return match ($this) {
            self::ONE => 1,
            self::TWO => 2,
            self::THREE => 3,
            self::FOUR => 4,
            self::FIVE => 5,
        };
    }

    public function toNumeric(): int
    {
        return $this->value();
    }

    public function stars(): string
    {
        return str_repeat('⭐', $this->value());
    }

    public static function fromInt(int $rating): self
    {
        return match ($rating) {
            1 => self::ONE,
            2 => self::TWO,
            3 => self::THREE,
            4 => self::FOUR,
            5 => self::FIVE,
            default => throw new \InvalidArgumentException("Invalid rating: {$rating}"),
        };
    }
}
