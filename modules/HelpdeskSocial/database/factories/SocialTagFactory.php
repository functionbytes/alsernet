<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialTag;

class SocialTagFactory extends Factory
{
    protected $model = SocialTag::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'slug' => $this->faker->slug(),
            'color' => $this->faker->hexColor(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
