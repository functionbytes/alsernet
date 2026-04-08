<?php

namespace Modules\Page\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

class PageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $title = $this->faker->sentence(6);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => $this->faker->paragraphs(5, true),
            'description' => $this->faker->sentence(15),
            'template' => $this->faker->randomElement(['default', 'full-width', 'no-sidebar']),
            'status' => $this->faker->randomElement(['draft', 'published', 'pending']),
            'user_id' => User::factory(),
            'seo_title' => $title,
            'seo_description' => $this->faker->sentence(20),
            'seo_keywords' => implode(', ', $this->faker->words(5)),
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the page is published.
     *
     * @return Factory
     */
    public function published()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PageStatus::Published->value,
                'published_at' => now()->subDays(rand(1, 30)),
            ];
        });
    }

    /**
     * Indicate that the page is draft.
     *
     * @return Factory
     */
    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PageStatus::Draft->value,
                'published_at' => null,
            ];
        });
    }

    /**
     * Indicate that the page is pending.
     *
     * @return Factory
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PageStatus::Pending->value,
                'published_at' => null,
            ];
        });
    }
}
