<?php

namespace Modules\Faqs\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Faqs\Enums\FaqStatus;
use Modules\Faqs\Models\Faq;
use Modules\Faqs\Models\FaqCategory;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence(6).'?',
            'answer' => $this->faker->paragraphs(2, true),
            'category_id' => FaqCategory::factory(),
            'order' => $this->faker->numberBetween(0, 100),
            'status' => FaqStatus::PUBLISHED->value,
        ];
    }
}
