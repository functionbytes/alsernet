<?php

namespace Modules\Template\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Template\Models\Template;

class TemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Template::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->word().' Template';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(15),
            'content' => $this->generateBladeContent(),
            'template_path' => 'public/templates/'.Str::slug($name),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'user_id' => User::factory(),
            'author' => $this->faker->name(),
            'version' => '1.0.0',
            'inherit' => null,
        ];
    }

    /**
     * Indicate that the template is active.
     */
    public function active(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    /**
     * Indicate that the template is inactive.
     */
    public function inactive(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'inactive',
            ];
        });
    }

    /**
     * Indicate that the template inherits from another.
     */
    public function inherits(string $parentSlug): self
    {
        return $this->state(function (array $attributes) {
            return [
                'inherit' => $parentSlug,
            ];
        });
    }

    /**
     * Generate sample Blade template content
     */
    private function generateBladeContent(): string
    {
        return <<<'BLADE'
@extends('template::layouts.base')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <article class="template-content">
                <header class="mb-4">
                    <h1 class="display-4">{{ $title ?? 'Welcome' }}</h1>
                    <p class="lead text-muted">{{ $description ?? 'Template description' }}</p>
                </header>

                <section class="template-body">
                    {!! $content ?? '<p>No content available</p>' !!}
                </section>

                @if(isset($metadata))
                <footer class="template-footer mt-5 pt-4 border-top">
                    <small class="text-muted">
                        Published: {{ $metadata['published_at'] ?? 'N/A' }} |
                        Author: {{ $metadata['author'] ?? 'Anonymous' }}
                    </small>
                </footer>
                @endif
            </article>
        </div>

        <aside class="col-lg-4">
            <div class="sidebar">
                @if(isset($sidebar))
                    {!! $sidebar !!}
                @else
                    <div class="card mb-3">
                        <div class="card-body">
                            <p class="text-muted">Sidebar content goes here</p>
                        </div>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>
@endsection
BLADE;
    }
}
