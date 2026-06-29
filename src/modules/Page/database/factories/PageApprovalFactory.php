<?php

namespace Modules\Page\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageApproval;

class PageApprovalFactory extends Factory
{
    protected $model = PageApproval::class;

    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'requested_by' => User::factory(),
            'reviewed_by' => null,
            'status' => 'pending',
            'notes' => $this->faker->optional()->sentence(),
            'rejection_reason' => null,
            'requested_at' => now(),
            'reviewed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => 'approved',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Content needs revision.'): static
    {
        return $this->state([
            'status' => 'rejected',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
