<?php

namespace Modules\Forms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Forms\Models\FormCategory;

class FormCategoryFactory extends Factory
{
    protected $model = FormCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'color' => sprintf('#%06X', fake()->numberBetween(0, 0xFFFFFF)),
            'icon' => fake()->randomElement(['fas fa-file-alt', 'fas fa-envelope', 'fas fa-star', 'fas fa-heart', 'fas fa-cog']),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
