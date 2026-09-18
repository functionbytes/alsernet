<?php

namespace Modules\HelpdeskHelpcenter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;

class HelpCenterCategoryFactory extends Factory
{
    protected $model = HelpCenterCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'description' => $this->faker->sentence(),
            'icon' => 'fas fa-folder',
            'image' => null,
            'position' => $this->faker->numberBetween(1, 100),
            'parent_id' => null,
            'is_section' => false,
            'visible_to_role' => null,
            'managed_by_role' => null,
        ];
    }

    public function section(HelpCenterCategory $parent): static
    {
        return $this->state([
            'parent_id' => $parent->id,
            'is_section' => true,
        ]);
    }
}
