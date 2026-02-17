<?php

namespace Modules\Menu\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Menu\Models\Menu;

class MenuFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Menu::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(2),
            'location' => $this->faker->randomElement(['header', 'footer', 'sidebar', 'mobile', null]),
            'status' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the menu is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => true,
        ]);
    }

    /**
     * Indicate that the menu is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }

    /**
     * Indicate that the menu is for the header location.
     */
    public function header(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'header',
        ]);
    }

    /**
     * Indicate that the menu is for the footer location.
     */
    public function footer(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'footer',
        ]);
    }
}
