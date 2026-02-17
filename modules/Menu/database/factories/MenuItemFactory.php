<?php

namespace Modules\Menu\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;

class MenuItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = MenuItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'parent_id' => null,
            'title' => $this->faker->words(3, true),
            'url' => $this->faker->url(),
            'target' => $this->faker->randomElement(['_self', '_blank']),
            'icon' => $this->faker->optional(0.3)->randomElement([
                'fa fa-home',
                'fa fa-info',
                'fa fa-phone',
                'fa fa-envelope',
                'fa fa-user',
            ]),
            'css_class' => $this->faker->optional(0.2)->word(),
            'order' => $this->faker->numberBetween(0, 10),
            'type' => $this->faker->randomElement(['custom', 'page', 'post', 'category', 'route']),
            'reference_id' => null,
            'reference_type' => null,
        ];
    }

    /**
     * Indicate that the menu item is a custom link.
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'custom',
            'url' => $this->faker->url(),
            'reference_id' => null,
            'reference_type' => null,
        ]);
    }

    /**
     * Indicate that the menu item is a page reference.
     */
    public function page(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'page',
            'reference_id' => $this->faker->numberBetween(1, 100),
            'reference_type' => 'Modules\Page\Models\Page',
            'url' => null,
        ]);
    }

    /**
     * Indicate that the menu item has an icon.
     */
    public function withIcon(string $icon = 'fa fa-home'): static
    {
        return $this->state(fn (array $attributes) => [
            'icon' => $icon,
        ]);
    }

    /**
     * Indicate that the menu item opens in a new window.
     */
    public function newWindow(): static
    {
        return $this->state(fn (array $attributes) => [
            'target' => '_blank',
        ]);
    }

    /**
     * Indicate that the menu item is a child of another item.
     */
    public function child(MenuItem $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'menu_id' => $parent->menu_id,
            'parent_id' => $parent->id,
        ]);
    }
}
