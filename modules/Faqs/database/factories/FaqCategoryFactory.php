<?php

namespace Modules\Faqs\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Faqs\Enums\FaqStatus;
use Modules\Faqs\Models\FaqCategory;

class FaqCategoryFactory extends Factory
{
    protected $model = FaqCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(2),
            'description' => $this->faker->optional()->paragraph(),
            'order' => $this->faker->numberBetween(0, 100),
            'status' => FaqStatus::PUBLISHED->value,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (FaqCategory $category) {
            $category->translations()->create([
                'locale' => 'es',
                'name' => $category->name,
                'description' => $category->description,
            ]);
        });
    }
}
