<?php

namespace Modules\Page\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Page\Models\PageTag;

class PageTagFactory extends Factory
{
    protected $model = PageTag::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
