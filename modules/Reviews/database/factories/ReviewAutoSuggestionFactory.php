<?php

namespace Modules\Reviews\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Reviews\Models\ReviewAutoSuggestion;
use Modules\Reviews\Models\ReviewReplyTemplate;

class ReviewAutoSuggestionFactory extends Factory
{
    protected $model = ReviewAutoSuggestion::class;

    public function definition(): array
    {
        return [
            'trigger_keywords' => ['gracias', 'excelente', 'bueno'],
            'rating_range' => '4-5',
            'suggested_template_id' => ReviewReplyTemplate::factory(),
            'priority' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    public function positive(): static
    {
        return $this->state([
            'trigger_keywords' => fake()->randomElement([
                ['excelente', 'increíble', 'perfecto', 'genial'],
                ['gracias', 'fantástico', 'maravilloso', 'extraordinario'],
                ['mejor', 'encantado', 'feliz', 'satisfecho'],
                ['recomiendo', 'profesional', 'calidad', 'atención'],
            ]),
            'rating_range' => '4-5',
            'priority' => fake()->numberBetween(5, 10),
        ]);
    }

    public function negative(): static
    {
        return $this->state([
            'trigger_keywords' => fake()->randomElement([
                ['malo', 'terrible', 'pésimo', 'horrible'],
                ['decepcionado', 'frustrado', 'molesto', 'insatisfecho'],
                ['lento', 'tardado', 'demora', 'espera'],
                ['sucio', 'descuidado', 'desorganizado', 'mal estado'],
                ['rudo', 'grosero', 'mala atención', 'mal servicio'],
            ]),
            'rating_range' => '1-2',
            'priority' => fake()->numberBetween(7, 10),
        ]);
    }

    public function neutral(): static
    {
        return $this->state([
            'trigger_keywords' => fake()->randomElement([
                ['normal', 'regular', 'aceptable', 'decente'],
                ['ok', 'bien', 'correcto', 'adecuado'],
                ['promedio', 'estándar', 'común', 'ordinario'],
            ]),
            'rating_range' => '3',
            'priority' => fake()->numberBetween(3, 7),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state([
            'priority' => fake()->numberBetween(8, 10),
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state([
            'priority' => fake()->numberBetween(0, 3),
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function forRating(string $ratingRange): static
    {
        return $this->state([
            'rating_range' => $ratingRange,
        ]);
    }

    public function withKeywords(array $keywords): static
    {
        return $this->state([
            'trigger_keywords' => $keywords,
        ]);
    }

    public function withTemplate(int $templateId): static
    {
        return $this->state([
            'suggested_template_id' => $templateId,
        ]);
    }
}
