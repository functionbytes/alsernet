<?php

namespace Modules\Supplier\Database\Factories\Content;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Content\ContentLog;

class ContentLogFactory extends Factory
{
    protected $model = ContentLog::class;

    public function definition(): array
    {
        return [
            'content_id' => AiContent::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'status_changed', 'published', 'rejected']),
            'previous_status' => fake()->optional()->randomElement(['draft', 'pending', 'approved']),
            'new_status' => fake()->optional()->randomElement(['pending', 'approved', 'published', 'rejected']),
            'user_id' => User::factory(),
            'details' => null,
            'ip_address' => fake()->optional()->ipv4(),
        ];
    }

    public function automated(): static
    {
        return $this->state([
            'user_id' => null,
            'ip_address' => null,
        ]);
    }

    public function statusChange(string $from, string $to): static
    {
        return $this->state([
            'action' => 'status_changed',
            'previous_status' => $from,
            'new_status' => $to,
        ]);
    }
}
