<?php

namespace Modules\Forms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormCategory;

class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'category_id' => FormCategory::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->optional()->paragraph(),
            'success_message' => fake()->optional()->sentence(),
            'is_active' => true,
            'allow_multiple' => true,
            'honeypot_enabled' => true,
            'captcha_enabled' => false,
            'is_password_protected' => false,
            'send_confirmation' => false,
            'prevent_duplicate_email' => false,
            'access_control' => 'public',
            'limit_per_user' => false,
            'is_multi_step' => false,
            'theme' => 'default',
            'submit_button_text' => 'Enviar',
            'button_position' => 'left',
            'button_size' => 'md',
            'button_variant' => 'primary',
            'success_animation' => 'fade',
            'progress_bar_style' => 'bar',
            'floating_label' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function multiStep(): static
    {
        return $this->state([
            'is_multi_step' => true,
            'steps_config' => [
                ['number' => 1, 'title' => 'Información básica', 'description' => null],
                ['number' => 2, 'title' => 'Detalles adicionales', 'description' => null],
            ],
            'progress_bar_style' => 'steps',
        ]);
    }

    public function passwordProtected(): static
    {
        return $this->state([
            'is_password_protected' => true,
            'password' => bcrypt('secret'),
        ]);
    }
}
