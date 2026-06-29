<?php

namespace Modules\Template\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Template\Models\Template;
use Modules\Template\Models\TemplateVersion;

class TemplateVersionFactory extends Factory
{
    protected $model = TemplateVersion::class;

    public function definition(): array
    {
        return [
            'template_id' => Template::factory(),
            'version' => 1,
            'content' => '<div>{{ $title }}</div>',
            'description' => $this->faker->sentence(),
            'changed_fields' => [],
            'created_by' => null,
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->slug(2),
            'author' => $this->faker->name(),
            'created_at' => now(),
        ];
    }
}
