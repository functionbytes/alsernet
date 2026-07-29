<?php

namespace Modules\Supplier\Database\Factories\Ai;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Ai\AiContentVersion;

class AiContentVersionFactory extends Factory
{
    protected $model = AiContentVersion::class;

    public function definition(): array
    {
        return [
            'content_id' => null,
            'user_id' => null,
            'version_number' => $this->faker->numberBetween(1, 10),
            'long_description' => $this->faker->paragraphs(3, true),
            'generated_name' => $this->faker->words(4, true),
            'short_description' => $this->faker->sentence(15),
            'seo_title' => $this->faker->sentence(8),
            'seo_description' => $this->faker->sentence(20),
            'change_reason' => $this->faker->randomElement(['initial', 'edit', 'regenerate', 'translate']),
        ];
    }
}
