<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketTemplate;

class TicketTemplateFactory extends Factory
{
    protected $model = TicketTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'subject' => fake()->sentence(5),
            'body' => fake()->paragraphs(2, true),
            'category_id' => null,
            'priority_id' => null,
            'fields' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withFields(): static
    {
        return $this->state([
            'fields' => [
                ['name' => 'product_name', 'type' => 'text', 'required' => true],
            ],
        ]);
    }
}
