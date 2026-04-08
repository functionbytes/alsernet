<?php

namespace Modules\Template\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Template\Models\Shortcode;

class ShortcodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Shortcode::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'icon' => $this->faker->randomElement([
                'fas fa-puzzle-piece',
                'fas fa-star',
                'fas fa-heart',
                'fas fa-code',
                'fas fa-cog',
            ]),
            'shortcode_template' => '['.str_replace('-', '_', $this->faker->slug(2)).']',
            'render_template' => $this->faker->optional(0.7)->paragraph(),
            'js_code' => null,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(80),
        ];
    }

    /**
     * Indicate that the shortcode is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the shortcode is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the shortcode has a render template.
     */
    public function withRenderTemplate(): static
    {
        return $this->state(fn (array $attributes) => [
            'render_template' => '<div class="shortcode">{{ content }}</div>',
        ]);
    }

    /**
     * Indicate that the shortcode has config fields.
     */
    public function withConfigFields(): static
    {
        return $this->state(fn (array $attributes) => [
            'config_fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['name' => 'color', 'label' => 'Color', 'type' => 'color'],
            ],
        ]);
    }
}
