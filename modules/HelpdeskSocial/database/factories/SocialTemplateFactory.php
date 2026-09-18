<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialTemplate;

class SocialTemplateFactory extends Factory
{
    protected $model = SocialTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'platform' => $this->faker->randomElement(['facebook', 'instagram', null]),
            'body' => 'Hola {{author_name}}, gracias por tu mensaje. '.$this->faker->sentence(),
            'variables' => ['author_name', 'platform'],
            'quick_replies' => [],
            'category' => $this->faker->randomElement(['greeting', 'support', 'sales', 'feedback']),
            'is_active' => true,
            'is_default' => false,
            'usage_count' => 0,
            'created_by_user_id' => 1,
        ];
    }
}
