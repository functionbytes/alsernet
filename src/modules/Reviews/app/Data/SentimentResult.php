<?php

namespace Modules\Reviews\Data;

readonly class SentimentResult
{
    public function __construct(
        public string $sentiment,
        public float $score,
        public string $reason,
    ) {}
}
