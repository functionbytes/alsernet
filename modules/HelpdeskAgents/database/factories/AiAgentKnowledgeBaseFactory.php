<?php

namespace Modules\HelpdeskAgents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskAgents\Models\AiAgentKnowledgeBase;

class AiAgentKnowledgeBaseFactory extends Factory
{
    protected $model = AiAgentKnowledgeBase::class;

    public function definition(): array
    {
        return [
            'ai_agent_id' => null,
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'type' => fake()->randomElement(['article', 'faq', 'policy', 'manual', 'snippet']),
            'source_url' => fake()->optional()->url(),
            'source_type' => fake()->randomElement(['manual', 'url', 'file', 'api']),
            'source_id' => null,
            'embedding' => null,
            'embedding_model' => null,
            'metadata' => null,
            'tags' => [],
            'summary' => fake()->optional()->sentence(),
            'usage_count' => 0,
            'last_used_at' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function faq(): static
    {
        return $this->state(['type' => 'faq']);
    }

    public function withEmbedding(): static
    {
        return $this->state([
            'embedding' => array_fill(0, 1536, 0.0),
            'embedding_model' => 'text-embedding-3-small',
        ]);
    }
}
