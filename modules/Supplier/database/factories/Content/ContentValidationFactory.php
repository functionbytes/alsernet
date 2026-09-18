<?php

namespace Modules\Supplier\Database\Factories\Content;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Content\ContentValidation;

class ContentValidationFactory extends Factory
{
    protected $model = ContentValidation::class;

    public function definition(): array
    {
        $quality = fake()->randomFloat(2, 30, 100);

        return [
            'content_id' => AiContent::factory(),
            'quality_score' => $quality,
            'readability_score' => fake()->randomFloat(2, 30, 100),
            'keyword_density' => fake()->randomFloat(4, 0, 0.08),
            'unique_words_ratio' => fake()->randomFloat(4, 0.3, 0.9),
            'sentence_avg_length' => fake()->randomFloat(2, 8, 28),
            'has_required_sections' => fake()->boolean(80),
            'has_brand_terms' => fake()->boolean(70),
            'has_prohibited_words' => fake()->boolean(10),
            'plagiarism_score' => fake()->randomFloat(2, 0, 30),
            'validation_status' => $this->statusForQuality($quality),
            'issues' => fake()->optional()->randomElements(['too_short', 'low_readability', 'missing_section', 'keyword_stuffing'], 2),
            'suggestions' => fake()->optional()->randomElements(['add_specs', 'rephrase_intro', 'expand_benefits'], 2),
            'validated_by' => fake()->randomElement(['system', 'reviewer']),
            'validated_at' => now(),
        ];
    }

    public function passed(): static
    {
        return $this->state(['validation_status' => 'passed', 'quality_score' => fake()->randomFloat(2, 85, 100)]);
    }

    public function failed(): static
    {
        return $this->state(['validation_status' => 'failed', 'quality_score' => fake()->randomFloat(2, 30, 59)]);
    }

    public function needsReview(): static
    {
        return $this->state(['validation_status' => 'needs_review', 'quality_score' => fake()->randomFloat(2, 60, 84)]);
    }

    private function statusForQuality(float $quality): string
    {
        return match (true) {
            $quality >= 85 => 'passed',
            $quality >= 60 => 'needs_review',
            default => 'failed',
        };
    }
}
