<?php

namespace Modules\HelpdeskLivechat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskLivechat\Models\PreChatForm;

class PreChatFormFactory extends Factory
{
    protected $model = PreChatForm::class;

    public function definition(): array
    {
        return [
            'name' => 'Default Form',
            'inbox_id' => null,
            'fields' => [
                ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'required' => true],
                ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ],
            'is_active' => true,
        ];
    }

    /**
     * Attach the form to a specific inbox.
     */
    public function forInbox(int $inboxId): static
    {
        return $this->state(['inbox_id' => $inboxId]);
    }

    /**
     * Mark the form as inactive.
     */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
